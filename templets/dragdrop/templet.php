<?php echo $inputForm; ?>

<div class="ModuleAttachmentDragDrop">
	<div data-role="filedrop" data-status="ready" drag-status-init="ready" data-id="<?php echo $id; ?>" class="dropzone">
		<div class="helpBlock">
			<div>
				<p class="ready"><?php echo $Module->getLanguage('drop/ready'); ?></p>
				<p class="drag"><?php echo $Module->getLanguage('drop/drag'); ?></p>
			</div>
		</div>
		<div class="fileList" data-insert="<?php echo $Module->getLanguage('insert'); ?>"></div>
	</div>
</div>