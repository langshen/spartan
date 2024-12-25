<?php
namespace Spartan\Driver\Model;
use Spartan\Lib\Model;

class Entity extends Model
{
    public $arrConfig = [
        'filter'=>[],//不自动select的字段
        'auto'=>true,//自动使用request内容做匹配
        'array'=>false,//返回数组的格式
        'condition'=>false,//自动外露条件
        'enum'=>true,//自动解析枚举类型
        'count'=>false,//select时，是否需要汇总
        'action'=>'update',//update时，指定的动作,update或insert
        'limit'=>20,//select时的默认记录数
    ];
    public $strPrefix = '';//表名前缀
    public $strTable = '';//表名
    public $strComment = '';//表备注
    public $strAlias = 'a';//别名
    public $arrPrimary = [];//唯一主键 = ['主键名',主键值]
    public $arrCondition = [];//支持外露的查询条件
    public $arrShow = [];//支持外露且可见的查询条件
    public $arrDesign = [];//已经生成的事件
    public $arrEnum = [];//各种枚举类型
    public $arrRequire = [];//添加时必的字段
    public $arrFields = [];//所有的字段名,[类型,长度,小数,字段格式,主键,增值,否空,默认值,注释]
    public $arrKeyExp = ['in','like','between','gt','egt','lt','elt','neq','eq'];//支持的item对比类型

    /**
     * 自动的快捷操作
     * @param bool $blo
     * @return $this
     */
    public function Auto($blo = true){
        $this->setConfig(['auto'=>$blo]);
        return $this;
    }

    /**
     * 返回数组的格式的快捷操作
     * @param bool $blo
     * @return $this
     */
    public function setArr($blo = true){
        $this->setConfig(['array'=>$blo]);
        return $this;
    }

    /**
     * @description 设置是否自动解析枚举
     * @param bool $blo
     * @return $this
     */
    public function setEnum($blo = true){
        $this->setConfig(['enum'=>$blo]);
        return $this;
    }

    /**
     * 是否需要汇总的快捷操作
     * @param bool $blo
     * @return $this
     */
    public function setCount($blo = true){
        $this->setConfig(['count'=>$blo]);
        return $this;
    }

    /**
     * 指定的动作,update或insert
     * @param bool $blo
     * @return $this
     */
    public function Action($action = 'update'){
        $this->setConfig(['action'=>$action]);
        return $this;
    }

    /**
     * select时的默认记录数
     * @param int $limit
     * @return $this
     */
    public function limit($limit = 20){
        $this->setConfig(['limit'=>$limit]);
        return $this;
    }

    /**
     * 返回一个表的可查询条件
     * @return array
     */
    public function getSearchShow(){
        $arrShow = $this->arrShow;
        (!is_array($arrShow) || !$arrShow) && $arrShow = $this->arrCondition;
        !is_array($arrShow) && $arrShow = [];
        $arrResultCondition = [];
        foreach ($arrShow as $key=>$item){
            if (is_array($item)){
                $value = $item[2]??'';
            }else{
                $key = $item;
                $value = explode('#',$this->arrFields[$key][8]??'')[0];
            }
            $arrResultCondition[] = ['id'=>'a.'.$key,'text'=>$value];
        }
        return $arrResultCondition;
    }

    /**
     * 返回一个操作符数组
     * @return array
     */
    public function getSearchSymbol(){
        return Array(
            ['id'=>'like','text'=>'模糊'],
            ['id'=>'eq','text'=>'等于'],
            ['id'=>'gt','text'=>'大于'],
            ['id'=>'lt','text'=>'小于'],
            ['id'=>'neq','text'=>'不等于'],
            ['id'=>'egt','text'=>'大于等于'],
            ['id'=>'elt','text'=>'小于等于'],
            ['id'=>'between','text'=>'范围区间'],
        );
    }

    /**
     * 读取单一记录，返回一个记录的Array();
     * @param array $options
     * @param string $math
     * @return mixed
     */
    public function find($options = [],$math = ''){
        $bolArray = $this->getConfig('array');
        if (!$math){
            $options = $this->parseCondition($options);
        }
        $arrInfo = db()->find([$this->strTable,$this->strAlias],$options,$math);
        $this->arrConfig['enum'] && $this->parseEnum($arrInfo);
        return $bolArray?['success',$arrInfo===false?1:0,$arrInfo]:$arrInfo;
    }

    /**
     * 读取一个列表记录，返回一个列表的Array();
     * @param array $options
     * @return mixed
     */
    public function select($options = []){
        $options = $this->parseCondition($options);
        $options = $this->commonVariable($options);//自动POST或GET来的变量
        $arrResult = Array('data'=>[],'count'=>0);
        if ($this->getConfig('count') == true){//如果需要总条数
            $arrResult['count'] = db()->find(
                [$this->strTable,$this->strAlias],
                $options,'count(*)'
            );
        }
        $arrResult['data'] = db()->select([$this->strTable,$this->strAlias],$options);
        !$arrResult['data'] && $arrResult['data'] = [];
        if ($this->arrConfig['enum'] && $arrResult['data']){
            foreach ($arrResult['data'] as &$item){
                $item = $this->parseEnum($item);
            }unset($item);
        }
        $bolArray = $this->getConfig('array');
        if ($this->getConfig('count') == true){
            return $bolArray?['success',$arrResult['data']===false?1:0,$arrResult]:$arrResult;
        }else{
            return $bolArray?['success',$arrResult['data']===false?1:0,$arrResult['data']]:$arrResult['data'];
        }
    }

    /**
     * 删除记录。
     * @param array $options
     * @return mixed
     */
    public function delete($options = []){
        $bolArray = $this->getConfig('array');
        if (isset($options['where']['id'])){
            if (!is_array($options['where']['id']) && stripos($options['where']['id'],',')){
                $options['where']['id'] = explode(',',$options['where']['id']);
            }
            if (is_array($options['where']['id'])){//如果是个数据
                if (!isset($options['where']['id'][1]) || !$options['where']['id'][1]){
                    return $bolArray?['删除条件为空。',1]:false;
                }
                if (is_string($options['where']['id'][1])){
                    $options['where']['id'][1] = explode(',',$options['where']['id'][1]);
                }
                if (is_array($options['where']['id'][1]) && !is_numeric(implode('',$options['where']['id'][1]))){
                    return $bolArray?['删除ID数组不是数字。',1]:false;
                }
                $options['where']['id'][1] = array_filter(array_flip(array_flip($options['where']['id'][1])));
            }else{
                if (!is_numeric($options['where']['id'])){
                    return $bolArray?['删除ID不是数字。',1]:false;
                }
            }
        }
        $result = db()->delete($this->strTable,$options);
        return $bolArray?[$result===false?'删除失败':'删除成功',$result===false?1:0,[]]:$result;
    }

    /**
     * @param array $options
     * @param array $arrData
     * @return mixed
     */
    public function update($arrData = [], $options = []){
        $bolArray = $this->getConfig('array');
        $bolUpdate = false;
        !is_array($options) && $options = [];
        //主键中的自增字段不允许在data里。
        $strPrimary = '';//自增主键名
        $arrPrimary = [];//主键数组
        foreach ($this->arrPrimary as $key=>$value){
            if (!array_key_exists($key,$this->arrFields)){
                return $bolArray?["主键：{$key}不在字段中",1,[]]:false;
            }
            //主键是自增
            //所有的字段名,[类型,长度,小数,字段格式,主键,增值,否空,默认值,注释]
            if ($this->arrFields[$key][4] == 'true'||$this->arrFields[$key][5] == 'true'){
                $strPrimary = $key;//这是自增主键的名
                if (array_key_exists($key,$arrData) && $arrData[$key]){
                    $options['where'][$key] = $arrData[$key];
                    $arrPrimary[$key] = $arrData[$key];
                    unset($arrData[$key]);
                }else{
                    $arrPrimary[$key] = 0;
                }
            }
        }

        //传递数据不是表字段的，删除
        foreach ($arrData as $key=>$value){
            if (!array_key_exists($key,$this->arrFields)){
                unset($arrData[$key]);
            }
        }
        if (isset($options['where']) && $options['where']){
            foreach ($this->arrPrimary as $key=>$value){
                if (//只要找到一个自增主键，就是更新，不然都是添加
                    isset($options['where'][$key]) &&
                    $options['where'][$key] &&
                    array_key_exists($key,$this->arrFields) &&
                    ($this->arrFields[$key][4] == 'true'||$this->arrFields[$key][5] == 'true')
                ){
                    $bolUpdate = true;
                    break;
                }else{
                    $bolUpdate = false;
                }
            }
        }else{
            $bolUpdate = false;
            if (is_array($options) && isset($options['where'])){
                unset($options['where']);
            }
        }
        if (!$bolUpdate || $this->getConfig('action') == 'insert'){
            if ($this->getConfig('action') == 'insert' && isset($options['where']) && is_array($options['where'])){
                $arrData = array_merge($arrData,$options['where']);
            }
            foreach ($arrPrimary as $k=>$v){
                unset($arrData[$k]);
            }
            $result = max(0,db()->insert($this->strTable,$arrData,$options));
            $arrPrimary[$strPrimary] = $result;
        }else{
            $result = db()->update($this->strTable,$arrData,$options);
        }
        return $bolArray?[$result===false?'操作失败':'操作成功',$result===false?1:0,$arrPrimary]:$result;
    }

    /**
     * 添加和修改，返回的Data中，
     * @param array $arrData
     * @param array $options
     * @return mixed
     */
    public function updateField($arrData = [],$options = []){
        $bolArray = $this->getConfig('array');
        $result = db()->update($this->strTable,$arrData,$options);
        return $bolArray?[$result===false?'操作失败':'操作成功',$result===false?1:0,[]]:$result;
    }

    /**
     * 自动识别常用的POST或GET变量，并合并到options里
     * @param $options
     * @return mixed
     */
    private function commonVariable($options){
        //常用分页和排序
        $data['page'] = max(0, request()->param('pageIndex',0));
        $data['limit'] = request()->param('pageSize',0);
        !$data['page'] && $data['page'] = max(0, request()->param('page',0));
        !$data['limit'] && $data['limit'] = max(0,request()->param('limit',20));
        $data['order'] = request()->param('sortField','');
        if ($data['order']) {
            $data['order'] .= ' ' . request()->param('sortOrder','');
        }else{
            unset($data['order']);
        }
        //常用的搜索
        $searchType = request()->param('search_type','');
        $searchSymbol = request()->param('search_symbol','');
        $searchKey = trim(request()->param('search_key',''));
        $searchKeySelect = trim(request()->param('search_key_select',''));
        if ($searchKeySelect && $searchSymbol == 'eq' && $searchKey){
            $searchKey = '';//多个条件时，优先选择的值
        }
        if (stripos($searchKey,'\u') === 0){
            $searchKey = json_decode('"'.$searchKey.'"');
        }
        !in_array($searchSymbol, $this->arrKeyExp) && $searchSymbol = 'eq';
        (!$searchKey && $searchKeySelect) && $searchKey = $searchKeySelect;
        //指定搜索，search = [字段,eq,值]
        $arrSearch = json_decode(request()->param('search',''),true);
        !is_array($arrSearch) && $arrSearch = [];
        if ($arrSearch && (!$searchType || !$searchKey)){ //如果search_系列不成立
            $searchType = $arrSearch[0] ?? '';
            $searchSymbol = $arrSearch[1] ?? 'eq';
            $searchKey = $searchKey[2] ?? '';
        }
        if ($searchType && $searchKey) {
            if ($searchSymbol == 'like'){
                $arrSearchKey = explode(' ',$searchKey);
                $arrSearchSymbol = [];
                foreach ($arrSearchKey as $value){
                    $arrSearchSymbol[] = "%{$value}%";
                }
                if (count($arrSearchSymbol) > 1){
                    $data['where'][$searchType] = Array(
                        $searchSymbol,
                        $arrSearchSymbol,
                        'and'
                    );
                }else{
                    $data['where'][$searchType] = Array(
                        $searchSymbol,
                        $arrSearchSymbol[0]
                    );
                }
            }elseif ($searchSymbol == 'between'){
                if (stripos($searchKey,' ')>0){
                    list($key1,$key2) = explode(' ',$searchKey);
                }elseif(stripos($searchKey,'，')>0){
                    list($key1,$key2) = explode('，',$searchKey);
                }elseif(stripos($searchKey,'至')>0){
                    list($key1,$key2) = explode('至',$searchKey);
                }else{
                    list($key1,$key2) = explode(',',$searchKey);
                }
                if (strtotime($key1) && !strtotime($key2)){
                    $key2 = date('Y-m-d 23:59:59',strtotime($key1));
                }elseif (strtotime($key1) && strtotime($key2) && $key1 == $key2 && stripos($key1,' ')===false){
                    $key2 = date('Y-m-d 23:59:59',strtotime($key1));
                }
                $data['where'][$searchType] = Array($searchSymbol, trim($key1),trim($key2));
            }else{
                $data['where'][$searchType] = Array($searchSymbol, $searchKey);
            }
        }
        return $this->mergeOptions($options,$data);
    }

    /**
     * 并合二个Db的options
     * @param $options1 array 主选
     * @param $options2 array 设选
     * @return mixed
     */
    private function mergeOptions($options1,$options2){
        if (isset($options2['page']) && intval($options2['page']) > 0){
            $options1['page'] = $options2['page'];//如果第二个有就要了
            unset($options2['page']);
        }
        if (!isset($options1['limit']) && isset($options2['limit']) && intval($options2['limit']) > 0){
            $options1['limit'] = $options2['limit'];//如果第一个没有，第二个有就要了
            unset($options2['limit']);
        }
        if (isset($options2['order']) && $options2['order']){
            $options1['order'] = $options2['order'];//如果第二个有就要了
            unset($options2['order']);
        }
        if (isset($options1['where']) && isset($options2['where'])){
            $options1['where'] = array_merge($options1['where'],$options2['where']);
            unset($options2['where']);
        }elseif (!isset($options1['where']) && isset($options2['where'])){
            $options1['where'] = $options2['where'];
        }
        return $options1;
    }

    /**
     * 自动加入where条件，目前只有int和str两种，
     * @param array $arrOptions
     * @param string $strAction
     * @return array
     */
    private function parseCondition($arrOptions = [],$strAction = 'select'){
        if (!$this->arrConfig['condition']){
            return $arrOptions;
        }
        $tempWhere = [];//需要重写的where
        $arrWhere = (isset($arrOptions['where']) && is_array($arrOptions['where']))?$arrOptions['where']:[];
        $arrKeyType = [
            'int'=>['int','tinyint','smallint','mediumint','integer','bigint','float','numeric','decimal','double'],
            'str'=>['char','varchar','tinytext','text','mediumtext','longtext'],
            'time'=>['datetime','timestamp','date','time','year','timestamp']
        ];//目前支持的key类型
        foreach ($this->arrCondition as $key) {
            is_array($key) && $key = $key[0]??'';
            if (!$key){continue;}
            $strValue = request()->param($key,null);//传递过来的数据
            $strSymbol = request()->param("{$key}_symbol",'');
            if (!$strValue){continue;};
            $strKeyType = '';
            $strFieldType = $this->arrFields[$key][0]??'';
            foreach($arrKeyType as $k=>$v){
                if($strFieldType && in_array($strFieldType,$v)){
                    $strKeyType = $k;break;
                }
            }
            if(!$strKeyType){continue;}//找不到Key的类型
            !$strSymbol && $strSymbol = $this->getConfig($key);
            (!$strSymbol && !in_array($strSymbol,$this->arrKeyExp)) && $strSymbol = 'eq';
            $bolResult = $this->parseValue($strKeyType,$strSymbol,$strValue);
            if (!$bolResult || !$strValue){continue;}
            if (isset($arrWhere[$key]) || isset($arrWhere["a.{$key}"]) || isset($arrWhere["b.{$key}"])){
                continue;
            }
            stripos($key,'.') === false && $key = "a.$key";
            $tempWhere[$key] = $this->parseWhere($strSymbol,$strValue);
        }
        $arrOptions['where'] = !$arrWhere?$tempWhere:array_merge($arrWhere,$tempWhere);
        return $arrOptions;
    }

    /**
     * 根据类型条件，判断自动的条件的值
     * @param $strKeyType string 字段类型
     * @param $strSymbol string 操作符
     * @param $strValue mixed 变量值
     * @return mixed
     */
    private function parseValue($strKeyType,$strSymbol,&$strValue){
        if ($strSymbol == 'between' || $strSymbol == 'in'){
            $strValue = str_replace([' ','-','，',',','至'],[',',',',',',',',','],$strValue);
            $strValue = explode(',',$strValue,2);
            $strValue[1] = $strValue[1] ?? '';
        }
        $strValue = [$strValue];
        switch ($strKeyType){
            case 'int':
                foreach ($strValue as &$v){
                    $v = max(0,intval($v));
                }unset($v);
                break;
            case 'str':
                foreach ($strValue as &$v){
                    $v = htmlspecialchars(strval($v));
                }unset($v);
                break;
            case 'time':
                foreach ($strValue as $k=>$v){
                    if (!strtotime($v)){unset($strValue[$k]);}
                }
                break;
            default:
                return false;
        }
        return true;
    }

    /**
     * 解析WHERE最后的样式
     * @param $strSymbol
     * @param $arrValue
     * @return array
     */
    private function parseWhere($strSymbol,$arrValue){
        if ($strSymbol == 'like'){
            $arrSymbol = [];
            foreach ($arrValue as $value){
                $arrSymbol[] = "%{$value}%";
            }
            if (count($arrSymbol) > 1){
                return Array($strSymbol,$arrSymbol,'and');
            }else{
                return Array($strSymbol,$arrValue[0]);
            }
        }elseif ($strSymbol == 'between'){
            return Array($strSymbol, $arrValue[0],$arrValue[2]);
        }elseif ($strSymbol == 'in'){
            return Array($strSymbol, implode(',',$arrValue));
        }elseif ($strSymbol == 'exp'){
            return Array($strSymbol, implode('',$arrValue));
        }else{
            return Array($strSymbol, $arrValue[0]);
        }
    }

    /**
     * 得到一个字段的中文说明
     * @param $name
     * @return mixed|string
     */
    public function getFieldText($name){
        return explode('#',$this->arrFields[$name][8])[0]??'';
    }

    /**
     * @description 解析list中的一条记录
     * @param $item
     * @return mixed
     */
    public function parseEnum(&$item){
        if (!$item){
            return $item;
        }
        foreach ($this->arrEnum as $key=>$value){
            isset($item[$key]) && $item["{$key}_text"] = $value[$item[$key]]??'';
        }
        return $item;
    }

    /**
     * @description 获取某种枚举类型
     * @param int $intStatus
     * @return string|string[]
     */
    public function getEnum($name,$value=0){
        $arrEnum = $this->arrEnum[$name] ?? [];
        return $value > 0? ($arrEnum[$value]??'') : $arrEnum;
    }

    /**
     * @description 得到一个枚举列表
     * @param $name
     * @param string $strTip 空提示
     * @param string $intValue 空值
     * @param bool $bloStart 是否需要全部
     * @param bool $bloStr 是否返回字段串
     * @return string
     */
    public function getEnums($name,$strTip='全 部',$intValue='0',$bloStart=true,$bloStr=true){
        $arrEnum = $bloStart?[['v'=>$intValue,'n'=>$strTip]]:[];
        foreach ($this->getEnum($name) as $key=>$value){
            $arrEnum[] = ['v'=>$key,'n'=>$value];
        }
        return $bloStr?json_encode($arrEnum,320):$arrEnum;
    }

    /**
     * @description 得到一个枚举JS列表
     * @return string
     */
    public function getEnumJs($intValue=0){
        $arrEnum = [];
        foreach ($this->arrEnum as $key=>$item){
            $strTip = '全部'.$this->getFieldText($key);
            $arrEnum["a.{$key}"] = $this->getEnums($key,$strTip,$intValue,true,false);
        }
        return json_encode($arrEnum,320);
    }

    /**
     * @description 得到一个枚举变量列表
     * @return array
     */
    public function getEnumList($intValue=''):array
    {
        $arrEnum = [];
        foreach ($this->arrEnum as $key=>$item){
            $strTip = '请选择'.$this->getFieldText($key);
            $arrEnum["{$key}"] = $this->getEnums($key,$strTip,$intValue,true,false);
        }
        return $arrEnum;
    }
    
}