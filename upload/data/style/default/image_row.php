<?php defined('KELLY_ROOT') or die('Wrong URL'); ?>
 
 <?php if (!empty($vars['start'])){ ?>
 <div class="container-fluid search-by-file-results">
 
<?php } elseif (!empty($vars['end'])) { ?>

 </div>
 
<?php } else { ?>
<div class="search-by-file-row">
	<div><a href="<?php echo JoyParser::JOYURL . 'post/' . $vars['material']['material_id']; ?>">Ссылка на первоисточник</a></div>
	<div class="search-by-file-tags">Уровень сходства : <?php echo $vars['diff']; ?>% | <?php echo $vars['material']['material_tags']; ?> | <?php echo $vars['image']->get('image_w'); ?>x<?php echo $vars['image']->get('image_h'); ?></div>
	<div>
	<?php 
	
	$html = '';
	foreach ($vars['image']->getPalete() as $color) {
		$html .= '<div style="'
		   . 'border : 1px solid #6a6a6a;'
		   . 'background-color : #' . $color . '; '
		   . 'display : inline-block; '
		   . 'width : 16px; '
		   . 'height : 16px; margin-right : 12px; '
		   . 'text-aling : center;"></div>';  
	}
	echo $html;
	?> 
	</div>
	<div class="search-by-file-row-img">
	<?php if ($vars['image']->get('image_preview')){ ?>
		<img src="<?php echo $vars['image']->getPreview(true); ?>" width="400">
	<?php } ?>		
	</div>
</div>
<div></div>   
	
 <?php } ?>