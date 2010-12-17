<?php
require('simpleMysql.php');
require('normalizeText.php');
$nt = new normalizeText();
$str = $nt->normalize('内容页面
多.媒体10.10.220.1a

A
帮 助和afa计划页面
全部
高级
下面显示从第1条开始的1条结果：
');
echo $str;
echo $nt->decode($str);
 $searchon = $nt->parseQuery('10.10.220.1');
 $sql = "SELECT * from `full` WHERE MATCH(`index`) AGAINST('$searchon' IN BOOLEAN MODE)";
 
 echo $sql;
?>