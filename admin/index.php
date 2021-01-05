<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 첨부파일모듈 관리자 패널을 생성한다.
 * 
 * @file /modules/attachment/admin/index.php
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.0.0
 * @modified 2018. 4. 1.
 */
if (defined('__IM__') == false) exit;
?>
<script>
Ext.onReady(function () { Ext.getCmp("iModuleAdminPanel").add(
	new Ext.TabPanel({
		id:"ModuleAttachment",
		border:false,
		tabPosition:"bottom",
		items:[
			new Ext.grid.Panel({
				id:"ModuleAttachmentList",
				iconCls:"fa fa-file-text-o",
				title:Attachment.getText("admin/list/title"),
				border:false,
				tbar:[
					Admin.searchField("ModuleAttachmentListKeyword",250,Attachment.getText("admin/list/keyword"),function(keyword) {
						Ext.getCmp("ModuleAttachmentList").getStore().getProxy().setExtraParam("keyword",keyword);
						Ext.getCmp("ModuleAttachmentList").getStore().loadPage(1);
					})
				],
				store:new Ext.data.JsonStore({
					proxy:{
						type:"ajax",
						simpleSortMode:true,
						url:ENV.getProcessUrl("attachment","@getAttachments"),
						reader:{type:"json"}
					},
					remoteSort:true,
					sorters:[{property:"reg_date",direction:"DESC"}],
					autoLoad:false,
					pageSize:50,
					fields:["idx","name","module","target","path","type","size","reg_date","download"],
					listeners:{
						load:function(store,records,success,e) {
							if (success == false) {
								if (e.getError()) {
									Ext.Msg.show({title:Admin.getText("alert/error"),msg:e.getError(),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
								} else {
									Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("DATA_LOAD_FAILED"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
								}
							}
						}
					}
				}),
				columns:[{
					text:Attachment.getText("admin/list/columns/name"),
					minWidth:240,
					flex:1,
					sortable:true,
					dataIndex:"name",
					renderer:function(value,p,record) {
						return '<i class="icon" style="background-image:url(' + record.data.icon + '); background-size:contain; background-repeat:no-repeat; background-position:50% 50%; margin-right:8px;"></i>' + value;
					}
				},{
					text:Attachment.getText("admin/list/columns/module"),
					width:200,
					dataIndex:"module",
					sortable:true,
					renderer:function(value,p,record) {
						return '<i class="icon ' + record.data.module_icon + '"></i>' + value;
					}
				},{
					text:Attachment.getText("admin/list/columns/target"),
					width:140,
					dataIndex:"target",
					sortable:true
				},{
					text:Attachment.getText("admin/list/columns/size"),
					width:90,
					dataIndex:"size",
					align:"right",
					sortable:true,
					renderer:function(value) {
						return iModule.getFileSize(value);
					}
				},{
					text:Attachment.getText("admin/list/columns/path"),
					width:280,
					dataIndex:"path",
					sortable:true,
					renderer:function(value) {
						var temp = value.split("/");
						var name = temp.pop();
						return '<span style="color:#999;">' + temp.join("/") + '/</span>' + name;
					}
				},{
					text:Attachment.getText("admin/list/columns/reg_date"),
					width:145,
					dataIndex:"reg_date",
					sortable:true,
					renderer:function(value) {
						return moment(value * 1000).locale($("html").attr("lang")).format("YYYY.MM.DD(dd) HH:mm");
					}
				},{
					text:Attachment.getText("admin/list/columns/status"),
					width:60,
					dataIndex:"status",
					align:"center",
					renderer:function(value,p) {
						if (value == "DRAFT") p.style = "color:#999;";
						else p.style = "color:blue;";
						return Attachment.getText("status/" + value);
					}
				},{
					text:Attachment.getText("admin/list/columns/download"),
					width:60,
					dataIndex:"download",
					sortable:true,
					align:"right",
					renderer:function(value) {
						return Ext.util.Format.number(value,"0,000");
					}
				}],
				selModel:new Ext.selection.CheckboxModel(),
				bbar:new Ext.PagingToolbar({
					store:null,
					displayInfo:false,
					items:[
						"->",
						{xtype:"tbtext",text:Attachment.getText("admin/list/grid_help")}
					],
					listeners:{
						beforerender:function(tool) {
							tool.bindStore(Ext.getCmp("ModuleAttachmentList").getStore());
						}
					}
				}),
				listeners:{
					itemdblclick:function(grid,record) {
						document.downloadFrame.location.href = ENV.DIR + "/attachment/download/" + record.data.idx + "/" + record.data.name;
					},
					itemcontextmenu:function(grid,record,item,index,e) {
						var menu = new Ext.menu.Menu();
						
						menu.addTitle(record.data.name);
						
						menu.add({
							iconCls:"xi xi-download",
							text:"다운로드",
							handler:function() {
								document.downloadFrame.location.href = ENV.DIR + "/attachment/download/" + record.data.idx + "/" + record.data.name;
							}
						});
						
						e.stopEvent();
						menu.showAt(e.getXY());
					}
				}
			}),
			new Ext.grid.Panel({
				id:"ModuleAttachmentTempList",
				iconCls:"xi xi-marquee-remove",
				title:Attachment.getText("admin/temp/title"),
				border:false,
				tbar:[
					Admin.searchField("ModuleAttachmentTempKeyword",250,Attachment.getText("admin/list/keyword"),function(keyword) {
						Ext.getCmp("ModuleAttachmentTempList").getStore().clearFilter();
						Ext.getCmp("ModuleAttachmentTempList").getStore().filter(function(record) {
							var filter = true;
							if (keyword.length > 0) {
								filter = filter && record.data.name.indexOf(keyword) > -1;
							}
							return filter;
						});
					}),
					"-",
					new Ext.Button({
						iconCls:"mi mi-trash",
						text:"선택된 임시파일 삭제",
						handler:function() {
							Attachment.temp.delete();
						}
					})
				],
				store:new Ext.data.JsonStore({
					proxy:{
						type:"ajax",
						simpleSortMode:true,
						url:ENV.getProcessUrl("attachment","@getTemps"),
						reader:{type:"json"}
					},
					remoteSort:false,
					sorters:[{property:"reg_date",direction:"DESC"}],
					autoLoad:false,
					fields:["idx","name","module","target","path","type","size","reg_date"],
					listeners:{
						load:function(store,records,success,e) {
							if (success == false) {
								if (e.getError()) {
									Ext.Msg.show({title:Admin.getText("alert/error"),msg:e.getError(),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
								} else {
									Ext.Msg.show({title:Admin.getText("alert/error"),msg:Admin.getErrorText("DATA_LOAD_FAILED"),buttons:Ext.Msg.OK,icon:Ext.Msg.ERROR});
								}
							}
						}
					}
				}),
				columns:[{
					text:Attachment.getText("admin/list/columns/name"),
					minWidth:300,
					flex:1,
					sortable:true,
					dataIndex:"name",
					renderer:function(value,p,record) {
						return '<i class="icon" style="background-image:url(' + record.data.icon + '); background-size:contain; background-repeat:no-repeat; background-position:50% 50%; margin-right:8px;"></i>' + value;
					}
				},{
					text:Attachment.getText("admin/list/columns/size"),
					width:90,
					dataIndex:"size",
					align:"right",
					sortable:true,
					renderer:function(value) {
						return iModule.getFileSize(value);
					}
				},{
					text:Attachment.getText("admin/list/columns/path"),
					width:350,
					dataIndex:"path",
					sortable:true,
					renderer:function(value) {
						var temp = value.split("/");
						var name = temp.pop();
						return '<span style="color:#999;">' + temp.join("/") + '/</span>' + name;
					}
				},{
					text:Attachment.getText("admin/temp/columns/reg_date"),
					width:145,
					dataIndex:"reg_date",
					sortable:true,
					renderer:function(value) {
						return moment(value * 1000).locale($("html").attr("lang")).format("YYYY.MM.DD(dd) HH:mm");
					}
				}],
				selModel:new Ext.selection.CheckboxModel(),
				bbar:[
					new Ext.Button({
						iconCls:"x-tbar-loading",
						handler:function() {
							Ext.getCmp("ModuleAttachmentTempList").getStore().reload();
						}
					}),
					"->",
					{xtype:"tbtext",text:Attachment.getText("admin/list/grid_help")}
				],
				listeners:{
					itemdblclick:function(grid,record) {
						document.downloadFrame.location.href = ENV.DIR + "/attachment/download/temp/" + record.data.name;
					},
					itemcontextmenu:function(grid,record,item,index,e) {
						var menu = new Ext.menu.Menu();
						
						menu.addTitle(record.data.name);
						
						menu.add({
							iconCls:"xi xi-download",
							text:"다운로드",
							handler:function() {
								document.downloadFrame.location.href = ENV.DIR + "/attachment/download/temp/" + record.data.name;
							}
						});
						
						menu.add({
							iconCls:"mi mi-trash",
							text:"파일삭제",
							handler:function() {
								Attachment.temp.delete();
							}
						})
						
						e.stopEvent();
						menu.showAt(e.getXY());
					}
				}
			})
		],
		listeners:{
			render:function(tabs) {
				tabs.fireEvent("tabchange",tabs,tabs.getActiveTab());
			},
			tabchange:function(tabs,tab) {
				if (tab.is("grid") == true && tab.getStore().isLoading() == false && tab.getStore().isLoaded() == false) {
					tab.getStore().load();
				}
			}
		}
	})
); });
</script>