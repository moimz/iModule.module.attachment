<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 임시 업로드 파일을 삭제한다.
 * 
 * @file /modules/attachment/process/reset.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.161211
 */
if (defined('__IM__') == false) exit;

$codes = Request('codes') ? explode(',',Request('codes')) : array();
$idxes = array();
foreach ($codes as $code) {
	$idx = Decoder($code);
	
	if ($idx !== false) {
		$file = $this->getFileInfo($idx);
		
		if ($file != null && $file->status == 'DRAFT') $this->fileDelete($idx);
	}
}

$results->success = true;
?>