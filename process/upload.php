<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 파일 업로드를 처리한다.
 * 
 * @file /modules/attachment/process/upload.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.161211
 */
if (defined('__IM__') == false) exit;

$code = Decoder(Request('code'));
if ($code == false) {
	$results->success = false;
	$results->message = $this->getErrorText('INVALID_UPLOAD_CODE');
} else {
	$file = $this->db()->select($this->table->attachment)->where('idx',$code)->getOne();
	if ($file == null) {
		$results->success = false;
		$results->message = $this->getErrorText('NOT_FOUND');
		return;
	}
	
	if (isset($_SERVER['HTTP_CONTENT_RANGE']) == true && preg_match('/bytes ([0-9]+)\-([0-9]+)\/([0-9]+)/',$_SERVER['HTTP_CONTENT_RANGE'],$fileRange) == true) {
		$chunkBytes = file_get_contents("php://input");;
		$chunkStart = intval($fileRange[1]);
		$chunkEnd = intval($fileRange[2]);
		$fileSize = intval($fileRange[3]);
		
		if ($fileSize != $file->size) {
			$results->success = false;
			$results->message = $this->getErrorText('INVALID_FILE_SIZE');
			return;
		}
		
		if ($chunkEnd - $chunkStart + 1 != strlen($chunkBytes)) {
			$results->success = false;
			$results->message = $this->getErrorText('INVALID_CHUNK_SIZE');
			return;
		}
		
		if ($chunkStart == 0) $fp = fopen($this->IM->getAttachmentPath().'/'.$file->path,'w');
		else $fp = fopen($this->IM->getAttachmentPath().'/'.$file->path,'a');
		
		fseek($fp,$chunkStart);
		fwrite($fp,$chunkBytes);
		fclose($fp);
		
		if ($chunkEnd + 1 === $fileSize) {
			if (intval($file->size) != filesize($this->IM->getAttachmentPath().'/'.$file->path)) {
				unlink($this->IM->getAttachmentPath().'/'.$file->path);
				$this->db()->delete($this->table->attachment)->where('idx',$file->idx)->execute();
				$results->success = false;
				$results->message = $this->getErrorText('INVALID_UPLOADED_SIZE');
			} else {
				$file = $this->fileUpload($file->idx);
				$file->status = 'COMPLETE';
				
				
				$results->success = true;
				$results->file = $file;
				
				//		$values->fileInfo = $this->fileUpload($file->idx);
				//		$results->file = $values->fileInfo;
			}
		} else {
			$results->success = true;
			$results->uploaded = filesize($this->IM->getAttachmentPath().'/'.$file->path);
		}
	} else {
		$results->success = false;
		$results->message = $this->getErrorText('INVALID_HTTP_CONTENT_RANGE');
	}
}
?>