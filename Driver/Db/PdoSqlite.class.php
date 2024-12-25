<?php
namespace Spartan\Driver\Db;

defined('APP_NAME') or exit();

class PdoSqlite implements Db {
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
        $this->arrConfig = $_arrConfig;
    }

    /**
     * @description 连接数据库方法
     * @return int
     */
    public function connect() {
        if (!extension_loaded('sqlite3')){
            \Spt::halt(['SQLite3 extension not exist',json_encode($this->arrConfig)]);
        }
        try {
            $intLinkID = new \PDO(
                "sqlite:{$this->arrConfig['HOST']}{$this->arrConfig['NAME']}",
                $this->arrConfig['USER'],
                $this->arrConfig['PWD']
            );
        }catch (Exception $e){
            \Spt::halt(['pdo_sqlite connect fail',json_encode($this->arrConfig)]);
        }
        return $intLinkID;
    }

    /**
     * 释放查询结果
     * @param $intQueryID \mysqli_result|\SQLite3Result
     * @return bool
     */
    public function free($intQueryID) {
	    return $intQueryID->closeCursor();
    }

    /**
     * 关闭数据库
     * @access public
     * @param $intLinkID
     */
    public function close($intLinkID) {
        return true;
    }

    /**
     * @param $intLinkID
     * @return bool
     */
    public function isReTry($intLinkID){
        return false;
    }

    /**
     * 执行查询 返回数据集
     * @access public
     * @param \mysqli|\SQLite3 $intLinkID 数据库连接
     * @param string $strSql sql指令
     * @return mixed
     */
    public function query($intLinkID,$strSql) {
        return $intLinkID->query($strSql);
    }

    /**
     * 执行查询 返回数据集
     * @access public
     * @param \mysqli $intLinkID 数据库连接
     * @param string $strSql sql指令
     * @return mixed
     */
    public function execute($intLinkID,$strSql) {
        return $intLinkID->exec($strSql);
    }
    /**
     * 获得影响的更新记录数
     * @param $queryID \SQLite3Result
     * @return int
     */
    public function getNumRows($queryID){
        return $queryID->rowCount();
    }

    /**
     * 获取影响的返回记录数
     * @param \mysqli|\SQLite3 $intLinkID 数据库连接
     * @return int
     */
    public function getAffectedRows($intLinkID,$result=0){
        return $result;
    }

    /**
     * 用于获取最后插入的ID
     * @access public
     * @param \mysqli|\SQLite3 $intLinkID 数据库连接
     * @return integer
     */
    public function getInsertId($intLinkID) {
	    return $intLinkID->lastInsertId();
    }

    /**
     * 获得所有的查询数据
     * @param  $queryID \SQLite3Result
     * @return array
     */
    public function getAll($queryID) {
        $arrResult = [];
        while($row = $queryID->fetch(\PDO::FETCH_ASSOC)){
            $arrResult[] = $row;
        }
        return $arrResult;
    }

    /**
     * 数据库错误信息
     * @param \PDO $intLinkID 数据库连接
     * @return string
     */
    public function error($intLinkID) {
	    return implode("\r\n",$intLinkID->errorInfo());
    }

    /**
     * SQL指令安全过滤
     * @access public
     * @param string $str  SQL指令
     * @return string
     */
    public function escapeString($str,$intLinkID=NULL) {
        return str_ireplace("'", "''", \SQLite3::escapeString($str));
    }

    /**
     * 随机排序
     * @access protected
     * @return string
     */
    public function parseRand(): string
    {
        return 'RANDOM()';
    }

    /**
     * limit
     * @access public
     * @return string
     */
    public function parseLimit($limit) {
        $limitStr = '';
        if (!empty($limit)) {
            $limit = explode(',', $limit);
            if (count($limit) > 1) {
                $limitStr .= ' LIMIT ' . $limit[1] . ' OFFSET ' . $limit[0] . ' ';
            } else {
                $limitStr .= ' LIMIT ' . $limit[0] . ' ';
            }
        }
        return $limitStr;
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
        return $intLinkID->beginTransaction();
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @access public
     * @param string $strName 事务的名称
     * @return boolean
     */
    public function commit($intLinkID,$strName = '')
    {
        return $intLinkID->commit();
    }

    /**
     * 事务回滚
     * @access public
     * @return boolean
     */
    public function rollback($intLinkID)
    {
        return $intLinkID->rollback();
    }

}