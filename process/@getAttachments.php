<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 첨부파일 목록을 가져온다.
 * 
 * @file /modules/attachment/process/@getAttachments.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 4. 1.
 */
if (defined('__IM__') == false) exit;

$start = Request('start');
$limit = Request('limit');
$keyword = Request('keyword');
$sort = Request('sort') ? Request('sort') : 'reg_date';
$dir = Request('dir') ? Request('dir') : 'desc';

$lists = $this->db()->select($this->table->attachment);
if ($keyword) $lists->where('(name like ? or path like ?)',array('%'.$keyword.'%','%'.$keyword.'%'));
$total = $lists->copy()->count();
$lists = $lists->orderBy($sort,$dir)->limit($start,$limit)->get();
for ($i=0, $loop=count($lists);$i<$loop;$i++) {
	$lists[$i]->icon = is_file($this->getModule()->getPath().'/images/icon_large_'.$lists[$i]->type.'.png') == true ? $this->getModule()->getDir().'/images/icon_large_'.$lists[$i]->type.'.png' : $this->getModule()->getDir().'/images/icon_large_etc.png';
	
	if ($lists[$i]->module == 'site') {
		$lists[$i]->module = 'iModule';
		$lists[$i]->module_icon = 'mi mi-imodule';
	} else {
		$package = $this->IM->getModule()->getPackage($lists[$i]->module);
		$lists[$i]->module = $this->IM->getModule()->getTitle($lists[$i]->module).'('.$lists[$i]->module.')';
		$lists[$i]->module_icon = $package != null && isset($package->icon) == true ? substr($package->icon,0,2).' '.$package->icon : 'xi xi-box';
	}
}

$results->success = true;
$results->lists = $lists;
$results->total = $total;
?>