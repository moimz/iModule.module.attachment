/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 첨부파일 관리자 UI 이벤트를 처리한다.
 *
 * @file /modules/attachment/admin/index.php
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.0.0
 * @modified 2020. 12. 30.
 */
var Attachment = {
	file:{
		replace:function(idx) {
			new Ext.Window({
				id:"ModuleAttachmentFileReplaceWindow",
				title:"파일변경",
				modal:true,
				width:500,
				border:false,
				autoScroll:true,
				items:[
					new Ext.form.Panel({
						id:"ModuleAttachmentFileReplaceForm",
						border:false,
						bodyPadding:"10 10 0 10",
						fieldDefaults:{labelAlign:"right",labelWidth:100,anchor:"100%",allowBlank:false},
						items:[
							new Ext.form.Hidden({
								name:"idx"
							}),
							new Ext.form.TextField({
								name:"name",
								fieldLabel:"파일명"
							}),
							new Ext.form.FieldContainer({
								fieldLabel:"파일업로드",
								layout:{type:"vbox",align:"stretch"},
								items:[
									new Ext.form.FieldContainer({
										layout:"hbox",
										items:[
											new Ext.Button({
												id:"ModuleAttachmentFileReplaceFileButton",
												iconCls:"xi xi-upload",
												text:"파일선택",
												handler:function(button) {
													var $input = $("input[type=file]",$(button.ownerCt.ownerCt.el.dom));
													$input.trigger("click");
												}
											})
										]
									}),
									new Ext.form.Hidden({
										name:"hash"
									}),
									new Ext.form.DisplayField({
										id:"ModuleAttachmentFileReplaceFileInput",
										hidden:true,
										value:'<input type="file" name="file">',
										listeners:{
											render:function() {
												var $input = $("input[type=file]",$(Ext.getCmp("ModuleAttachmentFileReplaceFileInput").el.dom));
												
												$input.on("change",function(e) {
													Ext.getCmp("ModuleAttachmentFileReplaceFileButton").disable();
													
													var file = e.target.files[0];
													file.uploaded = 0;
													$input.val("");
													$input.data("file",file);
													
													var draft = {};
													draft.name = file.name;
													draft.size = file.size;
													draft.type = file.type;
													
													Ext.getCmp("ModuleAttachmentFileReplaceForm").getForm().findField("name").setValue(file.name);
													
													$.send(ENV.getProcessUrl("attachment","@replace"),{file:JSON.stringify(draft)},function(result) {
														if (result.success == true) {
															Ext.getCmp("ModuleAttachmentFileReplaceForm").getForm().findField("hash").setValue(result.hash);
														}
														
														Ext.getCmp("ModuleAttachmentFileReplaceForm").upload();
													});
												});
											}
										}
									}),
									new Ext.ProgressBar({
										id:"ModuleAttachmentFileReplaceProgressBar",
										flex:1,
										listeners:{
											render:function(progress) {
												progress.updateProgress(0,"변경할 파일을 선택하여 주십시오.");
											}
										}
									})
								]
							})
						],
						upload:function() {
							var $input = $("input[type=file]",$(Ext.getCmp("ModuleAttachmentFileReplaceFileInput").el.dom));
							
							var file = $input.data("file");
							var chunkSize = 2 * 1000 * 1000;
							file.chunk = file.size > file.uploaded + chunkSize ? file.uploaded + chunkSize : file.size;
							
							$.ajax({
								url:ENV.getProcessUrl("attachment","@replace")+"?hash=" + Ext.getCmp("ModuleAttachmentFileReplaceForm").getForm().findField("hash").getValue(),
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
												Ext.getCmp("ModuleAttachmentFileReplaceProgressBar").updateProgress((file.uploaded + e.loaded) / file.size,"파일을 업로드중입니다. (" + ((file.uploaded + e.loaded) / file.size * 100).toFixed(2) + "%)");
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
										Ext.getCmp("ModuleAttachmentFileReplaceSubmitButton").enable();
									} else {
										file.uploaded = result.uploaded;
										Ext.getCmp("ModuleAttachmentFileReplaceForm").upload();
									}
								} else {
									if (file.failCount < 3) {
										file.failCount++;
										Ext.getCmp("ModuleAttachmentFileReplaceForm").upload();
									} else {
										
									}
								}
							}).fail(function() {
								if (file.failCount < 3) {
									file.failCount++;
									Ext.getCmp("ModuleAttachmentFileReplaceForm").upload();
								}
							});
						}
					})
				],
				buttons:[
					new Ext.Button({
						id:"ModuleAttachmentFileReplaceSubmitButton",
						text:Admin.getText("button/confirm"),
						disabled:true,
						handler:function() {
							Ext.getCmp("ModuleAttachmentFileReplaceForm").getForm().submit({
								url:ENV.getProcessUrl("attachment","@replaceFile"),
								submitEmptyText:false,
								waitTitle:Admin.getText("action/wait"),
								waitMsg:Admin.getText("action/saving"),
								success:function(form,action) {
									Ext.Msg.show({title:Admin.getText("alert/info"),msg:Admin.getText("action/saved"),buttons:Ext.Msg.OK,icon:Ext.Msg.INFO,fn:function() {
										Ext.getCmp("ModuleAttachmentList").getStore().reload();
										Ext.getCmp("ModuleAttachmentFileReplaceWindow").close();
									}});
								},
								failure:function(form,action) {
									if (action.result) {
										if (action.result.message) {
											Ext.Msg.show({title:Admin.getText("alert/error"),msg:action.result.message,buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
										} else {
											Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("DATA_SAVE_FAILED"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
										}
									} else {
										Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("INVALID_FORM_DATA"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
									}
								}
							});
						}
					}),
					new Ext.Button({
						text:Admin.getText("button/cancel"),
						handler:function() {
							Ext.getCmp("ModuleAttachmentFileReplaceWindow").close();
						}
					})
				],
				listeners:{
					show:function() {
						Ext.getCmp("ModuleAttachmentFileReplaceForm").getForm().load({
							url:ENV.getProcessUrl("attachment","@getFile"),
							params:{idx:idx},
							waitTitle:Admin.getText("action/wait"),
							waitMsg:Admin.getText("action/loading"),
							success:function(form,action) {
							},
							failure:function(form,action) {
								if (action.result && action.result.message) {
									Ext.Msg.show({title:Admin.getText("alert/error"),msg:action.result.message,buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
								} else {
									Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("DATA_LOAD_FAILED"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
								}
								Ext.getCmp("ModuleAttachmentFileReplaceWindow").close();
							}
						});
					}
				}
			}).show();
		}
	},
	temp:{
		delete:function() {
			Ext.Msg.show({title:Admin.getText("alert/info"),msg:"선택된 임시파일을 삭제하시겠습니까?<br>임시파일을 이용하여 특정한 작업이 수행되고 있을 수 있으므로 가급적 생성된지 오래된 임시파일만 삭제하는 것을 권장합니다.",buttons:Ext.Msg.OKCANCEL,icon:Ext.Msg.QUESTION,fn:function(button) {
				if (button == "ok") {
					var selected = Ext.getCmp("ModuleAttachmentTempList").getSelectionModel().getSelection();
					if (selected.length == 0) {
						Ext.Msg.show({title:Admin.getText("alert/error"),msg:"삭제할 임시파일을 선택하여 주십시오.",buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
						return;
					}
		
					var files = [];
					for (var i=0, loop=selected.length;i<loop;i++) {
						files[i] = selected[i].data.name;
					}
					
					Ext.Msg.wait(Admin.getText("action/working"),Admin.getText("action/wait"));
					$.send(ENV.getProcessUrl("attachment","@deleteTemp"),{files:JSON.stringify(files)},function(result) {
						if (result.success == true) {
							Ext.Msg.show({title:Admin.getText("alert/info"),msg:Admin.getText("action/worked"),buttons:Ext.Msg.OK,icon:Ext.Msg.INFO,fn:function() {
								Ext.getCmp("ModuleAttachmentTempList").getStore().reload();
							}});
						}
					});
				}
			}});
		}
	}
};