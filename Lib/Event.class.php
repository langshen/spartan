<?php
namespace Spartan\Lib;
defined('APP_NAME') OR die('404 Not Found');

/**
 * 事件管理类
 * Class Event
 * @package Spartan\Lib
 */
class Event
{
    /**
     * 监听者
     * @var array
     */
    protected $arrListener = [];

    /**
     * 事件别名
     * @var array
     */
    protected $arrBind = [];

    public function __construct($arrConfig=[]){
        $arrConfig = include_once(APP_ROOT.'Common'.DS.'EventConfig.php');
        $this->listenEvents($arrConfig['LISTEN']??[]);
        $this->subscribe($arrConfig['SUBSCRIBE']??[]);
    }

    /**
     * @param array $arrConfig
     * @return Event
     */
    public static function instance($arrConfig = []) {
        return \Spt::getInstance(__CLASS__,$arrConfig);
    }

    /**
     * 批量注册事件监听
     * @access public
     * @param array $events 事件定义
     * @return $this
     */
    public function listenEvents(array $events){
        foreach ($events as $event => $listeners) {
            if (isset($this->arrBind[$event])) {
                $event = $this->arrBind[$event];
            }
            !is_array($listeners) && $listeners = [$listeners];
            $this->arrListener[$event] = array_merge($this->arrListener[$event] ?? [], $listeners);
        }
        return $this;
    }

    /**
     * 注册事件监听
     * @access public
     * @param string $event    事件名称
     * @param mixed  $listener 监听操作（或者类名）
     * @param bool   $first    是否优先执行
     * @return $this
     */
    public function listen(string $event, $listener, bool $first = false){
        if (isset($this->arrBind[$event])) {
            $event = $this->arrBind[$event];
        }
        if ($first && isset($this->arrListener[$event])) {
            array_unshift($this->arrListener[$event], $listener);
        } else {
            $this->arrListener[$event][] = $listener;
        }
        print_r($this->arrBind);
        print_r($this->arrListener);
        return $this;
    }

    /**
     * 是否存在事件监听
     * @access public
     * @param string $event 事件名称
     * @return bool
     */
    public function hasListener(string $event): bool{
        if (isset($this->arrBind[$event])) {
            $event = $this->arrBind[$event];
        }
        return isset($this->arrListener[$event]);
    }

    /**
     * 移除事件监听
     * @access public
     * @param string $event 事件名称
     * @return $this
     */
    public function remove(string $event){
        if (isset($this->arrBind[$event])) {
            $event = $this->arrBind[$event];
        }
        unset($this->arrListener[$event]);
        return $this;
    }

    /**
     * 指定事件别名标识 便于调用
     * @access public
     * @param array $events 事件别名
     * @return $this
     */
    public function bind(array $events){
        $this->arrBind = array_merge($this->arrBind, $events);
        return $this;
    }

    /**
     * 注册事件订阅者
     * @access public
     * @param mixed $subscribers 订阅者
     * @return $this
     */
    public function subscribe($subscribers){
        !is_array($subscribers) && $subscribers = [$subscribers];
        foreach ($subscribers as $subscriber) {
            if (is_string($subscriber)) {
                $subscriber = \Spt::getInstance($subscriber);
            }
            if (method_exists($subscriber, 'subscribe')) {
                $subscriber->subscribe($this);// 手动订阅
            } else {
                $this->observe($subscriber);// 智能订阅
            }
        }
        return $this;
    }

    /**
     * 自动注册事件观察者
     * @access public
     * @param string|object $observer 观察者
     * @return $this
     */
    public function observe($observer){
        if (is_string($observer)) {
            $observer = \Spt::getInstance($observer);
        }
        if (!is_object($observer)){
            \Spt::halt('注册事件观察者类不存在:'.json_encode('$observer',320));
        }
        $reflect = new \ReflectionClass($observer);
        $observer = get_class($observer);
        $methods = $reflect->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if ($method->class !== $observer){continue;}
            $name = $method->getName();
            if (0 === strpos($name, 'on')) {
                $this->listen(substr($name, 2), [$observer, $name]);
            }
        }
        return $this;
    }

    /**
     * 触发事件
     * @access public
     * @param string|object $event  事件名称
     * @param mixed         $params 传入参数
     * @param bool          $once   只获取一个有效返回值
     * @return mixed
     */
    public function trigger($event, $params = null, bool $once = false){
        if (is_object($event)) {
            $params = $event;
            $event  = get_class($event);
        }
        if (isset($this->arrBind[$event])) {
            $event = $this->arrBind[$event];
        }
        $listeners = $this->arrListener[$event] ?? [];
        if (strpos($event, '.')) {
            [$prefix, $event] = explode('.', $event, 2);
            if (isset($this->arrListener[$prefix . '.*'])) {
                $listeners = array_merge($listeners, $this->arrListener[$prefix . '.*']);
            }
        }
        $result = [];
        $listeners = array_unique(array_filter($listeners), SORT_REGULAR);
        foreach ($listeners as $key => $listener) {
            $result[$key] = $this->dispatch($listener, $params);
            if (false === $result[$key] || (!is_null($result[$key]) && $once)) {
                break;
            }
        }
        return $once ? end($result) : $result;
    }

    /**
     * 触发事件(只获取一个有效返回值)
     * @param      $event
     * @param null $params
     * @return mixed
     */
    public function until($event, $params = null){
        return $this->trigger($event, $params, true);
    }

    /**
     * 执行事件调度
     * @access protected
     * @param mixed $event  事件方法
     * @param mixed $params 参数
     * @return mixed
     */
    protected function dispatch($event, $params = null){
        $handle = 'handle';
        if (is_object($event)){
            $event = get_class($event);
        }elseif (is_array($event)){
            [$event,$handle] = $event;
        }elseif (strpos($event, '::')) {
            [$event,$handle] = explode('::',$event,2);
        }elseif (strpos($event, '.')) {
            [$event,$handle] = explode('.',$event,2);
        }
        is_string($event) && $event = str_replace('/','\\',$event);
        !is_array($params) && $params = [$params];
        return \Spt::getInstance($event)->setData($params)->{$handle}();
    }

}
