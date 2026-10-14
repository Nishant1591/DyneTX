<?php
	if ( ! defined( 'ABSPATH' ) ) exit;

	$admin_buttons = array(
		array(
			'classes'    => 'button-secondary alignright svg-icon-button',
			'attr'       => ['target' => '_blank'],
			'href'       => 'https://whirlocal.io/get-started?_wl=186',
			'text'       => $this->get_svg( 'map-pin', 'icon' ).' Get a FREE WhirLocal Account',
			'echo'       => true,
			'avoid_keys' => []
		),
		array(
			'classes'    => 'button-secondary alignright svg-icon-button',
			'attr'       => ['target' => '_blank'],
			'href'       => 'https://www.paypal.me/xhynk/',
			'text'       => $this->get_svg( 'heart', 'icon' ).' Donate',
			'echo'       => true,
			'avoid_keys' => ['irdr','trey','river','fahn','whirlocal']
		),
		array(
			'classes'    => 'button-secondary alignright svg-icon-button',
			'attr'       => ['target' => '_blank'],
			'href'       => 'https://wordpress.org/support/plugin/content-mask',
			'text'       => $this->get_svg( 'help-circle', 'icon' ).' Help',
			'echo'       => true,
			'avoid_keys' => []
		),
		array(
			'classes'    => 'button-secondary alignright svg-icon-button',
			'attr'       => ['target' => '_blank'],
			'href'       => 'https://xhynk.com/content-mask/',
			'text'       => $this->get_svg( 'bookmark', 'icon' ).' Docs',
			'echo'       => true,
			'avoid_keys' => []
		),
		array(
			'classes'    => 'button-secondary alignright svg-icon-button',
			'attr'       => ['target' => '_blank'],
			'href'       => 'https://xhynk.com/',
			'text'       => $this->get_svg( 'mail', 'icon' ).' Contact',
			'echo'       => true,
			'avoid_keys' => []
		)
	);

	ob_start();
	
	foreach( $admin_buttons as $button ){
		$this->show_button( $button['classes'], $button['attr'], $button['href'], $button['text'], $button['echo'], $button['avoid_keys'] );
	}

	echo ob_get_clean();
?>