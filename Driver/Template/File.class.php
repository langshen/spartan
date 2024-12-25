<?php
namespace Spartan\Driver\Template;

class File{
    protected $cacheFile;

    /**
     * 取得数据库类实例
     * @return File
     */
    public static function instance(){
        return \Spt::getInstance(__CLASS__);
    }

    /**
     * 写入编译缓存
     * @access public
     * @param  string $cacheFile 缓存的文件名
     * @param  string $content 缓存的内容
     * @return void|array
     */
    public function write($cacheFile, $content)
    {
        // 检测模板目录
        $dir = dirname($cacheFile);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // 生成模板缓存文件
        if (false === file_put_contents($cacheFile, $content)) {
            \Spt::halt('WRITE ERROR:'.$cacheFile,'cached directory has no write permissions');
        }
    }

    /**
     * 读取编译编译
     * @access public
     * @param  string  $cacheFile 缓存的文件名
     * @param  array   $vars 变量数组
     * @return mixed
     */
    public function read($cacheFile, $vars = [])
    {
        $this->cacheFile = $cacheFile;

        if (!empty($vars) && is_array($vars)) {
            // 模板阵列变量分解成为独立变量
            extract($vars, EXTR_OVERWRITE);
        }

        //载入模版缓存文件
        return include($this->cacheFile);
    }

    /**
     * 检查编译缓存是否有效
     * @access public
     * @param  string  $cacheFile 缓存的文件名
     * @param  int     $cacheTime 缓存时间
     * @return boolean
     */
    public function check($cacheFile, $cacheTime)
    {
        // 缓存文件不存在, 直接返回false
        if (!file_exists($cacheFile)) {
            return false;
        }

        if (0 != $cacheTime && time() > filemtime($cacheFile) + $cacheTime) {
            // 缓存是否在有效期
            return false;
        }

        return true;
    }

}
