<?php
/**
 * @Name: index.php
 * @Role:   
 * @Author: 拓少
 * @Date:   2015-11-07 21:21:51
 * @Last Modified by:   拓少
 * @Last Modified time: 2015-11-10 15:13:38
 */
header('Content-Type:text/html;Charset=UTF8');

define('PATH', __DIR__);

// echo PATH;exit;
//配置显示列
define('NO_SIZE', true);   //true：不显示大小
function_exists('finfo_open') ? define('NO_MIME', false) : define('NO_MIME', false);  //true：不显示类型
define('NO_CTIME', true);  //true：不显示创建时间
define('NO_MTIME', true);  //true：不显示修改时间


function __autoload($class){
	include './class/' . ucwords($class) . ".class.php";
}

$module = isset($_GET['m']) ? $_GET['m'] : 'Index';
$action = isset($_GET['a']) ? $_GET['a'] : 'show';

$module = class_exists($module) ? $module : 'Index';
$_module = new $module;
$action = method_exists($module, $action) ? $action : 'show';
$row = $_module->$action();
if($action != 'show'){
	if($row !== true){//操作不成功
		echo $row;
	}else{//操作成功
		echo 1;
	}
}
