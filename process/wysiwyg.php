<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 위지윅에디터에서 파일 업로드를 처리한다.
 * 
 * @file /modules/attachment/process/wysiwyg.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.161211
 */
if (defined('__IM__') == false) exit;

if (isset($_FILES['file']['name']) == true && $_FILES['file']['name']) {
	$module = Request('module');
	$target = Request('target');
	
	$name = $_FILES['file']['name'];
	$mime = $this->getFileMime($_FILES['file']['tmp_name']);
	$type = $this->getFileType($mime);
	if ($type == 'image') {
		if ($name == 'blob') {
			$extension = explode('/',$mime);
			$name = 'clipboard.'.end($extension);
		}
		$check = getimagesize($_FILES['file']['tmp_name']);
		$width = $check[0];
		$height = $check[1];
	} else {
		$width = $height = 0;
	}
	$size = filesize($_FILES['file']['tmp_name']);
	$path = $this->getCurrentPath().'/'.md5_file($_FILES['file']['tmp_name']).'.'.base_convert(microtime(true)*10000,10,32).'.'.$this->getFileExtension($name,$_FILES['file']['tmp_name']);
	move_uploaded_file($_FILES['file']['tmp_name'],$this->IM->getAttachmentPath().'/'.$path);
	
	$idx = $this->db()->insert($this->table->attachment,array('module'=>$module,'target'=>$target,'path'=>$path,'name'=>$name,'type'=>$type,'mime'=>$mime,'size'=>$size,'width'=>$width,'height'=>$height,'wysiwyg'=>'TRUE','reg_date'=>time()))->execute();
	
	$file = $this->getFileInfo($idx);
	$results->idx = $idx;
	$results->code = Encoder($idx);
	$results->link = $type == 'image' ? $file->path : $file->download;
}
?>