/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 첨부파일모듈 기본 템플릿
 * 
 * @file /modules/attachment/templets/default/scripts/script.js
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 9. 17.
 */
$(document).ready(function() {
	var $uploader = $("div[data-module=attachment][data-uploader=TRUE][data-templet=default]");
	
	$uploader.on("add",function(e,files) {
		if (files.length > 0) Attachment.start($(this).attr("id"));
	});
	
	$uploader.on("print",function(e,$file,file) {
		var $name = $("div.name",$file);
		
		$name.html(file.name);
		if ($name.height() > 40) {
			var length = file.name.length;
			while ($name.height() > 40) {
				$name.html(Attachment.substring(file.name,--length));
			}
		}
	});
	
	$uploader.on("progress",function(e,$file,loaded,total) {
		var $progress = $("div.progress > div",$file);
		$progress.width((loaded / total * 100) + "%");
	});
});