/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 아이모듈 코어 및 모든 모듈에서 첨부파일과 관련된 모든 기능을 제어한다.
 * 
 * @file /modules/attachment/scripts/script.js
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.161211
 */
var Attachment = {
	/**
	 * 업로더를 초기화한다.
	 */
	init:function(id) {
		var $uploader = $("#"+id);
		
		$uploader.data("total",0);
		$uploader.data("uploaded",0);
		$uploader.data("files",[]);
		$uploader.data("uploading",null);
		$uploader.data("queue",[]);
		$("button[data-action=select]",$uploader).on("click",function() {
			var $uploader = $(this).parents("div[data-uploader=TRUE]");
			$("input[type=file]",$uploader).trigger("click");
		});
		
		$("input[type=file]",$uploader).on("change",function(e) {
			var $uploader = $(this).parents("div[data-uploader=TRUE]");
			$("button[data-action=select]",$uploader).status("loading");
			
			var files = [];
			for (var i=0, loop=e.target.files.length;i<loop;i++) {
				var file = e.target.files[i];
				files.push(file);
			}
			
			$(this).val("");
			Attachment.add($uploader.attr("id"),files);
		});
		
		if ($uploader.attr("data-uploader-loader")) {
			$(document).ready(function() {
				Attachment.load($uploader.attr("id"));
			});
		}
	},
	/**
	 * 파일을 불러온다.
	 *
	 * @param string id 업로더고유값
	 */
	load:function(id) {
		var $uploader = $("#"+id);
		if (!$uploader.attr("data-uploader-loader")) return;
		
		var url = $uploader.attr("data-uploader-loader");
		var params = {};
		if ($uploader.attr("data-uploader-module")) params.module = $uploader.attr("data-uploader-module");
		if ($uploader.attr("data-uploader-target")) params.target = $uploader.attr("data-uploader-target");
		
		$.send(url,params,function(result) {
			if (result.success == true) {
				for (var i=0, loop=result.files.length;i<loop;i++) {
					var file = result.files[i];
					file.status = "COMPLETE";
					Attachment.print(id,file);
				}
			}
		});
	},
	/**
	 * 파일을 추가한다.
	 *
	 * @param string id 업로더고유값
	 * @param File[] files 업로드 대상파일
	 */
	add:function(id,files) {
		var drafts = [];
		for (var i=0, loop=files.length;i<loop;i++) {
			var draft = {};
			draft.name = files[i].name;
			draft.size = files[i].size;
			draft.type = files[i].type;
			
			drafts.push(draft);
		}
		
		var params = {};
		params.files = JSON.stringify(drafts);
		
		var $uploader = $("#"+id);
		if ($uploader.attr("data-uploader-module")) params.module = $uploader.attr("data-uploader-module");
		if ($uploader.attr("data-uploader-target")) params.target = $uploader.attr("data-uploader-target");
		
		$.send(ENV.getProcessUrl("attachment","draft"),params,function(result) {
			if (result.success == true) {
				for (var i=0, loop=result.files.length;i<loop;i++) {
					if (result.files[i].code != null) {
						files[i].idx = result.files[i].idx;
						files[i].code = result.files[i].code;
						files[i].mime = result.files[i].mime;
						files[i].uploaded = result.files[i].uploaded;
						files[i].extension = result.files[i].extension;
						files[i].status = result.files[i].status;
						
						$uploader.data("total",$uploader.data("total") + files[i].size);
						if (files[i].status == "COMPLETE") $uploader.data("uploaded",$uploader.data("uploaded") + files[i].size);
						else $uploader.data("queue").push(files[i]);
						Attachment.print(id,result.files[i],files[i]);
					}
				}
				
				$uploader.triggerHandler("add",[files]);
			}
			
			$("button[data-action=select]",$uploader).status("default");
		});
	},
	/**
	 * 파일을 파일목록에 출력한다.
	 */
	print:function(id,file,oFile) {
		var $uploader = $("#"+id);
		var $files = $("ul[data-role=files]",$uploader);
		
		var $file = $("<li>").attr("data-role","file").attr("data-idx",file.idx).attr("data-status",file.status).data("file",file);
		
		var $item = $("<div>");
		
		var $icon = $("<i>").addClass("icon").attr("data-type",file.type).attr("data-extension",file.extension);
		var $preview = $("<div>").addClass("preview");
		
		if (file.thumbnail) $preview.css("backgroundImage","url("+file.thumbnail+")");
		else $preview.hide();
		
		$icon.append($preview);
		$item.append($icon);
		
		var $progress = $("<div>").addClass("progress").append($("<div>"));
		$item.append($progress);
		
		var $name = $("<div>").addClass("name").html(file.name);
		$item.append($name);
		
		var $size = $("<div>").addClass("size").html(iModule.getFileSize(file.size));
		$item.append($size);
		
		var $delete = $("<button>").attr("type","button").attr("data-action","delete");
		$delete.append($("<i>"));
		$delete.on("click",function() {
			var $file = $(this).parents("li[data-role=file]");
			var $files = $file.parents("ul[data-role=files]");
			
			Attachment.delete($uploader.attr("id"),$file.attr("data-idx"));
		});
		$item.append($delete);
		
		var $insert = $("<button>").attr("type","button").attr("data-action","insert");
		$insert.append($("<i>"));
		$insert.on("click",function() {
			var $file = $(this).parents("li[data-role=file]");
			var $files = $file.parents("ul[data-role=files]");
			
			Attachment.insert($uploader.attr("id"),$file.attr("data-idx"));
		});
		$item.append($insert);
		
		$file.append($item);
		
		if ($("li[data-role=file][data-idx="+file.idx+"]",$files).length == 0) {
			$files.append($file);
		} else {
			$("li[data-role=file][data-idx="+file.idx+"]",$files).replaceWith($file);
		}
		
		if ($uploader.parents("form").length > 0) {
			var $form = $uploader.parents("form").eq(0);
			var $input = $("<input>").attr("type","hidden").attr("name","attachments[]").attr("data-role","file").attr("data-idx",file.idx);
			$input.val(file.code);
			
			if ($("input[data-role=file][data-idx="+file.idx+"]",$form).length == 0) {
				$form.append($input);
			} else {
				$("input[data-role=file][data-idx="+file.idx+"]",$form).replaceWith($input);
			}
		}
		
		if ($uploader.attr("data-uploader-wysiwyg") == "TRUE") {
			var $form = $uploader.parents("form").eq(0);
			var $wysiwyg = $("textarea[name="+$uploader.attr("data-uploader-target")+"]",$form);
			if ($wysiwyg.length == 0) return;
			
			if (oFile && oFile.wysiwyg == true) {
				var reader = new FileReader();
				reader.onload = function (e) {
					var result = e.target.result;
					if (file.type == "image") {
						$wysiwyg.froalaEditor("html.insert",'<p><img data-idx="'+file.idx+'" class="fr-uploading" src="'+result+'"></p>');
					} else {
						$wysiwyg.froalaEditor("file.insert",file.path,file.name,{idx:file.idx});
					}
				};
				reader.readAsDataURL(oFile);
			}
			
			if (file.status == "COMPLETE") {
				if (file.type == "image" && $("img[data-idx="+file.idx+"].fr-uploading",$wysiwyg.data("froala.editor").$el).length > 0) {
					$wysiwyg.froalaEditor("image.insert",file.path,false,{idx:file.idx},$("img[data-idx="+file.idx+"].fr-uploading",$wysiwyg.data("froala.editor").$el),{success:true,file:file});
				}
			}
		}
		
		$uploader.triggerHandler("print",[$file,file]);
	},
	/**
	 * 파일상태를 변경한다.
	 *
	 * @param string id 업로더 고유값
	 * @param int 파일 고유값
	 * @param int/string 상태값 (숫자일 경우 업로드 된 용량)
	 */
	update:function(id,idx,status) {
		var $uploader = $("#"+id);
		var $files = $("ul[data-role=files]",$uploader);
		var $file = $("li[data-role=file][data-idx="+idx+"]");
		
		if ($file.length == 0) return;
		if (typeof status == "number") {
			if ($file.attr("data-status") != "UPLOADING") $file.attr("data-status","UPLOADING");
			$uploader.triggerHandler("progress",[$file,status,$file.data("file").size]);
		}
	},
	/**
	 * 파일 업로드를 시작한다.
	 */
	start:function(id) {
		var $uploader = $("#"+id);
		if ($uploader.data("uploading") != null) return;
		if ($uploader.data("queue").length == 0) return Attachment.complete(id);
		
		$uploader.data("uploading",$uploader.data("queue").shift());
		Attachment.upload(id);
	},
	/**
	 * 파일을 업로드한다.
	 */
	upload:function(id) {
		var $uploader = $("#"+id);
		if ($uploader.data("uploading") == null) return Attachment.start(id);
		
		var file = $uploader.data("uploading");
		Attachment.update(id,file.idx,file.uploaded);
		
		var chunkSize = 2 * 1000 * 1000;
		file.chunk = file.size > file.uploaded + chunkSize ? file.uploaded + chunkSize : file.size;
		
		$.ajax({
			url:ENV.getProcessUrl("attachment","upload")+"?code="+encodeURIComponent(file.code),
			method:"POST",
			contentType:file.mime,
			headers:{
				"Content-Range":"bytes " + file.uploaded + "-" + (file.chunk - 1) + "/" + file.size
			},
			xhr:function() {
				var xhr = $.ajaxSettings.xhr();

				if (xhr.upload) {
					xhr.upload.addEventListener("progress",function(e) {
						if (e.lengthComputable) {
							Attachment.update(id,file.idx,file.uploaded + e.loaded);
						}
					},false);
				}

				return xhr;
			},
			processData:false,
			data:file.slice(file.uploaded,file.chunk)
		}).done(function(result) {
			if (result.success == true) {
				file.failCount = 0;
				
				if (file.chunk == file.size) {
					Attachment.print(id,result.file);
					$uploader.data("uploaded",$uploader.data("uploaded") + file.size);
					$uploader.data("uploading",null);
					Attachment.start(id);
				} else {
					file.uploaded = result.uploaded;
					Attachment.upload(id);
				}
			} else {
				if (file.failCount < 3) {
					file.failCount++;
					Attachment.upload(id);
				} else {
					file.status = "FAIL";
				}
			}
		}).fail(function() {
			if (file.failCount < 3) {
				file.failCount++;
				Attachment.upload(id);
			}
		});
	},
	complete:function() {
		
	},
	/**
	 * 파일을 위지윅에디터에 삽입한다.
	 *
	 * @param string id 업로더 고유값
	 * @param int idx 파일 고유값
	 */
	insert:function(id,idx) {
		var $uploader = $("#"+id);
		var $files = $("ul[data-role=files]",$uploader);
		var $file = $("li[data-role=file][data-idx="+idx+"]",$files);
		var file = $file.data("file");
		
		if ($uploader.attr("data-uploader-wysiwyg") == "FALSE" || $uploader.parents("form").length == 0) return;
		
		var $form = $uploader.parents("form").eq(0);
		var $wysiwyg = $("textarea[name="+$uploader.attr("data-uploader-target")+"]",$form);
		if ($wysiwyg.length == 0) return;
		
		if (file.type == "image") {
			$wysiwyg.froalaEditor("image.insert",file.path,false,{"idx":file.idx});
		} else {
			$wysiwyg.froalaEditor("file.insert",file.download,file.name,{idx:file.idx});
		}
	},
	/**
	 * 파일을 삭제한다.
	 *
	 * @param string id 업로더 고유값
	 * @param string idx 파일 고유값
	 */
	delete:function(id,idx) {
		var $uploader = $("#"+id);
		var $files = $("ul[data-role=files]",$uploader);
		var $file = $("li[data-role=file][data-idx="+idx+"]",$files);
		var file = $file.data("file");
		var code = file.code;
		
		iModule.modal.get(ENV.getProcessUrl("attachment","getModal"),{modal:"delete",code:code},function($modal,$form) {
			$form.on("submit",function() {
				$form.send(ENV.getProcessUrl("attachment","delete"),function(result) {
					if (result.success == true) {
						$file.remove();
						
						if ($uploader.parents("form").length > 0) {
							var $form = $uploader.parents("form").eq(0);
							$("input[data-role=file][data-idx="+idx+"]",$form).remove();
							
							var $wysiwyg = $("textarea[name="+$uploader.attr("data-uploader-target")+"]",$form);
							if ($wysiwyg.length > 0) {
								if ($("*[data-idx="+idx+"]",$wysiwyg.froalaEditor("html.get")).length > 0) {
									$wysiwyg.froalaEditor("image.remove",$("*[data-idx="+idx+"]",$wysiwyg.froalaEditor("html.get")));
								}
							}
						}
						
						$uploader.triggerHandler("delete",[file]);
					}
				});
			});
		});
	},
	/**
	 * 파일의 확장자를 유지한채 파일명을 자른다.
	 *
	 * @param string name 파일명
	 * @param int length 자를 길이
	 */
	substring:function(name,length) {
		if (name.length < 12 || name.length < length) return name;
		
		return name.substr(0,length-8).replace(/[ ]+$/,'')+"..."+name.substr(name.length-8,8).replace(/^[ ]+/,'');
	}
};