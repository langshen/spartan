<?php
require_once "lib/WxPay.Api.php";
require_once "lib/WxPay.Config.php";
require_once 'lib/WxPay.Notify.php';
require_once "example/WxPay.JsApiPay.php";
require_once "example/WxPay.NativePay.php";

class wxpay{
    public $arrConfig = [];
    public $arrData = [];

    public function __construct(){
        $this->arrConfig = [
            'APP_ID' => '',//公众号APP_ID
            'MCH_ID' => '',//商户号（必须配置，开户邮件中可查看）
            'APP_KEY' =>'',//商户支付密钥，（必须配置，登录商户平台自行设置）
            'APP_SECRET'=> '',//公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置），
            'NOTIFY_URL'=> '',//异步通知url
            'API_CLIENT_CERT'=>'',
            'API_CLIENT_KEY'=>'',
        ];
    }

    /**
     * 设置支付配置文件
     * @param $arrConfig
     * @return $this
     */
    public function setConfig($arrConfig){
        if (is_array($arrConfig)){
            $this->arrConfig = array_merge($this->arrConfig,$arrConfig);
        }
        return $this;
    }

    public function createJsApi($arrOrder){
        $tools = new JsApiPay($this->arrConfig);
        if (!$arrOrder['wx_open_id']??''){
            $arrOrder['wx_open_id'] = $tools->GetOpenid();
        }
        $arrUnifiedOrder = $this->unifiedOrder($arrOrder);
        if (isset($arrUnifiedOrder['result_code']) && $arrUnifiedOrder['result_code'] != 'SUCCESS'){
            return [$arrUnifiedOrder['err_code_des'],1,[]];
        }
        $jsApiParameters = $tools->GetJsApiParameters($arrUnifiedOrder);
        return ['成功。',0,$jsApiParameters];
    }

    /**
     * //②、统一下单
     * @param $arrOrder array
     * @return mixed 成功时返回，其他抛异常
     * @throws WxPayException
     */
    public function unifiedOrder($arrOrder){
        $input = new WxPayUnifiedOrder();
        $input->SetBody($arrOrder['body']??'');
        $input->SetAttach($arrOrder['attach']??'');
        $input->SetOut_trade_no($arrOrder['order_num']??'');
        $input->SetTotal_fee(bcmul(($arrOrder['total_money']??0),100));
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag($arrOrder['tag']??'');
        $input->SetNotify_url($this->arrConfig['NOTIFY_URL']??'');
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($arrOrder['wx_open_id']??'');
        $config = new WxPayConfig($this->arrConfig);
        return WxPayApi::unifiedOrder($config, $input);
    }

    /**
     * 生成一个Native的订单
     * @param $arrOrder
     * @return array|string
     */
    public function nativeOrder($arrOrder){
        $input = new WxPayUnifiedOrder();
        $input->SetBody($arrOrder['body']??'');
        $input->SetAttach($arrOrder['attach']??'');
        $input->SetOut_trade_no($arrOrder['order_num']??'');
        $input->SetTotal_fee(bcmul(($arrOrder['total_money']??0),100));
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag($arrOrder['tag']??'');
        $input->SetNotify_url($this->arrConfig['NOTIFY_URL']??'');
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id($arrOrder['product_id']??'');
        $config = new WxPayConfig($this->arrConfig);
        return WxPayApi::unifiedOrder($config, $input);
    }
}

class NativeNotifyCallBack extends WxPayNotify
{
    public $arrConfig = [];
    public $call = null;

    public function __construct(){
        $this->arrConfig = [
            'APP_ID' => '',//公众号APP_ID
            'MCH_ID' => '',//商户号（必须配置，开户邮件中可查看）
            'APP_KEY' =>'',//商户支付密钥，（必须配置，登录商户平台自行设置）
            'APP_SECRET'=> '',//公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置），
            'NOTIFY_URL'=> '',//异步通知url
            'API_CLIENT_CERT'=>'',
            'API_CLIENT_KEY'=>'',
        ];
    }

    /**
     * 设置支付配置文件
     * @param $arrConfig
     * @return $this
     */
    public function setConfig($arrConfig){
        if (is_array($arrConfig)){
            $this->arrConfig = array_merge($this->arrConfig,$arrConfig);
        }
        return $this;
    }

    /**
     * @param WxPayNotifyResults $objData 回调解释出的参数
     * @param WxPayConfigInterface $config
     * @param string $msg 如果回调处理失败，可以将错误信息输出到该方法
     * @return bool 回调出来完成不需要继续回调，false回调处理未完成需要继续回调
     */
    public function NotifyProcess($objData, $config, &$msg)
    {
        $arrData = $objData->GetValues();
        if(!array_key_exists("appid", $arrData) || !array_key_exists("openid", $arrData) || !array_key_exists("transaction_id", $arrData))
        {
            return $this->toCall($arrData,"回调数据异常");
        }
        try {
            $checkResult = $objData->CheckSign($config);
            if($checkResult == false){
                return $this->toCall($arrData,"签名错误...");
            }
        } catch(Exception $e) {
            $arrData['e'] = json_encode($e);
            return $this->toCall($arrData,"验签错误...");
        }
        return $this->toCall($arrData,"ok");
    }

    public function setNotifyHandle($call=null){
        $this->call = $call;
        $config = new WxPayConfig($this->arrConfig);
        $this->Handle($config, true);
    }

    public function toCall($arrData,$msg){
        if (is_callable($this->call)){
            return ($this->call)($arrData,$msg);
        }else{
            print_r('回调函数异常。');
            return false;
        }
    }

}
