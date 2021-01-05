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