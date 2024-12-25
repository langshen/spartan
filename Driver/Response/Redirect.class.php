<?php
namespace Spartan\Driver\Response;
use Spartan\Lib\Response;

class Redirect extends Response
{
    protected $options = [];
    // URL参数
    protected $params = [];

    public function __construct($data = '', $code = 302, array $header = [], array $options = [])
    {
        parent::__construct($data, $code, $header, $options);
        $this->cacheControl('no-cache,must-revalidate');
    }

    /**
     * 处理数据
     * @access protected
     * @param  mixed $data 要处理的数据
     * @return mixed
     */
    protected function output($data)
    {
        $this->header['Location'] = $this->getTargetUrl();
        return;
    }
    /**
     * 重定向传值（通过Session）
     * @access protected
     * @param  string|array  $name 变量名或者数组
     * @param  mixed         $value 值
     * @return $this
     */
    public function with($name, $value = null)
    {
        $session = \Spartan\Lib\Session::instance();
        if (is_array($name)) {
            foreach ($name as $key => $val) {
                $session->flash($key, $val);
            }
        } else {
            $session->flash($name, $value);
        }
        return $this;
    }

    /**
     * 获取跳转地址
     * @access public
     * @return string
     */
    public function getTargetUrl()
    {
        if (strpos($this->data, '://') || (0 === strpos($this->data, '/') && empty($this->params))) {
            return $this->data;
        }elseif (isset($this->params['url'])){
            return $this->params['url'];
        }elseif (isset($this->options['url'])){
            return $this->options['url'];
        }else{
            return $this->data;
        }
    }

    public function params($params = [])
    {
        $this->params = $params;
        return $this;
    }

    /**
     * 记住当前url后跳转
     * @access public
     * @return $this
     */
    public function remember()
    {
        \Spartan\Lib\Session::instance()->set('redirect_url', config('URL'));
        return $this;
    }

    /**
     * 跳转到上次记住的url
     * @access public
     * @return $this
     */
    public function restore()
    {
        $session = $session = \Spartan\Lib\Session::instance();
        if ($session->has('redirect_url')) {
            $this->data = $session->get('redirect_url');
            $session->delete('redirect_url');
        }
        return $this;
    }
}
