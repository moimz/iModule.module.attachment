<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 첨부파일을 무조건 다운로드한다.
 * 
 * @file /modules/attachment/process/@download.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2021. 8. 12.
 */
if (defined('__IM__') == false) exit;

$path = Param('path');
$file = explode('/',$path);
if (count($file) != 2) {
	$this->printError('FILE_NOT_FOUND',$path);
	exit;
}

if ($file[0] != 'temp' && preg_match('/^[0-9]{6}$/',$file[0]) == false) {
	$this->printError('FILE_NOT_FOUND',$path);
	exit;
}

$path = $this->getAttachmentPath().'/'.$path;
$name = basename($path);
$mime = $this->getFileMime($path);

if (is_file($path) == true) {
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: private",false);
	if (preg_match('/Safari/',$_SERVER['HTTP_USER_AGENT']) == true) {
		header('Content-Disposition: attachment; filename="'.$name.'"');
	} else {
		header('Content-Disposition: attachment; filename="'.rawurlencode($name).'"; filename*=UTF-8\'\''.rawurlencode($name));
	}
	header("Content-Transfer-Encoding: binary");
	header('Content-Type: '.$mime);
	header('Content-Length: '.filesize($path));

	session_write_close();

	readfile($path);
	exit;
} else {
	$this->printError('FILE_NOT_FOUND',$path);
	exit;
}
?>