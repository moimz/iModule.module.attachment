<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 첨부파일모듈 드래그&드롭 템플릿
 * 
 * @file /modules/attachment/templets/dragdrop/index.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2020. 1. 7.
 */
if (defined('__IM__') == false) exit;
?>
<div class="ModuleAttachmentDragDrop">
	<div data-role="filedrop" data-id="<?php echo $me->getId(); ?>" data-status="empty" data-drag-status="ready" class="dropzone">
		<button type="button" data-action="select">
			<div>
				<p class="ready"><?php echo $Templet->getText('ready'); ?></p>
				<p class="drag"><?php echo $Templet->getText('drag'); ?></p>
			</div>
		</button>
		
		<ul data-role="files"></ul>
	</div>
</div>