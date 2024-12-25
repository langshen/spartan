<?php
namespace Spartan\Driver\Uploader;
if(!defined('APP_NAME')){exit('404 Not Found');}

/**
 * 基础文件上传，图片或文件或Base64后的文件或图片
 * Class BaseFile
 * @package Spartan\Driver\Uploader
 */
class UpFile
{
    private $fileField;            //文件域名
    private $file;                 //文件上传对象
    private $config = Array(//配置信息
        'allow_files'=>['.gif', '.jpg', '.jpeg', '.bmp', '.png', '.swf'],
        'max_size'=>50*1024,
        'save_path'=>'',
        'url_root'=>'/',//URL的根目录
        'base64'=>false,//是否解析base64编码，可省略。若开启，则$fileField代表的是base64编码的字符串表单名
    );
    private $oriName;              //原始文件名
    private $fileName;             //新文件名
    private $fullName;             //完整文件名,即从当前配置目录开始的URL
    private $fileSize;             //文件大小
    private $fileType;             //文件类型
    private $stateInfo;            //上传状态信息,
    private $stateMap = array(    //上传状态映射表，国际化用户需考虑此处数据的国际化
        "SUCCESS" ,                //上传成功标记，在UEditor中内不可改变，否则flash判断会出错
        "文件大小超出 upload_max_filesize 限制" ,
        "文件大小超出 MAX_FILE_SIZE 限制" ,
        "文件未被完整上传" ,
        "没有文件被上传" ,
        "上传文件为空" ,
        "POST" => "文件大小超出 post_max_size 限制" ,
        "SIZE" => "文件大小超出网站限制" ,
        "TYPE" => "不允许的文件类型" ,
        "DIR" => "目录创建失败" ,
        "IO" => "输入输出错误" ,
        "UNKNOWN" => "未知错误" ,
        "MOVE" => "文件保存时出错",
        "DIR_ERROR" => "创建目录失败"
    );

    /**
     * 构造函数
     * @param string $fileField 表单名称
     */
    public function __construct( $fileField ){
        $this->fileField = $fileField;
        $this->stateInfo = $this->stateMap[ 0 ];
    }

    /**
     *
     * 设置配置
     * @param $name
     * @param $value
     * @return $this
     */
    public function setConfig($name,$value=null){
        if (is_array($name)){
            $this->config = array_merge($this->config,$name);
        }else{
            $this->config[$name] = $value;
        }
        return $this;
    }

    /**
     * 上传文件的主处理方法
     * @param $strSaveFileName string 保存完整路径
     * @return mixed
     */
    public function saveToFile($strSaveFileName = ''){
        if (stripos(trim($strSaveFileName,'.'),'.')>0){
            $this->config['save_path'] = pathinfo($strSaveFileName,PATHINFO_DIRNAME);
            $this->fileName = pathinfo($strSaveFileName,PATHINFO_FILENAME);
        }elseif(substr($strSaveFileName,0,1)=='.'){
            $this->fileName = md5(microtime(true)) . $strSaveFileName;
        }else{
            $this->fileName = md5(microtime(true)) . '.png';
        }
        $this->fullName = $this->getFolder() . '/' . $this->fileName;
        //处理base64上传
        if ( true == $this->config['base64'] ) {
            if (!isset($_POST[ $this->fileField ]) || !$_POST[ $this->fileField ]){
                $this->stateInfo = $this->getStateInfo( 4 );
                return $this->getFileInfo();
            }
            $content = $_POST[ $this->fileField ];
            return $this->base64ToImage( $content );
        }
        //处理普通上传
        if (!isset($_FILES[ $this->fileField ])){
            $this->stateInfo = $this->getStateInfo( 4 );
            return $this->getFileInfo();
        }
        $file = $this->file = $_FILES[ $this->fileField ];
        if ( !$file ) {
            $this->stateInfo = $this->getStateInfo( 'POST' );
            return $this->getFileInfo();
        }
        if ( $this->file[ 'error' ] ) {
            $this->stateInfo = $this->getStateInfo( $file[ 'error' ] );
            return $this->getFileInfo();
        }
        if ( !is_uploaded_file( $file[ 'tmp_name' ] ) ) {
            $this->stateInfo = $this->getStateInfo( "UNKNOWN" );
            return $this->getFileInfo();
        }

        $this->oriName = $file[ 'name' ];
        $this->fileSize = $file[ 'size' ];
        $this->fileType = $this->getFileExt();
        if ( !$this->checkSize() ) {
            $this->stateInfo = $this->getStateInfo( "SIZE" );
            return $this->getFileInfo();
        }
        if ( !$this->checkType() ) {
            $this->stateInfo = $this->getStateInfo( "TYPE" );
            return $this->getFileInfo();
        }
        $this->fullName = dirname($this->fullName) . '/' . $this->getName();
        if (!$this->checkPath()){
            return $this->getFileInfo();
        }
        if ( $this->stateInfo == $this->stateMap[ 0 ] ) {
            if ( !move_uploaded_file( $file[ "tmp_name" ] , $this->fullName ) ) {
                $this->stateInfo = $this->getStateInfo( "MOVE" );
                return $this->getFileInfo();
            }
        }
        return $this->getFileInfo();
    }

    /**
     * 处理base64编码的图片上传
     * @param $base64Data
     * @return mixed
     */
    private function base64ToImage( $base64Data ){
        $img = base64_decode(str_replace('-','+',$base64Data));
        if (!$this->checkPath()){
            return $this->getFileInfo();
        }
        if ( !file_put_contents( $this->fullName , $img ) ) {
            $this->stateInfo = $this->getStateInfo( "IO" );
            return $this->getFileInfo();
        }
        $this->oriName = "Base64";
        $this->fileSize = strlen( $img );
        $this->fileType = strtolower( strrchr( $this->fileName , '.' ) );
        return $this->getFileInfo();
    }

    /**
     * 获取当前上传成功文件的各项信息
     * @return array
     */
    public function getFileInfo(){
        return array(
            "originalName" => $this->oriName ,
            "name" => $this->fileName ,
            "url" =>  str_ireplace($this->config['save_path'],$this->config['url_root'],$this->fullName),
            "path"=>  $this->fullName,
            "size" => $this->fileSize ,
            "type" => $this->fileType ,
            "status" => $this->stateInfo
        );
    }

    /**
     * 上传错误检查
     * @param $errCode
     * @return string
     */
    private function getStateInfo( $errCode ){
        return !$this->stateMap[ $errCode ] ? $this->stateMap[ "UNKNOWN" ] : $this->stateMap[ $errCode ];
    }

    /**
     * 重命名文件
     * @return string
     */
    private function getName(){
        return $this->fileName = md5(microtime(true)) . $this->getFileExt();
    }

    /**
     * 文件类型检测
     * @return bool
     */
    private function checkType(){
        return in_array( $this->getFileExt() , $this->config[ "allow_files" ] );
    }

    /**
     * 文件大小检测
     * @return bool
     */
    private function  checkSize(){
        return $this->fileSize <= ( $this->config[ "max_size" ] * 1024 );
    }

    /**
     * 获取文件扩展名
     * @return string
     */
    private function getFileExt(){
        return strtolower( strrchr( $this->file[ "name" ] , '.' ) );
    }

    /**
     * 按照日期自动创建存储文件夹
     * @return string
     */
    private function getFolder(){
        $pathStr = $this->config[ "save_path" ];
        if ( strrchr( $pathStr , "/" ) != "/" ) {
            $pathStr .= "/";
        }
        $pathStr .= date( "Ymd" );
        return $pathStr;
    }

    /**
     * 检查目录是否可写
     * @access protected
     * @return boolean
     */
    protected function checkPath()
    {
        $path = dirname($this->fullName);
        if (is_dir($path)) {
            return true;
        }
        if (mkdir($path, 0755, true)) {
            return true;
        }
        $this->stateInfo = $this->getStateInfo( "DIR_ERROR" );
        return false;
    }
}