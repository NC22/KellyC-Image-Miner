<?php defined('KELLY_ROOT') or die('Wrong URL'); ?>
 
 <?php if (!empty($vars['start'])){ ?>
<table class="table table-striped">
 <thead>
 <tr> <th>Пользователь</th> <th>Состояние</th> <th>Данные авторизации</th> <th>Актуальность</th> <th>Последняя активность</th> <th>Управление <a href="joy/sessionedit">Добавить</a></th> </tr> 
 </thead> 
 <tbody>
<?php } elseif (!empty($vars['end'])) { ?>
 </tbody> 
</table> 
<?php } else {
$name = $vars['session']['session_user'] ? $vars['session']['session_user'] : 'Гость ID ' . $vars['session']['session_id'];
 ?>
<tr> 
	<th scope="row">
        <?php echo $name ; ?>
    </th> 
	<td>
        <?php if ((int)$vars['session']['session_active']) { ?>
			<span style="color : #d44242;">Выполняет задачу</span>
		<?php } else { ?>
			Свободна
		<?php } ?>		
	</td> 
	<td style="width : 250px;">
        <a href="#" onclick="toogleDisplay('session-<?php echo $vars['session']['session_id']; ?>'); return false;">Показать</a><br>
		<span class="session-cookies" id="session-<?php echo $vars['session']['session_id']; ?>"><?php echo $vars['session']['session_cookies']; ?></span>
	</td> 
	 <td> 
		<?php echo $vars['session']['session_last_update']; ?>
	 </td> 
	 <td> 
		<?php echo $vars['session']['session_last_active']; ?>
	 </td> 	 
	 <td> 
		<form method="get" action="<?php echo KELLY_ROOT_URL; ?>joy/sessionedit">
			<input type="hidden" name="mode" value="sessionedit">
			<input type="hidden" name="sessionid" value="<?php echo $vars['session']['session_id'] ?>">
			<input type="submit" value="Изменить" style="min-width : 190px;">
		</form>
	</td> 
 </tr> 
	
 <?php } ?>