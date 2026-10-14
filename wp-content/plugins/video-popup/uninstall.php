<?php if ( !defined('WP_UNINSTALL_PLUGIN') ) {
    exit;
}

delete_option('video_popup_settings');
delete_option('video_popup_settings_version');

delete_option('video_popup_settings_onpage_load');
delete_option('video_popup_settings_onpage_load_version');

delete_option('video_popup_settings_first_use');
delete_option('video_popup_settings_admin_notify');