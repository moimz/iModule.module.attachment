<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 첨부파일을 불러온다.
 * 
 * @file /modules/attachment/process/load.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 4. 1.
 */
if (defined('__IM__') == false) exit;

$idx = Decoder(Request('key')) != false ? json_decode(Decoder(Request('key'))) : array();
$values->files = array();
for ($i=0, $loop=sizeof($idx);$i<$loop;$i++) {
	$fileInfo = $this->getFileInfo($idx[$i]);
	if ($fileInfo != null) $values->files[] = $fileInfo;
}
$results->success = true;
$results->files = $values->files;
?>