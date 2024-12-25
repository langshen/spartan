<?php
namespace Spartan\Extend;

include_once "PHPExcel-1.8.2/Classes/PHPExcel.php";

/**
 * Class PhpExcel
 * 项目地址：https://github.com/PHPOffice/PHPExcel
 */
class VenderPhpExcel{
    public $arrConfig = [];
    public $objPHPExcel = null;
    /** @var $objActiveSheet null|\PHPExcel\PHPExcel_Worksheet */
    public $objActiveSheet = null;//活的工作表
    private $numToEng = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ'];

    public function __construct($arrConfig = []){

    }

    public static function instance($arrConfig = []) {
        return \Spt::getInstance(__CLASS__,$arrConfig);
    }

    /**
     * 加载一个表格文件
     * @param $fileName
     * @param int $index
     * @return $this
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public function load($fileName,$index=0){
        $this->objPHPExcel = \PHPExcel_IOFactory::load($fileName);
        return $this->setActiveSheetByIndex($index);
    }

    /**
     * 得到表格数
     * @return mixed
     */
    public function getSheetCount(){
        return $this->objPHPExcel->getSheetCount();
    }

    /**
     * 设置一个活动的表格
     * @param int $index
     * @return $this
     */
    public function setActiveSheetByIndex($index=0){
        $this->objPHPExcel->setActiveSheetIndex($index);
        $this->objActiveSheet = $this->objPHPExcel->getActiveSheet();
        return $this;
    }

    /**
     * 获取表格行数
     * @return mixed
     */
    public function getHighestRow(){
        return $this->objActiveSheet->getHighestRow();
    }

    /**
     * 获取表格列数
     * @return mixed
     */
    public function getHighestColumn(){
        return $this->objActiveSheet->getHighestColumn();
    }

    /**
     * 得到一个表格内容
     * @param $columnRrow
     * @return mixed
     */
    public function getCell($columnRrow){
        return $this->objActiveSheet->getCell($columnRrow);
    }

    /**
     * 返回一个文档
     * @return \PHPExcel_Worksheet
     * @throws \PHPExcel_Exception
     */
    public function getSheet($title='',$index=0){
        $this->objPHPExcel = new \PHPExcel();
        $cls = $this->objPHPExcel->getProperties()->setCreator('spartan framework')
            ->setSubject("如需要生成更多需求的Excel文档，可以联系作者。")
            ->setDescription("请使用WPS或Office 2007或更高版本打开。");
        $title && $cls->setTitle($title);
        $this->objPHPExcel->setActiveSheetIndex($index);
        $this->objActiveSheet = $this->objPHPExcel->getActiveSheet();
        return $this->objActiveSheet;
    }

    /**
     * 设置一个活动文档的标题
     * @param $title
     */
    public function setSheetTitle($title){
        $this->objActiveSheet->setTitle($title);
    }

    /**
     * 新建一个工作表，并是否返回工作表
     * @param $index
     * @param false $result
     * @return bool
     */
    public function createSheet($index,$result=false){
        $this->objPHPExcel->createSheet($index);
        return $this->setActiveSheetIndex($index,$result);
    }

    /**
     * 设置一个工作表，并是否返回工作表
     * @param $index
     * @param false $result
     * @return bool
     */
    public function setActiveSheetIndex($index,$result=false){
        $this->objPHPExcel->setActiveSheetIndex($index);
        $this->objActiveSheet = $this->objPHPExcel->getActiveSheet();
        return $result?$this->objActiveSheet:true;
    }

    /**
     * 设置一个单元格
     * @param string $pCoordinate
     * @param null $pValue
     * @return mixed
     */
    public function setCellValue($pCoordinate = 'A1', $pValue = null){
        return $this->objActiveSheet->setCellValue($pCoordinate,$pValue);
    }

    /**
     * 合并单元格
     * @param $pRange
     * @return mixed
     */
    public function mergeCells($pRange){
        return $this->objActiveSheet->mergeCells($pRange);
    }
    
    /**
     * 快速设置一个列头
     * @param $array
     * @param int $index 开始行
     * @return bool
     */
    public function setColumnTitle($array,$index=1){
        $i = 0 ;
        foreach($array as $key => $value){//设置标头
            $iCell = $this->numToEng[$i].$index;
            //if (stripos($key,"\n")){
                //$this->objActiveSheet->getStyle($iCell)->getAlignment()->setWrapText(true);
            //}
            $this->setCellValue($iCell,$key);
            $i++;
        }
        return true;
    }

    /**
     * 快递填充内容
     * @param $array
     * @param int $index 开始行
     * @return bool
     */
    public function setRowContent($array,$index=2){
        for($i = 0; $i < count($array); $i++){
            $k = 0;
            foreach($array[$i] as $value){
                $iCell = $this->numToEng[$k].($i+$index);
                //if (stripos($value,"\n")) {
                    //$this->objActiveSheet->getStyle($iCell)->getAlignment()->setWrapText(true);
                //}
                $value = str_replace('\'','',$value);
                $this->objActiveSheet->setCellValueExplicit($iCell,$value,\PHPExcel_Cell_DataType::TYPE_STRING);
                $k++;
            }
        }
        return true;
    }

    /**
     * 输出并下一个文件
     * @param $fileName
     * @param string $writerType
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function outPut($fileName,$writerType='Excel5'){
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:attachment;filename=".$fileName.'.xls');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($this->objPHPExcel, $writerType);//Excel5
        $objWriter->save('php://output');
        return null;
    }

    /**
     * 快递把一个数组输出为excel
     * @param $array
     * @param string $fileName
     * @param string $fileType
     * @return null
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function outExcel($array,$fileName='',$title='',$fileType='xls'){
        !$fileName && $fileName = date('Y-m-d',time());
        $fileName.='.' . $fileType;// Create new PHPExcel object
        $this->getSheet();
        $title && $this->setSheetTitle($title);
        if(is_array($array)){
            if (isset ($array[0])) {
                $this->setColumnTitle($array[0]);
            }
            $this->setRowContent($array);//填充内容
        }
        $this->outPut($fileName);
        return null;
    }

}


