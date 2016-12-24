<?php
  if (!defined('KELLY_ROOT')) exit;
?>    
<script>
function selectJobContainer(el) {
    var li = el.parentNode;
    var target = '';
    
    if (!target) target = 'modal';
    
    allLi = document.getElementsByClassName('selectjob-menu');
        
    for (var i=0; i <= allLi.length-1; i++) { 
        allLi[i].className = allLi[i].className.replace('active', '');
        target = allLi[i].getAttribute('targetItem');
        target = getById(target);
        if (target) target.style.display = 'none';
    };  
    
    target = li.getAttribute('targetItem');
    target = getById(target);
    if (target) target.style.display = 'block';
        
    li.className += ' active';
}
</script>

<div class="col-md-3">
    <div class="panel panel-default">
        <div class="panel-heading">Общие параметры</div>
        <div class="panel-body">
            <div class="form-group">
			<label for="timeout-time">Таймаут при ошибке (сек)</label>
			<input name="timeout" class="form-control" id="timeout-time" placeholder="Кол-во" value="40">
		  </div>        
      </div>
    </div>
    <div class="panel panel-default">
        <div class="panel-heading">Список работ</div>
        <div class="panel-body">
          <ul class="nav nav-pills nav-stacked">
            <li class="selectjob-menu" targetItem="custom-job-container" ><a href="#" onclick="selectJobContainer(this); return false;">Произвольная работа</a></li>
            <li class="selectjob-menu" targetItem="image-job-container"><a href="#" onclick="selectJobContainer(this); return false;">Скачивание картинок</a></li>
            <li class="selectjob-menu active" targetItem="page-job-container"><a href="#" onclick="selectJobContainer(this); return false;">Парсинг страниц</a></li>
            <li class="selectjob-menu" targetItem="hash-job-container"><a href="#" onclick="selectJobContainer(this); return false;">Вычисление хэша</a></li>
        </ul>                
      </div>
    </div>
</div>
<div class="col-md-9">
    <div class="panel panel-default job-container-hidden" id="custom-job-container">
        <div class="panel-heading">Произвольная работа</div>
        <div class="panel-body">
     
        <form method="post" action="<?php echo KELLY_ROOT_URL; ?>" id="custom-parse" >

            <input type="hidden" name="formName" value="custom_work">
            <input id="custom-parse-token" type="hidden" name="token_data" value="<?php echo Token::set('custom_work'); ?>">
           
            <div class="form-group">    
                <label class="col-sm-3 control-label">Состояние</label>                 
                <div id="custom-parse-state">Не запущена</div>
            </div> 
            <div class="form-group">
                <label for="custom-session">Сессия</label>

                <select id="custom-session" class="form-control" name="sessionid">
                  <?php echo $vars['session_select']; ?>
                </select>

            </div>         
            <div class="form-group">
                <label for="custom-parse-count">Кол-во записей за одно подключение</label>
                <input name="count" class="form-control" id="custom-parse-count" placeholder="Кол-во" value="100">
            </div>
            <div class="form-group">
                <label for="custom-parse-count">Начать с</label>
                <input name="from" class="form-control" id="custom-from" placeholder="С" value="0">
            </div>
            <div class="form-group">
                <label for="custom-parse-link">Скрипт</label>
                <input name="link" class="form-control" id="custom-parse-link" placeholder="Скрипт" value="<?php echo KELLY_ROOT_URL; ?>joy/getimagessize">
            </div>	
            <div class="form-group">    
                <label for="custom-parse-log" class="col-sm-3 control-label" >Ход выполнения</label>
                     
                <textarea id="custom-parse-log" class="kelly-textarea" style="width: 100%; height: 150px;"></textarea>

            </div> 		
            <button id="custom-parse-button" class="btn btn-default" onclick="startJobCustom(); return false;" style="min-width : 125px;">Начать</button>
            <button class="btn btn-default" onclick="getById('custom-parse-log').innerHTML =''; return false;" style="min-width : 125px;">Отчистить</button>
        </form>
      </div>
    </div>
    <div class="panel panel-default" id="page-job-container">
        <div class="panel-heading">Запуск работы - загрузка новых материалов</div>
        <div class="panel-body">
     
        <form method="post" action="<?php echo KELLY_ROOT_URL; ?>" id="page-parse" >

            <input type="hidden" name="formName" value="parse_pages">
            <input id="page-parse-token" type="hidden" name="token_data" value="<?php echo Token::set('parse_pages'); ?>">
           
            <div class="form-group">    
                <label class="col-sm-3 control-label">Состояние</label>                 
                <div id="page-parse-state">Не запущена</div>
            </div> 
            <div class="form-group">
                <label for="page-session">Сессия</label>

                <select id="page-session" class="form-control" name="sessionid">
                  <?php echo $vars['session_select']; ?>
                </select>

            </div>         
            <div class="form-group">
                <label for="page-parse-count">Кол-во страниц за одно подключение</label>
                <input name="count" class="form-control" id="page-parse-count" placeholder="Кол-во" value="100">
              </div>
            <div class="radio">
              <label>
                <input id="fail-page" type="radio" name="fail" value="1">
                Работа с недозагруженными страницами
              </label>
            </div>
            <div class="radio">
              <label>
                <input id="new-page" type="radio" name="fail" value="0" checked>
                Работа с новыми страницами
              </label>
            </div>		
            <div class="form-group">    
                <label for="page-parse-log" class="col-sm-3 control-label" >Ход выполнения</label>
                     
                <textarea id="page-parse-log" class="kelly-textarea" style="width: 100%; height: 150px;"></textarea>

            </div> 		
            <button id="page-parse-button" class="btn btn-default" onclick="startJobPageParse(); return false;" style="min-width : 125px;">Начать</button>
            <button class="btn btn-default" onclick="getById('page-parse-log').innerHTML =''; return false;" style="min-width : 125px;">Отчистить</button>
        </form>
      </div>
    </div>
    <div class="panel panel-default job-container-hidden" id="image-job-container">
        <div class="panel-heading">Запуск работы - загрузка картинок</div>
        <div class="panel-body">
     
        <form method="post" action="<?php echo KELLY_ROOT_URL; ?>" id="image-parse" >

            <input type="hidden" name="formName" value="download_images">
            <input id="image-parse-token" type="hidden" name="token_data" value="<?php echo Token::set('download_images'); ?>">
            
            <div class="form-group">    
                <label class="col-sm-3 control-label">Состояние</label>                 
                <div id="image-parse-state">Не запущена</div>
            </div>        
            <div class="form-group">
                <label for="page-session">Сессия</label>

                <select id="page-session" class="form-control" name="sessionid">
                  <?php echo $vars['session_select']; ?>
                </select>

            </div>        
            <div class="form-group">
                <label for="image-parse-count">Кол-во картинок за одно подключение</label>
                <input name="count" class="form-control" id="image-parse-count" placeholder="Кол-во" value="10">
              </div>
            <div class="radio">
              <label>
                <input id="fail-image" type="radio" name="fail" value="1">
                Работа с изображениями загруженными с ошибками
              </label>
            </div>
            <div class="radio">
              <label>
                <input id="new-image" type="radio" name="fail" value="0" checked>
                Работа с незагруженными изображениями
              </label>
            </div>	
			<div class="checkbox">
				<label>
				  <input type="checkbox" id="image-check-hash" name="checkHash" value="1" checked> Проверять хэш
				</label>
			</div>            
            <div class="form-group">    
                <label for="image-parse-log" class="col-sm-3 control-label" >Ход выполнения</label>
                     
                <textarea id="image-parse-log" class="kelly-textarea" style="width: 100%; height: 150px;"></textarea>

            </div> 		
            <button id="image-parse-button" class="btn btn-default" onclick="startJobImageParse(); return false;" style="min-width : 125px;">Начать</button>
            <button class="btn btn-default" onclick="getById('image-parse-log').innerHTML =''; return false;" style="min-width : 125px;">Отчистить</button>
        </form>
      </div>
    </div>

    <div class="panel panel-default job-container-hidden" id="hash-job-container"> 
        <div class="panel-heading">Запуск работы - обновление хэша на загруженых изображениях</div>
        <div class="panel-body">
     
        <form method="post" action="<?php echo KELLY_ROOT_URL; ?>" id="hash-parse" >

            <input type="hidden" name="formName" value="re_hash">
            <input id="hash-parse-token" type="hidden" name="token_data" value="<?php echo Token::set('re_hash'); ?>">
            
            <div class="form-group">    
                <label class="col-sm-3 control-label">Состояние</label>                 
                <div id="hash-parse-state">Не запущена</div>
            </div>         
            <div class="form-group">
                <label for="hash-parse-count">Кол-во картинок за одно подключение</label>
                <input name="count" class="form-control" id="hash-parse-count" placeholder="Кол-во" value="100">
              </div>
              
            <div class="form-group">    
                <label for="hash-parse-log" class="col-sm-3 control-label" >Ход выполнения</label>
                     
                <textarea id="hash-parse-log" class="kelly-textarea" style="width: 100%; height: 150px;"></textarea>

            </div> 		
            <button id="hash-parse-button" class="btn btn-default" onclick="startJobRehash(); return false;" style="min-width : 125px;">Начать</button>
            <button class="btn btn-default" onclick="getById('hash-parse-log').innerHTML =''; return false;" style="min-width : 125px;">Отчистить</button>
        </form>
      </div>
    </div>
</div>