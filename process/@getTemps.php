<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 임시파일 목록을 가져온다.
 * 
 * @file /modules/attachment/process/@getTemps.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2021. 8. 12.
 */
if (defined('__IM__') == false) exit;

$lists = array();
$path = Request('path') ? Request('path') : 'temp';
$files = GetDirectoryItems($this->getAttachmentPath().'/'.$path,'file',false);
foreach ($files as $file) {
	if ($this->db()->select($this->table->attachment)->where('path',str_replace($this->getAttachmentPath().'/','',preg_replace('/\.(view|thumb)$/','',$file)))->has() == true) continue;
	
	$item = new stdClass();
	$item->name = basename($file);
	$item->realpath = $file;
	$item->path = str_replace($this->getAttachmentPath().'/','',$file);
	$item->size = filesize($file);
	$item->reg_date = filemtime($file);
	$item->mime = $this->getFileMime($file);
	$item->extension = $this->getFileExtension($item->name,$file);
	$item->type = $this->getFileType($item->mime);
	$item->icon = $this->getFileIcon($item->type,$item->extension);
	
	$lists[] = $item;
}

$results->success = true;
$results->lists = $lists;
$results->total = count($lists);
?>