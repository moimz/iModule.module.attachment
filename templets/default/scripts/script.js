$(document).on("Attachment.add",function(e,id,file) {
	if ($("#"+id).attr("data-templet") != "default") return;
	
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
});

$(document).on("Attachment.submit",function(e,id,file) {
	if ($("#"+id).attr("data-templet") != "default") return;
	
	var item = $("#"+file.id).attr("data-status",file.status);
});

$(document).on("Attachment.progress",function(e,id,file) {
	if ($("#"+id).attr("data-templet") != "default") return;
	
	$("#"+file.id+" .progress > div").css("width",(file.loaded/file.total*100)+"%");
});

$(document).on("Attachment.progressAll",function(e,id,progress) {
	if ($("#"+id).attr("data-templet") != "default") return;
	
	iModule.alertMessage.progress(id,progress.loaded,progress.total);
});

$(document).on("Attachment.complete",function(e,id,file) {
	if ($("#"+id).attr("data-templet") != "default") return;
	
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
	if ($("#"+id).attr("data-templet") != "default") return;
	
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
	if ($("#"+id).attr("data-templet") != "default") return;
	
	console.log("fail",data);
});

$(document).on("Attachment.reset",function(e,id) {
	if ($("#"+id).attr("data-templet") != "default") return;
	
	$("#"+id+" div.fileList").empty();
});