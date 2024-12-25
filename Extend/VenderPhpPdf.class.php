<?php
namespace Spartan\Extend;

include_once "TCPDF-6.4.2/tcpdf.php";

/**
 * Class TCPDF
 * 项目地址：https://github.com/tecnickcom/TCPDF/releases
 */
class VenderPhpPdf {
    public $arrConfig = [];
    public $clsPdf = null;

    public function __construct($arrConfig = []){
        $this->arrConfig = $arrConfig;
    }

    public function getPdf($orientation='P'){
        $this->clsPdf = new \TCPDF($orientation, 'mm', 'A4', true, 'UTF-8', false);
        return $this->clsPdf;
    }


}


