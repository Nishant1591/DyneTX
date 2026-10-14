<?php
/*
    Plugin Name: CSS default version fixer
    Author: Workbox Inc.
    Version: 1.0
	Description: Rerlaces CSS files version number to avoid cache issues when css-file updated
*/

class WorkboxCSSDefaultVersionFixer {
	public $wpver = '';
	public $wpverhash = '';

	public function __construct() {
		add_filter('style_loader_tag', array($this, 'checkCss'), 99, 4);
		$this->wpver = get_bloginfo( 'version' );

	}

	public function checkCss($tag, $handle = '', $href = '', $media = '') {
		$this->wpverhash = wp_hash($this->wpver);

		// get current WP version
		if (preg_match('/ver='.preg_quote($this->wpver, '/').'/', $tag) || preg_match('/ver='.preg_quote($this->wpverhash, '/').'/', $tag)) {
			$i = preg_match('/href=[\'"]{0,1}([^\'"]*)[\'"]{0,1}/', $tag, $matches);
			if ($i) {
				$link = preg_replace('/'.preg_quote(site_url(), '/').'/', '', $matches[1]);
				$link = explode( '?', $link );
				$link = $link[0];

				// now get file update time
				$time = @filemtime($_SERVER['DOCUMENT_ROOT'].$link);
				if (!$time) {
					$time = time().'-na';
				}

				$tag = preg_replace('/ver='.preg_quote($this->wpver, '/').'/', 'ver='.$time, $tag);
				$tag = preg_replace('/ver='.preg_quote($this->wpverhash, '/').'/', 'ver='.$time, $tag);
			}

			//echo '<!-- default: '.$_SERVER['DOCUMENT_ROOT'].$link.' -->';
		}

		return $tag;
	}
}

new WorkboxCSSDefaultVersionFixer();