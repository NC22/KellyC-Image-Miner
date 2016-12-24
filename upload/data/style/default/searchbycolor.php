<?php defined('KELLY_ROOT') or die('Wrong URL'); 
	$showResult = false;
	
    $colors = empty($_POST['color']) ? array() : $_POST['color'];
    $tags = empty($_POST['tags']) ? '' :  $_POST['tags'];
	$saturation = empty($_POST['saturation']) ? 1 :  (float) $_POST['saturation'];
	$censored = empty($_POST['censored']) ? false : true;
	
	if ($saturation > 1) $saturation = 1;
	if ($saturation < 0) $saturation = 0;
	
	if ($colors or $tags) $showResult = true;
	
	// autocomplite form by get data 
	
	if (!$colors) {
		$colors = empty($_GET['cl']) ? array() : $_GET['cl'];
	}
	
	if (!$tags) {
		$tags = empty($_GET['t']) ? '' :  $_GET['t'];
	}
	
	if (!$saturation) {
		$saturation = empty($_GET['s']) ? 1 :  (float) $_GET['s'];	
	}
?>

<script src="data/style/default/js/imagegrid.js"></script>

<script>
 // defult image grid 800x250 
 var imgGrid = new KellyImageGrid({blockName : 'search-image-block', width : 800, height : 250});
 var jobDelete = false;
 
 function deleteFromSearch(id) {
	if (jobDelete) return;
	var imageBlock = document.getElementById('search-image-' + id);
	var token = getById('delete-search-token').value;
	
	if (!imageBlock) return;
	
    var event = function(response) {
        
        if (response['token']) {
            getById('delete-search-token').value = response['token'];
        }
        
        if (response['code'] === 0) {
            imageBlock.parentNode.removeChild(imageBlock);
        } 
        
        jobDelete = false;
    }
    
    //encodeURIComponent(text) 
    var send = 'ajaxQuery=1' + '&id=' + id; 
    sendByXmlHttp('joy/deletefromsearch', send, event, token);
    
    return false;
 }
 
  function deleteFromTag(id, tag) {
	if (jobDelete) return;
	
	var imageBlock = document.getElementById('search-image-' + id);
	var token = getById('delete-search-token').value;
	
	if (!imageBlock) return;
	if (!tag) return;
	
    var event = function(response) {
        
        if (response['token']) {
            getById('delete-search-token').value = response['token'];
        }
        
        if (response['code'] === 0) {
            imageBlock.parentNode.removeChild(imageBlock);
        } 
        
        jobDelete = false;
    }
    
    var send = 'ajaxQuery=1' + '&id=' + id + '&tag=' + encodeURIComponent(tag); 
    sendByXmlHttp('joy/deletefromtag', send, event, token);
    
    return false;
 }
 
 function updateShareLink() {
	
	var share = document.getElementById('search-by-color-share');
	var linkInput = share.getElementsByTagName('INPUT')[0];
	
	var url = '?s=' + encodeURIComponent(parseFloat(document.getElementById('saturation-input').value).toFixed(1));
	
    for (var i = 0; i < 3; ++i) {
		var color = document.getElementById('search-color' + i).value;
		if (color) url += '&cl[]' + '=' + encodeURIComponent(color);
	}
	
	var tags = document.getElementById('tags-input').value;
	if (tags)
	url += '&t=' + encodeURIComponent(tags);
    
    var censored = document.getElementById('censored-input');
    if (censored.checked) url += '&censored=1';
	
	linkInput.value = linkInput.getAttribute('aHref') + url;
 }
 
 function expandGrid(el) {
 
    if (!imgGrid.isFullscreen()) {
        imgGrid.setFullscreen(true);
        el.innerHTML = 'Свернуть';
    } else {
        imgGrid.setFullscreen(false);
        el.innerHTML = 'На весь экран';
    }
    
    imgGrid.updateGrid();
    return false;
 }
 
 function continueSearch(from) {
 
	document.getElementById('from-input').value = from;
	
	var form = document.getElementById('search-form');
		form.submit();
		
	return false;
 }

</script>

<!-- todo ajax version -->

<div id="search-color-show-button" class="<?php echo !$showResult ? '' : 'block-active'; ?>">
	<a href="#" onclick="toogleActiveBlock('search-color-show-button'); toogleActiveBlock('search-color-form-container'); return false;" class="search-button" >Показать поиск</a>
    <a href="#" onclick="expandGrid(this); return false;" class="search-button" >На весь экран</a>		
	
		<a href="#" onclick="continueSearch(<?php echo $vars['from']-100; ?>); return false;" class="search-button">Назад (к <?php echo $vars['from']-100; ?>)</a>
	
	<?php if ($vars['continue']) { ?>

		<a href="#" onclick="continueSearch(<?php echo $vars['continue']; ?>); return false;" class="search-button">Еще (с <?php echo $vars['continue']; ?>)</a>

	<?php } ?>

</div>

<div id="search-color-form-container" class="<?php echo $showResult ? '' : 'block-active'; ?>">
    <form id="search-form" method="post" action="<?php echo KELLY_ROOT_URL; ?>joy/searchbycolor">
        <input type="hidden" name="formName" value="searchbycolor">
        <input type="hidden" name="token_data" value="<?php echo Token::set('searchbycolor'); ?>">
		<input id="saturation-input" type="hidden" name="saturation" value="<?php echo $saturation; ?>">
        <div class="saturation">
            <div>Насыщенность (Saturation)</div>
            
            <div class="saturation-controll">
                <a href="#" onclick=" picker.setS(0.1, true); updateShareLink(); document.getElementById('saturation-input').value = picker.getS(); document.getElementById('saturation').innerHTML = picker.getS(true) + '%'; return false;">+</a>
                <a href="#" onclick="picker.setS(-0.1, true); updateShareLink(); document.getElementById('saturation-input').value = picker.getS(); document.getElementById('saturation').innerHTML = picker.getS(true) + '%'; return false;">-</a>
            </div>
            
            <div id="saturation"><?php echo (int) ($saturation * 100); ?>%</div>
        </div>
        
        <canvas id="picker"></canvas>
        <br>
		<input id="censored-input" type="checkbox" name="censored" <?php echo $censored ? 'checked' : '' ?>> Безопасный поиск
        <br>
		<input id="from-input" type="text" name="from" placeholder="Начать с">
        <br>
		<input id="tags-input" type="text" class="search-tags" onchange="updateShareLink();" name="tags" placeholder="Ключевые слова через запятую" value="<?php echo $tags; ?>" autocomplete="off"><br>
        
        <input class="search-button" id="search-submit" type="submit" value="Поиск">
		<a href="#" onclick="toogleActiveBlock('search-by-color-share'); updateShareLink(); return false;" class="search-button">Поделиться</a>
						
		<a href="<?php echo KELLY_ROOT_URL; ?>" class="search-button">На главную</a><br>
		<div id="search-by-color-share">
			<input type="text" name="share" aHref="<?php echo KELLY_ROOT_URL; ?>joy/searchbycolor" value="">
		</div>
        <?php 
			$colorsBlock = 'none';
			for ($i = 0; $i < 3; $i++) { 
			if (empty($colors[$i])) $colors[$i] = ''; 
			else $colorsBlock = 'block';
			
        } ?>
		
		<div id="selected-colors-block" style="display : <?php echo $colorsBlock; ?>;">
		
			<?php for ($i = 0; $i < 3; $i++) { ?>
				<input class="search-color" id="search-color<?php echo $i; ?>" name="color[]" value="<?php echo $colors[$i]; ?>" autocomplete="off" readonly>
			<?php } ?>
		
		</div>
    </form>
</div>

 <form method="post" action="<?php echo KELLY_ROOT_URL; ?>" id="delete-search" >
	<input type="hidden" name="formName" value="delete_fromsearch">
	<input id="delete-search-token" type="hidden" name="token_data" value="<?php echo Token::set('delete_fromsearch'); ?>">
 </form>
 
<div id="search-results-scaled" style="margin-top : 12px;">
</div>
 
<div id="search-results">
	<?php echo $vars['images']; ?>
</div>
<div style="text-align : center;">
	<a href="#" onclick="continueSearch(<?php echo $vars['from']-100; ?>); return false;" class="search-button">Назад</a>
<?php if ($vars['continue']) { ?>
	<a href="#" onclick="continueSearch(<?php echo $vars['continue']; ?>); return false;" class="search-button">Далее</a>

<?php } ?>
</div>	
<script>
	var picker = new KellyHlPicker({
		place : 'picker', 
		input : ['search-color0', 'search-color1', 'search-color2'], 
		size : 350,	
        chunks : 12,
		userEvents : {
			updateinput : function(handler, input, manualEnter) {
			
                updateShareLink();
				
                var checkSelected = handler.getSelected(false);
                var isSelected = false;
                
                for (var i = 0; i < checkSelected.length; i++) {
                    if (checkSelected[i]) {
                        isSelected = true; break;
                    }
                }
                
                if (isSelected) {
					document.getElementById('selected-colors-block').style.display = 'block';
                   // document.getElementById('search-submit').style.display = 'inline-block';
                } else {
					document.getElementById('selected-colors-block').style.display = 'none';
                   // document.getElementById('search-submit').style.display = 'none';
                }
                
				if (handler.getSelected(input) === false) {
				
					input.value = '';
					//input.className = 'search-color';
					input.style.backgroundColor = 'transparent';
					return false;
				} else {
					input.value = handler.getSelectedHex(input);
				}
				
				return true;
			},
		}
	});
	
	picker.setS(<?php echo $saturation; ?>, false);
	
	/*
	var colorpickers = KellyColorPicker.attachToInputByClass('search-color', {
		alpha_slider : false, 
		size : 150,
		userEvents : {
			change : function(self) { 
				if(!self.getInput().value) return;
				
				var input = self.getInput();
					input.value = self.getCurColorHex(); 
				if(self.getCurColorHsv().v < 0.5){
						input.style.color = "#FFF";
				} else {
						input.style.color = "#000";
				}

				input.style.background = self.getCurColorHex();						
			}, 
			updateinput : function(self, input, manualEnter) {
				return false;
			},
			popupshow : function(self, e) {
				if(!self.getInput().value) return false;
				return true;
			}
		}
	});
	*/
</script>
