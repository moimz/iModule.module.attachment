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
 * @modified 2018. 4. 1.
 */
if (defined('__IM__') == false) exit;

$lists = array();
$tempPath = @opendir($this->getTempPath(true));
while ($tempName = @readdir($tempPath)) {
	if ($tempName != '.' && $tempName != '..' && is_file($this->getTempPath(true).'/'.$tempName) == true) {
		if ($this->db()->select($this->table->attachment)->where('path',$this->getTempDir().'/'.$tempName)->has() == true) continue;
		
		$file = new stdClass();
		$file->name = $tempName;
		$file->path = $this->getTempPath(false).'/'.$tempName;
		$file->size = filesize($this->getTempPath(true).'/'.$tempName);
		$file->reg_date = filemtime($this->getTempPath(true).'/'.$tempName);
		$file->mime = $this->getFileMime($this->getTempPath(true).'/'.$tempName);
		$file->extension = $this->getFileExtension($file->name,$this->getTempPath(true).'/'.$tempName);
		$file->type = $this->getFileType($file->mime);
		$file->icon = $this->getFileIcon($file->type,$file->extension);
		
		$lists[] = $file;
	}
}
@closedir($tempPath);

$results->success = true;
$results->lists = $lists;
$results->total = count($lists);
?>