var Attachment = {
	configs:{},
	fileList:{},
	progress:{},
	getConfigs:function(id,config) {
		var configs = Attachment.configs[id] !== undefined ? Attachment.configs[id] : {"module":null,"target":null,"wysiwyg":false};
		if (config !== undefined && configs[config] != undefined) return configs[config];
		else return configs;
	},
	init:function(id,configs) {
		configs = configs == undefined ? null : configs;
		
		Attachment.initEvent(id);
		
		if (configs == null) configs = {"module":null,"target":null,"wysiwyg":false};
		configs.chunkSize = configs.chunkSize ? configs.chunkSize : 2 * 1024 * 1024;
		Attachment.configs[id] = configs;
		Attachment.fileList[id] = new Array();
		Attachment.progress[id] = {loaded:0,total:0};
	},
	initEvent:function(id) {
		$("#"+id+" input[data-attachment-input-file=true]").on("change",function(e) {
			for (var i=0, loop=e.target.files.length;i<loop;i++) {
				var file = e.target.files[i];
				file.status = "WAITING";
				file.autoInsert = e.target.name.indexOf("wysiwyg") == 0;
				Attachment.add(id,file);
			}
			$("input[data-attachment-input-file=true]").val("");
			Attachment.submit(id);
		});
	},
	reset:function(id) {
		var form = $("#"+id).parents("form");
		form.find("input[name='attachments[]']").remove();
		Attachment.initEvent(id);
		Attachment.fileList[id] = new Array();
		Attachment.progress[id] = {loaded:0,total:0};

		$(document).triggerHandler("Attachment.reset",[id]);
	},
	select:function(id,type) {
		$("#"+id+" input[data-attachment-input-file=true][name=attachment_"+type+"]").trigger("click");
	},
	add:function(id,file) {
		file.id = id+"-"+Attachment.fileList[id].length;
		Attachment.fileList[id].push(file);
		Attachment.progress[id].total+= file.size;
		if (file.status == "COMPLETE") {
			Attachment.progress[id].loaded+= file.size;
		}
		
		if (file.idx !== undefined && file.code !== undefined) {
			var form = $("#"+id).parents("form");
			if (form.find("input[idx="+file.idx+"]").length == 0) {
				form.append($("<input>").attr("data-idx",file.idx).attr("type","hidden").attr("name","attachments[]").val(file.code));
			}
		}
		
		$(document).triggerHandler("Attachment.add",[id,file]);
	},
	submit:function(id) {
		for (var i=0, loop=Attachment.fileList[id].length;i<loop;i++) {
			if (Attachment.fileList[id][i].status == "UPLOADING") return;
			if (Attachment.fileList[id][i].status == "WAITING") {
				Attachment.upload(id,Attachment.fileList[id][i]);
				return;
			}
		}
		
		$(document).triggerHandler("Attachment.completeAll",[id]);
	},
	upload:function(id,file) {
		if (file.code === undefined) {
			var meta = {};
			meta.module = Attachment.getConfigs(id,"module");
			meta.target = Attachment.getConfigs(id,"target");
			meta.wysiwyg = true;
			
			meta.name = file.name;
			meta.type = file.type;
			meta.size = file.size;
			
			file.status = "UPLOADING";
			file.loaded = 0;
			
			$(document).triggerHandler("Attachment.submit",[id,file]);
			
			$.ajax({
				type:"POST",
				url:ENV.getProcessUrl("attachment","upload"),
				data:{meta:JSON.stringify(meta)},
				dataType:"json",
				success:function(result) {
					if (result.success) {
						file.idx = result.idx;
						file.code = result.code;
						file.failCount = 0;
						Attachment.upload(id,file);
					} else {
						file.status = "FAIL";
					}
				},
				error:function() {
					file.status = "FAIL";
				}
			});
		} else {
			file.chunkStart = file.chunkStart ? file.chunkStart : 0;
			file.chunkEnd = file.size > file.chunkStart + Attachment.configs[id].chunkSize ? file.chunkStart + Attachment.configs[id].chunkSize : file.size;
			
			$.ajax({
				url:ENV.getProcessUrl("attachment","upload")+"?idx="+encodeURIComponent(file.code),
				method:"POST",
				contentType:file.type,
				headers:{
					"Content-Range":"bytes " + file.chunkStart + "-" + (file.chunkEnd - 1) + "/" + file.size
				},
				xhr:function() {
					var xhr = $.ajaxSettings.xhr();
	
					if (xhr.upload) {
						xhr.upload.addEventListener("progress",function(e) {
							if (e.lengthComputable) {
								$(document).triggerHandler("Attachment.progress",[id,{id:file.id,loaded:file.loaded + e.loaded,total:file.size}]);
								$(document).triggerHandler("Attachment.progressAll",[id,{loaded:Attachment.progress[id].loaded + file.loaded + e.loaded,total:Attachment.progress[id].total}]);
							}
						},false);
					}
	
					return xhr;
				},
				processData:false,
				data:file.slice(file.chunkStart,file.chunkEnd)
			}).done(function(result) {
				if (result.success == true) {
					file.failCount = 0;
					file.loaded = file.chunkEnd;
					
					if (file.chunkEnd == file.size) {
						file.status = "COMPLETE";
						var isAutoInsert = file.autoInsert;
						var fileId = file.id;
						
						file = result.file;
						file.id = fileId;
						file.status = "COMPLETE";
						
						Attachment.progress[id].loaded+= file.size;
						
						if (file.idx !== undefined && file.code !== undefined) {
							var form = $("#"+id).parents("form");
							if (form.find("input[idx="+file.idx+"]").length == 0) {
								form.append($("<input>").attr("data-idx",file.idx).attr("type","hidden").attr("name","attachments[]").val(file.code));
							}
						}
						
						if (isAutoInsert == true) {
							Attachment.insertFile(id,file);
						}
						
						$(document).triggerHandler("Attachment.complete",[id,file]);
						Attachment.submit(id);
					} else {
						file.chunkStart = file.chunkEnd;
						Attachment.upload(id,file);
					}
				} else {
					if (file.failCount < 3) {
						file.failCount++;
						Attachment.upload(id,file);
					} else {
						file.status = "FAIL";
					}
				}
			}).fail(function() {
				if (file.failCount < 3) {
					file.failCount++;
					Attachment.upload(id,file);
				}
			});
		}
	},
	getFileSize:function(size,isKIB) {
		var depthSize = isKIB === true ? 1024 : 1000;
		if (size / depthSize / depthSize / depthSize > 1) return (size / depthSize / depthSize / depthSize).toFixed(2)+(isKIB === true ? 'GiB' : 'GB');
		else if (size / depthSize / depthSize > 1) return (size / depthSize / depthSize).toFixed(2)+(isKIB === true ? 'MiB' : 'MB');
		else if (size / depthSize > 1) return (size / depthSize).toFixed(2)+(isKIB === true ? 'KiB' : 'KB');
		return size+"B";
	},
	substring:function(str,length) {
		if (str.length < 12 || str.length < length) return str;
		
		return str.substr(0,length-8)+"..."+str.substr(str.length-8,8);
	},
	loadFile:function(id,key) {
		$.ajax({
			type:"POST",
			url:ENV.getProcessUrl("attachment","load"),
			data:{key:key},
			dataType:"json",
			success:function(result) {
				if (result.success == true) {
					for (var i=0, loop=result.files.length;i<loop;i++) {
						var file = result.files[i];
						file.status = "COMPLETE";
						
						Attachment.add(id,file);
					}
				}
			}
		});
	},
	getFiles:function(id) {
		var attachments = $("#"+id).find("[id|="+id+"]");
		var files = new Array();
		for (var i=0, loop=attachments.length;i<loop;i++) {
			files.push($(attachments[i]).data("file"));
		}
		
		return files;
	},
	setFiles:function(id,files) {
		Attachment.fileList[id] = new Array();
		Attachment.progress[id] = {loaded:0,total:0};
		
		$("#"+id).find("[id|="+id+"]").remove();
		$("#"+id).parents("form").find("input[name='attachments[]']").remove();
		
		for (var i=0, loop=files.length;i<loop;i++) {
			Attachment.add(id,files[i]);
		}
	},
	insertFile:function(id,file) {
		if (Attachment.getConfigs(id,"wysiwyg") == false) return;
		if (Attachment.getConfigs(id,"target").indexOf("#") == 0) {
			var editor = $(Attachment.getConfigs(id,"target"));
		} else {
			var editor = $("#"+id).parents("form").find("textarea[name="+Attachment.getConfigs(id,"target")+"]");
		}
		
		var inEditor = editor.froalaEditor("selection.inEditor");
		if (inEditor == true && $(editor.froalaEditor("selection.element")).is("a.btnDownload") == true) {
			editor.froalaEditor("selection.setAtEnd",$(editor.froalaEditor("selection.element")).get(0).parentNode);
		}
		
		if (typeof Attachment.insertWysiwyg[file.type] == "function") {
			Attachment.insertWysiwyg[file.type](id,file,editor);
		} else {
			Attachment.insertWysiwyg.download(id,file,editor);
		}
	},
	insertWysiwyg:{
		image:function(id,file,editor) {
			editor.froalaEditor("image.insert",file.path,false,{"idx":file.idx});
		},
		download:function(id,file,editor) {
			editor.froalaEditor("file.insert",file.path,file.name,{success:true,fileInfo:{idx:file.idx,size:file.size,name:file.name}});
		}
	}
};

var globaltags = null;