<?php
namespace Spartan\Extend;

/**
 * Class PhpExcel
 * 项目地址：https://github.com/dodgepudding/wechat-php-sdk
 */
class VenderWeChat{

    /**
     * @return \Spartan\Extend\WeChatSdk\WeChat
     */
    public function getWeChat($_arrConfig = []){
        $arrConfig = config('get.MP_CONFIG');
        $arrConfig = array_merge($arrConfig,$_arrConfig);
        if (!isset($arrConfig['APP_ID'])||!isset($arrConfig['APP_SECRET'])||!isset($arrConfig['TOKEN'])){
            \Spt::halt('微信公众号配置不正确。');
        }
        $arrConfig = Array(
            'token'=>$arrConfig['TOKEN'],
            'encodingaeskey'=>$arrConfig['ENCODING_AES_KEY'],
            'appid'=>$arrConfig['APP_ID'],
            'appsecret'=>$arrConfig['APP_SECRET'],
        );
        return \Spt::getInstance('Spartan\\Extend\\WeChatSdk\\WeChat',$arrConfig);
    }
}
