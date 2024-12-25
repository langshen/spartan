<?php
namespace Spartan\Lib;
defined('APP_NAME') OR die('404 Not Found');

class Console{

    /**
     * 输出信息
     * @param string $key
     * @param bool $end
     * @param string $value
     * @return mixed
     */
    public function console($key='',$value='',$end = false){
        \Spt::console($key,$value,$end);
        return true;
    }
}
