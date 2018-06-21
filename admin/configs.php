<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodule.kr)
 *
 * 첨부파일모듈 설정을 위한 설정폼을 생성한다.
 * 
 * @file /modules/attachment/admin/configs.php
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.0.0
 * @modified 2018. 3. 18.
 */
if (defined('__IM__') == false) exit;
?>
<script>
new Ext.form.Panel({
	id:"ModuleConfigForm",
	border:false,
	bodyPadding:10,
	width:700,
	fieldDefaults:{labelAlign:"right",labelWidth:100,anchor:"100%",allowBlank:true},
	items:[
		new Ext.form.FieldSet({
			title:Attachment.getText("admin/configs/form/default_setting"),
			items:[
				Admin.templetField(Attachment.getText("admin/configs/form/templet"),"templet","module","attachment")
			]
		}),
		new Ext.form.FieldSet({
			title:Attachment.getText("admin/configs/form/upload_setting"),
			items:[
				new Ext.form.FieldContainer({
					fieldLabel:Attachment.getText("admin/configs/form/file_limit"),
					layout:"hbox",
					items:[
						new Ext.form.NumberField({
							name:"file_limit",
							width:100
						}),
						new Ext.form.DisplayField({
							value:"MB",
							style:{marginLeft:"5px"}
						}),
						new Ext.form.DisplayField({
							value:Attachment.getText("admin/configs/form/file_limit_help"),
							flex:1,
							style:{textAlign:"right"}
						})
					]
				}),
				new Ext.form.FieldContainer({
					fieldLabel:Attachment.getText("admin/configs/form/total_limit"),
					layout:"hbox",
					items:[
						new Ext.form.NumberField({
							name:"total_limit",
							width:100
						}),
						new Ext.form.DisplayField({
							value:"MB",
							style:{marginLeft:"5px"}
						}),
						new Ext.form.DisplayField({
							value:Attachment.getText("admin/configs/form/total_limit_help"),
							flex:1,
							style:{textAlign:"right"}
						})
					]
				}),
				new Ext.form.TextField({
					fieldLabel:Attachment.getText("admin/configs/form/allow_type"),
					name:"allow_type",
					afterBodyEl:'<div class="x-form-help">'+Attachment.getText("admin/configs/form/allow_type_help")+'</div>'
				})
			]
		})
	]
});
</script>