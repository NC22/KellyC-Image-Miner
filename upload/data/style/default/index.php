<?php
  if (!defined('KELLY_ROOT')) exit;
  if ($vars['mode'] == 'searchbycolor') $marginTop = 0;
  else $marginTop = 60;  
  
  // if ($vars['title'] != 'Без названия')
  $vars['title'] = 'Поиск картинок';
  
  if ($vars['mode'] == 'main') {
	
  } elseif ($vars['mode'] == 'searchbycolor') {
	$vars['title'] .= ' - поиск по цвету';
  }
?>    
<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <title><?php echo $vars['title']; ?></title>
		
		<link rel="stylesheet" href="<?php echo View::$themeUrl; ?>css/bootstrap.css" />
		<link rel="stylesheet" href="<?php echo View::$themeUrl; ?>css/style.css" />
		
		<meta name="author" content="NC22">
		
		<script src="<?php echo View::$themeUrl; ?>js/bootstrap-without-jquery.js"></script>
        <script src="<?php echo View::$themeUrl; ?>js/enviroment.js"></script>
        <base href="<?php echo KELLY_ROOT_URL; ?>"> 
        <script>var baseUrl = '<?php echo KELLY_ROOT_URL; ?>';</script>
        
        <?php if ($vars['mode'] == 'jobselect' or $vars['mode'] == 'searchbycolor') { ?>
            
            <script src="<?php echo View::$themeUrl; ?>js/ajax.js"></script>
        <?php } ?>
		<?php if ($vars['mode'] == 'searchbycolor') { ?>
            
            <script src="<?php echo View::$themeUrl; ?>js/html5kellycolorpicker.js"></script>
			<script src="<?php echo View::$themeUrl; ?>js/html5kellyhlpicker.js"></script>
			
        <?php } ?>
    </head>

    <body>
		<div class="container-fluid container-<?php echo $vars['mode']; ?>">
			
			<?php if ($vars['mode'] != 'main' and $vars['mode'] != 'searchbycolor') { ?>
			<nav class="navbar navbar-default navbar-fixed-top" role="navigation">

				<div class="container"> 
					<div class="navbar-header">
						<button class="navbar-toggle" data-target=".bs-navbar-collapse" data-toggle="collapse" type="button">

							<span class="sr-only"></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>

						</button>

						<a class="navbar-brand" href="/">Поиск по картинке</a>
					</div>

					<div class="collapse navbar-collapse bs-navbar-collapse">
						<ul class="nav navbar-nav navbar-left">
                            <li <?php if ($vars['mode'] == 'main') { ?>class="active"<?php } ?>><a href="<?php echo KELLY_ROOT_URL; ?>">Главная</a></li>
                            <li <?php if ($vars['mode'] == 'searchbycolor') { ?>class="active"<?php } ?>><a href="joy/searchbycolor">Поиск по цвету</a></li>
                            <?php if ($vars['user']) { ?>
                            <li <?php if ($vars['mode'] == 'jobselect') { ?>class="active"<?php } ?>><a href="joy/jobselect">Управление задачами</a></li>
                            <li <?php if ($vars['mode'] == 'materiallist') { ?>class="active"<?php } ?>><a href="joy/materiallist">Материалы</a></li>
							<li <?php if ($vars['mode'] == 'sessionlist') { ?>class="active"<?php } ?>><a href="joy/sessionlist">Сессии</a></li>
                            
                            <?php } ?>
						</ul>
                        <?php if ($vars['user']) { ?>
                        <ul class="nav navbar-nav navbar-right">
                            <li><a href="<?php echo KELLY_ROOT_URL; ?>/login/do-logout"> <?php echo $vars['user']->login; ?>  - Выход</a>
						</ul>
                        <?php } ?>
					</div>
				</div>
			</nav>
			<?php } ?>
			
			<div class="content" style="margin-top : <?php echo $marginTop; ?>px;">
            <?php if ($vars['mode'] == 'searchbycolor') { ?>
            
             <?php echo $vars['main']; ?>
             
            <?php } else { ?>
                <div class="container">     
                    <?php if ($vars['mode'] == 'main') { ?>
                        <div class="row">
                            <div class="col-md-12">
                                <?php echo $vars['upload_form']; ?>
                            </div>
                        </div>
                        <?php if ($vars['main']) { ?>
                        <div class="row">
                            <div class="col-md-12">
                                <?php echo $vars['main']; ?>
                            </div>
                        </div>	                
                    <?php } } else { ?>
                        <div class="row">
                            <div class="col-md-12">
                                <?php echo $vars['main']; ?>
                            </div>
                        </div>	
                    <?php } ?>
                    <?php if (!empty($vars['pagination'])) { ?>
                    <div class="row">
                        <div class="col-md-12">
                        
                            <div class="panel panel-default">
                            <div class="panel-body">
                                <?php echo $vars['pagination'] ?>
                            </div>
                            </div>	
                            
                        </div> 
                    </div>                    
                    <?php } ?>                
                </div>
            <?php }  ?>
            </div>
		
		<div class="container">
			<div class="row">
				<div class="col-md-12" style="color : #b3b3b3; text-align : center; padding-top : 24px;">
				Powered by kellyWebMiner v0.5 | <a href="https://github.com/NC22?tab=repositories" style="text-decoration : none; color : #a8a8a8;">NC22</a>
				</div>
			</div>
		</div>
	</body>
	
</html>