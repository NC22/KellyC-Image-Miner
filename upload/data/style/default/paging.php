<?php defined('KELLY_ROOT') or die('Wrong URL'); ?>
<?php if ($arrows) { ?>
<ul class="pagination"><?php echo $arrows; ?>
</ul>
<?php } else { ?>
<li <?php if ($selected) { ?>class="active"<?php } ?>>
<a href="<?php echo $link . 'l=' . $var ?>"><?php echo $text; ?></a>
</li>
<?php } ?>