<form action='#'>
	<div>
		<a href='#' class='mapp-travelmode mapp-travelmode-on' title='<?php _e('By car', 'mappress'); ?>'><span class='mapp-dir-icon mapp-dir-car'></span></a>
		<a href='#' class='mapp-travelmode' title='<?php _e('Walking', 'mappress'); ?>'><span class='mapp-dir-icon mapp-dir-walk'></span></a>
		<a href='#' class='mapp-travelmode' title='<?php _e('Bicycling', 'mappress'); ?>'><span class='mapp-dir-icon mapp-dir-bike'></span></a>
	</div>


	<div class='mapp-route'>
		<?php if (Mappress::$options->geolocation) : ?>
			<a href='#' class='mapp-myloc'><?php _e('My location', 'mappress'); ?></a>
		<?php endif; ?>

		<div>
			<span class='mapp-dir-icon mapp-dir-a'></span>
			<input class='mapp-dir-saddr' tabindex='1'/>
			<a href='#' class='mapp-dir-swap'><span class='mapp-dir-icon mapp-dir-arrows' title='<?php _e ('Swap start and end', 'mappress'); ?>'></span></a>

		</div>
		<div class='mapp-dir-saddr-err'></div>

		<div>
			<span class='mapp-dir-icon mapp-dir-b'></span>
			<input class='mapp-dir-daddr' tabindex='2'/>
		</div>
		<div class='mapp-dir-daddr-err'></div>
	</div>

	<p>
		<input type='submit' class='mapp-dir-get' value='<?php _e('Get Directions', 'mappress'); ?>'/>
		<input type='button' class='mapp-dir-print' value='<?php _e('Print', 'mappress'); ?>'/>
		<input type='button' class='mapp-dir-close' value ='<?php _e('Close', 'mappress'); ?>'/>
		<span class='mapp-spinner' style='display:none'></span>
	</p>
</form>

<div class='mapp-dir-renderer'></div>