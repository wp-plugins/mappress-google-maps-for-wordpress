<?php
class Monster_Upgrade {

	var $basename;
	var $version;
	var $slug;
	var $prolink;
	var $beta_version;

	function monster_upgrade($basename, $slug, $version, $betalink = null, $prolink = null) {
		$this->basename = $basename;
		$this->version = $version;
		$this->slug = $slug;
		$this->betalink = $betalink;
		$this->prolink = $prolink;

		add_filter( 'site_transient_update_plugins', array(&$this, 'site_transient_update_plugins'));
	}

	// Block repository updates - will be replaced with custom notification
	function site_transient_update_plugins($value) {

		// For PRO versions, get updates from the home page
		if (isset($value->response[$this->basename]) && stristr($this->version, 'pro')) {
			unset($value->response[$this->basename]->package);
			if (!has_filter( "after_plugin_row_$this->basename" ))
				add_filter("after_plugin_row_$this->basename", array(&$this, 'after_plugin_row_pro'), 20);
			return $value;
		}

		// If an update is available pass it through
		if (isset($value->response[$this->basename]))
			return $value;

		// Check for a beta & notify
		$version = $this->max_beta_version($this->slug);

		if ($version) {
			if (!has_filter( "after_plugin_row_$this->basename" ))
				add_filter("after_plugin_row_$this->basename", array(&$this, 'after_plugin_row_beta'), 20);
		}

//      Code for automatic upgrades - dangerous because WP deletes files
//		if ($version) {
//			$beta = new stdClass;
//			$beta->slug = $this->slug;
//			$beta->new_version = $version;
//			$beta->url = "http://wordpress.org/extend/plugins/$this->slug/";
//			$beta->package = "http://downloads.wordpress.org/plugin/$this->slug.$version.zip";
//			$value->response[$this->basename] = $beta;
//		}

		return $value;
	}

	/**
	* Get the maximum beta version
	* Beta versions MUST have a "b" somewhere in the version
	*
	*/
	function max_beta_version() {
		// Only try to get beta versions once - the filter gets called five times
		if (isset($this->beta_version))
			return $this->beta_version;

		$this->beta_version = false;

		$remote = wp_remote_post("http://wordpress.org/extend/plugins/$this->slug/download/");
		if (is_wp_error($remote))
			return false;

		$array = array();
		$pregslug = str_replace('-','\-', $this->slug);
		preg_match_all("!http://downloads\.wordpress\.org/plugin/$this->slug\.(.*)\.zip!i", $remote['body'], &$array, PREG_PATTERN_ORDER);

		if (!is_array($array))
			return false;

		$versions = array_unique($array[1]);
		$current = $this->version;

		foreach ($versions as $version) {
			if (version_compare($version, $current, '>') > 0)
				$current = $version;
		}

		if ($current == $this->version)
			return false;

		$this->beta_version = $current;
		return $this->beta_version;
	}

	function after_plugin_row_pro($value) {
		echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update"><div class="update-message">';
		echo "A new PRO version is available for this plugin.  Get it here: <a href='$this->prolink'>$this->prolink</a>";
		echo '</div></td></tr>';
	}

	function after_plugin_row_beta($value) {
		echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update"><div class="update-message">';
		echo "A new BETA version is available for this plugin.  Get it here: <a href='$this->betalink'>$this->betalink</a>";
		echo '</div></td></tr>';
	}
} // Class Super_Upgrade
?>
