<!DOCTYPE><!--使用layui界面-->
<html>
<head>
    <title>数据表名生成器</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0"/>
    <script language="JavaScript" src="/static/admin/layui_common.js"></script>
</head>

<body>
{eq name="action" value="list"}
<div class="layui-fluid">
    <div class="layui-card">
        <div class="layui-card-body">
            <table class="layui-table">
                <thead>
                <tr>
                    <th>表名称（共{$list.total}个表）</th>
                    <th>表注释（共{$list.total}个表）
                        <input type="text" id="key" value="{$Spt.GET.key}" placeholder="过滤表" style="border:#ccc solid 1px;border-radius: 2px;padding:2px 4px;" />
                        <button class="layui-btn layui-btn-normal layui-btn-xs" onclick="search()">搜索</button>
                    </th>
                    <th>表行数</th>
                    <th>建表时间</th>
                    <th>表编码</th>
                    <th>引擎</th>
                    <th>管理</th>
                </tr>
                </thead>
                <tbody>
                {volist name="list.data" id="vo"}
                <tr>
                    <td>{$vo.name}</td>
                    <td>{$vo.comment}</td>
                    <td>{$vo.rows}</td>
                    <td>{$vo.create_time}</td>
                    <td>{$vo.collation}</td>
                    <td>{$vo.engine}</td>
                    <td><a class="layui-btn layui-btn-normal layui-btn-xs{eq name='vo.status' value='未建立'} layui-btn-danger{/eq}" href="?action=info&table={$vo.name}">{$vo.status}</a></td>
                </tr>
                {/volist}
                </tbody>
            </table>
        </div>
    </div>
</div>
<script language="JavaScript">
    layui.use(['form'],function () { });
    function search(){
        let key = layui.jquery('#key').val();
        if (/^[A-Za-z0-9\-\_]+$/ig.test(key)){
            location.href='?key='+key;
        }else{
            layui.jquery('#key').val('');
            layui.layer.msg('关键字异常。',{icon:2});
        }
    }
</script>
{/eq}

{eq name="action" value="info"}
<div class="layui-fluid">
    <form id="frmCreate" onsubmit="return false;">
        <div class="layui-card">
            <div class="layui-card-body">
                <table class="layui-table">
                    <thead>
                    <tr>
                        <td colspan="9" style="background-color: white;">
                            <div class="layui-inline">
                                <label class="layui-form-label">表名称</label>
                                <div class="layui-input-block">
                                    <input type="text" value="{$info.name}" name="table_name" class="layui-input" />
                                </div>
                            </div>
                            <div class="layui-inline" style="width: 35%">
                                <label class="layui-form-label">表注释</label>
                                <div class="layui-input-block">
                                    <input type="text" value="{$info.comment}" placeholder="表名：{$info.name}，默认别名：a，表注释自动获取" class="layui-input" readonly />
                                </div>
                            </div>
                            <div class="layui-inline">
                                <button type="button" class="layui-btn layuiadmin-btn-useradmin" onclick="postCreate(this);">提交生成</button>
                                <button type="button" class="layui-btn" onclick="window.history.go(-1);">返回</button>
                                （文件生成到Model\Entity，需要写入权）
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th style="width:60px">自动查询</th>
                        <th style="width:60px">是否必填</th>
                        <th style="width:300px">函数体 / 变量</th>
                        <th>提示词</th>
                        <th style="width:100px">默认值</th>
                        <th>字段名称</th>
                        <th style="width:80px">注释</th>
                        <th>字段类型</th>
                        <th>为空(默认)</th>
                    </tr>
                    </thead>
                    {volist name="info.fields" id="vo"}
                    <tr>
                        <td><select name="condition[{$vo.name}]">
                            <option value="1" {eq name="vo.condition" value="1"}selected{/eq}>可查</option>
                            <option value="0" {neq name="vo.condition" value="1"}selected{/neq}>不可</option>
                        </select></td>
                        <td><select name="require[{$vo.name}]">
                            <option value="null" {eq name="vo.require.0.0" value="null"}selected{/eq}>可为空</option>
                            <option value="" {eq name="vo.require.0.0" value="no"}selected{/eq}>不设置</option>
                            <option value="require" {eq name="vo.require.0.0" value="require"}selected{/eq}>必填</option>
                        </select></td>
                        <td><input name="function[{$vo.name}]" value="{$vo.require.0.1}" style="width:100%;" /></td>
                        <td>
                            <input name="tip[{$vo.name}]" value="{$vo.require.1}" style="width:100%;" />
                        </td>
                        <td style="width:100px"><input name="default[{$vo.name}]" value="{$vo.require.2}" style="width:100%;" /></td>
                        <td title="{$vo.collation}">{$vo.name} {eq name="vo.pri" value="true"}[主]{/eq}</td>
                        <td>{$vo.comment}</td>
                        <td>{$vo.type}({$vo.long})</td>
                        <td>{$vo.null}({$vo.default})</td>
                    </tr>
                    {/volist}
                </table>
            </div>
        </div>
    </form>
</div>
<style type="text/css">
    .layui-table td, .layui-table th {
        padding: 4px 8px;
    }
    input {padding:4px 2px;}
</style>
<script language="JavaScript">
    layui.config({
        base: layAdmin //静态资源所在路径
    }).extend({
        index: 'lib/index' //主入口模块
    }).use('index',function () {

    });
    function postCreate(btn) {
        layui.jquery.ajax({
            url:'?action=save',
            data:layui.jquery('#frmCreate').serialize(),
            type:'post',
            dataType:'json',
            success:function(json) {
                layer.msg(json.msg, {icon: json.code === 0?1:2,time: 3000},function () {
                    if (json.code == 0){
                        window.location.reload();
                    }
                });
            }
        });
    }
</script>
{/eq}

{eq name="action" value="model_list"}
<div class="layui-fluid">
    <div class="layui-card">
        <div class="layui-card-body">
            <table class="layui-table">
                <thead>
                <tr>
                    <th>模型名称（共{$list.total}个模型,{$list.table_total}个表）</th>
                    <th>表注释（共{$list.total}个模型,{$list.table_total}个表）</th>
                    <th>表行数</th>
                    <th>建表时间</th>
                    <th>表编码</th>
                    <th>引擎</th>
                    <th>管理</th>
                </tr>
                </thead>
                <tbody>
                {volist name="list.data" id="vo"}
                <tr>
                    <td>{$vo.name}</td>
                    <td>{$vo.comment}</td>
                    <td>{$vo.rows}</td>
                    <td>{$vo.create_time}</td>
                    <td>{$vo.collation}</td>
                    <td>{$vo.engine}</td>
                    <td><button class="layui-btn layui-btn-normal layui-btn-xs{neq name='vo.status' value='表模正常'} layui-btn-danger{/neq}" onclick="postCreate(this,'{$vo.name}')">{$vo.status}</button></td>
                </tr>
                {/volist}
                </tbody>
            </table>
        </div>
    </div>
</div>
<script language="JavaScript">
    layui.config({
        base: layAdmin //静态资源所在路径
    }).extend({
        index: 'lib/index' //主入口模块
    }).use('index');
    function postCreate(btn,table) {
        let status = btn.innerHTML;
        if (status === '表模正常' || status === '建表成功'){
            return layer.msg(table+'，数据表和模型都正常，无需建表。', {icon: 1,time: 3000});
        }else if (status !== '未建立表'){
            return layer.msg(table+'，模型类无法识别，请删除后重新生成。', {icon: 2,time: 3000});
        }
        layui.jquery.ajax({
            url:'?action=save',
            data:{table:table},
            dataType:'json',
            success:function(json) {
                if (json.code == 0){
                    btn.innerHTML = '建表成功';
                }
                layer.msg(json.msg, {icon: json.code === 0?1:2,time: 3000});
            }
        });
    }
</script>
{/eq}

</body>
</html>