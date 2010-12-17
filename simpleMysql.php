<?php


class simpleMysql{
	var $conn;
	
	function __construct(){
		
	}
	
	function getAll($sql, $assoc=true){
		$result = $this->query($sql, $this->conn);
		if (!$result){
			throw new Exception(mysql_error($this->conn));
		}
		$ret = array();
		if ($assoc){
			while ($row = mysql_fetch_assoc($result)){
				$ret[] = $row;
			}
		}else{
			while ($row = mysql_fetch_row($result)){
				$ret[] = $row;
			}
		}
		
		return $ret;
	}
	
	function __call($method, $param){
		$method = 'mysql_' . $method;
		if (!function_exists($method)){
			throw new Exception(sprintf('Unknown method "%s"', $method));
		}
		
		if ($method == 'mysql_connect'){
			$this->conn = call_user_func_array('mysql_connect', $param);
			if (!$this->conn) {
				throw new Exception(mysql_error());
			}
		}else{
			return call_user_func_array($method, $param);
		}
	}
}

if (count(explode($_SERVER['SCRIPT_FILENAME'], __FILE__)) > 1){
	$db = new simpleMysql();
	//if the main be test
	$db->connect('10.10.221.12', 'root', 'yhnji-db-yoqoo');
	$db->select_db('wenjin_search');
}

?>