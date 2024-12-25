<?php
require_once 'aop/AopClient.php';
require_once 'aop/request/AlipayTradeWapPayRequest.php';
require_once 'aop/request/AlipayTradePagePayRequest.php';
require_once 'aop/request/AlipayTradeQueryRequest.php';

class Alipay{
    public $arrConfig = [];
    public $arrData = [];

    public function __construct(){
        $this->arrConfig = [
            //'wayUrl'=>'https://openapi.alipay.com/gateway.do',
            //'appId'=>'2021001140665776',
            //'rsaPrivateKey'=>'',
            //'alipayrsaPublicKey'=>'',
            'wayUrl'=>'https://openapi.alipaydev.com/gateway.do',
            'appId'=>'2016101800718716',
            'rsaPrivateKey'=>'MIIEowIBAAKCAQEAjgvmKaMomm7iBmCfoV1U4Lp7SPbTpvo8U50gEmMR0BMUHUSnJmS4XAgB+9O1bpjw+vEDn82HDYz3dJ4+LLQzyqbDRvq48lxRCEPVfviWKDhsAErORVvm04PW0S6aVqxIlRmjgGDdSRuj7VmW1faOH9dMqHN/G0Xv4ax/x4uRmN5fOjXG2s5fkxRlD1vbJacXqfKzDefmGfSATsAKsUGXcdqcBjS+smgyqQstlh1f7xp6hU5Vw0dH79zNQo7Ygy//+O0mkpAkMwjBXCBDHPUIUNmlbPHu/NM8S1i+EgN88RAblhwZ/3bHTemEeCg2Sk0zvErPxm0utdBXjXabR3QY1QIDAQABAoIBAH7UxxVEdtu1yCFzovT9Fe129N/IbGF6q+TothtU1DHa5ynfA/R3GFosWEEX3rc63mjiTJ8ib8U8qjM5nEBkQp5e2pwFAKB+p2fe0cGGiuSsxFpacOVjUq5n2KZ8mxhqyoA/HUtishM2F9+1D8ZKWMq7fdonDkY24yK42Hs+9qjsBhW/yeyq6z+ZED7npCzYnitnNraiAusr8WfOSJfwPjEKplF6YyAG8ZLhKBah5KAw4N8M6TerYeTdQrIKUdSxw4qWApoE29bKy8cw3/5Suad8uRJdK81voIZifVTM177oq9c3PeBJDacZh4sg78KAaOOltWMUvp9Hn2IHeYbzCWECgYEA2DsNm1dUv2K3MMTxkl8mkWQ8L9Jst/q1Pf5ouQIVkHrxUqnHAB3PbZYoj+qMvfU1SVYmvrnhPoKIKE01H9OjHZJEJH7bkoElVC0P9JPTV8fl8jcpjCGIpWzYB6PCpNv6ya3n3frabGsSbJcS07bU0xAbtyfVVxszXouOe48p0ykCgYEAqCv6Ld1++NwDIvmyP9TIREhknI3pjKx6smbPuHKAxVUhyRZJ8n1vsBaxRc/idlC4PIdCd7KPhCS3ktYwUIWSURzLMAVd+9eUMAvN6f9iM1Bw6J4C4GApK6Ez+/rc5kH+PVd0a8r5HlV3xJxgEDbWaGXyx9t3xZjhMvmbvex/Gc0CgYEAi7ptaoqemyZBVuSNbpbKJ33sXsLNun3qDOuP5K3yHXE07MQFco/Q8PHtuEJLPJ1uF7vyQaGAapKTRefOgoiSiZNxMVxAq4WFB8Yu309/bOQiNclrscAhuzSAzT8Hkt0MTLNyeEGYUCNIvp9JbUJieRmZr1uwQx+yrE/mfPXggzECgYAakP/vmOsDAzaXotxuyv1sFAeY165KY8DqR+WOnMAM7Fru+k9qODiZl6wffCypRi0kmrV8VT/ovygk0SNGxSMNH0BV8LMdIrwtLuAzk/1+X7nGdZe8vFQkqU2eA847rbctF5CzqpfaG+RUseNXDGKokVpeCiSMmY8Rz26z/RVDDQKBgAJBaHYfIaMBqkzQ/8dVOe37RkhnLayl4bYb6htldGepgw7Ix3gNvH2OzfEtsijGMqDo0ZyTklUHHvYmiDprP7mUkdYrA5il1wPGjUOmY2Okcw7P5+81cL1aah1xiyaWEUTIR1v8qrh6I3nOtfTnQhXFBvET7t7IQjQfpAfeKhAj',
            'alipayrsaPublicKey'=>'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAnyBPJ+FcRPMDWKYwMizZ4B1yIlQH8Y0IqepnP5uKK3pilKLXsqS4gsCWNiKEiVq9BQEum+/JA9ddcd8SWj6ah9fagGcbuRy4lDlfgnpS1rFZ0f30DGFbVlgK46rWdD+VfGf63wp9WoRo3TaB9k8JT9AX7JqPPC+2aY3PhUIVz/nY3R6Nw+zyxt++OFdYD/nUoycjL3+skNqUx2RpfE4ovQ1wCso3fpEDo4w20ryOt/Gm6y7Veg4n3LtplmJ0VzIUnXfVJwSFra+2vsexCZRHqegtZMR5dV5BEzIBrjt8NjqAycb6c1yLQ/p6Ib4nbG+Tg2jrkUJRHEydPQEp1mSOvQIDAQAB',
        ];
    }

    public function setConfig($arrConfig){
        if (is_array($arrConfig)){
            $this->arrConfig = array_merge($this->arrConfig,$arrConfig);
        }
        return $this;
    }

    public function Aop(){
        $aop = new AopClient ();
        $aop->gatewayUrl = $this->arrConfig['wayUrl'];
        $aop->appId = $this->arrConfig['appId'];
        $aop->rsaPrivateKey = $this->arrConfig['rsaPrivateKey'];
        $aop->alipayrsaPublicKey = $this->arrConfig['alipayrsaPublicKey'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset = 'utf-8';
        $aop->format = 'json';
        return $aop;
    }

    //https://docs.open.alipay.com/api_1/alipay.trade.page.pay
    public function tradePagePay($arrData){
        $request = new AlipayTradePagePayRequest();
        $request->setNotifyUrl($arrData['notify_url']??'');
        $request->setReturnUrl($arrData['return_url']??'');
        unset($arrData['notify_url'],$arrData['return_url']);
        $request->setBizContent(json_encode($arrData,256));
        return $this->Aop()->pageExecute($request);
    }

    //https://docs.open.alipay.com/api_1/alipay.trade.wap.pay/
    public function tradeWapPay($arrData){
        $request = new AlipayTradeWapPayRequest();
        $request->setNotifyUrl($arrData['notify_url']??'');
        $request->setReturnUrl($arrData['return_url']??'');
        unset($arrData['notify_url'],$arrData['return_url']);
        $request->setBizContent(json_encode($arrData,256));
        return $this->Aop()->pageExecute($request);
    }

    //https://docs.open.alipay.com/api_1/alipay.trade.query/
    public function tradeQuery($arrData){
        $request = new AlipayTradeQueryRequest();
        $request->setNotifyUrl($arrData['notify_url']??'');
        $request->setReturnUrl($arrData['return_url']??'');
        unset($arrData['notify_url'],$arrData['return_url']);
        $request->setBizContent(json_encode($arrData,256));
        return $this->Aop()->pageExecute($request);
    }
}
