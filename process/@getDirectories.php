<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 첨부파일 월별 디렉토리 목록을 가져온다.
 * 
 * @file /modules/attachment/process/@getDirectories.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2021. 8. 12.
 */
if (defined('__IM__') == false) exit;

$lists = array();
$directories = GetDirectoryItems($this->getAttachmentPath(),'directory',false);
foreach ($directories as $directory) {
	if (basename($directory) != 'temp' && preg_match('/^[0-9]{6}$/',basename($directory)) == false) continue;
	$lists[] = array('path'=>basename($directory));
}

$results->success = true;
$results->lists = $lists;
$results->total = count($lists);
?>