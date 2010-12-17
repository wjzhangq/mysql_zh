<?php
require('simpleMysql.php');
require('normalizeText.php');
$nt = new normalizeText();
$str = $nt->normalize('内容页面
多.媒体10.10.220.1
帮助和afa计划页面
全部
高级
下面显示从第1条开始的1条结果：
');
echo $str;
$tmp = explode(' ', $str);
foreach($tmp as $k=>$v){
	if (strncmp($v, 'u8', 2) == 0){
		$v = substr($v, 2);
		$seq =  array_map('hexChr', str_split($v,2));
		echo implode('', $seq);
	}else{
		$v_len = strlen($v);
		if ($v_len >4 && $v[$v_len -4] == 'u' && $v[$v_len -3] == '8' && $v[$v_len -2] == '0' && $v[$v_len -1] == '0'){
			echo substr($v, 0, $v_len -4);
		}else{
			echo $v;
		}
	}
}


function hexChr($str){
	return chr(hexdec($str));
}

// $searchon = $nt->parseQuery('afa');
// $sql = "SELECT * from `full` WHERE MATCH(`index`) AGAINST('$searchon' IN BOOLEAN MODE)";
// 
// echo $sql;
?>