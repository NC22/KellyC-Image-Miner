<?php defined('KELLY_ROOT') or die('Wrong URL');
 ?>
<div class="panel panel-default">
	<div class="panel-heading">Подтвердите удаление <b><?php echo $vars['title'] ?></b>
    </div>
    <div class="panel-body">
	<form method="post"  action="<?php echo KELLY_ROOT_URL; ?>joy/sessiondelete">
		<input type="hidden" name="formName" value="<?php echo $vars['formName']; ?>">
		<input type="hidden" name="itemid" value="<?php echo $vars['id']; ?>">
		<input type="hidden" name="token_data" value="<?php echo Token::set($vars['formName']); ?>">
		
        <button type="submit" class="btn btn-default">Подтвердить</button>
        <a href="<?php echo KELLY_ROOT_URL . $vars['cancel'] ; ?>">Отменить</a>

	</form>
 </div>
</div>