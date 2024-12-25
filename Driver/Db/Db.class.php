<?php
namespace Spartan\Driver\Db;

defined('APP_NAME') or exit();

interface Db{
    public function __construct($_arrConfig = []);//初始化
    public function setConfig($_arrConfig = []);//设置config
    public function connect();//连接数据库
    public function parseKey($key);
    public function escapeString($key,$intLinkID = null);
    public function parseRand();
    public function parseLimit($limit);
    public function isReTry($intLinkID);
    public function execute($intLinkID,$strSql);
    public function query($intLinkID,$strSql);
    public function getNumRows($queryID);
    public function getAffectedRows($intLinkID,$result);
    public function getAll($queryID);
    public function getInsertId($intLinkID);
    public function error($intLinkID);
    public function free($queryID);
    public function close($intLinkID);
    public function startTrans($intLinkID,$strName = '');
    public function commit($intLinkID,$strName = '');
    public function rollback($intLinkID);
}