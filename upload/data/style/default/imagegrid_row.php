<?php defined('KELLY_ROOT') or die('Wrong URL');
        $user = KellyUser::getCurrentUser();   
		if ($user and $user->permissions !== 777) $user = false;
		
		$tags = empty($_POST['tags']) ? '' :  $_POST['tags'];
		
		/*
		
			'link' => $parsedImage->getPreview(true),
			'width' => $image['image_w'],
			'height' => $image['image_h'],
			'tags' => $image['tags'],
			'rating' => $image['rating'],
			'color' => $image['image_color'],
			'id' => $image['image_id'],
			
		*/
 ?>
  
<!-- todo on click display resource link -->
<div>
    <?php foreach($vars['images'] as &$image) { ?>

        <div id="search-image-<?php echo $image['id']; ?>" class="search-image-block" style="width : <?php echo $image['width']; ?>px; height : <?php echo $image['height']; ?>px; background-color : #<?php echo $image['color']; ?>; ">
            <img src="<?php echo $image['link']; ?>" width="<?php echo $image['width']; ?>" height="<?php echo $image['height']; ?>" alt="<?php echo $image['alt']; ?>">
            <?php if ($user) { ?> 
				<div class="search-controll" >
					<?php echo $image['rating']; ?> | 
					<a class="controll-delete" href="#" onclick="deleteFromSearch(<?php echo $image['id']; ?>); return false;">Удалить</a>
					
					<?php if ($tags) { ?>
						<a class="controll-deletetag" href="#" onclick="deleteFromTag(<?php echo $image['id']; ?>, '<?php echo $tags; ?>'); return false;">Удалить тег</a>
					<?php } ?>
					
					<?php if (strpos($tags, '_censored_') === false) { ?>
						<a class="controll-delete" href="#" onclick="setCensored(<?php echo $image['id']; ?>, false); return false;">Отметить : Нецензурно</a>
					<?php } else { ?>
						<a class="controll-delete" href="#" onclick="setCensored(<?php echo $image['id']; ?>, true); return false;">Отметить : Цензурно</a>
					<?php } ?>
				</div>
			<?php } ?>
        </div>
        
    <?php } ?>
</div>