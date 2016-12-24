<?php
  if (!defined('KELLY_ROOT')) exit;
?>    

<script>
//this.previousSibling.previousSibling.innerHTML = this.value
function searchMatches(el){
      if (el.tagName != 'FORM') {
          while ((el = el.parentElement) && el.tagName != 'FORM') {
          	
          }
        
          if (!el || el.tagName != 'FORM') return false;
      }
	  
	  el.submit();
}
</script>

<div style="text-align : center; margin : 0 auto;" >
	<form enctype="multipart/form-data" method="post" action="<?php echo KELLY_ROOT_URL; ?>" id="image-search" >
	
	<p style="font-size : 16px; text-align : center;">Поиск картинок</p>
	<input type="hidden" name="formName" value="image_search">
	<input type="hidden" name="token_data" value="<?php echo Token::set('image_search'); ?>">
		
	<label class="search-from-file">
		<div class="search-button" >Поиск по файлу</div><!-- <?php echo $vars['max_size']; ?> -->
		<div class='input'></div>
		<input type="file" name="imagefile" onchange="searchMatches(this);"/>
	</label>
	
	
	<a href="#" onclick="toogleActiveBlock('search-by-url'); return false;" class="search-button">по ссылке</a>
	<a href="joy/searchbycolor" class="search-button">по цвету</a>
	
	<div id="search-by-url">
		<input type="text" id="image-search-url" placeholder="Ссылка на файл"  name="imageurl" value="" autocomplete=off/>
		<input class="search-button" type="submit" value="Поиск" onchange="searchMatches(this);"/>
	</div>
	</form>
</div>

<?php if (!empty($vars['message'])) { ?>
<div class="panel panel-default">
  <div class="panel-body">
    <?php echo $vars['message']; ?>
  </div>
</div>
<?php } ?>