/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 첨부파일모듈 드래그&드롭 템플릿
 * 
 * @file /modules/attachment/templets/dragdrop/scripts/script.js
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2020. 1. 7.
 */
$(document).ready(function() {
	var $uploader = $("div[data-module=attachment][data-uploader=TRUE][data-templet=dragdrop]");
	
	$uploader.on("add",function(e,files) {
		if (files.length > 0) {
			$("div[data-role=filedrop]",$(this)).attr("data-status","disabled");
		}
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
	
	$uploader.on("init",function() {
		if ($("ul[data-role=files] > li",$(this)).length == 0) {
			$("div[data-role=filedrop]",$(this)).attr("data-status","empty");
		} else {
			$("div[data-role=filedrop]",$(this)).attr("data-status","ready");
		}
	});
	
	$uploader.on("load",function() {
		if ($("ul[data-role=files] > li",$(this)).length == 0) {
			$("div[data-role=filedrop]",$(this)).attr("data-status","empty");
		} else {
			$("div[data-role=filedrop]",$(this)).attr("data-status","ready");
		}
	});
	
	$uploader.on("complete",function() {
		if ($("ul[data-role=files] > li",$(this)).length == 0) {
			$("div[data-role=filedrop]",$(this)).attr("data-status","empty");
		} else {
			$("div[data-role=filedrop]",$(this)).attr("data-status","ready");
		}
	});
	
	$uploader.on("delete",function() {
		if ($("ul[data-role=files] > li",$(this)).length == 0) {
			$("div[data-role=filedrop]",$(this)).attr("data-status","empty");
		} else {
			$("div[data-role=filedrop]",$(this)).attr("data-status","ready");
		}
	});
	
	$("div[data-role=filedrop]",$uploader).each(function() {
		$(this).on("dragenter",function(e) {
			e.stopPropagation();
			e.preventDefault();
			
			if ($(this).attr("data-status") == "disabled") return;
			$(this).attr("data-drag-status","dragenter");
		});
		
		$(this).on("dragleave",function(e) {
			e.stopPropagation();
			e.preventDefault();
			
			if ($(this).attr("data-status") == "disabled") return;
			$(this).attr("data-drag-status","dragleave");
		});
		
		$(this).on("dragover",function(e) {
			e.stopPropagation();
			e.preventDefault();
		});
		
		$(this).on("drop",function(e) {
			e.preventDefault();
			if ($(this).attr("data-status") == "disabled") return;
			
			var files = [];
			for (var i=0, loop=e.originalEvent.dataTransfer.files.length;i<loop;i++) {
				var file = e.originalEvent.dataTransfer.files[i];
				files.push(file);
			}
			Attachment.add($(this).attr("data-id"),files);
		});
	});
});
/*
$(document).on("Attachment.add",function(e,id,file) {
	if ($("#"+id).attr("data-templet") != "dragdrop") return;
	
	var list = $("#"+id+" div.fileList");
	
	var item = $("<div>").addClass("item").attr("id",file.id).attr("data-status",file.status);
	item.data("id",id);
	item.data("file",file);
	var preview = $("<div>").addClass("preview");
	var icon = $("<div>").addClass("icon").append(
		$("<div>").addClass(file.name.split(".").pop().toLowerCase())
	);
	preview.append(icon);
	var progress = $("<div>").addClass("progress").append($("<div>"));
	preview.append(progress);
	var waiting = $("<div>").addClass("waiting").append($("<div>"));
	preview.append(waiting);
	var insertButton = $("<a>").attr("href","#").attr("tabindex","-1").attr("rel","Insert File").addClass("insert").html('<i class="fa fa-clipboard"></i>');
	insertButton.on("click",function(event) {
		event.preventDefault();
		Attachment.insertFile($(this).parents(".item").data("id"),$(this).parents(".item").data("file"));
	});
	preview.append(insertButton);
	var insertText = $("<div>").addClass("insertText").html('<div class="arrowBox">'+list.attr("data-insert")+'</div>');
	preview.append(insertText);
	
	item.append(preview);
	
	var filename = $("<div>").addClass("filename").addClass("systemFont").html(file.name);
	item.append(filename);
	
	var filesize = $("<div>").addClass("filesize").html(Attachment.getFileSize(file.size));
	item.append(filesize);
	
	var deleteButton = $("<div>").addClass("delete");
	item.append(deleteButton);
	
	list.append(item);
	
	if (filename.height() > 30) {
		var length = file.name.length;
		while (filename.height() > 30) {
			filename.html(Attachment.substring(file.name,--length));
		}
	}
	
	if (file.thumbnail != null) {
		item.attr("data-thumbnail","TRUE");
		item.find(".preview > .icon > div").addClass("image").css("backgroundImage","url("+file.thumbnail+")");
		if (file.type == "video") item.find(".preview > .icon > div").addClass("video").append($("<span>").addClass("fa fa-play"));
	} else {
		item.attr("data-thumbnail","FALSE");
	}
	
	list.css("height","auto");
	list.scrollTop(list.prop("scrollHeight"));

	var isValid = true;
	
	if (list.is(":empty") == false) {
		list.parent().attr("data-status","stop").attr("data-status-init","stop");
	}
});

$(document).on("Attachment.submit",function(e,id,file) {
	if ($("#"+id).attr("data-templet") != "dragdrop") return;
	
	var item = $("#"+file.id).attr("data-status",file.status);
});

$(document).on("Attachment.progress",function(e,id,file) {
	if ($("#"+id).attr("data-templet") != "dragdrop") return;
	
	$("#"+file.id+" .progress > div").css("width",(file.loaded/file.total*100)+"%");
});

$(document).on("Attachment.progressAll",function(e,id,progress) {
	if ($("#"+id).attr("data-templet") != "dragdrop") return;
	
	iModule.alertMessage.progress(id,progress.loaded,progress.total);
});

$(document).on("Attachment.complete",function(e,id,file) {
	if ($("#"+id).attr("data-templet") != "dragdrop") return;
	
	var item = $("#"+file.id).attr("data-status",file.status);
	item.data("file",file);
	
	if (file.thumbnail != null) {
		item.attr("data-thumbnail","TRUE");
		item.find(".preview > .icon > div").addClass("image").css("backgroundImage","url("+file.thumbnail+")");
		if (file.type == "video") item.find(".preview > .icon > div").addClass("video").append($("<span>").addClass("fa fa-play"));
	} else {
		item.attr("data-thumbnail","FALSE");
	}
	
	$("#"+id+" .infoBlock > .default").hide();
	$("#"+id+" .infoBlock > .wysiwyg").show();
});

$(document).on("Attachment.change",function(e,id,file) {
	if ($("#"+id).attr("data-templet") != "dragdrop") return;
	
	var item = $("#"+file.id).attr("data-status",file.status);
	item.data("file",file);
	
	if (file.thumbnail != null) {
		item.attr("data-thumbnail","TRUE");
		item.find(".preview > .icon > div").addClass("image").css("backgroundImage","url("+file.thumbnail+")");
		if (file.type == "video") item.find(".preview > .icon > div").addClass("video").append($("<span>").addClass("fa fa-play"));
	} else {
		item.attr("data-thumbnail","FALSE");
	}
});

$(document).on("Attachment.fail",function(e,id,data){
	if ($("#"+id).attr("data-templet") != "dragdrop") return;
	
	console.log("fail",data);
});

$(document).on("Attachment.reset",function(e,id) {
	if ($("#"+id).attr("data-templet") != "dragdrop") return;
	
	$("#"+id+" div.fileList").empty();
});
*/