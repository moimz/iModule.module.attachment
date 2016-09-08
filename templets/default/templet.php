<?php echo $inputForm; ?>

<div class="ModuleAttachmentDefault">
	<table class="ButtonTable<?php echo $Module->isWysiwyg() == true ? ' hidden-xs' : ''; ?>"<?php echo $Module->isWysiwygOnly() == true ? ' style="display:none;"' :''; ?>>
	<tr>
		<td class="buttonBlock">
			<button type="button" class="btn btnRed" onclick="Attachment.select('<?php echo $id; ?>','image');"><i class="fa fa-upload"></i> <?php echo $Module->getLanguage('attach'); ?></button>
		</td>
		<td class="infoBlock hidden-xs">
			<div class="default"><?php echo $Module->getLanguage('help/default'); ?></div>
			<div class="wysiwyg"><?php echo $Module->getLanguage('help/wysiwyg'); ?></div>
		</td>
	</tr>
	</table>
	
	<div class="fileList" data-insert="<?php echo $Module->getLanguage('insert'); ?>"></div>
</div>