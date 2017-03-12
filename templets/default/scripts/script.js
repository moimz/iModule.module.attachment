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