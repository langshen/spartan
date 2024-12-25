<?php
namespace Spartan\Lib;
defined('APP_NAME') OR exit('404 Not Found');

class Cookie
{
    /**
     * 配置参数
     * @var array
     */
    protected $config = [
        'prefix'    => '',// cookie 名称前缀
        'expire'    => 0,// cookie 保存时间
        'path'      => '/',// cookie 保存路径
        'domain'    => '',// cookie 有效域名
        'secure'    => false,//  cookie 启用安全传输
        'httponly'  => false,// httponly设置
        'setcookie' => true,// 是否使用 setcookie
    ];

    /**
     * @param array $arrConfig
     * @return Cookie
     */
    public static function instance($arrConfig = []) {
        return \Spt::getInstance(__CLASS__,$arrConfig);
    }

    /**
     * 构造方法
     * Cookie constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->init($config);
    }

    /**
     * Cookie初始化
     * @access public
     * @param  array $config
     * @return mixed
     */
    public function init(array $config = [])
    {
        $_config = array_change_key_case(config('COOKIE'),CASE_LOWER);
        $this->config = array_merge($this->config,$_config,$config);
        if (!empty($this->config['httponly']) && PHP_SESSION_ACTIVE != session_status()) {
            ini_set('session.cookie_httponly', 1);
        }
        return $this;
    }

    /**
     * 设置或者获取cookie作用域（前缀）
     * @access public
     * @param  string $prefix
     * @return mixed
     */
    public function prefix($prefix = '')
    {
        if (empty($prefix)) {
            return $this->config['prefix'];
        }
        $this->config['prefix'] = $prefix;
        return $this;
    }

    /**
     * Cookie 设置、获取、删除
     *
     * @access public
     * @param  string $name  cookie名称
     * @param  mixed  $value cookie值
     * @param  mixed  $option 可选参数 可能会是 null|integer|string
     * @return mixed
     */
    public function set($name, $value = '', $option = null)
    {
        if (!is_null($option)) {
            if (is_numeric($option)) {
                $option = ['expire' => $option];
            } elseif (is_string($option)) {
                parse_str($option, $option);
            }
            $config = array_merge($this->config, array_change_key_case($option));
        } else {
            $config = $this->config;
        }
        $name = $config['prefix'] . $name;
        // 设置cookie
        if (is_array($value)) {
            array_walk_recursive($value, [$this, 'jsonFormatProtect'], 'encode');
            $value = 'spt:' . json_encode($value);
        }
        $expire = !empty($config['expire']) ? $_SERVER['REQUEST_TIME'] + intval($config['expire']) : 0;
        if ($config['setcookie']) {
            $this->setCookie($name, $value, $expire, $config);
        }
        $_COOKIE[$name] = $value;
        return $this;
    }

    /**
     * Cookie 设置保存
     *
     * @access public
     * @param  string $name  cookie名称
     * @param  mixed  $value cookie值
     * @param  mixed  $expire 有效期
     * @param  array  $option 可选参数
     * @return mixed
     */
    protected function setCookie($name, $value, $expire, $option = [])
    {
        setcookie($name, $value, $expire, $option['path'], $option['domain'], $option['secure'], $option['httponly']);
        return $this;
    }

    /**
     * 永久保存Cookie数据
     * @access public
     * @param  string $name  cookie名称
     * @param  mixed  $value cookie值
     * @param  mixed  $option 可选参数 可能会是 null|integer|string
     * @return mixed
     */
    public function forever($name, $value = '', $option = null)
    {
        if (is_null($option) || is_numeric($option)) {
            $option = [];
        }
        $option['expire'] = 315360000;
        $this->set($name, $value, $option);
        return $this;
    }

    /**
     * 判断Cookie数据
     * @access public
     * @param  string        $name cookie名称
     * @param  string|null   $prefix cookie前缀
     * @return bool
     */
    public function has($name, $prefix = null)
    {
        $prefix = !is_null($prefix) ? $prefix : $this->config['prefix'];
        $name   = $prefix . $name;
        return isset($_COOKIE[$name]);
    }

    /**
     * Cookie获取
     * @access public
     * @param  string        $name cookie名称 留空获取全部
     * @param  string|null   $prefix cookie前缀
     * @return mixed
     */
    public function get($name = '', $prefix = null)
    {
        $arrKeyName = explode('.', $name);
        !$arrKeyName && $arrKeyName = [''];
        $strKeyName = array_shift($arrKeyName);
        $prefix = !is_null($prefix) ? $prefix : $this->config['prefix'];
        $key    = $prefix . $name;
        if ('' == $name) {
            if ($prefix) {
                $value = [];
                foreach ($_COOKIE as $k => $val) {
                    if (0 === strpos($k, $prefix)) {
                        $value[$k] = $val;
                    }
                }
            } else {
                $value = $_COOKIE;
            }
        } elseif (isset($_COOKIE[$strKeyName])) {
            $value = $_COOKIE[$strKeyName]??'';
            if (0 === strpos($value, 'spt:')) {
                $value = substr($value, 4);
                $value = json_decode($value, true);
                array_walk_recursive($value, [$this, 'jsonFormatProtect'], 'decode');
                if ($arrKeyName && is_array($value)){
                    foreach ($arrKeyName as $val) {
                        if (isset($value[$val])) {
                            $value = $value[$val];
                        } else {
                            $value = null;
                            break;
                        }
                    }
                }
            }
        } else {
            $value = null;
        }
        return $value;
    }

    /**
     * Cookie删除
     * @access public
     * @param  string        $name cookie名称
     * @param  string|null   $prefix cookie前缀
     * @return mixed
     */
    public function delete($name, $prefix = null)
    {
        $config = $this->config;
        $prefix = !is_null($prefix) ? $prefix : $config['prefix'];
        $name   = $prefix . $name;
        if ($config['setcookie']) {
            $this->setcookie($name, '', $_SERVER['REQUEST_TIME'] - 3600, $config);
        }
        unset($_COOKIE[$name]);// 删除指定cookie
        return $this;
    }

    /**
     * Cookie清空
     * @access public
     * @param  string|null $prefix cookie前缀
     * @return mixed
     */
    public function clear($prefix = null)
    {
        // 清除指定前缀的所有cookie
        if (empty($_COOKIE)) {
            return $this;
        }
        // 要删除的cookie前缀，不指定则删除config设置的指定前缀
        $config = $this->config;
        $prefix = !is_null($prefix) ? $prefix : $config['prefix'];
        if ($prefix) {
            // 如果前缀为空字符串将不作处理直接返回
            foreach ($_COOKIE as $key => $val) {
                if (0 === strpos($key, $prefix)) {
                    if ($config['setcookie']) {
                        $this->setcookie($key, '', $_SERVER['REQUEST_TIME'] - 3600, $config);
                    }
                    unset($_COOKIE[$key]);
                }
            }
        }
        return $this;
    }

    private function jsonFormatProtect(&$val, $key, $type = 'encode')
    {
        if (!empty($val) && true !== $val) {
            $val = 'decode' == $type ? urldecode($val) : urlencode($val);
        }
        unset($key);
    }

}
