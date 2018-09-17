<?php
/**
 * 이 파일은 iModule 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 첨부파일모듈 기본 템플릿
 * 
 * @file /modules/attachment/templets/default/index.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 9. 17.
 */
?>
<div class="button">
	<button type="button" data-action="select"><i class="mi mi-upload"></i><span><?php echo $me->getButtonText(); ?></span></button>
	
	<span><?php echo $Templet->getText('text/help'); ?></span>
</div>

<ul data-role="files"></ul>