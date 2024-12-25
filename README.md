<p align="center">
<img src="./logo.png" width="100" height="100" align="middle" />
<span style="font-size:18px;">Spartan Framework</span>
</p>

[![Latest Version](https://img.shields.io/badge/beta-v1.0.0-green.svg?maxAge=2592000)](https://github.com/swoft-cloud/swoft/releases)
[![Build Status](https://travis-ci.org/swoft-cloud/swoft.svg?branch=master)](https://travis-ci.org/swoft-cloud/swoft)
[![Php Version](https://img.shields.io/badge/php-%3E=7.0-brightgreen.svg?maxAge=2592000)](https://secure.php.net/)


### **简介**

一个基于PSR-4协议的新手入门框架，简化版的TP5.1框架，从新手的角度阐述PHP中常用的单例模式、OOP思想和MVC的框架思想，本着代码越少、知识点越少、门槛越底、上手越快的想法，框架对很多不常用的地方做统一固定，省去了过多自定义配置带来的学习和使用成本。对于高手本框架似乎没啥亮点^o^，期望更多的高手加入，共享、服务和提高更多的PHPer开发技能。

- 基于PSR-4协议，使用命名空间自动懒加载全部所需类。
- 去掉新手不常使用到的路由功能，直接从URL规定Controller和Action，支持EmptyController和_empty空Action。
- 基于OOP，把逻辑层、控制层、视图层和应用层的各类独立分解，方便新手更好地理解和专注于学习功能开发。
- 使用函数助手，把应用类（Cookies、Session、Request、Response、Validate、Db等）单实例化方便新手全局使用。
- 更方便新手的数据库ORM，后台常用的CURD自动生成，规范调用，代码少，思路清清晰。
- 方便的Session共享（默认Redis），让新手更容易做出一套跨环境（APP、小程序、公众号等）的应用。
- 独立Model目录，应用逻辑代码可重复应用于多项目，合适新手建站。


## 环境要求

1. [PHP 7.2 +](http://php.net/)
2. [MySQL5.6 +](https://www.mysql.com/downloads/)

## 目录结构
```
├─spartan                           框架根目录（一般和项目目录同级）
│  ├─Common                         公共目录
│  │  ├─Functions.php               函数助手
│  ├─Driver                         Lib核心类的驱动目录
│  │  ├─Db                          Db数据库驱动
│  │  │  ├─Mysqli.class.php         Mysql驱动
│  │  │  ├─Pgsql.class.php          Pgsql驱动
│  │  ├─Uploader                    文件上传驱动
│  │  │  ├─File.class.php           表单文件域上传，在request类中实例化
│  │  │  ├─UpFile.class.php         文件域或Base64文件上传，在request类中实例化
│  │  ├─...
│  ├─Extend                         扩展库
│  │  ├─Sender.class.php            邮件或手机验证码发送
│  │  ├─VenderPhpExcel.class.php    第三方PhpExcel库
│  │  ├─VenderWeChat.class.php      第三方WeChat库
│  ├─Lang                           语言目录
│  │  ├─zh-cn.lang.php              中文
│  ├─Lib                            核心类库
│  │  ├─Controller.class.php        控制器根类，其它控制器一般继承该类
│  │  ├─Model.class.php             逻辑层根类，其它逻辑层一般继承该类
│  │  ├─Request.class.php           输入控制类，所有的交互输入都从该类获取
│  │  ├─Validate.class.php          验证类，判断输入信息是否合法。
│  │  ├─...
│  ├─Tpl                            初始化模版
│  │  ├─default_config.tpl          配置文件模版
│  │  ├─...
│  ├─Spartan.php                    框架主文件，在项目入口文件引用即可
  
```

### 安装使用
* 项目部署

    Spartan框架本身不自带配置文件，不需要个性更改，所有配置选项都在“项目站点”，多个项目可共用框架，独立管理、部署和更新。

* 多个项目站点并存时，推荐以下目录布局，方便项目及框架的更新
```
├─project                  专门放项目的目录，也可以WebSite命名
│  ├─simple                例子基础项目
│  │  ├─application         项目基础目录
│  │  ├─attachroot          附件独立目录
│  │  ├─wwwroot             站点文档目录
│  │  │  ├─index.php        站点入口文件（代码在下面）
│  ├─project1               其它项目1
│  │  ├─wwwroot
│  │  │  ├─index.php        站点入口文件
│  ├─project2               其它项目2
│  │  ├─wwwroot
│  │  │  ├─index.php        站点入口文件
│  ├─spartan                框架根目录
│  │  ├─Common              公共目录
│  │  ├─Driver              Lib核心类的驱动目录
│  │  ├─Extend              扩展库
│  │  ├─Lang                语言包
│  │  ├─Lib                 核心类库
│  │  ├─Tpl                 默认模版
│  │  ├─Spartan.php         框架主文件，在项目入口文件引用即可
```

* 项目部署
    **新建项目project1/wwwroot并配置站点，加入index.php
```
<?php
require('../../spartan/Spartan.php');
Spt::start(
    Array(
        'DEBUG'=>true,//调试模式
        'SAVE_LOG'=>true,//保存日志
    )
);

```
* 运行站点（如：http://www.test.com）即可自动生成相应项目及目录

## 更新日志

[更新日志](changelog.md)

## 协议

Spartan 的开源协议为 Apache-2.0，详情参见[LICENSE](LICENSE)

    