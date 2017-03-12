<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 모달창을 가져온다.
 * 
 * @file /modules/attachment/process/getModal.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.161211
 */
if (defined('__IM__') == false) exit;

$modal = Request('modal');
if ($modal == 'delete') {
	$idx = Decoder(Request('code'));
	
	if ($idx == false || $this->getFileInfo($idx) == null) {
		$results->success = false;
		$results->message = $this->getErrorText('NOT_FOUND');
	} else {
		$results->success = true;
		$results->modalHtml = $this->getDeleteModal($idx);
	}
}
?>