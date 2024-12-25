<?php
/*
*项目的基础配置，如果使用SVN或GIT更新生产环境，忽略该文件即可，非常实用
*/
defined('APP_NAME') or die('404 Not Found');
return Array(
    'SITE'=>Array(//站点设置
        'DOMAIN_ROOT'=>'',//根域名：baidu.com,如果是国家级域名 com.cn net.cn 之类的域名需要配置
        'NAME'=>'Spartan主页',
        'KEY_NAME'=>'spartan,framework,db orm',
        'DESCRIPTION'=>'spartan是一个轻量级的PHP框架，非常非常地轻；部署非常常方便。',
    ),
    'DB'=>Array(//数据库设置
        'TYPE'=>'mysqli',//数据库类型
        'HOST'=>'',//服务器地址
        'NAME'=>'',//数据库名
        'USER'=>'',//用户名
        'PWD'=>'',//密码
        'PORT'=>'3306',//端口
        'PREFIX'=>'j_',//数据库表前缀
        'CHARSET'=>'utf8',//数据库编码默认采用utf8
    ),
    'SESSION_HANDLER'=>Array(//Session服务器，如果启用，可以共享session
        'OPEN'=>false,
        'NAME'=>'redis',
        'PATH'=>'',
    ),
    'SMS'=>Array(//短信发送配置
        'SENDER'=>'Sms',//短信发送类，默认使用“创世漫道”
        'PROTOCOL'=>'http://',
        'SERVER'=>'sdk.entinfo.cn',
        'USER_NAME'=>'',
        'PASS_WORD'=>'',
        'PORT'=>'8060',
        'INTERVAL'=>3,//间隔时间，秒
        'CHARSET'=>'GBK',
        'ACTION'=>'/webservice.asmx/mdSmsSend_u',//发送的动作，
        'DEBUG'=>true,//测试发送是模拟发送
    ),
    'EMAIL'=>Array(//邮件服务器配置
        'SENDER'=>'Mailer',//邮件发送类
        'SERVER'=>'',//邮件STMP地址
        'USER_NAME'=>'',//地址
        'PASS_WORD'=>'',//密码
        'PORT'=>25,//端口
        'FROM_EMAIL'=>'',//发件人EMAIL
        'FROM_NAME'=>'', //发件人名称
    ),
    'WX_PAYMENT'=>Array(//微信支付配置
        'APP_ID' => '',
        'MCH_ID' => '',//商户号（必须配置，开户邮件中可查看）
        'APP_KEY' =>'',//商户支付密钥，（必须配置，登录商户平台自行设置）
        'APP_SECRET'=> '',//公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置），
        'NOTIFY_URL'=> '',//异步通知url
        'API_CLIENT_CERT'=>APP_ROOT.'Common'.DS.'Keys'.DS.'Wx'.DS.'apiclient_cert.pem',
        'API_CLIENT_KEY'=>APP_ROOT.'Common'.DS.'Keys'.DS.'Wx'.DS.'apiclient_key.pem',
    ),
    'XCX_CONFIG'=>Array(//小程序配置
        'APP_ID'=>'',
        'APP_SECRET'=>'',
        'TOKEN'=>'',
        'ENCODING_AES_KEY'=>'',
    ),
    'MP_CONFIG'=>Array(//公众号配置
        'APP_ID'=>'',
        'APP_SECRET'=>'',
        'TOKEN'=>'',
        'ENCODING_AES_KEY'=>'',
    )
);
{Config}
<?php
/*
*项目的常用、公共的配置
*/
defined('APP_NAME') or die('404 Not Found');
$arrConfig = include('BaseConfig.php');
$arrTemp =  Array(
    'COOKIE'=>Array(
        'PREFIX'=>'',//cookie 名称前缀
        'EXPIRE'=>0,//cookie 保存时间
        'PATH'=>'/',//cookie 保存路径
        'DOMAIN'=>'',//cookie 有效域名,为空时，默认为：.xx.com
        'HTTPONLY'=>'',//httponly设置
        'SECURE'=>false,//cookie 启用安全传输
        'SETCOOKIE'=>true,//是否使用 setcookie
    ),
    'SESSION'=>Array(
        'AUTO_START'=>true,// 是否自动开启Session
        'PREFIX'=>'',// session 前缀
        'VAR_SESSION_ID'=>'',//SESSION_ID的提交变量,解决flash上传跨域
        'NAME'=>'SPASESSION',//sessionID的变量名
        'DOMAIN'=>'',//为空时，默认为：.xx.com
        'EXPIRE'=>24*3600,//存活时间
        'TYPE'=>'',//驱动方式 支持redis memcache memcached
    ),
    'SUB_APP'=>Array(//是否启用多应用模式，即多个应用模式分离，如：前台和后台
        'Www'=>Array(//key为SUB_APP_NAME,
            'OPEN'=>true,//是否启用
            'NAME'=>'Www',//子应用名称，即SUB_APP_NAME
        ),
        'Admin'=>Array(
            'OPEN'=>true,
            'NAME'=>'Admin',
        ),
        'Xcx'=>Array(
            'OPEN'=>true,
            'NAME'=>'Xcx',
        ),
        'Mp'=>Array(
            'OPEN'=>true,
            'NAME'=>'Mp',
        ),
        'App'=>Array(
            'OPEN'=>true,
            'NAME'=>'App',
        ),
    ),
    'ACCOUNT_TYPE'=>Array(//帐目类型，'调用别名'=>Array(ID号,名称);
        'recharge'=>Array(1,'充值'),
        'withdraw'=>Array(2,'提现'),
        'reward'=>Array(3,'奖励'),
        'vip'=>Array(4,'升级Vip'),
    ),
);

return array_merge($arrConfig,$arrTemp);
{Config}
<?php
/*
*站点的常用、公共的配置
*/
defined('APP_NAME') or die('404 Not Found');
$arrConfig = include(APP_ROOT.'Common'.DS.'Config.php');
$arrTemp = Array(

);
return array_merge($arrConfig,$arrTemp);
{Config}
<?php
/*
*项目的常用、公共的函数、常量，可以在模版直接使用，如：{$Spt.wwwUrl}。
*/
defined('APP_NAME') or die('404 Not Found');

function attachPath($path=''){
    //return "../attachroot/".trim($path,'/');//复件独立站点请使用该行
    return "../wwwroot/attach/".trim($path,'/');//复件在www下请使用该行
}
function uploadPath($path=''){
    return attachPath($path);
}
function wwwUrl(){
    return request()->scheme().'://www.'.request()->rootDomain();
}
function staticUrl(){
    return '/public/';
}
function attachUrl($path=''){
    return request()->scheme().'://www.'.request()->rootDomain().'/attach/'.trim($path,'/');
}
function apiUrl(){
    return request()->scheme().'://api.'.request()->rootDomain();
}
function adminUrl(){
    return request()->scheme().'://www.'.request()->rootDomain().'/admin';
}
function domainUrl(){
    return request()->rootDomain();
}
{Config}
<?php
/*
*子项目的常用、公共的函数、常量，可以在模版直接使用，如：{$Spt.wwwUrl}。
*/
