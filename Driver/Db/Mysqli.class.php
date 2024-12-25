<?php
namespace Spartan\Driver\Db;

defined('APP_NAME') or exit();

class Mysqli implements Db {
    private $arrConfig = [];//数据库配置

    /**
     * 初始化配置
     * Mysqli constructor.
     * @param array $_arrConfig
     */
    public function __construct($_arrConfig = []){
        $this->setConfig($_arrConfig);
    }

    /**
     * 设置配置文件
     * @param array $_arrConfig
     */
    public function setConfig($_arrConfig = []){
        (!isset($_arrConfig['CHARSET']) || !$_arrConfig['CHARSET']) && $_arrConfig['CHARSET'] = 'utf8';
        $this->arrConfig = $_arrConfig;
    }

    /**
     * @description 连接数据库方法
     * @return int
     */
    public function connect() {
        $intLinkID = mysqli_connect(
            $this->arrConfig['HOST'],
            $this->arrConfig['USER'],
            $this->arrConfig['PWD'],
            $this->arrConfig['NAME'],
            $this->arrConfig['PORT']
        );
        if (!$intLinkID){
            \Spt::halt(['sql connect fail',json_encode($this->arrConfig)]);
        }
        mysqli_query($intLinkID,"SET NAMES '".$this->arrConfig['CHARSET']."'");
        mysqli_query($intLinkID,"SET sql_mode=''");
        return $intLinkID;
    }

    /**
     * 释放查询结果
     * @param $intQueryID \mysqli_result
     * @return bool
     */
    public function free($intQueryID) {
	    return mysqli_free_result($intQueryID);
    }

    /**
     * 关闭数据库
     * @access public
     * @param $intLinkID
     */
    public function close($intLinkID) {
        $intLinkID && @mysqli_close($intLinkID);
    }

    /**
     * @param $intLinkID
     * @return bool
     */
    public function isReTry($intLinkID){
        $intErrNo = mysqli_errno($intLinkID);
        return ($intErrNo == 2013 || $intErrNo == 2006)?true:false;
    }

    /**
     * 执行查询 返回数据集
     * @access public
     * @param \mysqli $intLinkID 数据库连接
     * @param string $strSql sql指令
     * @return mixed
     */
    public function query($intLinkID,$strSql) {
        return mysqli_query($intLinkID,$strSql);
    }

    /**
     * 执行查询 返回数据集
     * @access public
     * @param \mysqli $intLinkID 数据库连接
     * @param string $strSql sql指令
     * @return mixed
     */
    public function execute($intLinkID,$strSql) {
        return mysqli_query($intLinkID,$strSql);
    }

    /**
     * 获得影响的更新记录数
     * @param $queryID
     * @return int
     */
    public function getNumRows($queryID){
        return mysqli_num_rows($queryID);
    }

    /**
     * 获取影响的返回记录数
     * @param $intLinkID
     * @return int
     */
    public function getAffectedRows($intLinkID,$result=0){
        return mysqli_affected_rows($intLinkID);
    }

    /**
     * 用于获取最后插入的ID
     * @access public
     * @param $intLinkID
     * @return integer
     */
    public function getInsertId($intLinkID) {
	    return mysqli_insert_id($intLinkID);
    }

    /**
     * 获得所有的查询数据
     * @param  $queryID
     * @return array
     */
    public function getAll($queryID) {
	    $arrResult = Array();
        while($row = mysqli_fetch_assoc($queryID)){
            $arrResult[] = $row;
        }
        mysqli_data_seek($queryID,0);
	    return $arrResult;
    }

    /**
     * 数据库错误信息
     * @param $intLinkID
     * @return string
     */
    public function error($intLinkID) {
	    return mysqli_error($intLinkID);
    }

    /**
     * SQL指令安全过滤
     * @access public
     * @param string $value  SQL指令
     * @param \mysqli $intLinkID 资源
     * @return string
     */
    public function escapeString($value,$intLinkID = null) {
        return mysqli_real_escape_string($intLinkID,$value);
    }

    /**
     * 随机排序
     * @access protected
     * @return string
     */
    public function parseRand(): string
    {
        return 'RAND()';
    }

    /**
 * 取得数据表的字段信息
 * @param $intLinkID
 * @param $tableName
 * @return array
 */
    public function getFields($intLinkID,$tableName) {
        $result = mysqli_query($intLinkID,'SHOW COLUMNS FROM '.$tableName);
        $arrInfo = Array();
        if(!$result) {
            return $arrInfo;
        }
        foreach ($result as $key => $val) {
            $arrInfo[$val['Field']] = Array(
                'name'    => $val['Field'],
                'type'    => $val['Type'],
                'notnull' => (bool)($val['Null'] === ''), // not null is empty, null is yes
                'default' => $val['Default'],
                'primary' => (strtolower($val['Key']) == 'pri'),
                'autoinc' => (strtolower($val['Extra']) == 'auto_increment'),
            );
        }
        return $arrInfo;
    }

    /**
     * 取得数据表的字段信息及注释
     * [类型,长度,小数,字段格式,主键,增值,否空,默认值,注释]
     * @param $intLinkID
     * @param $tableName
     * @return array
     */
    public function getFullFields($intLinkID,$tableName) {
        $result = mysqli_query($intLinkID,'SHOW FULL FIELDS FROM '.$this->arrConfig['PREFIX'].$tableName);
        $arrInfo = Array();
        if($result) {
            foreach ($result as $key => $val) {
                if (stripos($val['Type'],'(')){
                    list($typeName,$typeLong) = explode('(',$val['Type']);
                    list($typeLong,$typeSign) = explode(')',$typeLong);
                    $typeSign = trim($typeSign);
                }else{
                    $typeName = $val['Type'];
                    $typeLong = '0,0';
                    $typeSign = $val['Collation'];
                }
                !$typeSign && $typeSign = $val['Collation'];
                stripos($typeLong,',')===false && $typeLong .= ',0';
                list($typeLong1,$typeLong2) = explode(',',$typeLong);
                $arrInfo[$val['Field']] = Array(
                    $typeName,//字段类型
                    $typeLong1,//长度
                    !$typeLong2?0:$typeLong2,//小数
                    trim($typeSign),//字段格式
                    (strtolower($val['Key']) == 'pri')?'true':'false',//是否主键
                    (strtolower($val['Extra']) == 'auto_increment')?'true':'false',//是否自动增值
                    (strtolower($val['Null'] == 'no')?'true':'false'),
                    is_null($val['Default'])?'null':(stripos($typeSign,'general')?'Empty String':$val['Default']),
                    $val['Comment'],
                );
            }
        }
        return $arrInfo;
    }

    /**
     * @param $intLinkID
     * @param $tableName
     * @return array
     */
    public function showCreateTable($intLinkID,$tableName){
        $result = mysqli_query($intLinkID,'SHOW CREATE TABLE '.$this->arrConfig['PREFIX'].$tableName);
        $result = $result->fetch_array();
        !isset($result[1]) && $result[1] = [];
        return Array($tableName,$result[1],$this->arrConfig['PREFIX']);
    }

    /**
     * 取得数据库的表信息
     * @param $intLinkID
     * @param $strTable
     * @param $limit
     * @param $page
     * @return array
     */
    public function getTables($intLinkID,$strTable = '',$limit = 0,$page = 1,$strKey = '') {
        $page = max(1,intval($page));
        $strLimit = $limit > 0?' LIMIT '.$limit*($page-1).','.$limit:'';
        $arrResult = Array('data'=>[],'total'=>0);
        if (preg_match('/^[A-Za-z0-9\-\_]+$/',$strKey)){
            $strKey = " AND TABLE_NAME like '%".$strKey."%'";
        }else{
            $strKey = '';
        }
        if ($strTable){
            $arrResult['count'] = 1;
        }else{
            $strSql = "SELECT"." count(*) as tmp FROM information_schema.tables WHERE table_schema = '".$this->arrConfig['NAME']."'".$strKey;
            $queryID = mysqli_query($intLinkID,$strSql);
            $arrRow = mysqli_fetch_assoc($queryID);
            if($arrRow && isset($arrRow['tmp'])){
                $arrResult['count'] = $arrRow['tmp'];
            }
        }
        $strSql = "SELECT"." TABLE_NAME,ENGINE,TABLE_ROWS,AUTO_INCREMENT,CREATE_TIME,UPDATE_TIME,TABLE_COLLATION,".
            "TABLE_COMMENT FROM information_schema.tables WHERE table_schema = '".$this->arrConfig['NAME']."'".$strKey.
            ($strTable?" AND TABLE_NAME = '".$this->arrConfig['PREFIX'].$strTable."'":'').
            " {$strLimit}";
        $result = mysqli_query($intLinkID,$strSql);
        if ($result === false){
            return $arrResult;
        }
        $intPerTable = strlen($this->arrConfig['PREFIX']);
        foreach ($result as $val) {
            $arrResult['data'][] = Array(
                'name'=>substr($val['TABLE_NAME'],$intPerTable),
                'engine'=>$val['ENGINE'],
                'rows'=>$val['TABLE_ROWS'],
                'auto'=>$val['AUTO_INCREMENT'],
                'create_time'=>$val['CREATE_TIME'],
                'update_time'=>$val['UPDATE_TIME'],
                'collation'=>$val['TABLE_COLLATION'],
                'comment'=>$val['TABLE_COMMENT'],
            );
        }
        return $arrResult;
    }

    /**
     * limit
     * @param int
     * @return string
     */
    public function parseLimit($limit) {
	    return !empty($limit)?' LIMIT '.$limit.' ':'';
    }

	/**
	 * 字段和表名处理添加`
	 * @access protected
	 * @param string $key
	 * @return string
	 */
	public function parseKey($key) {
		$key = trim($key);
		if(!preg_match('/[,\'\"\*\(\)`.\s]/',$key)) {
            $key = '`'.$key.'`';
		}
		return $key;
	}
    
    /**
     * 启动事务,数据rollback 支持
     * @access public
     * @param string $strName 事务的名称
     * @return boolean
     */
    public function startTrans($intLinkID,$strName='')
    {
        return $this->query($intLinkID,'START TRANSACTION');
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @access public
     * @param string $strName 事务的名称
     * @return boolean
     */
    public function commit($intLinkID,$strName = '')
    {
        return $this->query($intLinkID,'COMMIT');
    }

    /**
     * 事务回滚
     * @access public
     * @return boolean
     */
    public function rollback($intLinkID)
    {
        return $this->query($intLinkID,'ROLLBACK');
    }

}