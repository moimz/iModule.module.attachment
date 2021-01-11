<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 파일을 변경한다.
 * 
 * @file /modules/attachment/process/@replaceFile.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2021. 1. 11.
 */
if (defined('__IM__') == false) exit;

$idx = Param('idx');
$hash = Param('hash');
$name = Request('name');

$file = $this->db()->select($this->table->attachment)->where('idx',$idx)->getOne();
if ($file == null) {
	$results->success = true;
	$results->message = $this->getErrorText('NOT_FOUND');
	return;
}

if (is_file($this->getTempPath(true).'/'.$hash) == false) {
	$results->success = true;
	$results->message = $this->getErrorText('NOT_FOUND');
	return;
}

$this->fileReplace($idx,$name,$this->getTempPath(true).'/'.$hash,true);

$results->success = true;
?>