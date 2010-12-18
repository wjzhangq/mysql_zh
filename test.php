<?php
require('simpleMysql.php');
require('normalizeText.php');
$nt = new normalizeText();
$str = $nt->encode('写代码需谋定而后动，没有想清楚绝对不动手,10.10.2.1测试ip地址');
echo $str;

echo $nt->decode($str);
 $searchon = $nt->queryEncode('绝对不');
 $sql = "SELECT * from `full` WHERE MATCH(`index`) AGAINST('$searchon' IN BOOLEAN MODE)";
 
 echo $sql;
?>