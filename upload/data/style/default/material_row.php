<?php defined('KELLY_ROOT') or die('Wrong URL'); ?>
 
 <?php if (!empty($vars['start'])){ ?>
<table class="table table-striped">
 <thead>
 <tr> <th>Ид</th> <th>Заголовок</th> <th>Теги</th> <th>Дата</th><th>Синхронизирован</th>  <th>Кол-во изображений</th> </tr> 
 </thead> 
 <tbody>
<?php } elseif (!empty($vars['end'])) { ?>
 </tbody> 
</table> 
<?php } else { ?>
<tr> 
	<th scope="row"><?php echo $vars['material']['material_id']; ?></th> 
	<td><?php echo $vars['material']['material_title']; ?></td> 
	<td><?php echo $vars['material']['material_tags']; ?></td> 
    	 <td> 
		<?php echo $vars['material']['material_date']; ?>
	 </td> 
	 <td> 
		<?php echo $vars['material']['material_loaded_date']; ?>
	 </td> 
	 <td> 
		<?php echo $vars['material']['material_images_count']; ?>
	 </td> 	 
 </tr> 
	
 <?php } ?>