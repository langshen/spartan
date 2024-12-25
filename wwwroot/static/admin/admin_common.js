if (typeof staticUrl === 'undefined'){staticUrl = 'http://www.baidu.com';}
let layPath = staticUrl+'/static/admin/layui-2.6.8/layui-v2.6.8/';
let layAdmin = staticUrl+'/static/admin/layui-2.6.8/lay-admin/';
document.write('<link rel="stylesheet" href="'+layPath+'css/layui.css">');
document.write('<link rel="stylesheet" href="'+layAdmin+'css/admin.css">');
document.write('<script src="'+layPath+'layui.js" charset="utf-8" type="text/javascript"></sc'+'ript>');
document.write('<script src="'+staticUrl+'/static/admin/layui-2.6.8/common.js?v=2.2" charset="utf-8" type="text/javascript"></sc'+'ript>');
document.write('<link rel="stylesheet" href="'+staticUrl+'/static/admin/layui-2.6.8/common.css">');