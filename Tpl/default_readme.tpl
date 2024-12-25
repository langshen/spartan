#Table的实体目录，存放数据库表结构的基本类。

{README}
#Model，存放常用的数据逻辑类。

{README}
# cache
template cache

{README}
# Log
sql,err log

{README}
# attachroot
用户上传附件的根目录，可在此建立多个有意义的文件目录，如face,file。

把附件目录分离并存放在站点目录以上，有以下几个优势：
1、方便管理，可以很方便地只备份站点源文件或只备份附件；
2、可设置单独访问附件的站点来分流；
3、可设置该目录权限，以便用户上传更安全。

如不想使用单独二级域名访问，可在wwwroot目录下，建立一个attach的目录，并软连接指向该目录。

文件上传位置，可在项目下Common下的Functions.php中设置。

{README}
# extend
该项目需要用到的第三方扩展，或框架并不存大的功能模块。

{README}
# wwwroot/static/admin
存放管理后台所需的静态资源文件。

{README}
# wwwroot/static/www
存放主站前台所需的静态资源文件。
