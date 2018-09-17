<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 첨부파일이 웹페이지상에서 embed 될 수 있는 경우, 브라우져상에서 파일을 출력하고, 그렇지 않은 경우 다운로드한다.
 * 
 * @file /modules/attachment/process/view.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 8. 23.
 */
if (defined('__IM__') == false) exit;

$idx = Request('idx');
$name = Request('name');
$file = $this->db()->select($this->table->attachment)->where('idx',$idx)->getOne();
if ($file == null) {
	header("HTTP/1.1 404 Not Found");
	exit;
} else {
	if ($file->type == 'image' && is_file($this->IM->getAttachmentPath().'/'.$file->path) == true) {
		if ($file->width > 1000) {
			if (is_file($this->IM->getAttachmentPath().'/'.$file->path.'.view') == true) {
				if ($file->type == 'image') header('Content-Type: '.$file->mime);
				else header('Content-Type: image/jpeg');
				header('Content-Length: '.filesize($this->IM->getAttachmentPath().'/'.$file->path.'.view'));
				
				session_write_close();
				readfile($this->IM->getAttachmentPath().'/'.$file->path.'.view');
				exit;
			} else {
				if ($this->createThumbnail($this->IM->getAttachmentPath().'/'.$file->path,$this->IM->getAttachmentPath().'/'.$file->path.'.view',1000,0,false) == false) {
					header("HTTP/1.1 404 Not Found");
					exit;
				}
				header('Content-Type: '.$file->mime);
				header('Content-Length: '.filesize($this->IM->getAttachmentPath().'/'.$file->path.'.view'));
				
				session_write_close();
				readfile($this->IM->getAttachmentPath().'/'.$file->path.'.view');
				exit;
			}
		} else {
			header('Content-Type: '.$file->size);
			session_write_close();
			readfile($this->IM->getAttachmentPath().'/'.$file->path);
		}
		exit;
	} elseif (in_array($file->type,array('icon','svg')) == true) {
		if (is_file($this->IM->getAttachmentPath().'/'.$file->path) == true) {
			header('Content-Type: '.$file->mime);
			header('Content-Length: '.filesize($this->IM->getAttachmentPath().'/'.$file->path));
			
			session_write_close();
			readfile($this->IM->getAttachmentPath().'/'.$file->path);
			exit;
		} else {
			header("HTTP/1.1 404 Not Found");
			exit;
		}
	} else {
		$this->doProcess('download');
	}
}
?>