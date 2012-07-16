<?php
	$width = $map->width();
	$height = $map->height();
?>

<?php echo $map->get_show_link(); ?>
<table id="<?php echo $map->name . '_layout';?>" class="mapp-layout" style="<?php echo "width: $width; " . $map->get_layout_style(); ?>">
	<tr>
		<td>
			<div id="<?php echo $map->name . '_links';?>" class="mapp-map-links"><?php echo $map->get_links(); ?></div>
			<div id="<?php echo $map->name;?>" class="mapp-canvas" style="<?php echo "width: $width; height: $height; "; ?>">
				<span class="mapp-spinner-center"></span>
			</div>
		</td>
	</tr>
	<tr>
		<td>
			<div id="<?php echo $map->name . '_poi_list';?>" class="mapp-poi-list" style="width:100%">
				<span class="mapp-spinner-center"></span>
			</div>
		</td>
	</tr>
	<tr>
		<td>
			<div id="<?php echo $map->name . '_directions';?>" class="mapp-directions" style="width:100%">
				<span class="mapp-spinner-center"></span>
			</div>
		</td>
	</tr>
</table>