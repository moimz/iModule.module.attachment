<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 첨부파일 데이터를 가져온다.
 * 
 * @file /modules/attachment/process/@getFile.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2021. 1. 11.
 */
if (defined('__IM__') == false) exit;

$idx = Param('idx');
$data = $this->db()->select($this->table->attachment)->where('idx',$idx)->getOne();
if ($data == null) {
	$results->success = false;
	$results->message = $this->getErrorText('NOT_FOUND');
	return;
}

$results->success = true;
$results->data = $data;
?>