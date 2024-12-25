<?php
namespace Spartan\Lib;

defined('APP_NAME') OR exit('404 Not Found');

class Model {
    public $arrRequest = [];//寄存的数据池
    public $arrConfig = [];//当前配置

    /**
     * Logic constructor.
     * @param array $arrData
     */
    public function __construct(array $arrData = []){
        $this->setData($arrData);
    }

    /**
     * 实例化当前主模型类
     * @param array $arrData '初始化的setData
     * @return object | Model
     */
    public static function instance(array $arrData = []){
        return \Spt::getInstance(__CLASS__,$arrData);
    }

    /**
     * 实例化一个静态调用的Model子类自己，可以理解为初始化自身
     * @param array $arrData '初始化的setData
     * @return object | Model
     */
    public static function init(array $arrData = []){
        return \Spt::getInstance(get_called_class())->setData($arrData);
    }

    /**
     * 实例化一个Table的表类
     * @param Object|string $objClass
     * @param bool $bolHalt
     * @return mixed|object
     */
    public function getTable($objClass, bool $bolHalt = true){
        $clsTable = $this->getModel($objClass,'Model\\Entity');
        if (!is_object($clsTable)){
            if ($bolHalt) {
                \Spt::halt(['表类不存在,请生成:' . $objClass, $clsTable]);
            }else{
                return null;
            }
        }
        return $clsTable;
    }

    /**
     * 实例化一个Model类或指定名称的Model子类
     * @param $objClass \stdClass|string
     * @param $strType string
     * @return mixed|Model|\stdClass
     */
    public function getModel($objClass, string $strType = 'Model')
    {
        !$strType && $strType = 'Model';
        if (is_object($objClass)){//是一个类
            return \Spt::setInstance(get_class($objClass),$objClass);
        }
        $strClassName = $this->getClassFullName($objClass,$strType);
        if (!class_exists($strClassName)){
            return $strClassName;
        }else{
            return \Spt::getInstance($strClassName);
        }
    }

    /**
     * 得到一个全的类名，带命名空间
     * @param $strClassName
     * @param $strType string 空间名前缀
     * @return string
     */
    public function getClassFullName($strClassName, string $strType=''): string
    {
        $arrClass = explode('\\',str_replace(['_','.','/','@'],['\\','\\','\\','\\'],$strClassName));
        array_walk($arrClass,function(&$v){$v = ucfirst($v);});unset($v);
        $strPathName = array_shift($arrClass);
        if (stripos($strClassName,'_') > 0){//如果下划线为目录分格，只拆分一层目录，
            $strClassName = $strPathName.($arrClass?'\\'.implode('',$arrClass):'');
        }else{//如果.或/为目录分格，全部折分为目录
            $strClassName = $strPathName.($arrClass?'\\'.implode('\\',$arrClass):'');
        }
        if (substr($strClassName,0,strlen($strType)) != $strType){
            $strClassName = $strType . '\\' . $strClassName;
        }
        return $strClassName;
    }

    /**
     * 设置一个共用的寄存的数据池
     * @param array $arrData
     * @param bool $bolClear
     * @return Model
     */
    public function setData(array $arrData = [], bool $bolClear=false): Model
    {
        $bolClear && $this->arrRequest = [];
        is_array($arrData) && $this->arrRequest = array_merge($this->arrRequest,$arrData);
        return $this;
    }

    /**
     * 获取调寄存数据池的值
     * @param string|array $name 要获取的$this->request_data[$key]数据
     * @param string|int|array $default 当$key数据为空时，返回$value的内容
     * @return mixed
     */
    public function getData($name = '', $default = null){
        is_array($name) && list($name,$default) = $name;
        if (!$name && !$default){
            return $this->arrRequest;
        }
        return $this->arrRequest[$name] ?? $default;
    }

    /**
     * 批量提取寄存变量
     * @param string|mixed $mixName
     * @return array
     */
    public function getFieldData($mixName): array
    {
        $arrTempName = [];
        if (is_array($mixName)){
            $arrName = $mixName;
            foreach ($arrName as $key=>$value){
                $arrTempName[$key] = $this->getData($key,$value);
            }
        }else{
            $arrName = explode(',',$mixName);
            $arrName = array_filter($arrName);
            foreach ($arrName as $value){
                $arrTempName[$value] = $this->getData($value,'');
            }
        }
        unset($mixName);
        return $arrTempName;
    }

    /**
     * 按表单顺序，选择第一个有用的用户
     * @param $mixValue
     * @param null $backFun
     * @return mixed|string
     */
    public function choose($mixValue,$backFun = null)
    {
        !is_array($mixValue) && $mixValue = [$mixValue];
        $value = '';
        foreach ($mixValue as $k=>$v){
            if (is_callable($backFun)){
                if ($backFun($v,$k) == true){
                    $value = $v;break;
                }
            }else{
                if ($v){$value = $v;break;}
            }
        }
        return $value;
    }
    /**
     * 重置配置
     * @return $this
     */
    public function reset(): Model
    {
        $this->arrConfig = [];
        return $this;
    }

    /**
     * 设置一个配置变量
     * 'auto'=>true,'array'=>true,'count'=>true
     * 自动使用过滤   返回数据格式  返回条数
     * @param $name
     * @param string|int|array $value
     * @return $this
     */
    public function setConfig($name,$value = ''): Model
    {
        if (is_array($name)){
            $this->arrConfig = array_merge($this->arrConfig,$name);
        }else{
            $this->arrConfig[$name] = $value;
        }
        return $this;
    }

    /**
     * 返回当前配置信息
     * @param string $name
     * @param string|int $default
     * @return array|mixed|string
     */
    public function getConfig(string $name = '', $default = ''){
        return !$name?$this->arrConfig:($this->arrConfig[$name] ?? $default);
    }

    /**
     * 从一个Table Model提取符合FieldData的字段
     * @param $arrFields mixed 表Model的所有字段
     * @return array
     */
    public function getTableFieldsToFieldData($arrFields = []): array
    {
        foreach($arrFields as $k=>$v){
            if (in_array($v[0],['int','tinyint','smallint'])){
                $arrFields[$k] = is_numeric($v[7])?$v[7]:0;
            }elseif (in_array($v[0],['varchar','char','text'])){
                $arrFields[$k] = $v[7]=='Empty String'?'':$v[7];
            }else{
                $arrFields[$k] = '';
            }
        }
        return $arrFields;
    }

    /**
     * 简单的错误记录器，使用SQL
     * //'level','class','info','err'
     * @param null $arrInfo
     */
    final public function sysError($arrInfo = null){
        $arrData = Array(
            'level' => $arrInfo['level'] ?? 0,
            'class' => $arrInfo['class'] ?? '',
            'info'  => $arrInfo['info'] ?? '',
            'err'   => $arrInfo['err'] ?? '',
            'add_time'   => date('Y-m-d H:i:s',time()),
        );
        $arrData['err'] .= PHP_EOL.
            "getData:".json_encode($this->getData(),JSON_UNESCAPED_UNICODE).PHP_EOL.
            "SQL:".json_encode(db()->getAllSql(),JSON_UNESCAPED_UNICODE).PHP_EOL.
            "SQL ERR:".json_encode(db()->getError(),JSON_UNESCAPED_UNICODE);
        db()->insert('system_errors',$arrData);
    }


} 