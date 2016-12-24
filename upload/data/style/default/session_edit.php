<?php defined('KELLY_ROOT') or die('Wrong URL');
$name = $vars['session']->user ? $vars['session']->user : 'Гость ID ' . $vars['session']->id;
 ?>
<div class="panel panel-default">
	<div class="panel-heading">
    <?php if (!$vars['session']->id) { ?>
    Добавить сессию
    <?php } else { ?>
    Изменить сессию <b><?php echo $name; ?></b>
    <?php } ?>
    </div>
    <div class="panel-body">
	<form method="post"  action="<?php echo KELLY_ROOT_URL; ?>joy/sessionedit">
		<input type="hidden" name="formName" value="session_edit">
		<input type="hidden" name="sessionid" value="<?php echo $vars['session']->id; ?>">
		<input type="hidden" name="token_data" value="<?php echo Token::set('session_edit'); ?>">
		
		  <div class="form-group">
			<label for="session-edit-user">Пользователь</label>
			<input name="user" class="form-control" id="session-edit-user" placeholder="Пользователь" value="<?php echo $vars['session']->user; ?>">
		  </div>
		  <div class="form-group">
			<label for="session-edit-password">Пароль</label>
			<input name="password" class="form-control" id="session-edit-password" placeholder="Пароль" value="">
		  </div>

		<?php if ($vars['session']->id) { ?>
			<div class="radio">
			  <label>
				<input type="radio" name="active" value="1"  <?php if($vars['session']->active) echo 'checked'; ?>>
				Используется 
			  </label>
			</div>
			<div class="radio">
			  <label>
				<input type="radio" name="active" value="0" <?php if(!$vars['session']->active) echo 'checked'; ?>>
				Не используется
			  </label>
			</div>	
			
			<button type="submit" class="btn btn-default">Изменить</button>
            <a href="<?php echo KELLY_ROOT_URL; ?>joy/sessiondelete?sessionid=<?php echo $vars['session']->id ?>">Удалить</a>


		<?php } else { ?>
			<button type="submit" class="btn btn-default">Создать</button>
		<?php } ?>
        
	</form>
 </div>
</div>

	
<?php if ($vars['message']) { ?>
<div class="panel panel-default">
	<div class="panel-body"> <?php echo $vars['message']; ?> </div>
</div>
<?php } ?>