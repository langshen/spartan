<?php
namespace Spartan\Lib;

defined('APP_NAME') OR die('404 Not Found');

class Controller{

    /** @var \Spartan\Lib\View */
    protected $view;
    /** @var \Spartan\Lib\Response */
    protected $response;
    /** @var string 模版，Web为PC端，Mobile为手机端,默认为不分端 */
    protected $tplName = '';

    public function __construct($_arrConfig = []){
        $this->response = Response::instance($_arrConfig);
        $this->view    = View::instance($_arrConfig);
        $this->view->init();
    }

    /**
     * 验证Rpc访问
     * @param string $strKeyName
     * @return array
     */
    public function rpcValid(string $strKeyName='rpc'): array
    {
        $strRpcSign = $this->request('rpc_sign','');
        if (strlen($strRpcSign) < 10){
            return ['Rpc 没来',2];
        }
        $arrRpcConfig = config('get.RPC_AUTH');
        !is_array($arrRpcConfig) && $arrRpcConfig = [];
        $arrRpcConfig = array_merge(['IP'=>'','UA'=>'','PASS'=>''],$arrRpcConfig);
        if (!$arrRpcConfig['PASS'] || !$arrRpcConfig['UA']){
            return ['Rpc 配置异常。',1];
        }
        $arrRpcConfig['IP'] = $arrRpcConfig['IP'] ?? '';$arrRpcConfig['UA'] = $arrRpcConfig['UA'] ?? '';
        $arrSignData = json_decode(gzDecodeAes($strRpcSign,$arrRpcConfig['PASS']),true);
        !is_array($arrSignData) && $arrSignData = [];
        $arrSignData = array_merge(['TIME'=>'','UA'=>'','SIGN'=>'','USER'=>[]],$arrSignData);
        if (!$arrSignData['TIME'] || !$arrSignData['UA'] || !$arrSignData['SIGN']){
            return ['Rpc sign异常。',1];
        }
        if (abs($arrSignData['TIME'] - time()) > 30){
            return ['Rpc 服务相差太大。',1];
        }
        if ($arrSignData['UA'] != $arrRpcConfig['UA'] || $arrSignData['UA'] != request()->server('HTTP_USER_AGENT')){
            return ['Rpc Ua 异常。',1];
        }
        if (md5("{$strKeyName}@{$arrSignData['TIME']}@{$arrSignData['UA']}") != $arrSignData['SIGN']){
            return ['Rpc 签名异常。',1];
        }
        if ($arrRpcConfig['IP'] && $arrRpcConfig['IP'] != '*'){//判断IP
            $arrRpcIp = array_unique(array_filter(explode(',',$arrRpcConfig['IP'])));
            $strIp = request()->ip();
            if (!$strIp || stripos($strIp,'.') === false){
                return ['Rpc Ip为空。',1];
            }
            $bloIpValid = false;
            foreach ($arrRpcIp as $rpcIp){
                if(ipExitList($strIp,$rpcIp)){
                    $bloIpValid = true;
                    break;
                }
            }
            if (!$bloIpValid){
                return ['Rpc 不在白名单'.$strIp,1];
            }
        }
        return [$arrRpcConfig,0,$arrSignData['USER']];
    }

    /**
     * 设置通用的各种Url
     * @param $strBaseUrl
     */
    public function setUrlAction($strBaseUrl){
        $strFullUrl = config('get.URL','');
        $strAction = $this->getUrl(2,'index');
        if (config('get.SUB_DIR','')){
            $strFullUrl = mb_substr($strFullUrl,0,strrpos($strFullUrl,'/'));
            $strAction = config('get.ACTION','index');
            $strAction == 'Index' && $strAction = 'index';
        }
        $this->assign('base_url',$strBaseUrl.'/');
        $this->assign('full_url',$strBaseUrl.'/'.$strFullUrl);
        $this->assign('cur_action',$strAction);
    }

    /**
     * 一个空操作，默认显示的404页面
     * @return \Spartan\Driver\Response\View
     */
    public function _empty(){
        return $this->display('hello, 404啦~，'.config('URL').' 未能显示。');
    }

    /**
     * 快速请求变量
     * @param $name
     * @param string $default
     * @return array|mixed|null
     */
    public function request($name,$default=''){
        return request()->param($name,$default);
    }

    /**
     * 加载模板输出
     * @access protected
     * @param  string $template 模板文件名
     * @param  array  $vars     模板输出变量
     * @param  array  $config   模板参数
     * @return mixed
     */
    protected function fetch($template = '', $vars = [], $config = []){
        $template = str_replace(['\\', '/', '@@'], [DS, DS, '@'], $template);
        if (stripos($template, FRAME_PATH) === 0) { //系统路径的不解析
            return $this->view->fetch($template, $vars, $config);
        }
        $strSubDir = config('get.SUB_DIR', '');//控制器子目录
        $strAction = $template;//先存着，一会重写
        if (!$strAction){ // 如果为空时，有子目录就拿控制器，否则会拿函数
            $strAction = strtolower(\Spt::$arrConfig['ACTION']);
            $strSubDir && $strAction = strtolower(\Spt::$arrConfig['CONTROL']);
        }
        //eg: $template='';$template='match@index';$template='Mobile@Math@index';//
        $template = '';//重新构建
        $this->tplName && $template .= $this->tplName . '@';
        if (stripos($strAction, '@') === false){
            $strSubDir && $template .= $strSubDir . '@';
        }
        $template .= $strAction;
        return $this->view->fetch($template, $vars, $config);
    }

    /**
     * 渲染内容输出
     * @access protected
     * @param  string $content 模板内容
     * @param  array  $vars    模板输出变量
     * @param  array  $config  模板参数
     * @return mixed
     */
    protected function display($content = '', $vars = [], $config = [])
    {
        return $this->view->display($content, $vars, $config);
    }

    /**
     * 模板变量赋值
     * @access protected
     * @param  mixed $name  要显示的模板变量
     * @param  mixed $value 变量的值
     * @return $this
     */
    protected function assign($name, $value = '')
    {
        $this->view->assign($name, $value);
        return $this;
    }

    /**
     * 视图过滤
     * @access protected
     * @param  Callable $filter 过滤方法或闭包
     * @return $this
     */
    protected function filter($filter)
    {
        $this->view->filter($filter);
        return $this;
    }

    /**
     * 初始化模板引擎
     * @access protected
     * @param  array|string $engine 引擎参数
     * @return $this
     */
    protected function engine($engine)
    {
        $this->view->engine($engine);
        return $this;
    }

    /**
     * 取得模板变量的值
     * @access public
     * @param string $name
     * @param string $default
     * @return mixed
     */
    protected function get($name = '',$default = ''){
        return isset($this->view->{$name})?$this->view->{$name}:$default;
    }

    /**
     * 返回URL的第几个或全部
     * @param int $number
     * @param string $default
     * @return string
     */
    protected function getUrl($number = 0,$default = ''){
        return getUrl($number,$default);
    }

    /**
     * 返回一个数据的ID
     * @param $name
     * @param $type
     * @return array|string
     */
    public function getMulId($name,$type=0){
        $strId = $this->request($name,'');
        if (is_array($strId)){
            $strId = implode(',',array_unique(array_filter($strId)));
        }
        $strId = trim(str_ireplace(' ','',$strId));
        if (!$strId){
            return [];
        }
        if(is_numeric($type) && !is_numeric(str_ireplace(',','',$strId))){
            return [];
        }
        return explode(',',$strId);
    }
    
    /**
     * 模板变量赋值
     * @access protected
     * @param  mixed $name  要显示的模板变量
     * @param  mixed $value 变量的值
     * @return $this|mixed
     */
    protected function set($name, $value = '')
    {
        $this->view->assign($name, $value);
        return $this;
    }

    /**
     * 操作成功跳转的快捷方法
     * @access protected
     * @param  mixed     $msg 提示信息
     * @param  string    $url 跳转的URL地址
     * @param  mixed     $data 返回的数据
     * @param  integer   $wait 跳转等待时间
     * @param  array     $header 发送的Header信息
     * @return mixed
     */
    protected function success($msg = '', $url = null, $data = '', $wait = 3, array $header = [])
    {
        if (is_null($url) && isset($_SERVER["HTTP_REFERER"])) {
            $url = $_SERVER["HTTP_REFERER"];
        }
        $result = [
            config('GET.API.CODE','code') => 1,
            config('GET.API.MSG','msg')  => $msg,
            config('GET.API.DATA','data') => $data,
            'url'  => $url,
            'wait' => $wait,
        ];
        $type = request()->isAjax()?'json':'html';
        // 把跳转模板的渲染下沉，这样在 response_send 行为里通过getData()获得的数据是一致性的格式
        if ('html' == strtolower($type)) {
            $type = 'jump';
        }
        $this->response->create($result, $type)
            ->header($header)
            ->options(['jump_template' => FRAME_PATH.'Tpl/dispatch_jump.tpl'])->send();
        exit(0);
    }

    /**
     * 操作错误跳转的快捷方法
     * @access protected
     * @param  mixed     $msg 提示信息
     * @param  string    $url 跳转的URL地址
     * @param  mixed     $data 返回的数据
     * @param  integer   $wait 跳转等待时间
     * @param  array     $header 发送的Header信息
     * @return mixed
     */
    protected function error($msg = '', $url = null, $data = '', $wait = 3, array $header = [])
    {
        $type = request()->isAjax()?'json':'html';
        if (is_null($url)) {
            $url = request()->isAjax() ? '' : 'javascript:history.back(-1);';
        }
        $result = [
            config('GET.API.CODE','code') => 0,
            config('GET.API.MSG','msg')  => $msg,
            config('GET.API.DATA','data') => $data,
            'url'  => $url,
            'wait' => $wait,
            'title' => $msg?$msg:'操作异常'
        ];
        if ('html' == strtolower($type)) {
            $type = 'jump';
        }
        $this->response->create($result, $type)
            ->header($header)
            ->options(['jump_template' => config('GET.JUMP_TPL',FRAME_PATH.'Tpl/dispatch_jump.tpl')])->send();
        exit(0);
    }

    /**
     * URL重定向
     * @access protected
     * @param  string         $url 跳转的URL表达式
     * @param  array|integer  $params 其它URL参数
     * @param  integer        $code http code
     * @param  array          $with 隐式传参
     * @return mixed
     */
    protected function redirect($url, $params = [], $code = 302, $with = [])
    {
        redirect($url, $params, $code)->with($with)->send();
        exit();
    }

    /**
     * 输出一个下载请求
     * @param string  $filename 要下载的文件
     * @param string  $name 显示文件名
     * @param string  $content 显示文件名
     * @return mixed
     */
    protected function download($filename, $name = '',$content = false)
    {
        return download($filename, $name, $content)->send();
    }

    /**
     * 输出一个获取xml对象实例
     * @param mixed   $data    返回的数据
     * @param integer $code    状态码
     * @param array   $header  头部
     * @param array   $options 参数
     * @return mixed
     */
    protected function xml($data = [], $code = 200, $header = [], $options = [])
    {
        return download($data, $code, $header, $options)->send();
    }

    /**
     * 输出一个Json对象实例
     * @param mixed   $data 返回的数据
     * @param integer $code 状态码
     * @param array   $header 头部
     * @param array   $options 参数
     * @return mixed
     */
    protected function json($data = [], $code = 200, $header = [], $options = [])
    {
        return json($data,$code,$header,$options)->send();
    }

    /**
     * 输出一个Jsonp对象实例
     * @param mixed   $data    返回的数据
     * @param integer $code    状态码
     * @param array   $header 头部
     * @param array   $options 参数
     * @return mixed
     */
    protected function jsonp($data = [], $code = 200, $header = [], $options = [])
    {
        return jsonp($data,$code,$header,$options)->send();
    }

    /**
     * 返回封装后的API数据到客户端
     * @access protected
     * @param  mixed     $data 要返回的数据
     * @param  integer   $code 返回的code
     * @param  mixed     $msg 提示信息
     * @param  array     $header 发送的Header信息
     * @return mixed
     */
    protected function api($msg = '', $code = 0,$data = [], array $header = [])
    {
        $data = [
            config('GET.API.CODE','code') => $code,
            config('GET.API.MSG','msg')  => $msg,
            config('GET.API.DATA','data') => $data,
            'time' => time(),
        ];
        return json($data,200,$header)->send();
    }

    /**
     * 快捷输出API数据到客户端
     * @param array|string $minxData 要返回的数据
     * @param int $intCode 状态
     * @param array $arrData 数据
     * @return mixed
     */
    protected function toApi($minxData,$intCode = 1,$arrData = []){
        $strMsg = '';
        if (!is_array($minxData)) {
            $strMsg = $minxData;
        }elseif (isset($minxData['code']) && isset($minxData['msg']) && isset($minxData['data'])){
            return $this->api($minxData['msg'],$minxData['code'],$minxData['data']);
        }elseif (!$arrData && isset($minxData['data']) && $intCode == 1){
            $arrData = isset($minxData['count']) ? $minxData : $minxData['data'];
            $intCode = 0;
        }else{
            if (isset($minxData[0])){
                $strMsg = $minxData[0];
            }
            if (isset($minxData[1])){
                $intCode = $minxData[1];
            }
            if (isset($minxData[2])){
                $arrData = $minxData[2];
            }
        }
        unset($minxData);
        return $this->api($strMsg,$intCode,$arrData);
    }

    /**
     * 针对于layui的格式输出
     * @param $arrInfo
     * @return mixed
     */
    public function tableList($arrInfo){
        $intCount = $arrInfo['count']??0;
        unset($arrInfo['count']);
        $arrData = [
            'code' => 0,
            'msg'  => 'success',
            'time' => time(),
            'data' => $arrInfo['data'],
            'count'=> $intCount,
        ];
        isset($arrInfo['plug']) && $arrData['plug'] = $arrInfo['plug'];
        isset($arrInfo['sql']) && $arrData['sql'] = $arrInfo['sql'];
        return json($arrData,200)->send();
    }

    /**
     * @param $data
     * @param $fun
     * 返回可执行的js脚本
     * @return mixed
     */
    protected function toJs($data,$fun=''){
        header('Content-Type:text/html; charset=utf-8');
        echo '<script language="javascript">';
        if ($fun){
            echo $fun.'("'.str_replace('"','\"',$data).'");';
        }else{
            echo $data;
        }
        exit('</script>');
        return;
    }

    /**
     * 批量提取寄存变量
     * @param string|mixed $mixName
     * @return array
     */
    public function getFieldData($mixName){
        $arrTempName = [];
        if (is_array($mixName)){
            $arrName = $mixName;
            foreach ($arrName as $key=>$value){
                $arrTempName[$key] = request()->param($key,$value);
            }
        }else{
            $arrName = explode(',',$mixName);
            $arrName = array_filter($arrName);
            foreach ($arrName as $value){
                $arrTempName[$value] = request()->param($value,'');
            }
        }
        unset($mixName);
        return $arrTempName;
    }

}
