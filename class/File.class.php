<?php
/**
 * @Name: File.class.php
 * @Role:   文件操作类
 * @Author: 拓少
 * @Date:   2015-11-07 10:22:06
 * @Last Modified by:   拓少
 * @Last Modified time: 2015-11-10 19:42:34
 */

class File{
	private $errno;  //错误代码
	private $error;  //错误信息
	
	private function setError($num=''){ //设置并获取错误信息
		if(empty($num)) $num = $this->errno;
		$str = '';
		switch($num){
			case 1:
				$str = '没有读取该文件/目录的权限';
				break;
			case 2:
				$str = '没有写入该文件/目录的权限';
				break;
			case 3:
				$str = '没有该文件/目录';
				break;
			case 4:
				$str = '打开该文件/目录失败';
				break;
			case 5:
				$str = '删除该文件/目录失败';
				break;
			case 6:
				$str = '该文件/目录已经存在';
				break;
			case 7:
				$str = '创建文件/目录失败';
				break;
			case 8:
				$str = '没有权限复制/移动到该目录';
				break;
			case 9:
				$str = '复制/移动文件失败';
				break;
			case 10:
				$str = '重命名文件/目录失败';
				break;
			case 11:
				$str = '写入内容失败';
				break;	
			default:
				$str = '未知错误';
				break;
		}
		$this->error = $str;
		return $str;
	}
	public function getError(){
		return $this->error;
	}
	private function openFile($path, $flag=false){  //$flag=ture，则以读写方式打开
		if(!file_exists($path)){
			$this->setError(3);
			return false;
		}
		if($flag){  
			if(is_writable($path)){  //如果没有写的权限，则用读的方式打开
				$fh = fopen($path, 'ab+');//读写
				if(!$fh){//打开失败
					$this->setError(5);
					return false;
				}
				return $fh;
			}else{//指定了读写方式打开，却没有写的权限
				$this->setError(2);
				return false;
			}
		}
		if(is_readable($path)){ //是否有读的权限
			$fh = fopen($path, 'rb');  //写
			if(!$fh){//打开失败
				$this->setError(5);
				return false;
			}
			return $fh;
		}else{//没有读的权限
			$this->setError(1);
			return false;
		}
		return true;
	}
	private function openDir($path){
		if(!file_exists($path)){
			$this->setError(3);
			return false;
		}
		$dh = opendir($path);
		if(!$dh){
			$this->setError(4);
			return false;
		}
		return $dh;
	}
	/**
	 * 复制文件，可重命名
	 * @param  string  $old  要复制的文件
	 * @param  string  $new  复制到哪里去（可同时重命名）
	 * @param  boolean $del  true:如果目标路径已经存在，则删除它，在移动，false:存在则取消操作
	 * @return boolean       true:成功，false:失败
	 */
	public function copyFile($old, $new, $del=false){
		if(!$this->checkExists($old)) return false;	
		if(!$this->checkRead($old)) return false;
		if(!$this->checkWrite(dirname($new))) return false;
		if($this->checkExists($new)){//目的路径已经存在而又不指定替换
			if($del){//指定替换
				if(!$this->rmFile($new)) return false;
			}else{//没有指定替换
				$this->setError(6);
				return false;
			}
		}
		if(!copy($old, $new)){
			$this->setError(9);
			return false;
		}
		return true;
	}
	/**
	 * 复制目录到指定目录下
	 * @param  string  $old  要复制的目录的路径
	 * @param  string  $new  复制到哪个目录下
	 * @param  boolean $del  true:如果目标路径已经存在，则删除它，在移动，false:存在则取消操作
	 * @param  boolean $flag 无需理会，函数内部实现需要
	 * @return boolean       true:成功，false:失败
	 */
	public function copyDir($old, $new, $del=false, $flag=true){
		if($flag){//仅在第一次进入运行
			$old = rtrim($old, '/');
			$new = rtrim($new, '/');
			if(!$this->checkExists($old)) return false;	
			if(!$this->checkRead($old)) return false;
			if(!$this->checkWrite(dirname($new))) return false;
			if($this->checkExists($new)){//目的路径已经存在而又不指定替换
				if($del){//指定替换
					if(!$this->rmFile($new)) return false;
				}else{//没有指定替换
					$this->setError(6);
					return false;
				}
			}
		}
		if(!$this->mkDir($new)) return false;
		if(!$dh = $this->openDir($old)) return false;
		while($row = readdir($dh)){
			if($row != '.' && $row != '..'){
				$old_tmp = $old . '/' .$row;
				$new_tmp = $new . '/' . $row;
				if(is_dir($old_tmp)){//是目录，则递归
					$this->copyDir($old_tmp, $new_tmp, $del, false);
				}else{//是文件，则直接复制文件
					if(!$this->copyFile($old_tmp, $new_tmp)) return false;
				}
			}
		}
		closedir($dh);
		return true;
	}
	/**
	 * 移动文件
	 * @param  string $old 移动哪个文件的路径
	 * @param  string $new 移动到哪里
	 * @param  string $del true:如果目标路径已经存在，则删除它，在移动，false:存在则取消操作
	 * @return boolean     true:成功，false:失败
	 */
	public function moveFile($old, $new, $del=false){
		if($this->copyFile($old, $new, $del)){//复制成功，则删除旧目录
			if(!$this->rmFile($old)){//删除旧目录不成功，则删除新目录
				$this->rmFile($new);
				return false;
			}else{
				return true;
			}
		}else{//复制不成功，则不删除源文件
			return false;
		}
	}
	/**
	 * 移动目录
	 * @param  string $old 移动哪个目录的路径
	 * @param  string $new 移动到哪里
	 * @param  string $del true:如果目标路径已经存在，则删除它，在移动，false:存在则取消操作
	 * @return boolean     true:成功，false:失败
	 */
	public function moveDir($old, $new, $del=false){
		if($this->copyDir($old, $new, $del)){//复制成功，则删除旧目录
			if(!$this->rmDir($old)){//删除旧目录不成功，则删除新目录
				$this->rmDir($new);
				return false;
			}else{
				return true;
			}
		}else{//复制不成功，则不删除源文件
			return false;
		}
	}

	/**
	 * 重命名一个文件/目录
	 * @param  string $old  旧路径
	 * @param  string $new  新路径
	 * @return boolean      true：成功，false：失败
	 */
	public function rename($old, $new){
		if(!$this->checkExists($old)) return false;
		if(!$this->checkWrite(dirname($old))) return false;
		if($this->checkExists($new)){
			$this->setError(6);
			return false;
		}
		if(!rename($old, $new)){
			$this->setError(10);
			return false;
		}
		return true;
	}

	/**
	 * 读取一个文件的内容
	 * @param  string $path 文件路径
	 * @return mixed             文件内容
	 */
	public function readFile($path){
		if(!$this->checkExists($path)) return false;
		if(!($fh = $this->openFile($path))) return false;
		$content = '';
		while(!feof($fh)){
			$content .= fread($fh, 4096);
		}
		return $content;
	}

	/**
	 * （不遍历）读取该目录下都是所有文件，包括目录本身
	 * @param  string  $path      要读取的目录的路径
	 * @param  bool    $no_ctime  true:不获取创建时间，false:获取创建时间（默认）
	 * @param  bool    $no_mtime  true:不获取修改时间，false:获取修改时间（默认）
	 * @param  bool    $no_size   true:不获取文件大小，false:获取文件大小（默认）
	 * @param  integer $level     所在目录层次，所在路径越深，值越大（函数内部实现需要，一般不用设置）
	 * @return array              遍历后结果组成的数组，项的说明：     
	 */
	public function readDir($path, $no_ctime=false, $no_mtime=false, $no_size=false, $no_mime=false){ //查看当前目录下的所有文件
		$path = rtrim($path, '/');
		if(!function_exists('finfo_open')) $no_mime = true;
		$files = array();
		if(!$dh = $this->openDir($path)) return false;
		while($row = readdir($dh)){
			if($row != '.' && $row != '..'){
				$tmp = $path . '/' . $row;
				if(!is_dir($tmp) && !$no_size) $size = $this->toSize(filesize($tmp));
				if(is_dir($tmp) && !$no_size) $size = $this->toSize($this->getDirSize($tmp));
				if(!$no_mtime) $mtime = filemtime($tmp);
				if(!$no_ctime) $ctime = filectime($tmp);
				if(!$no_mime){
					$finfo = finfo_open(FILEINFO_MIME_TYPE); 
					$mime = finfo_file($finfo, $tmp);
				}
				if($no_size){
					if($no_ctime){
						if($no_mtime){
							if($no_mime)
								$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp));
							else
								$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mime'=>$mime);
						}else{
							if($no_mime)
								$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime);
							else
								$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime, 'mime'=>$mime);
						}
					}else{
						if($no_mtime){
							if($no_mime)
								$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'ctime'=>$ctime);
							else
								$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'ctime'=>$ctime, 'mime'=>$mime);
						}else{
							if($no_mime)
								$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime, 'ctime'=>$ctime);
							else
								$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime, 'ctime'=>$ctime, 'mime'=>$mime);
						}
					}
				}else{
					if($no_ctime){
						if($no_mtime){
							if($no_mime)
								$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'size'=>$size);
							else
								$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mime'=>$mime, 'size'=>$size);
						}else{
							if($no_mime)
								$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime, 'size'=>$size);
							else
								$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime, 'mime'=>$mime, 'size'=>$size);
						}
					}else{
						if($no_mtime){
							if($no_mime)
								$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'ctime'=>$ctime, 'size'=>$size);
							else
								$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'ctime'=>$ctime, 'mime'=>$mime, 'size'=>$size);
						}else{
							if($no_mime)
								$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime, 'ctime'=>$ctime, 'size'=>$size);
							else
								$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime, 'ctime'=>$ctime, 'size'=>$size, 'mime'=>$mime);
						}
					}
				}
			}
		}
		return $files;
	}



	/**
	 * （遍历）读取该目录下都是所有文件，包括目录本身
	 * @param  string  $path      要读取的目录的路径
	 * @param  bool    $no_ctime  true:不获取创建时间，false:获取创建时间（默认）
	 * @param  bool    $no_mtime  true:不获取修改时间，false:获取修改时间（默认）
	 * @param  bool    $no_size   true:不获取文件大小，false:获取文件大小（默认）
	 * @param  bool    $no_dir    true:返回目录中的文件，不返回目录，false:全部都返回（默认）
	 * @param  integer $level     所在目录层次，所在路径越深，值越大（函数内部实现需要，一般不用设置）
	 * @param  boolean $flag      无需理会，函数内部实现需要
	 * @return array              遍历后结果组成的数组，项的说明：     
	 */
	public function readAllDir($path, $no_ctime=false, $no_mtime=false, $no_size=false, $no_mime=false, $no_dir=false, $level=0, $flag=true){ //(遍历)查看当前目录下的所有文件
		if($flag) $path = rtrim($path, '/');
		if(!$no_mime && !function_exists('finfo_open')) $no_mime = true;//显示mime但PHP版本不支持
		$finfo = finfo_open(FILEINFO_MIME_TYPE); 
		$files = array();
		if(!$dh = $this->openDir($path)) return false;
		while($row = readdir($dh)){
			if($row != '.' && $row != '..'){
				$tmp = $path . '/' . $row;
				if(is_dir($tmp)){
					$files = array_merge($files, $this->readAllDir($tmp, $no_ctime, $no_mtime, $no_size, $no_mime, $no_dir, $level+1, false));
				}
				if(!is_dir($tmp) && !$no_size) $size = $this->toSize(filesize($tmp));
				if(is_dir($tmp) && !$no_dir && !$no_size) $size = $this->toSize($this->getDirSize($tmp));
				if(!$no_mtime) $mtime = filemtime($tmp);
				if(!$no_ctime) $ctime = filectime($tmp);
				if(!$no_mime){
					$mime = finfo_file($finfo, $tmp);
				}
				if($no_dir && !is_dir($tmp)){
					if($no_size){
						if($no_ctime){
							if($no_mtime){
								if($no_mime)
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'level'=>$level);
								else
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mime'=>$mime, 'level'=>$level);
								
							}else{
								if($no_mime)
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime, 'level'=>$level);
								else
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime, 'mime'=>$mime, 'level'=>$level);
							}
						}else{
							if($no_mtime){
								if($no_mime)
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'ctime'=>$ctime, 'level'=>$level);
								else
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'ctime'=>$ctime, 'mime'=>$mime, 'level'=>$level);
							}else{
								if($no_mime)
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime, 'ctime'=>$ctime, 'level'=>$level);
								else
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime, 'ctime'=>$ctime, 'mime'=>$mime, 'level'=>$level);
							}
						}
					}else{
						if($no_ctime){
							if($no_mtime){
								if($no_mime)
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'size'=>$size, 'level'=>$level);
								else
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mime'=>$mime, 'size'=>$size, 'level'=>$level);
							}else{
								if($no_mime)
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime, 'size'=>$size, 'level'=>$level);
								else
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime, 'mime'=>$mime, 'size'=>$size, 'level'=>$level);
							}
						}else{
							if($no_mtime){
								if($no_mime)
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'ctime'=>$ctime, 'size'=>$size, 'level'=>$level);
								else
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'ctime'=>$ctime, 'mime'=>$mime, 'size'=>$size, 'level'=>$level);
							}else{
								if($no_mime)
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime, 'ctime'=>$ctime, 'size'=>$size, 'level'=>$level);
								else
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime, 'ctime'=>$ctime, 'size'=>$size, 'mime'=>$mime, 'level'=>$level);
							}
						}
					}
				}elseif(!$no_dir){
					if($no_size){
						if($no_ctime){
							if($no_mtime){
								if($no_mime)
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'level'=>$level);
								else
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mime'=>$mime, 'level'=>$level);
							}else{
								if($no_mime)
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime, 'level'=>$level);
								else
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime, 'mime'=>$mime, 'level'=>$level);
							}
						}else{
							if($no_mtime){
								if($no_mime)
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'ctime'=>$ctime, 'level'=>$level);
								else
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'ctime'=>$ctime, 'mime'=>$mime, 'level'=>$level);
							}else{
								if($no_mime)
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime, 'ctime'=>$ctime, 'level'=>$level);
								else
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime, 'ctime'=>$ctime, 'mime'=>$mime, 'level'=>$level);
							}
						}
					}else{
						if($no_ctime){
							if($no_mtime){
								if($no_mime)
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'size'=>$size, 'level'=>$level);
								else
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mime'=>$mime, 'size'=>$size, 'level'=>$level);
							}else{
								if($no_mime)
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime, 'size'=>$size, 'level'=>$level);
								else
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime, 'mime'=>$mime, 'size'=>$size, 'level'=>$level);
							}
						}else{
							if($no_mtime){
								if($no_mime)
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'ctime'=>$ctime, 'size'=>$size, 'level'=>$level);
								else
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'ctime'=>$ctime, 'mime'=>$mime, 'size'=>$size, 'level'=>$level);
							}else{
								if($no_mime)
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime, 'ctime'=>$ctime, 'size'=>$size, 'level'=>$level);
								else
									$files[] = array('name'=>$row, 'path'=>$tmp, 'type'=>filetype($tmp), 'mtime'=>$mtime, 'ctime'=>$ctime, 'size'=>$size, 'mime'=>$mime, 'level'=>$level);
							}
						}
					}
				}
			}
		}
		return $files;
	}

	/**
	 * 检测一个文件/目录是否存在
	 * @param  string $path  路径
	 * @return boolean       true：存在，false：不存在
	 */
	private function checkExists($path){
		if(!file_exists($path)){
			$this->setError(3);
			return false;
		}
		return true;
	}

	/**
	 * 检测一个文件/目录是否可写
	 * @param  string $path 路径
	 * @return boolean      true：可写，false：不可写
	 */
	private function checkWrite($path){
		if(!is_writable($path)){
			$this->setError(2);
			return false;
		}
		return true;
	}
	/**
	 * 检测一个文件/目录是否可读
	 * @param  string $path 路径
	 * @return boolean      true：可读，false：不可读
	 */
	private function checkRead($path){
		if(!is_readable($path)){
			$this->setError(1);
			return false;
		}
		return true;
	}

	/**
	 * 获取一个目录的大小
	 * @param  string $path 目录的路径
	 * @return number       返回字节为单位的目录的大小
	 */
	public function getDirSize($path){
		$num = 0;
		$path = rtrim($path, '/');
		$dh = $this->openDir($path);
		while($row = readdir($dh)){
			if($row != '.' && $row != '..'){
				$tmp = $path . '/' . $row;
				if(is_dir($tmp)){
					$num += $this->getDirSize($tmp);
				}else{
					$num += filesize($tmp);
				}
			}
		}
		closedir($dh);
		return $num;
	}

	/**
	 * 转换单位
	 * @param   number  $size    字节数
	 * @return                   转换为合适的单位
	 */
	private function toSize($size){
		$dw = 'bytes';
		if($size > pow(2, 40)){
			$size = round($size/pow(2, 40), 2);
			$dw = 'TB';
		}elseif($size > pow(2, 30)){
			$size = round($size/pow(2, 30), 2);
			$dw = 'GB';
		}elseif($size > pow(2, 20)){
			$size = round($size/pow(2, 20), 2);
			$dw = 'MB';
		}elseif($size > pow(2, 10)){
			$size = round($size/pow(2, 10), 2);
			$dw = 'KB';
		}
		return $size . ' ' . $dw;
	}

	/**
	 * 删除一个文件
	 * @param  string   $path    要删除的文件路径
	 * @return boolean           true:成功，false:失败
	 */
	public function rmFile($path){
		if(!file_exists($path)){
			$this->setError(3);
			return false;
		}
		//$path 这个字符串的字符集必须是当前操作系统使用的字符集（如在GBK编码的系统中，$path必须转换为GBK编码）
		if(!unlink($path)){
			$this->setError(5);
			return false;
		}
		return true;
	}
	/**
	 * 删除一个目录
	 * @param  string  $path 要删除的目录路径
	 * @param  boolean $flag 无需理会，函数内部实现需要
	 * @return boolean       true:成功，false:失败
	 */
	public function rmDir($path, $flag=true){//删除一个目录
		if($flag){
			$path = rtrim($path, '/');
			if(!file_exists($path)){
				$this->setError(3);
				return false;
			}
		}
		if(!$dh = $this->openDir($path)) return false;
		while($row = readdir($dh)){
			if($row != '.' && $row != '..'){
				$tmp = $path . '/' .$row;
				if(is_dir($tmp)){
					$this->rmDir($tmp, false);
				}else{
					if(!$this->rmFile($tmp)) return false;
				}
			}
		}
		closedir($dh);
		if(!rmdir($path)){
			$this->setError(5);
			return false;
		}
		return true;
	}
	/**
	 * 新建一个空文件
	 * @param  string  $path 要创建的文件的路径
	 * @param  boolean $flag 如果该文件已经存在，是否删除在新建
	 * @return boolean       true:成功，false:失败
	 */
	public function mkFile($path, $data, $flag=false){
		if(file_exists($path)){
			if($flag){  //文件已存在，且指定覆盖
				if(!$this->rmFile($path)) return false;
			}else{//不指定覆盖，则报错
				$this->setError(6);
				return false;
			}
		}
		if(!touch($path)){
			$this->setError(7);
			return false;
		}
		if(!file_put_contents($path, $data)){
			$this->setError(11);
			return false;
		}
		return true;
	}
	/**
	 * 新建一个目录
	 * @param  string  $path            要创建的目录的路径
	 * @param  boolean $flag            如果该目录已经存在，是否删除在新建
	 * @param  number  $mode            用数字表示的目录权限
	 * @param  boolean $recursive       是否递归创建，默认是
	 * @return boolean                  true:成功，false:失败
	 */
	public function mkDir($path, $flag=false, $mode=0755, $recursive=true){//创建一个目录
		if(file_exists($path)){//该目录已经存在
			if($flag){//指明覆盖参数
				if(!$this->rmDir($path)) return false;
			}else{//没有指明覆盖，则报错
				$this->setError(6);
				return false;
			}
		}
		if(!mkdir($path, $mode, $recursive)){
			$this->setError(7);
			return false;
		}
		return true;
	}
}