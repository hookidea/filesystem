<?php
/**
 * @Name: Index.class.php
 * @Role:   
 * @Author: 拓少
 * @Date:   2015-11-07 22:09:41
 * @Last Modified by:   拓少
 * @Last Modified time: 2015-11-11 16:16:40
 */

class Index{
	private $file = null;
	private $jsmethod = 'send';//用来处理ajax的函数名
	private $image = array('jpg', 'jpeg', 'png', 'gif');
	private $text = array('php', 'txt', 'html', 'js', 'sql', 'htm');
	//允许上传的类型，为 * 代表允许所有
	private $allowtype = '*';
	// private $allowtype = array('7z', 'gif', 'png', 'php', 'txt', 'js', 'jpg', 'jpeg');
	private $maxsize = 5000000;
	private $disk = array('C', 'D', 'E', 'F');//所有分区盘符

	public function __construct(){
		$this->file = new File;
		ob_start();

	}

	/**
	 * 表头
	 * @param  boolean $no_ctime true：不显示，false：显示
	 * @param  boolean $no_mtime true：不显示，false：显示
	 * @param  boolean $no_size  true：不显示，false：显示
	 * @param  boolean $no_mime  true：不显示，false：显示
	 * @return boolean           true：不显示，false：显示
	 */
	private function getTableTh($no_ctime, $no_mtime, $no_size, $no_mime){
		echo '<tr>';
		echo '<th>文件名称</th>';
		if(!$no_mime) echo '<th>文件类型</th>';
		if(!$no_size) echo '<th>文件大小</th>';
		if(!$no_mtime) echo '<th>修改时间</th>';
		if(!$no_ctime) echo '<th>创建时间</th>';
		echo '<th>文件操作</th>';
		echo '</tr>';
	}

	/**
	 * 切换磁盘分区（仅在WIN有效）
	 */
	public function partition(){
		// return "<script>javascript:(send('', 'parentname'))()</script>";
	}

	/**
	 * 查看目录（不遍历）
	 * @return [type] [description]
	 */
	public function show(){
		$no_size = NO_SIZE;
		function_exists('finfo_open') ? $no_mime = NO_MIME : $no_mime = true;
		$no_mtime = NO_MTIME;
		$no_ctime = NO_CTIME;

		$url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
		$root = $_SERVER['DOCUMENT_ROOT'];
		//当前所在的目录路径
		$dirname = isset($_GET['d']) ? $_GET['d'] : dirname(__DIR__);//UTF-8编码
		$dirname = str_replace('\\', '/', $dirname);
		$cur_dir = $dirname;  //用于当前位置
		$parentname = dirname($dirname);//上级目录按钮，UTF-8编码
		$parentname = str_replace('\\', '/', $parentname);

		//在到达某磁盘的根目录时的JS代码，用于跳转盘符
		$parent_js = $dirname != $parentname ? "index.php?d={$parentname}&a=show&m=index" : "javascript:{$this->jsmethod}(null,\"parentname\")";

		if(!function_exists('mb_convert_encoding')){   //一般WIN下的都会有mbstring扩展，linux下才要自己安装
			setcookie('char', 'UTF-8', time()+3600*240);
		}

		if(isset($_COOKIE['char']) && $_COOKIE['char'] == 'GBK'){
			$dirname = mb_convert_encoding($dirname, 'GBK', 'UTF-8');
		}
		
		if(!is_dir($dirname)){//目录不存在，则返回上一页
			echo "<script>alert('该目录不存在！');history.back();</script>";

		}
		//如果是GBK系统，必须转换为GBK编码提供用于给PHP文件系统函数
		$files = $this->file->readDir($dirname, $no_ctime, $no_mtime, $no_size, $no_mime);

		include('./view/index.html');//开始显示
		echo '<table>';

		$this->getTableTh($no_ctime, $no_mtime, $no_size, $no_mime);

		for($i=0, $len=count($files); $i<$len; $i++){
			$bgcolor = $i % 2 == 0 ? '#DDDDD' : '#FFFFFF';
			$fontcolor = $files[$i]['type'] == 'dir' ? 'blue' : '#000000';

			if(!$no_mtime) $mtime = date('Y-m-d H:i:s', $files[$i]['mtime']);
			if(!$no_ctime) $ctime = date('Y-m-d H:i:s', $files[$i]['ctime']);
			if(!$no_size) $size = $files[$i]['size'];
			if(!$no_mime) $mime = $files[$i]['mime'];
			$path = $files[$i]['path'];
			$name = $files[$i]['name'];
			//由于脚本声明的字符集是UTF-8，会自动把本脚本下的所有字符都转换为UTF-8，所以如果路径、文件名是其它编码的，要转换为UTF-8才能正确显示，不然乱码
			//因为一个脚本不能够声明使用两个字符集，也不能说这块使用GBK，这块使用UTF-8，必须一致
			if(!isset($_COOKIE['char']) && mb_detect_encoding($name, array('UTF-8', 'GBK')) != 'UTF-8'){
			//第一次运行系统，如果是GBK字符集的（仅在没有COOKIE['char']时运行）
			//防止没有COOKIE['char']时乱码（如第一次使用，而且又在GBK系统，会乱码）
				$path = mb_convert_encoding($path, 'UTF8', 'GBK');
				$name = mb_convert_encoding($name, 'UTF8', 'GBK');
				setcookie('char', 'GBK', time()+3600*240);//在cookie中保存当前系统使用的字符集
			}
			if(isset($_COOKIE['char']) && $_COOKIE['char'] == 'GBK'){//判断使用的字符集
				$path = mb_convert_encoding($path, 'UTF8', 'GBK');
				$name = mb_convert_encoding($name, 'UTF8', 'GBK');
			}

			echo "<tr style=\"background:{$bgcolor}\">";

			echo "<td style=\"color:{$fontcolor};\">{$name}</td>";
			if(!$no_mime) echo "<td>{$mime}</td>";
			if(!$no_size) echo "<td>{$size}</td>";
			if(!$no_mtime) echo "<td>{$mtime}</td>";
			if(!$no_ctime) echo "<td>{$ctime}</td>";
			echo "<td>
			<a href=\"javascript:{$this->jsmethod}('{$url}?d={$path}&a=copy&m=index', 'copy')\">复制</a>
			 / 
			 <a href=\"javascript:{$this->jsmethod}('{$url}?d={$path}&a=move&m=index', 'move')\">移动</a>
			  / 
			  <a href=\"javascript:{$this->jsmethod}('{$url}?d={$path}&a=rename&m=index', 'rename')\">重命名</a>
			   / 
			   <a href=\"javascript:{$this->jsmethod}('{$url}?d={$path}&a=delete&m=index', 'delete')\">删除</a>
			    / 
			    <a href=\"javascript:{$this->jsmethod}('{$url}?d={$path}&a=read&m=index', 'read')\">查看</a>
			     / 
			     <a href=\"javascript:{$this->jsmethod}('{$url}?d={$path}&a=down&m=index', 'down')\">下载</a>
			     ";
			if($files[$i]['type'] == 'dir'){
				echo "/ 
				     <a href=\"{$url}?d={$path}&a=show&m=index\">进入</a>
				     ";
			}
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';
	}

	/**
	 * 删除一个文件/目录
	 * @return [type] [description]
	 */
	public function delete(){
		$path = $_GET['d'];
		if($_COOKIE['char'] == 'GBK'){//判断使用的字符集
			$path = mb_convert_encoding($path, 'GBK', 'UTF8');
		}
		if(is_dir($path)){
			if(!$this->file->rmDir($path)) return $this->file->getError();
		}else{
			if(!$this->file->rmFile($path)) return $this->file->getError();
		}
		return true;
	}

	/**
	 * 复制一个目录/文件
	 * @return [type] [description]
	 */
	public function copy(){
		$old = $_GET['d'];
		$name = substr($old, strripos($old, '/')+1);
		$new = rtrim($_GET['n'], '/')  . '/' . $name;
		if($_COOKIE['char'] == 'GBK'){//是GBK的，必须转换编码为GBK，不然文件名和中文路径会乱码
			$old = mb_convert_encoding($old, 'GBK', 'UTF8');
			$new = mb_convert_encoding($new, 'GBK', 'UTF8');
		}
		if(is_dir($old)){
			if(!$this->file->copyDir($old, $new)) return $this->file->getError();
		}else{
			if(!$this->file->copyFile($old, $new)) return $this->file->getError();
		}
		return true;
	}

	/**
	 * 移动一个目录/文件
	 * @return [type] [description]
	 */
	public function move(){
		$old = $_GET['d'];
		$name = substr($old, strripos($old, '/')+1);//中文路径使用basename会有BUG，看笔记
		$new = rtrim($_GET['n'], '/')  . '/' . $name;
		if($_COOKIE['char'] == 'GBK'){
			$old = mb_convert_encoding($old, 'GBK', 'UTF8');
			$new = mb_convert_encoding($new, 'GBK', 'UTF8');
		}
		if(is_dir($old)){
			if($this->file->moveDir($old, $new)) return $this->file->getError();
		}else{
			if(!$this->file->moveFile($old, $new)) return $this->file->getError();
		}
		return true;
	}

	/**
	 * 重命名一个文件/目录
	 * @return [type] [description]
	 */
	public function rename(){
		$old = $_GET['d'];
		$name = $_GET['n'];
		$path = dirname($old);
		$new = $path . '/' . $name;
		if($_COOKIE['char'] == 'GBK'){
			$old = mb_convert_encoding($old, 'GBK', 'UTF8');
			$new = mb_convert_encoding($new, 'GBK', 'UTF8');
		}
		if(!$this->file->rename($old, $new)) return $this->file->getError();
		return true;
	}

	public function readimage(){
		$path = $_GET['d'];
		if($_COOKIE['char'] == 'GBK') $path = mb_convert_encoding($path, 'GBK', 'UTF-8');
		$ext = strtolower(substr($path, strripos($path, '.')+1));
		$mime = 'image/' . $ext;
		header("Content-Type:{$mime}");
		readfile($path);

		// $tmp = str_replace('\\', '/', PATH);
		// $path = str_replace($tmp, '.', $path);
		// $path_tmp = $path;
		// if($_COOKIE['char'] == 'GBK') $path_tmp = mb_convert_encoding($path, 'GBK', 'UTF-8');
		// $info = getimagesize($path_tmp);
		// $width = $info[0];
		// $height = $info[1];
		// return "<img style='width:{$width}px; height:{$height}px; margin: 0 auto;' src='{$path}'><br/><input type='button' style='margin-top: 20px;' onclick='javascript:history.back();' value='返回'>";
	}

	public function read(){
		$path = $_GET['d'];
		$ext = strtolower(substr($path, strripos($path, '.')+1));
		if(in_array($ext, $this->image))
			return $this->readimage();
		elseif(in_array($ext, $this->text))
			return $this->modify();
		else
			return "<script>alert('未知的类型！');history.back();</script>";
		

	}

	/**
	 * 修改文件的内容
	 */
	public function modify(){
		if(!isset($_POST['content'])){//显示修改表单
			if(isset($_GET['d'])){
				$url = $_SERVER['REQUEST_URI'];//表单的提交地址
				$filepath = $_GET['d'];
				if($_COOKIE['char'] == 'GBK')//如果是GBK的系统
					$filepath = mb_convert_encoding($filepath, 'GBK', 'UTF-8');
				if(file_exists($filepath) && !is_dir($filepath)){//$_GET['d']传过来的文件，且该文件存在
					$file = new File;
					$content = $file->readFile($filepath);//读取文件的内容
					include "./view/modify.html";
				}elseif(is_dir($filepath)){//$_GET['d']传过来的是目录，则重定向到显示该目录文件
					header("location:index.php?d={$filepath}&a=show&m=index");
				}
			}else{//没有传$_GET['d']，则重定向到文件系统首页
				header('location:index.php');
			}
		}else{//接收表单提交的值，且修改到源文件
			$content = $_POST['content'];
			$filepath = $_GET['d'];
			$dirpath = dirname($filepath);
			if($_COOKIE['char'] == 'GBK')
				$filepath = mb_convert_encoding($filepath, 'GBK', 'UTF-8');
			$fh = fopen($filepath, 'wb');
			$flag = true;

			for($i=0, $len=strlen($content); $i<$len; $i+=$write){
				$write = fwrite($fh, substr($content, $i, $i+4048));
				if($write === false) $flag = false;
			}
			if($flag){
				$url = 'index.php?d=' . $dirpath . '&a=show&m=index';
				return "<script>alert('修改成功！');location.href='{$url}';</script>";
			}else{
				return "<script>alert('修改失败！')</script>";
			}
		}
	}

	/**
	 * 新建一个目录
	 * @return [type] [description]
	 */
	public function mkdir(){
		$path = $_GET['d'];
		$name = $_GET['n'];
		$dirname = $path . '/' . $name;
		if($_COOKIE['char'] == 'GBK') //如果是GBK的系统
			$dirname = mb_convert_encoding($dirname, 'GBK', 'UTF8');
		if(!$this->file->mkDir($dirname)) return $this->file->getError();
		return true;
	}

	/**
	 * 新建一个文件
	 * @return [type] [description]
	 */
	public function mkfile(){
		if(isset($_POST['filename'])){
			$url = $_SERVER['REQUEST_URI'];
			$filename = $_POST['filename'];
			$filedir = $_GET['d'];
			$filepath = $filedir . '/' . $filename;
			if($_COOKIE['char'] == 'GBK'){
				$filepath = mb_convert_encoding($filepath, 'GBK', 'UTF-8');
			}
			$content = $_POST['content'];
			if(empty($content)) $content = ' ';
			$file = new File;
			if($file->mkFile($filepath, $content)){
				return '<script>if(confirm("创建成功！确定：查看文件列表，取消：继续创建")){location.href="index.php?d=' . $filedir . '&a=show&m=index"}else{location.href="' . $url . '";};</script>';
			}else{
				$error = $file->getError();
				return '<script>alert("创建失败！原因：' . $error . '");history.back();</script>';
			}
		}else{
			include "./view/mkfile.html";
		}
	}

	/**
	 * 修改设置
	 */
	public function setConfig(){
		if(!empty($_POST)){
			$url = $_SERVER['REQUEST_URI'];
			$url = str_replace('setconfig', 'show', $url);
			$content = file_get_contents('./index.php');
			$post = array_change_key_case($_POST, CASE_UPPER);
			$arr = array('NO_CTIME'=>0, 'NO_MTIME'=>0, 'NO_MIME'=>0, 'NO_SIZE'=>0);
			$tmp = array_diff_key($arr, $post);
			foreach($tmp as $k=>$v){//没有选中，则为false
				$content = str_replace('define(\'' . $k . '\', true)', "define('{$k}', false)", $content);
			}
			foreach($post as $k=>$v){//选中了，则为true
				$content = str_replace('define(\'' . $k . '\', false)', "define('{$k}', true)", $content);
			}
			file_put_contents('./index.php', $content);
			echo '<script>if(confirm("修改成功！确定：返回查看文件列表，取消：继续修改")){location.href="' . $url . '"}else{location.href="' . $url . '";};</script>';
		}else{
			include "./view/config.html";
		}
	}

	/**
	 * 下载文件
	 * @return [type] [description]
	 */
	public function down(){
		$path = $_GET['d'];  //提供给readfile的下载的文件/目录路径
		$name = basename($path);  //准备下载的文件的文件/目录的目录名
		$path_zip = $name . '.zip'; //压缩文档的保存路径
		$down_name = $name;      //显示的下载文件的文件名（显示用的）
		$flag = false;   //为true，说明是下载目录
		if($_COOKIE['char'] == 'GBK'){
			$path = mb_convert_encoding($path, 'GBK', 'UTF-8'); //下载文件、目录的所在路径
			if(is_dir($path))//下载目录
				//压缩文档的保存路径
				$path_zip = mb_convert_encoding($path_zip, 'GBK', 'UTF-8');
		}
		if(is_dir($path)){//下载目录
			$zip = new Zip;
			$zip->zip($path, $path_zip);//$path_zip是压缩文件保存路径
			$down_name .= '.zip';   //下载文件的文件名
			$path = $path_zip;   //如果是目录的话，则下载路径是压缩后的压缩文档文件的路径
			$flag = true;
		}
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=' . str_replace(' ', '', $down_name)); 
		header('Content-Length: ' . filesize($path));
		echo $this->file->readFile($path);        //$file必须是文件的路径，把文件的数据发送给浏览器，即下载
		if($flag)
			$this->file->rmFile($path);
	}

	public function upload(){
		if(isset($_FILES['file'])){
			include "FileUpload.class.php";
			$savepath = $_GET['d'];
			$savename = $_FILES['file']['name'];
			if($_COOKIE['char'] == 'GBK'){
				$savepath = mb_convert_encoding($savepath, 'GBK', 'UTF-8');
				$savename = mb_convert_encoding($savename, 'GBK', 'UTF-8');
			}
			$upload = new FileUpload(array(
						'filepath' => $savepath,        //上传的文件保存到哪里去(必须指定)
						'allowtype' => $this->allowtype,        //（如：array('jpg', 'jpeg', 'png', 'gif')）
						'israndname' => false,        //是否启用随机文件名，如果为false，则必须指定 'savefilename'
						'maxsize' => $this->maxsize,            //支持上传的文件大小（字节为单位）
						'savefilename' => $savename,    //
				));
			if($upload->uploadFile('file')){
				header('location:index.php?d=' . $savepath . '&a=show&m=index');
			}else{
				echo '<script>alert("上传失败！");history.back();</script>';
			}
		}else{
			$maxsize = (int)ini_get('post_max_size') > (int)ini_get('upload_max_filesize') ? ini_get('upload_max_filesize') : ini_get('post_max_size');
			$max_div = floor($this->maxsize / pow(2, 20));
			$size = $maxsize > $max_div  ? $max_div : $maxsize;
			$size .= ' MB';
			include "./view/upload.html";
		}
	}

	
}