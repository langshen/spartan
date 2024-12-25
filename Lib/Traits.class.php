<?php
namespace Spartan\Lib;

/**
 * @description 公共的CURD
 */
trait Traits {

    /**
     * @description 设置表名
     * @param $name
     * @return $this
     */
    private function setTableName($name){
        $this->table = $name;
        return $this;
    }

    /**
     * @description 设置模版
     * @param $name
     * @return $this
     */
    private function setTemplateName($name){
        $this->template = $name;
        return $this;
    }

    /**
     * @description 返回搜索配置
     * @return array
     */
    private function getSearchInfo()
    {
        $dal = dal($this->table);
        return [
            'type' => $dal->getSearchShow(),
            'symbol' => $dal->getSearchSymbol(),
            'enum' => $dal->getEnumJs(),
        ];
    }

    /**
     * @description 得到一个枚举列表
     * @return array
     */
    private function getEnumList():array
    {
        return dal($this->table)->getEnumList();
    }

    /**
     * @description 取得单页记录
     * @param array $options
     * @param callable|null $callStart
     * @param callable|null $callEnd
     * @return array|mixed
     */
    private function detailRs(array $options = [], callable $callStart = null, callable $callEnd = null){
        !is_array($options) && $options = [];
        $options['where'] = $options['where'] ?? [];
        $intId = $options['where']['id'] ?? ($options['where']['a.id'] ?? 0);
        $intId < 1 && $intId = intval($this->request('id', 0));
        if ($intId < 1) {
            return [$this->table . '.id为空。', 1];
        }
        $bloEnum = $options['enum'] ?? true;unset($options['enum']);
        $options['where'] = array_merge(['a.id' => $intId], $options['where']);
        if (is_callable($callStart)) {
            $options = $callStart($options);
        }
        $arrResult = dal($this->table)->setConfig(['array' => true, 'enum' => $bloEnum])->find($options);
        if (is_callable($callEnd)) {
            $arrResult[2] = $callEnd($arrResult[2]??[]);
        }
        return $arrResult;
    }

    /**
     * @description 列表
     * @param array $options
     * @param callable|null $callStart
     * @param callable|null $callEnd
     * @return array|mixed
     */
    private function listRs(array $options = [], callable $call = null){
        $options = array_merge(['order' => 'a.id desc'], $options);
        $bloEnum = $options['enum'] ?? true;unset($options['enum']);
        $bloCount = $options['count'] ?? true;unset($options['count']);
        $bloCondition = $options['condition'] ?? true;unset($options['condition']);
        $dal = dal($this->table);
        $arrInfo = $dal->setConfig(['count' => $bloCount,'condition' => $bloCondition])->select($options);
        !$bloCount && $arrInfo = ['data' => $arrInfo];
        if (is_callable($call) || $bloEnum) {
            foreach ($arrInfo['data'] as &$item) {
                $item = $dal->parseEnum($item);
                is_callable($call) && $item = $call($item);
            }
            unset($item);
        }
        return $arrInfo;
    }

    /**
     * @description 保存
     * @param array $arrData
     * @param callable|null $callStart
     * @param callable|null $callEnd
     * @param null|false $bloTest
     * @return array|false|mixed
     */
    private function saveRs(&$arrData = [], callable $call = null,$bloTest = false){
        !is_array($arrData) && $arrData = [];
        $arrUpData = dal($this->table)->arrRequire;
        if (!valid($arrUpData, $message)) {
            return [$message, 1];
        }
        if (is_callable($call)) {
            $arrUpData = $call($arrUpData);
        }
        if (!is_array($arrUpData) || !$arrUpData){
            return [$arrUpData,1];
        }
        $arrData = array_merge($arrUpData, $arrData);
        return $bloTest?
            $arrData:
            dal($this->table)->setConfig(['array' => true])->update($arrData);
    }

    /**
     * @description 删除
     * @param array $options
     * @param callable|null $callStart
     * @param callable|null $callEnd
     * @param null|false $bloTest
     * @return array|false|mixed
     */
    private function delRs(&$options = [], callable $call = null,$bloTest = false){
        !is_array($options) && $options = [];
        $options['where'] = $options['where'] ?? [];
        $arrID = $this->getMulId('id', 0);
        if (!$arrID) {
            return [$this->table . '.id为空。', 1];
        }
        $options['where'] = array_merge(['id'=>['IN',$arrID]],$options['where']);
        if (is_callable($call)) {
            $options = $call($options);
        }
        if (!is_array($options) || !$options){
            return [$options,1];
        }
        return $bloTest?
            $options:
            dal($this->table)->setConfig(['array' => true])->delete($options);
    }

    /**
     * @description 快速更新
     * @param array $options 更新条件
     * @param array $arrData 更新项
     * @return mixed
     */
    private function modifyRs($options=[],$arrData=[]){
        $arrID = $this->getMulId('id',0);
        $strKey = $this->request('key', '');
        $strType = $this->request('type', '');
        if ($strType == 'int') {
            $intValue = intval($this->request($strKey, 0));
        } else {
            $intValue = preg_replace('/\s*/', '', $this->request($strKey, ''));
        }
        if (!$arrID || !$strKey || strlen($intValue) < 1) {
            return ['id,key不能为空，value长度大于0且没有回车，防止误操作。', 1];
        }
        !is_array($options) && $options = [];
        $options['where'] = $options['where'] ?? [];
        $options['where'] = array_merge(['id'=>['IN',$arrID]],$options['where']);
        $arrData = array_merge([$strKey => $intValue],$arrData);
        $bloResult = db()->update($this->table, $arrData, $options);
        if ($bloResult === false) {
            return ['更新失败。', 1];
        }
        return ['设置成功。', 0,$bloResult];
    }

    /**
     * @description 快速审核
     * @param array $options 审核项
     * @return mixed
     */
    private function mulValidSave(&$arrData, callable $call = null,$arrWhere=[]){
        !is_array($arrData) && $arrData = [];
        $arrUpData = [
            'status'=>['number|egt:0','请选择审核状态。','0'],
            'valid_content'=>['require|length:0,128','请填写备注。',''],
        ];
        if (!valid($arrUpData,$message)){
            return [$message,1];
        }
        $arrUpData['id'] = $this->getMulId('ids',0);
        if (is_callable($call)) {
            $arrUpData = $call($arrUpData);
        }
        if (!is_array($arrUpData) || !$arrUpData){
            return [$arrUpData,1];
        }
        $arrUpData = array_merge($arrUpData, $arrData);
        $arrData = $arrUpData;//Data是用址的，要返回。
        $options = ['where'=>array_merge($arrWhere,['id'=>['in',$arrUpData['id']]])];
        unset($arrUpData['id']);
        return dal($this->table)->setConfig(['array' => true])->updateField($arrUpData,$options);
    }

    /**
     * @description 快速更新排序
     * @param array $options 更新项
     * @param string $sortKey 更新字段
     * @return mixed
     */
    private function quickSort($options=[], string $sortKey = 'sort'){
        $arrDataId = $this->request('data', '');
        if (!is_array($arrDataId)) {
            return ['请先勾选需要排序的记录。', 1];
        }
        foreach ($arrDataId as $data) {
            $data['id'] = intval($data['id'] ?? 0);
            $data['sort'] = intval($data['sort'] ?? 0);
            !is_numeric($data['sort']) && $data['sort'] = 0;
            if ($data['id'] < 1) {
                continue;
            }
            $options = array_merge(['where' => ['id' => $data['id']]],$options);
            $arrData = [$sortKey => $data['sort']];
            db()->update($this->table, $arrData, $options);
        }
        return ['更新成功。', 0];
    }

    /**
     * @description 显示API需要的文档
     * @return mixed
     */
    private function apiDoc(){


        return [];

    }
}