<?php
/**
 * @Name: Zip.class.php
 * @Role:   压缩文件类
 * @Author: 拓少
 * @Date:   2015-11-10 11:24:07
 * @Last Modified by:   拓少
 * @Last Modified time: 2015-11-10 16:07:28
 */

// 使用示例：
// $zip = new Zip;
// $zip->zip(array('./FilesSystem/', './tmp'), './2.zip');
// $zip->zip('./tmp', '4.zip');
// $zip->zip(array('./Calendar.class.php', './2.zip'), '2.zip');
// $zip->zip('./File.class.php', '2.zip');


class Zip{
	private $_zip = null;  //用来保存ZipArchive实例对象
	private $errno;  //错误代码
	private $error;  //错误信息

	public function __construct(){
		$this->_zip = new ZipArchive;
	}

	/**
	 * 获取出错原因
	 * @return string 错误的详细信息
	 */
	public function getError(){
		return $this->error;
	}

	/**
	 * 压缩一个目录、单个文件（只是压缩目录下面的所有文件，会遍历）
	 * @param  string $dir  要压缩的单个目录路径、多个目录组成的索引数组/单个文件路径、多个文件组成的索引数组
	 * @param  string $save 压缩文件的保存路径
	 * @return [type]       [description]
	 */
	public function zip($dir, $save){
		if(!is_dir(dirname($save))){
			$this->setError(7);
			return false;
		}
		if(!is_writable(dirname($save))){
			$this->setError(2);
			return false;
		}

		$this->_zip->open($save, ZIPARCHIVE::OVERWRITE);

		if(is_array($dir)){
			if(is_dir($dir[0])){//提供的是一个多个目录组成的索引数组
				$files = array();
				for($i=0, $len_i=count($dir); $i<$len_i; $i++){
					$files = array_merge($files, $this->readAllDir($dir[$i]));
				}
				for($j=0, $len_j=count($files); $j<$len_j; $j++){
					if(!$this->_zip->addFile($files[$j], basename($files[$j]))){
						$this->setError(5);
						return false;
					}
				}
			}else{//提供的是一个多个文件组成的索引数组
				for($i=0, $len=count($dir); $i<$len; $i++){
					$name = basename($dir[$i]);
					if(!$this->_zip->addFile($dir[$i], $name)){
						$this->setError(5);
						return false;
					}
				}
			}
			$this->_zip->close();
			return true;
		}

		if(!is_dir($dir) && file_exists($dir)){//直接传入单个文件路径（字符串）
			$this->_zip->open($save, ZIPARCHIVE::OVERWRITE);
			if(!$this->_zip->addFile($dir, basename($dir))) return false;
		}

		if(is_dir($dir)){//直接传入单个目录路径（字符串）
			$files = $this->readAllDir($dir);
			if(!$files){
				$this->setError(6);
				return false;
			}
			for($i=0, $len=count($files); $i<$len; $i++){
				$name = basename($files[$i]);
				if(!$this->_zip->addFile($files[$i], $name)){
					$this->setError(5);
					return false;
				}
			}
		}
		$this->_zip->close();
		return true;
	}

	/**
	 * 设置错误
	 * @param  string $num 错误代号
	 * @return string      错误的详细信息
	 */
	private function setError($num=''){ //设置并获取错误信息
		if(empty($num)) $num = $this->errno;
		$str = '';
		switch($num){
			case 1:
				$str = '没有读取目录的权限';
				break;
			case 2:
				$str = '没有写入目录的权限';
				break;
			case 3:
				$str = '没有该文件/目录';
				break;
			case 4:
				$str = '打开目录失败';
				break;
			case 5:
				$str = '压缩过程中出错，文件可能不完整';
				break;
			case 6:
				$str = '遍历该目录下的所有文件时出错！';
				break;
			case 7:
				$str = '保存目录不存在！';
				break;
			
		}
		$this->error = $str;
		return $str;
	}

	/**
	 * 遍历等到一个目录下面的所有文件
	 * @param  string  $path 目录的路径
	 * @param  boolean $flag 函数内部需要，无需理会
	 * @return array         返回所有文件组成的数组
	 */
	private function readAllDir($path, $flag=true){ //(遍历)查看当前目录下的所有文件
		if($flag) $path = rtrim($path, '/');
		$files = array();
		if(!$dh = $this->openDir($path)) return false;
		while($row = readdir($dh)){
			if($row != '.' && $row != '..'){
				$tmp = $path . '/' . $row;
				if(is_dir($tmp)){
					$files = array_merge($files, $this->readAllDir($tmp, false));
				}else{
					$files[] = $tmp;
				}

			}
		}
		return $files;
	}

	/**
	 * 打开一个目录
	 * @param  string  $path 目录的路径
	 * @return mixed         false：失败，资源型：目录资源句柄
	 */
	private function openDir($path){
		if(!file_exists($path)){
			$this->setError(3);
			return false;
		}
		if(!is_readable($path)){
			$this->setError(1);
			return false;
		}
		$dh = opendir($path);
		if(!$dh){
			$this->setError(4);
			return false;
		}
		return $dh;
	}

	
}


