<?php
namespace Spartan\Lib;

defined('APP_NAME') OR exit('404 Not Found');

class Http{
    private $arrConfig = [];
    private $clsInstance = null;

    /**
     * @param array $arrConfig
     * @return Http
     */
    public static function instance($arrConfig = []) {
        return \Spt::getInstance(__CLASS__,$arrConfig);
    }

    /**
     * Http constructor.
     * @param array $_arrConfig
     */
    public function __construct($_arrConfig = []){
        (!isset($_arrConfig['type']) || !$_arrConfig['type']) && $_arrConfig['type'] = 'Curl';
        $_arrConfig['type'] = ucfirst(strtolower($_arrConfig['type']));
        $this->clsInstance = \Spt::getInstance('Spartan\\Driver\\Http\\'.$_arrConfig['type'],$_arrConfig);
        $this->arrConfig = $_arrConfig;
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function setOpt($key,$value){
        return $this->clsInstance->setOpt($key,$value);
    }

    /**
     * @param $mixHeader
     * @return mixed
     */
    public function setHeader($mixHeader){
        return $this->clsInstance->setHeader($mixHeader);
    }
    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function setConfig($key,$value = null){
        return $this->clsInstance->setConfig($key,$value);
    }

    /**
     * @param string $cookies
     * @return mixed
     */
    public function startCookie($cookies='not null'){
        return $this->clsInstance->startCookie($cookies);
    }

    /**
     * @return mixed
     */
    public function getCookie(){
        return $this->clsInstance->getCookie();
    }
    /**
     * 关闭本次请求
     */
    public function close(){
        return $this->clsInstance->close();
    }

    /**
     * @param $url
     * @param string $postFields
     * @param string $method
     * @param string $dataType
     * @return mixed
     */
    public function send($url,$postFields='',$method='GET',$dataType='json'){
        return $this->clsInstance->send($url,$postFields,$method,$dataType);
    }
}