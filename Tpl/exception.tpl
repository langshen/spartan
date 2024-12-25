<!DOCTYPE>
<html>
<head>
    <title><?php echo $error['title'];?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style type="text/css">*{ margin:0;padding:0; }body{padding: 24px 48px;}</style>
</head>

<body>
    <h3><?php echo $error['title'];?></h3>
    <div><?php echo strip_tags($error['message'],'<br>');?></div>
<?php if(isset($error['exception'])) {?>
    <h3>Exception</h3>
    <div><?php echo nl2br($error['exception']);?></div>
<?php }?>
<?php if(isset($error['trace'])) {?>
    <h3>TRACE</h3>
    <div><?php echo nl2br($error['trace']);?></div>
<?php }?>
</body>
</html>
