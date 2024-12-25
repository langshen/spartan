<?php
/**
 * 第一步：
 * 建立项目目录，如：project1。
 * 第二步：
 * 复制本目录(wwwroot)到项目目录下，并配置站点指向wwwroot。
 * 第三步：
 * 配置HOST或直接访问站点，完成项目初始化
 *
 * 提示：
 * Spartan可作为多项目总框架，专注开发，建议把本入口目录与spartan框架目录同级
 * 如：
├─mySites                  专门放项目的目录，
│  ├─project1               其它项目1
│  │  ├─wwwroot
│  │  │  ├─index.php        站点入口文件
│  ├─project2               其它项目2
│  │  ├─wwwroot
│  │  │  ├─index.php        站点入口文件
│  ├─spartan                   框架根目录（git clone spartan）
 *
 */
require('../../spartan/Spartan.php');
Spt::start(
    Array(
        'DEBUG'=>true,//调试模式
        'SAVE_LOG'=>true,//保存日志
    )
);
