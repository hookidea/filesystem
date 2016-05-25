<?php
/**
 * @Name: FileUpload.class.php
 * @Role:   单/多 文件上传类
 * @Author: 拓少
 * @Date:   2015-11-07 06:38:55
 * @Last Modified by:   拓少
 * @Last Modified time: 2015-11-10 09:16:31
 */

/**
 * 调用实例：
 * 
 * 指定保存文件名
 * $upload = new FileUpload(array('filepath'=>'./upload', 'savefilename'=>'aaaa.jpg', 'israndname'=>false, 'maxsize'=>1000000, 'allowtype'=>array('jpg', 'png')));
 * 使用随机文件名
 * $upload = new FileUpload(array('filepath'=>'./upload', 'israndname'=>false, 'maxsize'=>1000000, 'allowtype'=>array('jpg', 'png')));
 *
 * $upload->uploadFile('pic');    
 */

/**
 * 单/多 文件上传类
 */
class FileUpload{
	private $filepath;  //保存路径
	private $allowtype = array('jpg', 'jpeg', 'png', 'gif');  //支持的类型，为 * 代表所有类型
	private $israndname = true;    //是否启用随机文件名，如果为false，则必须指定 $savefilename
	private $maxsize = 2000000;    //支持上传的文件大小
	private $savefilename = '';    //如果关闭随机文件名，则需指定此项，指明保存文件名


	private $newFileName;  //保存保存在服务器的路径
	private $fileType;     //保存文件类型
	private $fileSize;     //保存文件大小
	private $originName;   //保存源文件名
	private $tmpFileName;  //保存上传的临时文件名
	private $errno;        //错误代号
	private $error;        //错误信息

	/**
	 * 此次上传需要指定的配置项
	 * @param array $options = array(             //配置的各项不区分大小写
	*              'filepath' => (string),        //上传的文件保存到哪里去(必须指定)
	*              'allowtype' => (array),        //（如：array('jpg', 'jpeg', 'png', 'gif')）
	*              'israndname' => (bool),        //是否启用随机文件名，如果为false，则必须指定 'savefilename'
	*              'maxsize' => (int),            //支持上传的文件大小（字节为单位）
	*              'savefilename' => (string),    //指明保存的路径（带文件名），如果关闭随机文件名，则需指定此项，
	 *        )
	 *        				
	 */
	public function __construct($options){
		$vars = get_class_vars(get_class($this));
		foreach($options as $k=>$v){
			$k = strtolower($k);
			if(in_array($k, $vars)){
				$this->setOptions($k, $v);
			}
		}
	}
	/**
	 * 获取文件保存路径
	 * @return string/array 多文件上传返回数组，单文件上传返回字符串
	 */
	public function getNewFileName(){
		if(is_array($this->newFileName)) return $this->newFileName;
		return $this->newFileName;
	}
	/**
	 * 获取错误信息
	 * @return string/array 多文件上传返回数组，单文件上传返回字符串
	 */
	public function getErrorMsg(){
		if(is_array($this->error)) return $this->error;
		return $this->getError($this->errno);
	}
	/**
	 * 上传文件，支持单个/多个文件上传
	 * @param  string $name 表单的 name 值
	 *                      	如：pic['a'],pic['b'],pic['c']->则为"pic"    //多文件上传使用
	 *                      		pic[],pic[],pic[]         ->则为"pic"    //多文件上传使用
	 *                      		pic                       ->则为"pic"
	 * @return bool         true：成功，false：失败
	 */
	public function uploadFile($name){
		if(!isset($_FILES[$name])) {
			$this->setOptions('errno', -9);
			return false;
		}
		if(!$this->checkFilePath()) return false;
		$originname = $_FILES[$name]['name'];
		$tmpname = $_FILES[$name]['tmp_name'];
		$filetype = $_FILES[$name]['type'];
		$filesize = $_FILES[$name]['size'];
		$errno = $_FILES[$name]['error'];
		if(is_array($tmpname)){
			$error = array();
			$names = array();
			$flag = true;
			foreach($tmpname as $k=>$v){//验证全部
				if($errno[$k]){
					$this->setOptions('errno', $errno[$k]);
					$flag = false;
				}
				$this->setFiles($originname[$k], $tmpname[$k], $filetype[$k], $filesize[$k]);
				if(!$this->checkFileIsUpload() || !$this->checkFileSize() || !$this->checkFileType()){
					$error[] = '文件 <font color="red">' . basename($originname[$k]) . '</font> ' . $this->getError();
					$flag = false;
				}
			}
			if(!$flag){
				$this->error = $error;
				return false;
			}
			foreach($tmpname as $k=>$v){//开始上传全部
				$this->setFiles($originname[$k], $tmpname[$k], $filetype[$k], $filesize[$k]);
				if(!$this->proFileName()) return false;
				if(!$this->copyFile()) return false;
				$names[$k] = $this->newFileName;
			}
			$this->newFileName = $names;
			return true;
			
		}else{
			if($errno){
				$this->setOptions('errno', $errno);
				return false;
			}
			$this->setFiles($originname, $tmpname, $filetype, $filesize);
			if(!$this->checkFileIsUpload()) return false;
			if(!$this->checkFileSize()) return false;
			if(!$this->checkFileType()) return false;
			if(!$this->proFileName()) return false;
			if(!$this->copyFile()) return false;
			return true;
		}
	}
	/**
	 * 根据错误代号获取错误信息
	 * @param  string $num 错误代号，不提供，默认取 $this->errno
	 * @return string      错误代号对应的错误信息
	 */
	private function getError($num=''){
		if(empty($num)) $num = $this->errno;
		$str = '';
		switch($num){
			case -1:
				$str .= '文件保存路径创建失败';
				break;
			case -2:
				$str .= '文件超过限制大小';
				break;
			case -3:
				$str .= '不允许的文件类型';
				break;
			case -4:
				$str .= '文件保存路径不可写';
				break;
			case -5:
				$str .= '移动文件时失败';
				break;
			case -6:
				$str .= '必须配置保存路径';
				break;
			case -7:
				$str .= '非法文件';
				break;
			case -8:
				$str .= '你关闭了随机文件名，但又没有配置你要保存的文件名';
				break;
			case -9:
				$str .= '上传的文件的总大小超过了 php.ini 中 post_max_size 选项限制的值';
				break;
			case 1:
				$str .= '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值';
				break;
			case 2:
				$str .= '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值';
				break;
			case 3:
				$str .= '文件只有部分被上传';
				break;
			case 4:
				$str .= '没有文件被上传';
				break;
			case 6:
				$str .= '找不到临时文件夹';
				break;
			case 7:
				$str .= '文件写入失败';
				break;
			
			default:
				$str .= '未知错误';
				break;

		}
		$this->error = $str;
		return $str;
	}

	/**
	 * 检查文件的大小
	 * @return bool        true：成功，false：失败
	 */
	private function checkFileSize(){
		if($this->fileSize > $this->maxsize){
			$this->setOptions('errno', -2);
			return false;
		}
		return true;
	}
	/**
	 * 检查文件的保存路径
	 * @return bool        true：成功，false：失败
	 */
	private function checkFilePath(){
		$path = $this->filepath;
		if(empty($path)){
			$this->setOptions('errno', -6);
			return false;
		}
		if(!file_exists($path)){
			if(!mkdir($path, 0755, true)){
				$this->setOptions('errno', -1);
				return false;
			}
		}else{
			if(!is_writable($path)){
				$this->setOptions('errno', -4);
				return false;
			}
		}
		return true;
	}
	/**
	 * 检查文件的类型
	 * @return bool        true：成功，false：失败
	 */
	private function checkFileType(){
		if($this->allowtype == '*'){
			return true;
		}
		if(!in_array($this->fileType, $this->allowtype)){
			$this->setOptions('errno', -3);
			return false;
		}
		return true;
	}
	/**
	 * 检查文件的是否是通过 HTTP POST 上传的
	 * @return bool        true：是，false：不是
	 */
	private function checkFileIsUpload(){
		if(!is_uploaded_file($this->tmpFileName)){
			$this->setOptions('errno', -7);
			return false;
		}
		return true;
	}
	//设置类的属性
	private function setOptions($key, $value){
		$this->$key = $value;
	}
	//给（需要上传的每一个文件）需要使用到的属性赋值（多文件上传时，需要使用多次）
	private function setFiles($originname='', $tmpname='', $filetype='', $filesize=''){
			$this->setOptions('originName', $originname);
			$this->setOptions('tmpFileName', $tmpname);
			$arr = explode('/', $filetype);
			$this->setOptions('fileType', $arr[1]);
			$this->setOptions('fileSize', $filesize);

	}	
	//移动临时文件到指定路径
	private function copyFile(){
		if(!move_uploaded_file($this->tmpFileName, $this->newFileName)){
			$this->setOptions('errno', -5);
			return false;
		}
		return true;
	}
	//获取且设置新的保存路径（带文件名）
	private function proFileName(){
		$filepath = rtrim($this->filepath, '/') . '/';
		if(!$this->israndname){
			if(empty($this->savefilename)){
				$this->setOptions('errno', -8);
				return false;
			}
			$this->newFileName = $filepath . $this->savefilename;
			return true;
		}
		$filename = date('YmdHis') . rand(11111,99999) . '.' . $this->fileType;
		while(file_exists($filename)){
			$filename = date('YmdHis') . rand(11111,99999) . '.' . $this->fileType;
		}
		$this->newFileName = $filepath . $filename;
		return true;
	}
	
}