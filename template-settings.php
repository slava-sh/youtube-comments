<?php
/**
* Settings template for "YouTube Comments" plugin
*/
?>
<div class='wrap'>
	<div class="icon32" id="icon-options-general"><br></div>
	<h2>YouTube Comments Settings</h2>
	<form method='post' action='options.php'>
		<?php settings_fields('yc_settings_group'); ?>
		<?php do_settings_sections('yc_settings_page'); ?>
		<p class='submit'>
			<input id='submit' type='submit' name='submit' class='button-primary' value='<?php esc_attr_e("Save Changes"); ?>' />
		</p>
	</form>
</div>