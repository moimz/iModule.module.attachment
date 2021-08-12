<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 임시파일을 삭제한다.
 * 
 * @file /modules/attachment/process/@deleteTemp.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2020. 12. 30.
 */
if (defined('__IM__') == false) exit;

$files = Param('files') ? json_decode(Param('files')) : array();
foreach ($files as $path) {
	$file = explode('/',$path);
	if (count($file) != 2) {
		$results->success = false;
		$results->message = $this->getErrorText('FILE_NOT_FOUND');
		return;
	}
	
	if ($file[0] != 'temp' && preg_match('/^[0-9]{6}$/',$file[0]) == false) {
		$results->success = false;
		$results->message = $this->getErrorText('FILE_NOT_FOUND');
		return;
	}
	
	@unlink($this->getAttachmentPath().'/'.$path);
}

$results->success = true;
?>