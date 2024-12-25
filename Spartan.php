<?php
version_compare(PHP_VERSION,'7.2','<') && die('You need to use version 7.2 or higher.');
const DS = DIRECTORY_SEPARATOR,FRAME_PATH = __DIR__ . DS;//标准分隔符，设置框架路径

class Spt {
    public static $version = '1.24',$arrInstance = [],$arrConfig = [],$arrLang = [],$arrError = [];//实例化对象//站点实例的配置//语言包//错误信息

    /**
     * 框架开始启动
     * @param array $_arrConfig
     */
    public static function start(array $_arrConfig = [])
    {
        error_reporting(E_ALL);
        spl_autoload_register([__CLASS__,'autoLoad']);//注册AUTOLOAD方法
        set_error_handler([__CLASS__,'appError']);//用户自定义的错误处理函数
        set_exception_handler([__CLASS__,'appException']);//用户自己的异常处理方法
        register_shutdown_function([__CLASS__,'appShutdown']);//脚本执行关闭
        if (PHP_SAPI == 'cli'){
            if (!isset($_arrConfig['APP_NAME']) || !$_arrConfig['APP_NAME'] ||!isset($_arrConfig['APP_ROOT']) || !$_arrConfig['APP_ROOT']){
                die('You need to configure variable "APP_NAME" and "APP_ROOT" in cli mode.');
            }
        }
        $_arrConfig = array_merge([
                'APP_NAME'=>'Www',
                'APP_PATH'=>'',
                'SUB_APP_NAME'=>'',
                'APP_ROOT'=>str_ireplace('/',DS,dirname($_SERVER['DOCUMENT_ROOT']).DS.'application'),
                'LANG'=>'zh-cn',
                'IS_CLI'=>PHP_SAPI=='cli',
                'TIME_ZONE'=>'Asia/Shanghai',
                'DEBUG'=>false,
                'SAVE_LOG'=>false,
                'CONTROLLER'=>'',
                'MAIN_FUN'=>'',
                'FRAME_EXTEND'=>FRAME_PATH.'Extend'.DS,
            ],$_arrConfig);
        define('APP_NAME',$_arrConfig['APP_NAME']);
        !APP_NAME && die('You need to configure site\'s variable "APP_NAME".');
        substr($_arrConfig['APP_ROOT'],-1) != DS && $_arrConfig['APP_ROOT'] .= DS;//默认项目目录在上层的application
        define('APP_ROOT',$_arrConfig['APP_ROOT']);
        !$_arrConfig['APP_PATH'] && $_arrConfig['APP_PATH'] = APP_ROOT.APP_NAME.DS;
        substr($_arrConfig['APP_PATH'],-1) != DS && $_arrConfig['APP_PATH'] .= DS;
        !$_arrConfig['SUB_APP_NAME'] && $_arrConfig['SUB_APP_NAME'] = APP_NAME;
        date_default_timezone_set($_arrConfig['TIME_ZONE']);//设置系统时区
        $_arrConfig['APP_PATH'] = APP_ROOT.APP_NAME.DS;
        $_arrConfig['APP_BASE'] = dirname(APP_ROOT).DS;
        $_arrConfig['APP_EXTEND'] = $_arrConfig['APP_BASE'].'extend'.DS;
        $_arrConfig['CLASS_EXT'] = '.class.php';//设置类的后缀名
        self::$arrConfig = $_arrConfig;unset($_arrConfig);//全局化当前配置
        self::$arrConfig['DEBUG'] && self::createAppDir(APP_NAME); //检测并创建目录
        if (self::$arrConfig['IS_CLI']){
            self::loadApp();
            self::runServer();
        }else{
            self::setConfig(include(APP_ROOT.'Common'.DS.'Config.php'));
            if (self::getConfig('SESSION_HANDLER.NAME') && self::getConfig('SESSION_HANDLER.OPEN') === true){
                ini_set('session.save_handler',self::getConfig('SESSION_HANDLER.NAME'));
                ini_set('session.save_path',self::getConfig('SESSION_HANDLER.PATH'));
            }//session的配置完
            self::runController();
        }
    }

    /**
     * 创建app必要目录
     * @param string $strAppName
     */
    private static function createAppDir($strAppName=APP_NAME) {
        $arrDir = Array(
            APP_ROOT.'Common'.DS,
            APP_ROOT.'Model'.DS,
            APP_ROOT.'Traits'.DS,
            APP_ROOT.'Model'.DS.'Entity'.DS,
            APP_ROOT.'Runtime'.DS,
            APP_ROOT.'Runtime'.DS.'Cache'.DS,
            APP_ROOT.'Runtime'.DS.'Log'.DS,
            APP_ROOT.$strAppName.DS,
            APP_ROOT.$strAppName.DS.'Controller'.DS,
            APP_ROOT.$strAppName.DS.'Common'.DS,
        );
        if (!self::$arrConfig['IS_CLI']){
            $strDocumentDir = str_ireplace('/',DS,$_SERVER['DOCUMENT_ROOT']).DS;
            $arrDir[] = dirname($strDocumentDir).DS.'attachroot';
            $arrDir[] = dirname($strDocumentDir).DS.'extend';
            $arrDir[] = $strDocumentDir.'static'.DS.'admin';
            $arrDir[] = $strDocumentDir.'static'.DS.'www';
            $arrDir[] = APP_ROOT.'Runtime'.DS.'Cache'.DS.$strAppName.DS;
            $arrDir[] = APP_ROOT.'Runtime'.DS.'Log'.DS.$strAppName.DS;
            $arrDir[] = APP_ROOT.$strAppName.DS.'View'.DS;
            $arrDir[] = APP_ROOT.$strAppName.DS.'View'.DS.'Index'.DS;
        }else{
            $arrDir[] = APP_ROOT.$strAppName.DS.'Model'.DS;
        }
        $bolCreate = false;
        foreach ($arrDir as $dir){
            !is_dir($dir) && ($bolCreate = true && mkdir($dir,0755,true));
        }
        if (!$bolCreate && self::$arrConfig['IS_CLI']){
            if (!is_file(APP_ROOT.$strAppName.DS.'Controller'.DS.ucfirst(self::$arrConfig['CONTROLLER']).self::$arrConfig['CLASS_EXT'])){
                $bolCreate = true;
            }
        }
        $bolCreate && self::initAppConfig($strAppName);
    }

    /**
     * 初始化项目的某些内容
     * @param string $strAppName
     */
    private static function initAppConfig($strAppName=APP_NAME){
        //初始化首页的类和模版
        list($strSClass,$strClass,$strHtml) = explode('{Controller}',file_get_contents(FRAME_PATH.'Tpl'.DS.'default_index.tpl'));
        if (self::$arrConfig['IS_CLI']){
            (!isset(self::$arrConfig['CONTROLLER']) || !self::$arrConfig['CONTROLLER']) && self::$arrConfig['CONTROLLER'] = 'Main';
            (!isset(self::$arrConfig['MAIN_FUN']) || !self::$arrConfig['MAIN_FUN']) && self::$arrConfig['MAIN_FUN'] = 'runMain';
            $strFile = APP_ROOT.$strAppName.DS.'Controller'.DS.ucfirst(self::$arrConfig['CONTROLLER']).self::$arrConfig['CLASS_EXT'];
            !is_file($strFile) && file_put_contents($strFile, str_replace(
                ['{App_NAME}','{CONTROLLER}','{MAIN_FUN}'],
                [$strAppName,self::$arrConfig['CONTROLLER'],self::$arrConfig['MAIN_FUN']],
                trim($strSClass,PHP_EOL)
            ));
        }else{
            $strFile = APP_ROOT.$strAppName.DS.'Controller'.DS.'Index'.self::$arrConfig['CLASS_EXT'];
            !is_file($strFile) && file_put_contents($strFile,str_replace('{App_NAME}',$strAppName,trim($strClass,PHP_EOL)));
            $strFile = APP_ROOT.$strAppName.DS.'View'.DS.'Index'.DS.'index.html';
            !is_file($strFile) && file_put_contents($strFile,trim($strHtml,PHP_EOL));
        }

        //初始化配置文件
        list($strBaseConfig,$strConfig,$strAppConfig,$strFun,$strCommonFun) = explode('{Config}',file_get_contents(FRAME_PATH.'Tpl'.DS.'default_config.tpl'));
        $strFile = APP_ROOT.'Common'.DS.'BaseConfig.php';
        !is_file($strFile) && file_put_contents($strFile,trim($strBaseConfig,PHP_EOL));
        $strFile = APP_ROOT.'Common'.DS.'Config.php';
        !is_file($strFile) && file_put_contents($strFile,trim($strConfig,PHP_EOL));
        $strFile = APP_ROOT.'Common'.DS.'Functions.php';
        !is_file($strFile) && file_put_contents($strFile,trim($strFun,PHP_EOL));
        $strFile = APP_ROOT.$strAppName.DS.'Common'.DS.'Config.php';
        !is_file($strFile) && file_put_contents($strFile,trim($strAppConfig,PHP_EOL));
        $strFile = APP_ROOT.$strAppName.DS.'Common'.DS.'Functions.php';
        !is_file($strFile) && file_put_contents($strFile,trim($strCommonFun,PHP_EOL));
        //初始化Model/Model目录
        list($strEntity,$strModel,$strCache,$strLog,$strAttach,$strExtend,$strAdmin,$strStatic) = explode('{README}',file_get_contents(FRAME_PATH.'Tpl'.DS.'default_readme.tpl'));
        $strFile = APP_ROOT.'Model'.DS.'Entity'.DS.'README.md';
        !is_file($strFile) && file_put_contents($strFile,trim($strEntity,PHP_EOL));
        $strFile = APP_ROOT.'Model'.DS.'README.md';
        !is_file($strFile) && file_put_contents($strFile,trim($strModel,PHP_EOL));
        $strFile = APP_ROOT.'Traits'.DS.'README.md';
        !is_file($strFile) && file_put_contents($strFile,trim($strModel,PHP_EOL));
        $strFile = APP_ROOT.'Runtime'.DS.'Cache'.DS.'README.md';//当前项目
        !is_file($strFile) && file_put_contents($strFile,trim($strCache,PHP_EOL));
        $strFile = APP_ROOT.'Runtime'.DS.'Log'.DS.'README.md';//当前项目
        !is_file($strFile) && file_put_contents($strFile,trim($strLog,PHP_EOL));
        if (self::$arrConfig['IS_CLI']) {
            $strFile = APP_ROOT.$strAppName.DS.'Model'.DS.'README.md';//当前项目下的Model
            !is_file($strFile) && file_put_contents($strFile,trim($strModel,PHP_EOL));
        }else{
            $strDocumentDir = str_ireplace('/',DS,$_SERVER['DOCUMENT_ROOT']).DS;
            $strFile = dirname($strDocumentDir).DS.'attachroot'.DS.'README.md';//附件
            !is_file($strFile) && file_put_contents($strFile,trim($strAttach,PHP_EOL));
            $strFile = dirname($strDocumentDir).DS.'extend'.DS.'README.md';//扩展
            !is_file($strFile) && file_put_contents($strFile,trim($strExtend,PHP_EOL));
            $strFile = $strDocumentDir.'static'.DS.'www'.DS.'README.md';//前台
            !is_file($strFile) && file_put_contents($strFile,trim($strStatic,PHP_EOL));
            $strFile = $strDocumentDir.'static'.DS.'admin'.DS.'README.md';//后台
            !is_file($strFile) && file_put_contents($strFile,trim($strAdmin,PHP_EOL));
        }
    }

    /**
     * 加载语言包、函数、配置文件
     * @param string $strAppName
     */
    private static function loadApp($strAppName=APP_NAME){
        $arrFile = Array(
            FRAME_PATH.'Lang'.DS.self::$arrConfig['LANG'].'.lang.php',
            FRAME_PATH.'Common'.DS.'Functions.php',
            APP_ROOT.'Common'.DS.'Functions.php',
            APP_ROOT.$strAppName.DS.'Common'.DS.'Functions.php',
            APP_ROOT.$strAppName.DS.'Common'.DS.'Config.php'
        );
        foreach ($arrFile as $strFile){
            if (!is_file($strFile)){
                continue;
            }
            if (stripos($strFile,'Config.php') > 0){
                self::setConfig(include($strFile));
            }elseif (stripos($strFile,'.lang.php') > 0){
                self::setLang(include($strFile));
            }else{
                include($strFile);
            }
        }
        if (self::$arrConfig['IS_CLI']){
            self::loadDirFile(FRAME_PATH.'Lib');
            self::loadDirFile(APP_ROOT.'Common');
            self::loadDirFile(APP_ROOT.$strAppName.DS.'Controller');
        }
    }

    /**
     * 加载配置文件
     * @param null $name
     * @param null $value
     * @return boolean
     */
    public static function setConfig($name, $value = null){
        if (is_array($name)){
            $name = array_change_key_case($name,CASE_UPPER);
            foreach($name as $k=>$v){
                if (isset(self::$arrConfig[$k])){
                    if (is_array(self::$arrConfig[$k]) && is_array($v)){
                        self::$arrConfig[$k] = array_merge(self::$arrConfig[$k],$v);
                    }else{
                        self::$arrConfig[$k] = $v;
                    }
                }else{
                    self::$arrConfig[$k] = $v;
                }
            }
        }else{
            $arrName = explode('.',$name);
            if (count($arrName)==2){
                self::$arrConfig[$arrName[0]][$arrName[1]] = $value;
            }elseif (count($arrName)==2){
                self::$arrConfig[$arrName[0]][$arrName[1]][$arrName[2]] = $value;
            }elseif (count($arrName)==3){
                self::$arrConfig[$arrName[0]][$arrName[1]][$arrName[2]][$arrName[3]] = $value;
            }else{
                self::$arrConfig[$name] = $value;
            }
        }
        return true;
    }

    /**
     * 返回一个配置文件内容
     * @param string $name
     * @param null $default
     * @return mixed|string
     */
    public static function getConfig($name = '',$default = null){
        if ($name === '' && is_null($default)){
            return self::$arrConfig;
        }
        $arrName = explode('.',$name);
        $name = array_shift($arrName);
        $value = isset(self::$arrConfig[$name])?self::$arrConfig[$name]:null;
        if ($value === null){
            return $default;
        }
        foreach($arrName as $v){
            $value = isset($value[$v])?$value[$v]:null;
            if ($value === null){
                return $default;
            }
        }
        return $value;
    }

    /**
     * 设置一个提示语言
     * @param null $name
     * @param null $value
     * @return mixed
     */
    public static function setLang($name, $value=null){
        if (is_array($name)){
            $name = array_change_key_case($name,CASE_UPPER);
            foreach($name as $k=>$v){
                self::$arrLang[$k] = isset(self::$arrLang[$k])?array_merge(self::$arrLang[$k],$v):$v;
            }
        }else{
            self::$arrLang[$name] = $value;
        }
        return true;
    }

    /**
     * 返回一个提示语言
     * @param string $msg
     * @return mixed|string
     */
    public static function getLang($msg=''){
        return isset(self::$arrLang[$msg])?self::$arrLang[$msg]:$msg;
    }

    /**
     * 加载某一目录下所有文件，预加载
     * @param $strDir
     * @param $ext
     */
    public static function loadDirFile($strDir, $ext = '.class.php'){
        $arrDir = is_array($strDir)?$strDir:explode(',',$strDir);
        $intExtLen = strlen($ext);
        $arrNextPath = [];
        foreach($arrDir as $dir){
            $arrCore = new RecursiveDirectoryIterator(rtrim($dir,DS).DS);
            foreach($arrCore as $objFile){
                $strFile = $objFile->getPathname();
                if ($objFile->isDir()){
                    !in_array($objFile->getFilename(),['.','..']) && $arrNextPath[] = $strFile;
                }else{
                    substr($strFile,0 - $intExtLen) == $ext && include_once($strFile);
                }
            }
        }
        $arrNextPath && self::loadDirFile($arrNextPath,$ext);
    }

    /**
     * 提取一个实例
     * @param $className
     * @param array $config
     * @return mixed
     */
    public static function getInstance($className,$config = []){
        $className = trim($className,'\\');
        if (!$className){
            self::halt('类名为空，哪里错了？');
        }
        if (!isset(self::$arrInstance[$className])){
            self::$arrInstance[$className] = new $className($config);
        }
        return self::$arrInstance[$className];
    }

    /**
     * 设置一个实例
     * @param $className
     * @param $objClass
     * @return mixed
     */
    public static function setInstance($className,$objClass){
        self::$arrInstance[$className] = $objClass;
        return $objClass;
    }

    /**
     * 自动载加类
     * @param $class
     */
    public static function autoLoad($class){
        $appName = strstr($class,'\\',true);
        $dirName = strstr($class,'\\', false);
        if ($appName == 'Spartan'){//框架文件
            $dirName = FRAME_PATH . $dirName . self::$arrConfig['CLASS_EXT'];
        }elseif ($appName == 'Model') {//系统项目
            $dirName = APP_ROOT . $appName . $dirName . self::$arrConfig['CLASS_EXT'];
        }elseif ($appName == 'Extend') {//系统项目
            $dirName = self::$arrConfig['APP_EXTEND'] . $dirName . self::$arrConfig['CLASS_EXT'];
        }elseif(self::$arrConfig['EXTEND'][$appName]??[]){
            $dirName = self::$arrConfig['APP_EXTEND'] . (self::$arrConfig['EXTEND'][$appName]['path']??'') . $dirName;
            $dirName = $dirName . (self::$arrConfig['EXTEND'][$appName]['ext']??self::$arrConfig['CLASS_EXT']);
        }elseif ($appName == self::$arrConfig['SUB_APP_NAME'] || $appName == APP_NAME){//子项目
            $dirName = APP_ROOT . $appName . $dirName . self::$arrConfig['CLASS_EXT'];
        }else{//如果不是系统
            $dirName = self::$arrConfig['APP_PATH'].'../'. $appName . $dirName . self::$arrConfig['CLASS_EXT'];
        }
        $fileName = str_replace('//','/',str_replace('\\','/',$dirName));
        if (self::$arrConfig['IS_CLI'] && !is_file($fileName)){
            $dirName = realpath(pathinfo($fileName)['dirname']);
            $fileName = $dirName.DS.pathinfo($fileName)['basename'];
        }
        is_file($fileName) && include($fileName);
    }

    /**
     * 错误处理
     * @access public
     * @param  integer $errNo      错误编号
     * @param  integer $errStr     详细错误信息
     * @param  string  $errFile    出错的文件
     * @param  integer $errLine    出错行号
     * @return void
     */
    public static function appError($errNo, $errStr, $errFile = '', $errLine = 0){
        self::$arrError[] = Array('message'=>$errStr,'code'=>$errNo,'file'=>$errFile,'line'=>$errLine,'severity'=>E_ERROR);
    }

    /**
     * 异常处理
     * @access public
     * @param  Throwable $e 异常
     * @return void
     */
    public static function appException($e){
        if ($e instanceof \ParseError) {
            $message  = 'Parse error: ' . $e->getMessage();
            $severity = E_PARSE;
        } elseif ($e instanceof \TypeError) {
            $message  = 'Type error: ' . $e->getMessage();
            $severity = E_RECOVERABLE_ERROR;
        } else {
            $message  = 'Fatal error: ' . $e->getMessage();
            $severity = E_ERROR;
        }
        self::$arrError[] = Array('message'=>$message,'code'=>$e->getCode(),'file'=>$e->getFile(),'line'=>$e->getLine(),'severity'=>$severity);
    }

    /**
     * 异常中止处理
     * @access public
     * @return void
     */
    public static function appShutdown(){
        $error = error_get_last();
        if (is_null($error) && !self::$arrError){
            return;
        }
        if (in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            self::$arrError[] = Array('message'=>$error['message'],'code'=>$error['type'],'file'=>$error['file'],'line'=>$error['line']);

        }
        self::halt('system error');
    }

    /**
     * 错误输出
     * @param string $title
     * @param string|array $info
     * @return mixed
     */
    public static function halt($info,$title = 'system error'){
        list($info1,$info2) = is_array($info)?$info:[$info,''];
        $error = Array('title'=>self::getLang($title),'message'=>self::getLang($info1).($info2?':'.$info2:''));
        $trace = debug_backtrace();
        $error['file'] = $trace[0]['file'];
        $error['line'] = $trace[0]['line'];
        ob_start();
        debug_print_backtrace();
        $error['trace'] = ob_get_clean();
        $arrException = Array();
        sort(self::$arrError);
        foreach (self::$arrError as $v){
            !$error['message'] && $error['message'] = $v['message'];
            $arrException[] = $v['message'] . '<br>' . $v['file'] . '<br>'.'line:'.$v['line'] . ',code:'.$v['code'];
        }
        if (self::$arrConfig['IS_CLI']){//调试模式下输出错误信息
            $error['exception'] = implode(PHP_EOL,$arrException);
            unset($error['title']);
            foreach ($error as $k=>$v){
                print_r(iconv('UTF-8','gbk',$k.'='.str_ireplace('<br>',PHP_EOL,$v).PHP_EOL));
            }
        }else{
            $error['exception'] = '<p>' . implode('</p><p>',$arrException) . '</p>';
            include(FRAME_PATH.'Tpl'.DS.'exception.tpl');
        }
        !self::$arrError && self::$arrError = $error['exception']??[];
        self::$arrConfig['SAVE_LOG'] && self::saveLog($error['message'],'error');
        exit(0);
    }

    /**
     * Cli下显错误
     * @param string $key
     * @param bool $end
     * @param string $value
     * @return mixed
     */
    public static function console($key='',$value='',$end = false){
        if ($key && !$value){
            $value = $key;
            $key = '';
        }
        print_r("+++++++++++++++++{$key}++++++++++++++++++++".PHP_EOL);
        print_r($value);
        print_r(PHP_EOL);
        print_r("+++++++++++++++++{$key}++++++++++++++++++++".PHP_EOL);
        if ($end){
            self::$arrConfig['SAVE_LOG'] && self::saveLog('console end','notice');
            exit(0);
        }else{
            return true;
        }
    }

    /**
     * 保存错误日志
     * @param string $info
     * @param string $type
     */
    public static function saveLog($info = 'system error',$type='error') {
        if (!self::$arrConfig['SAVE_LOG']){return;}
        $strFileName = APP_ROOT . 'Runtime' . DS . 'Log' . DS . APP_NAME . DS .
            date('Ym') . DS . date('d') . (self::$arrConfig['IS_CLI'] ? '_cli' : '') . '.log';
        $strFilePath = dirname($strFileName);
        !is_dir($strFilePath) && mkdir($strFilePath, 0755, true);
        $arrMsg = Array(
            '---------------------------------------------------------------',
            implode(' ',Array(
                '['.date('Y-m-d H:i:s').']',
                isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:(isset($_SERVER['HTTP_REMOTE_HOST'])?$_SERVER['HTTP_REMOTE_HOST']:'unknown'),
                '[URL:'.self::$arrConfig['URL'].']',
                isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI',
                isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'CLI',
                json_encode($_POST,320),
            )),
            "[ {$type} ] {$info}" . (self::$arrError?var_export(self::$arrError,true):''),
            ''
        );
        error_log(implode(PHP_EOL,$arrMsg), 3, $strFileName);
    }

    /**
     * 启动运行一个服务
     */
    private static function runServer(){
        !self::$arrConfig['IS_CLI'] && self::console('Service only run in cli model.',true);
        self::$arrConfig['URL'] = isset($_SERVER['argv'][1])?$_SERVER['argv'][1]:'index/index';//如果是命令行，定义传入的参数
        (!isset(self::$arrConfig['CONTROLLER']) || !self::$arrConfig['CONTROLLER']) && self::$arrConfig['CONTROLLER'] = 'Main';
        (!isset(self::$arrConfig['MAIN_FUN']) || !self::$arrConfig['MAIN_FUN']) && self::$arrConfig['MAIN_FUN'] = 'runMain';
        $strClass = APP_NAME . '\\Controller\\'.ucfirst(self::$arrConfig['CONTROLLER']);//入口类
        !class_exists($strClass,true) && self::console($strClass.' not exist.',true);
        $objClass = new $strClass();
        !class_exists($strClass,true) && self::console($strClass.' not exist.',true);
        !method_exists($objClass,self::$arrConfig['MAIN_FUN']) && self::console("{$strClass}'s main function[".self::$arrConfig['MAIN_FUN']."] don't exits.",true);
        $objClass->{self::$arrConfig['MAIN_FUN']}();//入口函数
    }

    /**
     * 运行控制器
     */
    private static function runController() {
        $strPath = (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';//优先从REQUEST_URI
        (!$strPath && isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO']) && $strPath = $_SERVER['PATH_INFO'];//其次从PATH_INFO
        !$strPath && self::halt("This server don't support PathInfo.Unable to get URL address.");//无法得到URL
        $strPath = str_ireplace($_SERVER['SCRIPT_NAME'],'',$strPath);
        //开始整理得到的URL
        (($intPos = strpos($strPath,'?')) !== false) && $strPath = substr($strPath,0,$intPos);//只拿到？号之前
        $strPath = str_ireplace('.php','',str_ireplace('.html','',$strPath));//去掉常见后缀
        $arrPath = explode('/',strip_tags($strPath));//得到 / 拆分后的干净数组
        foreach ($arrPath as $k=>$v){if($v==''){unset($arrPath[$k]);}}
        $arrPath = array_values($arrPath);
        !$arrPath && $arrPath[0] = $arrPath[1] = 'Index';//默认为index
        $arrPath[0] = ucfirst($arrPath[0]);
        (!isset($arrPath[1]) || !$arrPath[1]) && $arrPath[1] = 'Index';//默认为index方法
        $arrSubApp = self::getConfig("SUB_APP.{$arrPath[0]}");//提取子项目列表
        if (isset($arrSubApp['OPEN']) && $arrSubApp['OPEN'] == true && array_shift($arrPath) == $arrSubApp['NAME']){
            $strSubAppName = $arrSubApp['NAME'];//当前存在的子项目
        }else{
            $strSubAppName = self::$arrConfig['SUB_APP_NAME'];
        }
        (!isset($arrPath[1]) || !$arrPath[1]) && $arrPath[1] = 'Index';//默认为index方法
        !preg_match('/^[A-Za-z_]([A-Za-z0-9_])*$/',$arrPath[0]) && $arrPath[0] = 'Index';//控制器不合法时设置为index
        self::$arrConfig['SUB_APP_NAME'] = $strSubAppName;
        self::$arrConfig['APP_PATH'] = APP_ROOT.$strSubAppName.DS;
        self::$arrConfig['DEBUG'] && !is_dir($strSubAppName) && self::createAppDir($strSubAppName); //检测并创建目录
        self::loadApp($strSubAppName);
        $strControl = ucfirst($arrPath[0]);//得到控制器
        self::$arrConfig['SUB_DIR'] = $strSubDir = '';//子目录
        if (is_dir(self::$arrConfig['APP_PATH'].'Controller'.DS.$strControl)){
            self::$arrConfig['SUB_DIR'] = $strControl;
            $strSubDir = $strControl . '\\';
            $strControl = ucfirst($arrPath[1]);//得到控制器
            $strAction = $arrPath[2] = ($arrPath[2] ?? 'Index');//得到方法
        }else{
            $strAction = $arrPath[1];//得到方法
        }
        self::$arrConfig['URL'] = ucfirst(implode('/',$arrPath));//定义全局使用的最终URL,除去子项目
        $strModule = $strSubAppName . '\\Controller\\' . $strSubDir . $strControl;//目标类
        $strErrorModule = $strSubAppName . '\\Controller\\Error';//空类
        self::$arrConfig['CONTROL'] = $strControl;//定义全局正在使用的最终控制器
        self::$arrConfig['ACTION'] = $strAction;//定义全局使用的最终方法
        $objModule = class_exists($strModule)?new $strModule():null;//实例化目标类
        if (!is_object($objModule)){//如果没有得到指定类，就使用空控制器
            (class_exists($strErrorModule) && $strControl = 'Error') && $objModule = new $strErrorModule();
        }
        !is_object($objModule) && self::halt(//控制器 和 空控制都不存在，退出并提示
            "[".$arrPath[0]."]({$strModule}) Controller not existing.".
            "[Error]({$strErrorModule}) Controller not existing."
        );
        if(!method_exists($objModule,$strAction)){//方法 和 空方法都不存在，退出并提示
            !method_exists($objModule,'_empty') && self::halt(
                "{$strControl} function [{$strAction}] and [_empty] not existing."
            );
            $strAction = '_empty';//真正执行的方法
        }
        unset($strPath,$arrPath,$intPos);
        $result = $objModule->{$strAction}();//执行对应控制器的方法，并把预想的原型方法传入。
        if ($result instanceof Spartan\Lib\Response){
            $result->send();
        }elseif ($result && !($result instanceof Spartan\Lib\Image)){
            Spartan\Lib\Response::create($result)->send();
        }
    }


} 