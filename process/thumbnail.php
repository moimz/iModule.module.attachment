<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 이미지파일의 썸네일을 가져온다.
 * 
 * @file /modules/attachment/process/thumbnail.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2017. 11. 22.
 */
if (defined('__IM__') == false) exit;

$idx = Request('idx');
$name = Request('name');

$file = $this->db()->select($this->table->attachment)->where('idx',$idx)->getOne();

if ($file == null) {
	header("HTTP/1.1 404 Not Found");
	exit;
} else {
	if (file_exists($this->IM->getAttachmentPath().'/'.$file->path.'.thumb') == true) {
		if ($file->type == 'image') header('Content-Type: '.$file->mime);
		else header('Content-Type: image/jpeg');
		header('Content-Length: '.filesize($this->IM->getAttachmentPath().'/'.$file->path.'.thumb'));
		readfile($this->IM->getAttachmentPath().'/'.$file->path.'.thumb');
		exit;
	} elseif ($file->type == 'image' && file_exists($this->IM->getAttachmentPath().'/'.$file->path) == true) {
		if ($this->createThumbnail($this->IM->getAttachmentPath().'/'.$file->path,$this->IM->getAttachmentPath().'/'.$file->path.'.thumb',($file->width <= $file->height ? 300 : 0),($file->width > $file->height ? 300 : 0),false) == false) {
			header("HTTP/1.1 404 Not Found");
			exit;
		}
		header('Content-Type: '.$file->mime);
		header('Content-Length: '.filesize($this->IM->getAttachmentPath().'/'.$file->path.'.thumb'));
		readfile($this->IM->getAttachmentPath().'/'.$file->path.'.thumb');
		exit;
	} else {
		header("HTTP/1.1 404 Not Found");
		exit;
	}
}
?>