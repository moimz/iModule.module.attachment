<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 첨부파일이 이미지인 경우 원본 이미지를 출력한다.
 * 
 * @file /modules/attachment/process/original.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2021. 5. 25.
 */
if (defined('__IM__') == false) exit;

$idx = Request('idx');
$name = Request('name');
$file = $this->db()->select($this->table->attachment)->where('idx',$idx)->getOne();
if ($file == null) {
	header("HTTP/1.1 404 Not Found");
	exit;
} else {
	if ($file->type == 'image') {
		if (is_file($this->IM->getAttachmentPath().'/'.$file->path) == true) {
			header('Content-Type: '.$file->mime);
			header('Content-Length: '.filesize($this->IM->getAttachmentPath().'/'.$file->path));
			header('Expires: '.gmdate('D, d M Y H:i:s',time() + 3600).' GMT');
			header('Cache-Control: max-age=3600');
			header('Pragma: public');
			
			session_write_close();
			readfile($this->IM->getAttachmentPath().'/'.$file->path);
			exit;
		} else {
			header("HTTP/1.1 404 Not Found");
			exit;
		}
	} else {
		$this->doProcess('view');
	}
}
?>