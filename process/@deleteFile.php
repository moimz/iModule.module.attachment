<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 파일을 삭제한다.
 * 
 * @file /modules/attachment/process/@deleteFile.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2021. 8. 12.
 */
if (defined('__IM__') == false) exit;

$idxes = Param('idxes') ? explode(',',Param('idxes')) : array();
foreach ($idxes as $idx) {
	$this->fileDelete($idx);
}

$results->success = true;
?>