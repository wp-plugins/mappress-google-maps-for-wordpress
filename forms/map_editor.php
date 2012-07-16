<?php
	// Polygon values
	$weights = array();
	for ($i = 1; $i <= 20; $i++)
		$weights[$i] = $i . "px";

	$opacities = array();
	for ($i = 100; $i >= 0; $i-= 10)
		$opacities[$i] = $i . "%";
?>

<div id='mapp_e_links'>
	<a target='_blank' style='vertical-align: middle;text-decoration:none'  href='http://wphostreviews.com/mappress'>
		<img alt='MapPress' title='MapPress' src='<?php echo Mappress::$baseurl . '/images/mappress_logo_small.png'; ?>' />
	</a>
	<?php echo Mappress::get_support_links(); ?>
</div>

<div id='mapp_e_adjust' style='display:none'>
	<table>
		<tr>
			<td><b><?php _e('Map ID', 'mappress');?>: </b></td>
			<td><span id='mapp_e_mapid'></span></td>
		</tr>
		<tr>
			<td><b><?php _e('Title');?>: </b></td>
			<td><input id='mapp_e_title' type='text' size='40' /></td>
		</tr>
		<tr>
			<td><b><?php _e('Size', 'mappress');?>:</b></td>
			<td>
				<?php
					foreach($options->mapSizes as $i => $size) {
						echo ($i > 0) ? " | " : "";
						$wh = $size['width'] . 'x' . $size['height'];
						echo "<a href='#' class='mapp-edit-size' title='$wh'>$wh</a>";
					}
				?>
				<input type='text' id='mapp_width' size='2' value='' /> x <input type='text' id='mapp_height' size='2' value='' />
			</td>
		</tr>
	</table>

	<input class='button-primary' type='button' id='mapp_save_btn' value='<?php _e('Done', 'mappress'); ?>' />
	<a href='#' id='mapp_e_recenter'><?php _e('Center map', 'mappress'); ?></a>

	<div id='mapp_e_add_poi_panel'>
		<b><?php _e('New POI', 'mappress') ?>: </b>
		<input size='50' type='text' id='mapp_e_saddr' />
		<input class='button-primary' type='button' id='mapp_e_add_poi' value='<?php _e('Add', 'mappress'); ?>' />

		<?php if (Mappress::$options->geolocation) : ?>
			<a href='#' id='mapp_myloc'><?php _e('My location', 'mappress'); ?></a>
		<?php endif; ?>

		<div id='mapp_e_saddr_err' style='display:none'></div>
	</div>
</div>

<div id='mapp_e_maplist'>
	<p>
		<b><?php _e('Current Maps', 'mappress')?></b>
		<input class='button-primary' type='button' id='mapp_e_add_map' value='<?php _e('New Map', 'mappress')?>' />
	</p>

	<div id='mapp_maplist'></div>
</div>

<div id='mapp_e_edit_map'>
	<table style='border-collapse:collapse;'>
		<tr>
			<td style='vertical-align:top;'>
				<div id='mapp_e_poi_list'></div>
			</td>

			<td style='vertical-align:top'>
				<div id='mapp_edit' class='mapp-e-canvas mapp-canvas-shadow'></div>
				<div>
					<?php _e('Click map for lat/lng: ', 'mappress'); ?>
					<span id='mapp_e_latlng'>0,0</span>
				</div>
			</td>
		</tr>
	</table>
</div>

<div style='display:none'>
	<div id='mapp_e_infobox'>
		<div id='mapp_e_poi_fields'>
			<div>
				<input id='mapp_e_poi_title' type='text' />
				<input id='mapp_e_poi_iconid' type='hidden' />
				<img id='mapp_e_poi_icon' class='mapp-icon' src='<?php echo Mappress::$baseurl . '/images/cleardot.gif';?>' />
			</div>

			<div id='mapp_e_poi_polyline_fields' style='display: none;'>
				<?php _e("Line: ", 'mappress'); ?>
				<input type='text' size='7' id='mapp_stroke_color' class='color'/>
				<?php echo Mappress_Settings::dropdown($weights, '', '', array('id' => 'mapp_stroke_weight', 'title' => __('Weight', 'mappress')) ); ?>
				<?php echo Mappress_Settings::dropdown($opacities, '', '', array('id' => 'mapp_stroke_opacity', 'title' => __('Opacity', 'mappress')) ); ?>
			</div>

			<div id='mapp_e_poi_polygon_fields' style='display: none;'>
				<?php _e("Fill: ", 'mappress'); ?>
				<input type='text' size='7' id='mapp_fill_color' />
				<?php echo Mappress_Settings::dropdown($opacities, '', '', array('id' => 'mapp_fill_opacity', 'title' => __('Opacity', 'mappress')) ); ?>
			</div>

			<div id='mapp_e_poi_kml_fields' style='display: none'>
				<input id='mapp_e_poi_kml_url' type='text' readonly='readonly'/>
			</div>

			<div>
				<textarea id='mapp_e_poi_body' rows='10' style='width:99%'></textarea>
			</div>
			<div>
				<input id='mapp_e_save_poi' class='button-primary' type='button' value='<?php _e('Done', 'mappress'); ?>' />
				<input id='mapp_e_cancel_poi' class='button' type='button' value='<?php _e('Cancel', 'mappress'); ?>' />
			</div>
		</div>

		<div id='mapp_e_poi_icon_picker'>
			<?php
				if (class_exists('Mappress_Icons'))
					echo Mappress_Icons::get_icon_picker();
			?>
		</div>
	</div>
</div>