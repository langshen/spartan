<?php
namespace {App_NAME}\Controller;
use Spartan\Lib\Console;

/**
 * 入口主类，多个CLI服务，可以使用多个入口
 * Class {CONTROLLER}
 * @package {App_NAME}\Controller
 */
class {CONTROLLER} extends Console{

    /**
     * 入口函数，方便和WEb一样的URL模式，可用如下调用：
     * php index.php member/login
     * getUrl可以得到传入的参数，上面例子中"member/login"就是URL，和WEB一样。     *
     */
    public function {MAIN_FUN}(){
        $arrUrl = explode('/',config('URL'));
        $this->console('Hello, this is {MAIN_FUN}.',$arrUrl,true);

    }
}
{Controller}
<?php
namespace {App_NAME}\Controller;
use Spartan\Lib\Controller;

defined('APP_NAME') or die('404 Not Found');

class Index extends Controller {

    public function index(){

        return $this->fetch();
    }

}
{Controller}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>欢迎使用Spartan.</title>
    <style type="text/css">
        *{ padding: 0; margin: 0; }
        div{ padding: 4px 48px;}
        a{color:#2E5CD5;cursor: pointer;text-decoration: none}
        a:hover{text-decoration:underline; }
        body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px;}
        h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; }
        p{ line-height: 1.6em; font-size: 42px;}
    </style>
</head>
<body>
    <div style="padding: 24px 48px;">
        <h1>^o^</h1>
        <h2>欢迎使用Spartan，项目已经初始化完成。</h2>
    </div>
</body>
</html>
