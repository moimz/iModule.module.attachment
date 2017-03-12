<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 업로드예정인 파일을 DRAFT 상태로 DB에 기록한다.
 * 
 * @file /modules/attachment/process/draft.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.161211
 */
if (defined('__IM__') == false) exit;

$module = Request('module') ? Request('module') : '';
$target = Request('target') ? Request('target') : '';
$files = json_decode(Request('files'));

for ($i=0, $loop=count($files);$i<$loop;$i++) {
	if (strlen($files[$i]->name) == 0 || $files[$i]->size == 0) {
		$files[$i]->code = null;
		continue;
	}
	
	$mNormalizer = new UnicodeNormalizer();
	$name = $mNormalizer->normalize($files[$i]->name);
	$path = $this->getTempPath().'/'.md5(serialize($files[$i])).'.'.base_convert(microtime(true)*10000,10,32).'.temp';
	$mime = isset($files[$i]->type) == true && $files[$i]->type ? $files[$i]->type : 'application/octet-stream';
	$type = $this->getFileType($mime);
	$size = $files[$i]->size;
	
	$insert = array();
	$insert['module'] = $module;
	$insert['target'] = $target;
	$insert['path'] = $path;
	$insert['name'] = $name;
	$insert['mime'] = $mime;
	$insert['type'] = $type;
	$insert['size'] = $size;
	$insert['reg_date'] = time();
	
	$idx = $this->db()->insert($this->table->attachment,$insert)->execute();
	$files[$i]->idx = $idx;
	$files[$i]->code = Encoder($idx);
	$files[$i]->status = 'WAIT';
	$files[$i]->mime = $mime;
	$files[$i]->type = $type;
	$files[$i]->extension = $this->getFileExtension($name);
	$files[$i]->uploaded = 0;
}

$results->success = true;
$results->files = $files;
?>