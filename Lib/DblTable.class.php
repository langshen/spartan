<?php
namespace Spartan\Lib;

defined('APP_NAME') OR die('404 Not Found');

/**
 * 生成表结构
 * Class DblTable
 * @package Spartan\Lib
 */
class DblTable {

    public $config = [//配置文件
        'load_path'=>APP_ROOT,//检测类目录，以/结尾
        'name_space'=>'Model\\Entity',//命名空间
    ];

    /**
     * @param array $arrConfig
     * @return DblTable
     */
    public static function instance($arrConfig = []) {
        return \Spt::getInstance(__CLASS__,$arrConfig);
    }

    /**
     * DblTable constructor.
     * @param array $_arrConfig
     */
    public function __construct($_arrConfig = []){
        $this->config = array_merge($this->config,$_arrConfig);
        $arrTempClass = explode('\\',$this->config['name_space']);
        array_walk($arrTempClass,function(&$v){$v = ucfirst($v);});unset($v);
        $this->config['name_space'] = implode('\\',$arrTempClass);
    }

    /**
     * 返回所有表的结构
     * @param int $intLimit
     * @param int $intPage
     * @return array|string
     */
    public function tableList($strKey='',$intLimit = 0,$intPage = 1){
        $intLimit < 1 && $intLimit = max(0,intval(request()->param('limit',0)));
        $intPage <= 1 && $intPage = max(0,intval(request()->param('page',1)));
        !$strKey && $strKey = request()->param('key','');
        !preg_match('/^[A-Za-z0-9\-\_]+$/',$strKey) && $strKey = '';
        $arrTable = Db()->getTables('',$intLimit,$intPage,$strKey);
        foreach ($arrTable['data'] as &$value){
            $clsTemp = $this->getModel($value['name']);
            $value['status'] = is_object($clsTemp)?'已生成':'未建立';
        }
        unset($value);
        return Array('success',0,$arrTable);
    }

    /**
     * 返回指定表的所有字段
     * @param string $strTableName
     * @return array|string
     */
    public function tableInfo($strTableName = ''){
        !$strTableName && $strTableName = explode(',',strip_tags(request()->param('table','')))[0];
        if (!$strTableName){
            return Array('表名不能为空。',1);
        }
        $getFunc = function ($type,$long = 0){
            if (in_array($type,['int','tinyint','smallint','bigint','mediumint'])){
                return 'number';
            }elseif (in_array($type,['varchar','char','text'])){
                return 'length'.($long>0?':1,'.$long:'');
            }elseif(in_array($type,['decimal','float','double','real'])) {
                return 'float';
            }elseif (in_array($type,['datetime','date'])){
                return 'date';
            }else{
                return '';
            }
        };
        $parseEnumData = function($arrValue){
            $strTxt = '';
            foreach ($arrValue as $k=>$v){
                if(!$k || !$v){continue;}
                $strTxt .= "{$k}=>{$v}\n";
            }
            return trim($strTxt);
        };
        $clsDalTable = $this->getModel($strTableName);
        $arrCondition = $arrShow = $arrRequire = $arrEnum = [];
        $strComment = '';
        if (is_object($clsDalTable)){
            $arrCondition = $clsDalTable->arrCondition;
            $arrShow = $clsDalTable->arrShow;
            $arrRequire = $clsDalTable->arrRequire;
            $arrEnum = $clsDalTable->arrEnum ?? [];
            $strComment = $clsDalTable->strComment;
        }
        $arrInfo = db()->getFullFields($strTableName);
        if (!$arrInfo){
            return Array('没有找到表的相关信息。',1);
        }
        foreach ($arrInfo as $k=>&$v){
            $arrTempRequire = array_key_exists($k,$arrRequire)?$arrRequire[$k]:['','',''];
            $arrTempRequire[0] = $arrTempRequire[0]??'';
            $arrTempRequire[1] = $arrTempRequire[1]??'';
            $arrTempRequire[2] = $arrTempRequire[2]??'';
            if ( $arrTempRequire[0]){
                if (mb_substr($arrTempRequire[0],0,8)=='require|'){//如果有内容，判断是否必填写
                    $arrTempRequire[0] = ['require',mb_substr($arrTempRequire[0],8)];
                }else{
                    $arrTempRequire[0] = ['null',$arrTempRequire[0]];
                }
            }else{
                $strTempFun = $getFunc($v[0],$v[1]);
                $arrTempRequire[0] = ['',$strTempFun];//如果为空，给出默认函数
                if (!$arrTempRequire[1]){
                    $arrTempRequire[1] = explode('#',$v[8])[0];//给出默认提示
                    if (stripos($strTempFun,'length')!==false){
                        $arrTempRequire[1] .= "长度应该在1-{$v[1]}之间。";
                    }
                }
                !$arrTempRequire[2] && $arrTempRequire[2] = $arrTempRequire[0][1]=='number'?intval($v[7]):'';//给出默认值
            }
            if ($v[7] == 'CURRENT_TIMESTAMP'){//有默认时间的不设置，$v[0] == 'datetime';
                $arrTempRequire[0] = ['no',''];
            }
            $v = Array(
                'condition'=>in_array($k,$arrCondition)?'1':'',
                'show'=>in_array($k,$arrShow)?'1':'',
                'require'=>$arrTempRequire,
                'enum'=>array_key_exists($k,$arrEnum)?1:'',
                'enum_data'=>$parseEnumData($arrEnum[$k]??[]),
                'name'=>$k,
                'type'=>$v[0],
                'long'=>$v[1] . ',' .$v[2],
                'collation'=>$v[3],
                'pri'=>$v[4],
                'null'=>$v[6],
                'default'=>$v[7],
                'comment'=>$v[8]
            );
        }
        unset($v);
        return Array('success',0,['name'=>$strTableName,'comment'=>$strComment,'fields'=>$arrInfo]);
    }

    /**
     * 提交一个表的配置，生成对应的类
     * @param string $strTableName 表名
     * @param array $arrData 配置信息
     * @return array|string
     */
    public function tableCreate($strTableName = '',$arrData = []){
        $arrEnumData = request()->param('enum',[]);
        $arrEnumData['name'] = $arrEnumData['name'] ?? [];
        !is_array($arrEnumData['name']) && $arrEnumData['name'] = [];
        $arrEnumData['data'] = $arrEnumData['data'] ?? [];
        !is_array($arrEnumData['data']) && $arrEnumData['data'] = [];
        $arrEnum = [];
        foreach ($arrEnumData['name'] as $name){
            $strValue = $arrEnumData['data'][$name]??'';
            if (stripos($strValue,'=')===false){continue;}
            $strValue = str_replace('=>','=',$strValue);
            $arrValue = explode("\n",$strValue);
            foreach ($arrValue as $value){
                $value = explode('=',$value);
                $arrEnum[$name][$value[0]] = $value[1]??'';
            }
        }
        $arrData = array_merge(Array(
            'condition'=>request()->param('condition',[]),
            'show'=>request()->param('show',[]),
            'require'=>request()->param('require',[]),
            'function'=>request()->param('function',[]),
            'tip'=>request()->param('tip',[]),
            'default'=>request()->param('default',[]),
            'enum'=>$arrEnum
        ),$arrData);
        if (!$arrData['condition']){
            return Array('至少勾选一个查询条件。',1);
        }
        if ($arrData['condition'] && !is_array($arrData['condition'])){
            return Array('查询条件condition应该是个数组。',1);
        }
        if ($arrData['show'] && !is_array($arrData['show'])){
            return Array('可见条件show应该是个数组。',1);
        }
        if ($arrData['require'] && !is_array($arrData['require'])){
            return Array('必填项require应该是个数组。',1);
        }
        !$strTableName && $strTableName = strip_tags(request()->param('table_name',''));
        if (!$strTableName){
            return Array('表名不能为空。',1);
        }
        $arrFields = db()->getFullFields($strTableName);
        if (!$arrFields){
            return Array('查询表字段失败。',1);
        }
        $arrTableInfo = db()->getTables($strTableName);
        if (!isset($arrTableInfo['data']) || !$arrTableInfo['data'] || !isset($arrTableInfo['data'][0])){
            return Array('查询表的信息失败。',1);
        }
        $arrTableInfo = $arrTableInfo['data'][0];
        $arrTable = db()->showCreateTable($strTableName);
        if (!$arrTable || !isset($arrTable[1])){
            return Array('查询表SQL信息失败。',1);
        }
        $arrTableInfo['sql'] = $arrTable[1];
        $arrTableInfo['prefix'] = $arrTable[2];
        return $this->tableSave($arrTableInfo,$arrFields,$arrData);
    }

    /**
     * 生成数据表类
     * @param $taleInfo array 表信息
     * @param $tableField array 表字段信息
     * @param $arrConfig array 用户配置信息
     * @return array|string
     */
    public function tableSave($taleInfo,$tableField,$arrConfig){
        $arrVars = Array(
            'strTableName'=>$taleInfo['name'],
            'strTableSql'=>str_replace('"','\"',strstr($taleInfo['sql'],'(')),
            'strAlias'=>'a',
            'strComment'=>$taleInfo['comment'],
            'strPrefix'=>$taleInfo['prefix'],
        );
        if (!$arrVars['strTableSql']){
            return Array('解析表SQL错误',1);
        }
        $arrVars['strTableSql'] = 'CREATE "." TABLE `".$this->strPrefix."'.$taleInfo['name'].'` '.$arrVars['strTableSql'];
        if (stripos($arrVars['strTableName'],'_') > 0){
            $arrTempClass = explode('_',$arrVars['strTableName']);
            array_walk($arrTempClass,function(&$v){$v = ucfirst(strtolower($v));});
            $arrVars['strTablePath'] = array_shift($arrTempClass);
            $arrVars['strTableClass'] = implode('',$arrTempClass);
        }
        $arrVars['strPrimary'] = $arrVars['strFields'] = $arrVars['strCondition'] = '';
        $arrVars['strEnum'] = $arrVars['strShow'] = $arrVars['strRequire'] = '';
        foreach ($tableField as $key=>$value){
            $value[4]=='true' && $arrVars['strPrimary'] .= ",'{$key}'=>'{$value[0]}'";
            //unset($value[3],$value[4],$value[5],$value[6],$value[7]);
            $arrVars['strFields'] .= "\t\t'{$key}'=>['".implode("','",$value)."'],".PHP_EOL;
        }
        if ($arrVars['strPrimary']){
            $arrVars['strPrimary'] = '['.substr($arrVars['strPrimary'],1).']';
        }
        //开始enum判断
        foreach ($arrConfig['enum'] as $key=>$value){
            if (is_array($value) && $value && array_key_exists($key,$tableField)){
                $arrVars['strEnum'] .= "\t\t'{$key}'=>[".PHP_EOL;
                foreach ($value as $ka=>$va){
                    if (!$ka || !$va){continue;}
                    $ka = str_replace('"','',$ka);
                    $va = str_replace('"','',$va);
                    $arrVars['strEnum'] .= "\t\t\t'{$ka}'=>'".$va."',".PHP_EOL;
                }
                $arrVars['strEnum'] .= "\t\t],".PHP_EOL;
            }
        }
        //开始condition判断
        foreach ($arrConfig['condition'] as $key=>$value){
            if ($value == 1 && array_key_exists($key,$tableField)){
                $arrVars['strCondition'] .= "\t\t'{$key}',".PHP_EOL;
            }
        }
        //开始show判断
        foreach ($arrConfig['show'] as $key=>$value){
            if ($value == 1 && array_key_exists($key,$tableField)){
                $arrVars['strShow'] .= "\t\t'{$key}',".PHP_EOL;
            }
        }
        //开始require判断
        foreach ($arrConfig['require'] as $key=>$value){
            if ($value && $value != 'no' && array_key_exists($key,$tableField)){
                $strFunction = $arrConfig['function'][$key]??'';
                $strTip = $arrConfig['tip'][$key]??'';
                $strDefault = trim($arrConfig['default'][$key]??'');
                !$strTip && $v[8] = explode('#',$tableField[$key][8])[0];
                if ($value == 'require') {
                    $value .= '|';
                }else {
                    $value = '';
                }
                $arrVars['strRequire'] .= "\t\t'{$key}'=>['{$value}{$strFunction}','{$strTip}','{$strDefault}'],".PHP_EOL;
            }
        }unset($value);
        $strContent = file_get_contents(FRAME_PATH.'Tpl'.DS.'default_table.tpl');
        preg_match_all('/\{\$(.*?)\}/',$strContent,$arrValue);
        foreach ($arrValue[1] as $item) {
            $value = $arrVars[$item]??'';
            $strContent = str_replace('{$'.$item.'}', $value, $strContent);
        }
        $strFilePath = $this->config['load_path'].$this->config['name_space'].DS.$arrVars['strTablePath'];
        $strFilePath = str_replace(['\\','/'],[DS,DS],$strFilePath);
        if (!is_dir($strFilePath)){
            if (!@mkdir($strFilePath,0755,true)){
                return Array('目录不可写:'.$strFilePath,1);
            }
        }
        $strFilePath .= DS.$arrVars['strTableClass'].'.class.php';
        if (!@file_put_contents($strFilePath,$strContent)){
            return Array('目录子目录写入失败:'.$strFilePath,1);
        }
        return Array('模型：'.$strFilePath.'生成完成。',0);
    }

    /**
     * 已经存在的模型文件
     * @param string $strKey
     * @param int $intLimit
     * @param int $intPage
     *  @return array|mixed
     */
    public function modelList($strKey='',$intLimit = 0,$intPage = 1){
        unset($intLimit,$intPage);
        $this->loadTableModel($this->config['load_path'].$this->config['name_space'],$arrFiles,$strKey);
        !(is_array($arrFiles)) && $arrFiles = [];
        $arrTableList = Db()->getTables();
        if (!isset($arrTableList['data']) || !$arrTableList['data']){
            $arrTableList = ['data'=>[],'total'=>0];
        }
        $arrTableKey = array_column($arrTableList['data'],'name');
        $arrTableList['data'] = array_combine($arrTableKey,$arrTableList['data']);
        $arrTable = [];
        foreach ($arrFiles as $v){
            $clsTemp = $this->getModel($v);
            if (!is_object($clsTemp)){
                $arrTable[] = Array(
                    'name'=>str_replace($this->config['name_space'].'\\','',$v).config('get.CLASS_EXT'),
                    'comment'=>$this->config['load_path'].$this->config['name_space'].'\\',
                    'rows'=>'模型失败',
                    'create_time'=>'模型失败',
                    'collation'=>'模型失败',
                    'engine'=>'模型失败',
                    'status'=>'模型失败',
                );
            }elseif (!isset($clsTemp->strTable)){
                $arrTable[] = Array(
                    'name'=>str_replace($this->config['name_space'].'\\','',$v).config('get.CLASS_EXT'),
                    'comment'=>$this->config['load_path'].$this->config['name_space'].'\\',
                    'rows'=>'模型异常',
                    'create_time'=>'模型异常',
                    'collation'=>'模型异常',
                    'engine'=>'模型异常',
                    'status'=>'模型异常',
                );
            }elseif (!isset($arrTableList['data'][$clsTemp->strTable])){
                $arrTable[] = Array(
                    'name'=>$clsTemp->strTable,
                    'comment'=>'未建立表',
                    'rows'=>'未建立表',
                    'create_time'=>'未建立表',
                    'collation'=>'未建立表',
                    'engine'=>'未建立表',
                    'status'=>'未建立表',
                );
            }else{
                $arrTableList['data'][$clsTemp->strTable]['status'] = '表模正常';
                $arrTable[] = $arrTableList['data'][$clsTemp->strTable];
            }
        }
        return Array('success',0,['data'=>$arrTable,'count'=>count($arrTable),'table_count'=>$arrTableList['total']]);
    }

    /**
     * 读取一个表模型
     * @param $strTable
     * @return array
     */
    public function modelTable($strTable): array{
        $clsModel = $this->getModel($strTable);
        if (!is_object($clsModel)){
            return ['模型类和文件不存在:'.$strModelFile,1];
        }
        $arrData = [
            'table'=>$clsModel->strPrefix . $clsModel->strTable,
            'comment'=>$clsModel->strComment,
            'alias'=>$clsModel->strAlias,
            'fields'=>$clsModel->arrFields,
            'sql'=>$clsModel->sql(),
        ];
        foreach ($arrData['fields'] as $k=>&$v){
            $v[9] = in_array($k,$clsModel->arrCondition)?'1':'0';
            $v[10] = in_array($k,$clsModel->arrShow)?'1':'0';
            $v[11] = $clsModel->arrRequire[$k] ?? ['','',''];
        }unset($v);
        return ['success',0,$arrData];
    }

    /**
     * 根据模型文件建立数据表
     * @param false $bloCover
     * @param string $strModelName
     * @return array
     */
    public function modelCreateTable($bloCover=false,$strModelName=''){
        !$strModelName && $strModelName = request()->param('table','');
        if (!$strModelName || !is_scalar($strModelName)){
            return ['模型名称丢失。',1];
        }
        $arrConfig = array_filter(config('DB'));
        if (!isset($arrConfig['NAME']) || !isset($arrConfig['USER'])){
            return ['请配置数据库连接。',1];
        }
        if (stripos($strModelName,$arrConfig['PREFIX'])===0){
            $strModelName = substr($strModelName,strlen($arrConfig['PREFIX']));
        }
        $clsModel = $this->getModel($strModelName);
        if (!is_object($clsModel)){
            return ['模型类和文件不存在。'.$strModelName,1];
        }
        if (!isset($clsModel->strTable)){
            return ['模型中的表名丢失。',1];
        }
        $arrFields = db()->getFullFields($clsModel->strTable);
        if ($arrFields && !$bloCover){// 不覆盖
            return [$strModelName.'，数据表已经存在，不覆盖生成。',1];
        }
        if (!method_exists($clsModel,'sql')){
            return [$strModelName.'获取Sql失败。',1];
        }
        db()->execute("DROP TABLE IF EXISTS `".$clsModel->strPrefix.$clsModel->strTable."`;");//先删除表
        $result = db()->execute($clsModel->sql());
        if ($result === false){
            return [$strModelName.'建立表失败，请检查权限。',1];
        }
        return [$strModelName.'建立表成功。',0];
    }

    /**
     * 删除已经存在的模型文件
     * @param string $strModelName
     * @return array
     */
    public function modelDelete($strModelName=''){
        !$strModelName && $strModelName = request()->param('table','');
        if (!$strModelName || !is_scalar($strModelName)){
            return ['模型名称丢失。',1];
        }
        if (stripos($strModelName,config('get.DB.PREFIX'))===0){
            $strModelName = substr($strModelName,strlen(config('get.DB.PREFIX')));
        }
        $arrTable = explode(' ',ucwords(str_replace('_',' ',$strModelName)));
        if (count($arrTable) < 2){
            return ["模型名不正确:{$strModelName}",1];
        }
        $strParentModel = array_shift($arrTable);//模型目录
        $strModelFile = implode('',$arrTable).config('get.CLASS_EXT');
        $strModelFile = $this->config['load_path'].implode(DS,[$this->config['name_space'],$strParentModel,$strModelFile]);
        $strModelFile = str_replace('\\',DS,$strModelFile);
        if (!is_file($strModelFile)){
            return ["模型文件不存在:{$strModelFile}",1];
        }
        $bloResult = @unlink($strModelFile);
        if ($bloResult){
            return [$strModelName.'删除成功。',0];
        }
        return ["删除失败:{$strModelFile}",1];
    }

    /**
     * 找到Table下的所有已生成的模型
     * @param $strDir
     * @param array $arrFileName
     * @param string $strKey
     */
    private function loadTableModel($strDir,&$arrFileName = [],$strKey=''){
        $arrDir = is_array($strDir)?$strDir:explode(',',$strDir);
        $intExtLen = strlen(\Spt::$arrConfig['CLASS_EXT']);
        $arrNextPath = [];
        foreach($arrDir as $dir){
            $arrCore = new \RecursiveDirectoryIterator(rtrim(str_replace(['\\','/'],[DS,DS],$dir),DS).DS);
            foreach($arrCore as $objFile){
                $strFile = $objFile->getPathname();
                if ($objFile->isDir()){
                    !in_array($objFile->getFilename(),['.','..']) && $arrNextPath[] = $strFile;
                }else{
                    if (substr($strFile,0 - $intExtLen) == \Spt::$arrConfig['CLASS_EXT']){
                        $strFile = trim(explode($this->config['load_path'],strstr($strFile,'.',true))[1],DS.'.');
                        $arrTemp = explode(DS,$strFile);
                        if ($arrTemp[0].'\\'.$arrTemp[1] != $this->config['name_space']){
                            continue;
                        }
                        $arrTemp[2] = $arrTemp[2]??'';$arrTemp[3] = $arrTemp[3]??'';
                        $strTable = classNameToTable($arrTemp[2].$arrTemp[3]);
                        if (($strKey && stripos($strTable,$strKey)!==false) || !$strKey){
                            $arrFileName[] = $strFile;
                        }
                    }
                }
            }
        }
        $arrNextPath && $this->loadTableModel($arrNextPath,$arrFileName,$strKey);
    }

    /**
     * 跳过autoload得到一个实例，
     * @param $strClass
     * @return mixed
     */
    public function getModel($strClass){
        $strClass = model()->getClassFullName($strClass,$this->config['name_space']);
        $strFile = $this->config['load_path'].$strClass.config('get.CLASS_EXT');
        if (!is_file($strFile)){
            return null;
        }
        include_once($strFile);
        if (!class_exists($strClass,false)){
            return null;
        }
        return new $strClass();
    }

    /**
     * @description 加载配置
     * @param $arrDbConfig
     * @param $arrTableConfig
     * @return array
     */
    public function loadConfig(&$arrDbConfig,&$arrTableConfig){
        $arrConfig = session('config');
        if (is_array($arrConfig)){
            $arrConfig['DB'] = $arrConfig['DB'] ?? [];
            $arrConfig['TABLE'] = $arrConfig['TABLE'] ?? [];
            if (is_array($arrConfig['DB']) && $arrConfig['DB']){
                $arrDbConfig = array_merge($arrDbConfig,$arrConfig['DB']);
            }
            if (is_array($arrConfig['TABLE']) && $arrConfig['TABLE']){
                $arrTableConfig = array_merge($arrDbConfig,$arrConfig['TABLE']);
            }
        }
        config('DB',$arrDbConfig);
        $this->config = array_merge($this->config,$arrTableConfig);
        return ['加载完成',0];
    }

    /**
     * @description 前端提交配置
     * @param $arrDbConfig
     * @param $arrTableConfig
     * @return array
     */
    public function setConfig($arrDbConfig,$arrTableConfig){
        $arrConfig = ['DB'=>[],'TABLE'=>[]];
        if (($arrDbConfig['NAME']??'') && is_array($arrDbConfig)){
            $arrConfig['DB'] = $arrDbConfig;
        }
        if (($arrTableConfig['load_path']??'') && is_array($arrTableConfig)){
            $arrTableConfig['load_path'] = str_replace(['\\','/'],[DS,DS],$arrTableConfig['load_path']);
            substr($arrTableConfig['load_path'],-1) != DS && $arrTableConfig['load_path'] .= DS;
            if (substr($arrTableConfig['load_path'],-12) != 'application'.DS){
                return ['请输入项目中application的目录，必有application文件夹。',1];
            }
            $arrConfig['TABLE'] = $arrTableConfig;
        }
        session('config',$arrConfig);
        return ['设置成功。',0];
    }

    /**
     * @description 清除配置
     */
    public function clearConfig(){
        session('config',null);
        return ['清除完成。',0];
    }

}