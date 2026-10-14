<?php

/*
Plugin Name: WPEngine Cache Cleaner
Author: Artemy Kudryavtsev (Workbox Inc.)
Version: 1.0
*/

class WorkboxWPEngineCacheClear {

    public function __construct() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        add_action ( 'admin_menu', [$this,'setOptionsMenu']);
        add_action ( 'admin_init', [$this,'saveOptions'], 10, 0 );
        
        add_action( 'add_meta_boxes',[$this, 'addPageMetabox'] );
        add_action( 'save_post', [$this, 'savePageMetabox'] );
    }

    public function addPageMetabox() {
        $postTypes = $this->getPostTypes();
        foreach ($postTypes as $key=>$name) {
            add_meta_box( 'page_options_'.$key, 'WPEngine Cache Options', array($this, 'showCacheMetabox'), $key, 'side', 'low' );
        }
    }

    public function showCacheMetabox($post) {
        $aValues = get_option('workbox_wpengine_cache_options');
        $key = $post->post_type;
        ?>
        <label>
            <input type="checkbox" name="_clear_wp_cache" value="1" <?php echo (isset($aValues[$key]) && $aValues[$key] == 1)?'checked':''?>>
            Clear Post Cache after update
        </label>
        <?php
    }

    public function savePageMetabox( $post_id ) {
        if (isset($_POST['_clear_wp_cache']) && $_POST['_clear_wp_cache'] == 1 && class_exists('wpecommon')) {
            wpecommon::purge_varnish_cache($post_id);
        }
    }

    public function activate() {}

    public function deactivate() {}

    public function setOptionsMenu() {
        add_options_page( 'WPEngine Cache Options', 'WPEngine Cache Options', 'manage_options', 'wpenginecache', array($this, 'showOptionsPage'));
    }

    private function getPostTypes() {
        $aPosts = [
            'post'=>'Blog Posts',
            'page'=>'Site Page',
        ];

        $customTypes = get_post_types(['public'   => true, '_builtin' => false,]);

        if (is_array($customTypes)) {
            foreach ($customTypes as $key=>$ctype) {
                $post_type_obj = get_post_type_object( $key );
                $aPosts[$key] = $post_type_obj->labels->singular_name;
            }
        }

        return $aPosts;
    }

    public function showOptionsPage(){
        $aPosts = $this->getPostTypes();

        $aValues = get_option('workbox_wpengine_cache_options');

        ?><div class="wrap">
        <h2>WPEngine Cache Options</h2>
        <h3>Types of posts for which automatic cache cleaning will be enabled</h3>
        <?php
            if (!class_exists('wpecommon')) {
                ?>
                <h3 style="color: red">Warning! This site is not hosted by WPEngine. This plugin will not work</h3>
                <?php
            }
        ?>
        <form method="post" >
            <?php wp_nonce_field('workboxwpenginecache'); ?>
            <table border="0" cellpadding="10">
                <?php
                foreach ($aPosts as $key=>$name) {
                    ?>
                    <tr>
                        <td>
                            <label>
                                <input type="checkbox" name="workbox_wpengine_cache_options[<?php echo $key; ?>]" value="1" <?php
                                    echo isset($aValues[$key]) && $aValues[$key] == 1?'checked':'';
                                ?>>
                                <?php echo $name; ?>
                            </label>
                        </td>
                    </tr>
                    <?php
                }
                ?>
                <tr>
                    <td><input type="submit" class="button button-primary" value="Update Options"></td>
                </tr>
            </table>
        </form>
        </div><?php
    }

    public function saveOptions() {
        $nonce = isset($_POST['_wpnonce'])?$_POST['_wpnonce']:'';
        if (wp_verify_nonce($nonce, 'workboxwpenginecache')) {
            $aPosts = $this->getPostTypes();
            $aTmp = array();


            foreach ($aPosts as $key=>$name) {
                if (isset($_POST['workbox_wpengine_cache_options'][$key])) {
                    $aTmp[$key] = 1;
                } else {
                    $aTmp[$key] = 0;
                }
            }

            update_option('workbox_wpengine_cache_options', $aTmp);

            wp_redirect ( 'options-general.php?page=wpenginecache&wbmessage=Updated' );
            die ();
        }
    }
}

new WorkboxWPEngineCacheClear();