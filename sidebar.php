<div id="news">
<div class="newsItem">
<h3>TurboCSV</h3>
<p><a href="<?php echo bloginfo('url')."/turbocsv"?>" >Check out TurboCSV</a>, the Excel import plugin for WordPress.
<a style="border-style:none" href="<?php echo bloginfo('url')."/turbocsv"?>" >
<img width="240px" src="<?php echo bloginfo('wpurl')."/wp-content/uploads/TurboCSVBox320.png"?>" style="border-style: none;float: right" alt="Buy TurboCSV" /></a>
<br/><b>Version 2.0 Now Available</b> with tons of new features!
</p>
<p class="button"><a href="http://wpplugins.com/plugin/145/turbocsv">Get TurboCSV Now!</a></p>
<br/>

<ul>
  <?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar() ) : ?>
    <?php
		global $notfound;
		if (is_page() and ($notfound != '1')) {
			$current_page = $post->ID;
			while($current_page) {
				$page_query = $wpdb->get_row("SELECT ID, post_title, post_status, post_parent FROM $wpdb->posts WHERE ID = '$current_page'");
				$current_page = $page_query->post_parent;
			}
			$parent_id = $page_query->ID;
			$parent_title = $page_query->post_title;
			// if ($wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_parent = '$parent_id' AND post_status != 'attachment'")) {
			if ($wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_parent = '$parent_id' AND post_type != 'attachment'")) {
    ?>
    <li>
      <h3><?php echo $parent_title; ?><?php _e('Subpages'); ?></h3>
      <ul>
        <?php wp_list_pages('sort_column=menu_order&title_li=&child_of='. $parent_id); ?>
      </ul>
    </li>
    <?php } } ?>
    <li>
      <h3><?php _e('Categories'); ?></h3>
      <ul>
        <?php wp_list_cats('sort_column=name&optioncount=1&hierarchical=0'); ?>
      </ul>
    </li>
	<li>
		<h3>Recent Entries</h1>
		<ul>
			<?php query_posts('showposts=5'); ?>
			<?php while (have_posts()) : the_post(); ?>
				<li><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php _e('Permanent link to'); ?> <?php the_title(); ?>"><?php the_title(); ?></a> - <?php the_time('m-d-Y') ?></small></li>
			<?php endwhile;?>
		</ul>
	</li>
    <li>
    	<h3>Recent Comments</h1>
		<ul>
			<?php include (TEMPLATEPATH . '/simple_recent_comments.php');?>
		<?php if (function_exists('src_simple_recent_comments')) { src_simple_recent_comments(5, 60, '', ''); } ?>
		</ul>
	</li>
	 <li>
	      <h3><?php _e('Archives'); ?></h3>
	      <ul>
	        <?php wp_get_archives('type=monthly'); ?>
	      </ul>
    </li>
  <?php endif; ?>
 </ul>
 <h3>MapPress Poll</h3>
<!-- <p class="button"><a href="http://wordpress.org/extend/plugins/mappress-google-maps-for-wordpress/">Download MapPress Now!</a></p>  -->
<p class="button"><a href="http://wordpress.org/extend/plugins/mappress-google-maps-for-wordpress/">Get MapPress Now!</a></p>

<script type="text/javascript" language="javascript" charset="utf-8" src="http://static.polldaddy.com/p/1792171.js"></script><noscript>
<a href="http://answers.polldaddy.com/poll/1792171/">What would you like to see in MapPress 2.0?</a><span style="font-size:9px;">(<a href="http://www.polldaddy.com">survey software</a>)</span>
</noscript>

</div>
	<div style="clear:both;"></div>
</div>
</div>
