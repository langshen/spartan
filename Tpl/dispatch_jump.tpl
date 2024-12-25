{__NOLAYOUT__}<!DOCTYPE>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
    <title>跳转提示</title>
    <style type="text/css">
        *{ padding: 0; margin: 0; }
        body{ background: #fff; font-family: "Microsoft Yahei","Helvetica Neue",Helvetica,Arial,sans-serif; color: #333; font-size: 16px; }
        .system-message{ padding: 24px 48px; }
        .system-message h1{ font-size: 100px; font-weight: normal; line-height: 120px; margin-bottom: 12px; }
        .system-message .jump{ padding-top: 10px; }
        .system-message .jump a{ color: #333; }
        .system-message .success,.system-message .error{ line-height: 1.8em; font-size: 36px; }
        .system-message .detail{ font-size: 12px; line-height: 20px; margin-top: 12px; display: none; }
    </style>
</head>
<body>
<div class="system-message">
    <?php
    $codeName = config('GET.API.CODE','code');
    $msgName = config('GET.API.MSG','msg');
    switch ($$codeName) { ?>
    <?php case 1:?>
    <h1>:)</h1>
    <p class="success"><?php echo(strip_tags($$msgName));?></p>
    <?php break;?>
    <?php case 0:?>
    <h1>:(</h1>
    <p class="error"><?php echo(strip_tags($$msgName));?></p>
    <?php break;?>
    <?php } ?>
    <p class="detail"></p>
    <p class="jump">
        页面自动 <a id="href" href="<?php echo($url);?>">跳转</a> 等待时间： <b id="wait"><?php echo($wait);?></b>
    </p>
</div>
<?php if($url != ''){ ?>
<script type="text/javascript">
    (function(){
        var wait = document.getElementById('wait'),
            href = document.getElementById('href').href;
        var interval = setInterval(function(){
            var time = --wait.innerHTML;
            if(time <= 0) {
                location.href = href;
                clearInterval(interval);
            };
        }, 1000);
    })();
</script>
    <?php } ?>
</body>
</html>
