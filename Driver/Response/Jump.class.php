<?php
namespace Spartan\Driver\Response;
use Spartan\Lib\Response;

class Jump extends Response
{
    protected $contentType = 'text/html';

    /**
     * 处理数据
     * @access protected
     * @param  mixed $data 要处理的数据
     * @return mixed
     * @throws \Exception
     */
    public function output($data)
    {
        $data = \Spartan\Lib\View::instance()->fetch($this->options['jump_template'], $data);
        return $data;
    }
}
