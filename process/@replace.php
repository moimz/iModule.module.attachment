<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 변경할 파일을 업로드한다.
 * 
 * @file /modules/attachment/process/@replace.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2021. 1. 11.
 */
if (defined('__IM__') == false) exit;

$file = Request('file');
$hash = Request('hash');

if ($file) {
	$path = $this->getTempFile(true);
	file_put_contents($path,'');
	chmod($path,0707);
	
	$results->success = true;
	$results->hash = basename($path);
	return;
} elseif ($hash) {
	if (isset($_SERVER['HTTP_CONTENT_RANGE']) == true && preg_match('/bytes ([0-9]+)\-([0-9]+)\/([0-9]+)/',$_SERVER['HTTP_CONTENT_RANGE'],$fileRange) == true) {
		$chunkBytes = file_get_contents("php://input");;
		$chunkStart = intval($fileRange[1]);
		$chunkEnd = intval($fileRange[2]);
		$fileSize = intval($fileRange[3]);
		
		if ($chunkEnd - $chunkStart + 1 != strlen($chunkBytes)) {
			$results->success = false;
			$results->message = $this->getErrorText('INVALID_CHUNK_SIZE');
			return;
		}
		
		if ($chunkStart == 0) $fp = fopen($this->getTempPath(true).'/'.$hash,'w');
		else $fp = fopen($this->getTempPath(true).'/'.$hash,'a');
		
		fseek($fp,$chunkStart);
		fwrite($fp,$chunkBytes);
		fclose($fp);
		
		$results->success = true;
		$results->uploaded = filesize($this->getTempPath(true).'/'.$hash);
	} else {
		$results->success = false;
		$results->message = $this->getErrorText('INVALID_HTTP_CONTENT_RANGE');
	}
}
?>