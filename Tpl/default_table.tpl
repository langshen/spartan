<?php
namespace Model\Entity\{$strTablePath};

defined('APP_NAME') or die('404 Not Found');
class {$strTableClass} extends \Spartan\Driver\Model\Entity
{
    //表名前缀
	public $strPrefix = '{$strPrefix}';
    //表名
	public $strTable = '{$strTableName}';
    //表备注
    public $strComment = '{$strComment}';
	//别名
	public $strAlias = '{$strAlias}';
	//唯一主键 = ['主键名',主键值]
	public $arrPrimary = {$strPrimary};
	//枚举类型
    public $arrEnum = Array(
{$strEnum}    );
	//支持选择的查询条件
    public $arrCondition = Array(
{$strCondition}    );
	//支持可见的查询条件
    public $arrShow = Array(
{$strShow}    );
    //添加时必的字段
    public $arrRequire = Array(
{$strRequire}    );
    //所有的字段名,[类型,长度,小数,字段格式,主键,增值,否空,默认值,注释]
    public $arrFields = Array(
{$strFields}    );

    //表的SQL
    public function sql(): string{
        return "{$strTableSql}";
    }
}