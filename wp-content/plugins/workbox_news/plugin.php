<?php
/**
 * Plugin Name: Workbox News (gcs-web.com parser)
 * Version:     1.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

class WorkboxNews {
	public function __construct() {
		register_activation_hook(__FILE__, array($this, 'activate'));
		register_deactivation_hook(__FILE__, array($this, 'deactivate'));

		add_action('init', array($this, 'registerPostType'));
		add_filter('save_post_news', array($this, 'saveNews'));
		add_action( 'admin_enqueue_scripts', array($this, 'enqueue_date_picker' ));

		add_filter( 'manage_news_posts_columns', array($this, 'managePostColumns') );
		add_filter( 'manage_news_posts_custom_column', array($this, 'showCustomPostColumns'), 10, 2 );

		add_shortcode('homepage_news', array($this, 'doHomepageNews'));
add_action ('show_homepage_news', array($this, 'doHomepageNewsAction'));

        add_action ( 'admin_menu', array ($this,'setOptionsMenu') );
        add_action ( 'admin_init', array ($this,'saveOptions'), 10, 0 );

        add_action('init', array($this, 'refreshData'), 90);
        add_shortcode('news', array($this, 'doShortcodeForm'));

		add_action( 'pre_get_posts', array($this, 'doRebuildQueryParams'));

        add_filter('the_content', array($this, 'rebuildContent'));


		add_action('generate_rewrite_rules', array($this, 'dateArchivesRewriteRules'));

		add_action('init',array($this,'addCustomUrlRule'));

		add_filter('header_post_id', array($this, 'getNewsPagePostID'));

	}

    public function refreshData() {
        if (isset($_GET['clearNewsTransient'])) {
            delete_transient('wb_news_xml_update_process');
        }
        
        if (get_transient('wb_news_xml_update_process'))
           return;

        set_transient('wb_news_xml_update_process', 1, 300);
        $last_ct = get_option('wb_xml_news_last_cache_time');
        $client_id = get_option('wb_xml_news_client_id');

        if ($last_ct+60*get_option('wb_xml_news_cache_minutes')< time() && $client_id != '') {
            $url = 'https://clientapi.gcs-web.com/data/'.$client_id.'/News';
            $file = @file_get_contents ($url);
            $file = @json_decode($file);

            if (isset($file->data) && is_array($file->data) && sizeof($file->data) > 0) {
                update_option('wb_xml_news_last_cache_time', time());

                foreach ($file->data as $item) {
                    $date = date('Y-m-d H:i:s', strtotime ($item->releaseDate->date));
                    $title = $item->title;
                    $excerpt = $item->teaser;
                    $description = '';
                    $hostedUrl = $item->link->hostedUrl;

                    if (isset($item->body[0]->link->url)) {
                        $details = @json_decode(@file_get_contents($item->body[0]->link->url));
                        if (isset($details->data)) {
                            $description = $details->data;
                        }
                    }

                    $news_id = $item->id;

                    $the_query = new WP_Query(array(
                        'post_type'=>'news',
                        'meta_key'=>'news_external_id',
                        'meta_value'=>$news_id
                    ));

                    if (!$the_query->have_posts()) {
                        $new_post_id = wp_insert_post(array(
                            'post_content'=>$description,
                            'post_title'=>$title,
                            'post_status'=>'publish',
                            'post_type'=>'news',
                            'post_excerpt'=>$excerpt,
                        ));
                    } else {
                        $new_post_id = $the_query->posts[0]->ID;

                        wp_update_post(
                            array(
                                'ID'=>$new_post_id,
                                'post_content'=>$description,
                                'post_title'=>$title,
                                'post_status'=>'publish',
                                'post_type'=>'news',
                                'post_excerpt'=>$excerpt,
                            )
                        );
                    }

                    update_post_meta($new_post_id, 'news_content_type', 1);
                    update_post_meta($new_post_id, 'news_external_id', $news_id);
                    update_post_meta($new_post_id, 'news_url', $hostedUrl);

                    if ($date != '')
                        $ts = strtotime($date);
                    else
                        $ts = time();

                    update_post_meta($new_post_id, 'news_date', $ts);

                }
            }
        }

        delete_transient('wb_news_xml_update_process');
    }

    public function setOptionsMenu() {
        add_submenu_page( 'edit.php?post_type=news', 'Options', 'Options', 'manage_options', 'wb_news_xml2', array($this, 'showOptionsPage'));
    }

    /**
     * Shows options page
     */
    public function showOptionsPage() {
        ?>
        <h1>News XML Feed Options</h1>
        <?php
        if (isset($_GET['wbmessage'])) {
            echo '<div id="message" class="updated" style="margin-left: 0"><p>'.urldecode($_GET['wbmessage']).'</p></div><br>';
        }
        ?>
        <form name="options_form" method="post">
            <?php wp_nonce_field('saveXMLNewsOptions'); ?>
            <table border="0">
                <tr>
                    <td align="right"></b>News to show on homepage</b>:</td>
                    <td>
                        <input type="text" name="wb_xml_news_items_amount" value="<?php echo get_option('wb_xml_news_items_amount','') ?>" size="5">
                    </td>
                </tr>
                <tr>
                    <td align="right"></b>Caching Period (in minutes)</b>:</td>
                    <td>
                        <input type="text" name="wb_xml_news_cache_minutes" value="<?php echo get_option('wb_xml_news_cache_minutes','') ?>" size="50">
                    </td>
                </tr>
                <tr>
                    <td align="right"></b>API Key</b>:</td>
                    <td>
                        <input type="text" name="wb_xml_news_client_id" value="<?php echo get_option('wb_xml_news_client_id','') ?>" size="50">
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td><input type="submit" class="button button-primary" value="Update Options"></td>
                </tr>
            </table>
        </form>
        <?php
    }

    public function saveOptions() {
        $nonce = isset($_POST['_wpnonce'])?$_POST['_wpnonce']:'';
        if (wp_verify_nonce($nonce, 'saveXMLNewsOptions')) {
            update_option ( 'wb_xml_news_items_amount', intval(isset($_POST['wb_xml_news_items_amount'])?$_POST['wb_xml_news_items_amount']:0) );
            update_option ( 'wb_xml_news_cache_minutes', intval(isset($_POST['wb_xml_news_cache_minutes'])?$_POST['wb_xml_news_cache_minutes']:0) );
            update_option ( 'wb_xml_news_client_id', (isset($_POST['wb_xml_news_client_id'])?$_POST['wb_xml_news_client_id']:0) );

            wp_redirect ( 'edit.php?post_type=news&page=wb_news_xml2&wbmessage=Updated' );
            die ();
        }

    }

public function doHomepageNewsAction() {
        $html = '';

	$count = get_option('wb_xml_news_items_amount','');
        $list = new WP_Query(
            array(
                'post_type'      => 'news',
                'post_status'    => 'publish',
                'order'          => 'DESC',
                'orderby'        => 'meta_value_num',
                'meta_key'       => 'news_date',
                'posts_per_page' => $count
            )
        );

        if ($list->have_posts()) {
        foreach ($list->posts as $item) {
                    switch(get_post_meta($item->ID, 'news_content_type', true)) {
                        case 0: $url = get_permalink($item); $target = '_self'; break;
                        case 1: $url = get_post_meta($item->ID, 'news_url', true); $target = '_blank'; break;
                        case 2: $url = get_post_meta($item->ID, 'news_file', true); $target = '_blank'; break;
                        default: $url = get_permalink($item); $target = '_self';
                    }
        $html .= '
							<div class="section-news-item">
								<p class="section-news-date">'.date('F d, Y', get_post_meta($item->ID, 'news_date', true)).'</p>
								<a href="'.$url.'" class="section-news-item-title" target="'.$target.'">'.$item->post_title.'</a>
								<a href="'.$url.'" class="btn btn-primary btn-lg" target="'.$target.'">Read More</a>
							</div>';
        };
/*            $html.= '
<section class="section news">
            <div class="fl-container">
                <div class="col-md-6 col-title-oric text-orange">
                    In the news
                </div>
                <div class="col-md-6 max-w-550">';
                foreach ($list->posts as $item) {
                $target = '_self';
                    switch(get_post_meta($item->ID, 'news_content_type', true)) {
                        case 0: $url = get_permalink($item); break;
                        case 1: $url = get_post_meta($item->ID, 'news_url', true); break;
                        case 2: $url = get_post_meta($item->ID, 'news_file', true); break;
                        default: $url = get_permalink($item);
                    }
	            //$url = get_permalink($item);
                $html .= '
                        <div class="item-1">
                            <div class="date has-underline">'.date('F d, Y', get_post_meta($item->ID, 'news_date', true)).'</div>
                            <p class="text-small"><a href="'.$url.'" target="'.$target.'">'.$item->post_title.'</a></p>
                        </div>';
            }
                   
                $html.= '</div>
            </div>
        </section>';
*/
    }

    echo $html;
}

	public function doHomepageNews($atts = '') {
	    $html = '';

	    $count = get_option('wb_xml_news_items_amount','');
        $list = new WP_Query(
            array(
                'post_type'      => 'news',
                'post_status'    => 'publish',
                'order'          => 'DESC',
                'orderby'        => 'meta_value_num',
                'meta_key'       => 'news_date',
                'posts_per_page' => $count
            )
        );

        if ($list->have_posts()) {
            $html.= '
            <script>
                function doShowNewsItem(url, target) {
                    if (target != "_blank") {
                        window.location.href = url;
                    } else {
                         var win = window.open(url, "_blank");
                        win.focus();
                    }
                }
            </script>
            
                
                    
                    ';
            foreach ($list->posts as $item) {
                $target = '_self';
                /*switch(get_post_meta($item->ID, 'news_content_type', true)) {
                    case 0: $url = get_permalink($item); break;
                    case 1: $url = get_post_meta($item->ID, 'news_url', true); $target = '_blank'; break;
                    case 2: $url = get_post_meta($item->ID, 'news_file', true); $target = '_blank'; break;
                    default: $url = get_permalink($item);
                }*/
	            $url = get_permalink($item);
                $html .= '<div class="col-12 col-lg-4 news-card wow fadeInUp fast" animation-name="fadeInUp" data-wow-delay="0.2s">
					<div class="card">
						<div class="card-body">

                            <div class="date has-underline">'.date('F d, Y', get_post_meta($item->ID, 'news_date', true)).'</div>
                            <p class="text-small"><a href="'.$url.'" target="'.$target.'">'.$item->post_title.'</a></p>
                        </div></div></div></div>';
            }
            $html.= '
                   
                
           
            ';
        }

	    return $html;
    }

	private function getShortcodePage() {
		global $wpdb;
		$sql = 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_type = "page" AND post_status="publish" AND post_content LIKE "%[news]%"';
		$id = $wpdb->get_var($sql);
		$page = get_post($id);
		return $page;
    }

    public function getNewsPagePostID($post_id) {
        global $post;
        if (isset($post->post_type) && $post->post_type == 'news') {
            $p = $this->getShortcodePage();
            if (isset($p->ID))
                return $p->ID;
        }

        return $post_id;
    }

	public function addCustomUrlRule() {
	    $page = $this->getShortcodePage();
	    if ($page) {
            add_rewrite_rule('^(' . preg_quote($page->post_name) . ')/(newsyear)/([^/]*)/?', 'index.php?pagename=$matches[1]&wb_news_year=$matches[3]');

            add_filter('query_vars', function ($vars) {
                $vars[] = 'wb_news_year';
                return $vars;
            });
        }
	}

	public function doShortcodeForm($atts = '') {
		global $wp;
		$year = isset($wp->query_vars['wb_news_year'])?intval($wp->query_vars['wb_news_year']):0;

		if ($year == 0) {
			$list = new WP_Query(
				array(
					'post_type'      => 'news',
					'post_status'    => 'publish',
					'order'          => 'DESC',
					'orderby'        => 'meta_value_num',
					'meta_key'       => 'news_date',
					'posts_per_page' => 10
				)
			);
		} else {
			$list = new WP_Query(
				array(
					'post_type'      => 'news',
					'post_status'    => 'publish',
					'order'          => 'DESC',
					'orderby'        => 'meta_value_num',
					'meta_key'       => 'news_date',
					'nopaging'       => true,
					'meta_query'=>array(
						'key'     => 'news_date',
						'value'   => array( mktime(0,0,0,1,1,$year), mktime(23,59,59,12,31,$year)),
						'type'    => 'numeric',
						'compare' => 'BETWEEN',
					)
				)
			);
		}
		$html = '';
		$selectYears = '';
		for ($i = 0; $i < 5; $i++) {
		    $selected = '';
		    if (date('Y', strtotime('-'.$i.' year')) == $year) {
                $selected = ' selected="selected" ';
            }
			$selectYears .= '<option'.$selected.'>'.date('Y', strtotime('-'.$i.' year')).'</option>';
        }
		if ($list->have_posts()) {
		    $html .= '
		        <h1 class="text-center">PRESS RELEASES</h1>
                <div class="filter-container space-bot-md">
                    <div class="flex-container">
                        <div class="label">Year</div>
                        <div class="select">
                            <select id="news-filter-select">
                                '.$selectYears.'
                            </select>
                        </div>
                        <div class="btn-container"><button id="news-filter-button" class="btn btn-primary">Filter</button></div>
                        <script>
                            jQuery(document).ready(function(){
                                jQuery("#news-filter-button").click(function() {
                                    window.location.href = "'.get_site_url().'/press-releases/newsyear/" + jQuery("#news-filter-select option:selected").text();
                                });	
                            });
                        </script>
                    </div>
                </div>
		    ';
			foreach ($list->posts as $p) {
				$url = '';
				$target = '_self';
				/*switch(get_post_meta($p->ID, 'news_content_type', true)) {
					case 0: $url = get_permalink($p); break;
					case 1: $url = get_post_meta($p->ID, 'news_url', true); $target = '_blank'; break;
					case 2: $url = get_post_meta($p->ID, 'news_file', true); $target = '_blank'; break;
					default: $url = get_permalink($p);
				}*/
				$url = get_permalink($p);
				$html .= '
				    <div class="release-item-md">
                        <div class="release-head">
                            <div class="date">'.date('F d | Y', get_post_meta($p->ID, 'news_date', true)).'</div>
                            ';
				if (get_post_meta($p->ID, 'news_content_type', true) == 2) {
					$html .= '
				            <p><a class="link-pdf" href="'.$url.'" target="'.$target.'"><img src="/wp-content/themes/rapt/images/ico-pdf.png" /></a></p>
				    ';
                }
				$html .= '
                        </div>
                        <div class="release-content">
                            <div class="title"><a href="'.$url.'" target="'.$target.'">'.$p->post_title.'</a></div>
                        </div>
                    </div>
                ';
			}
		}

		return $html;
	}

	public function doShortcodeFormOld($atts = '') {
	    global $wp;
		$year = isset($wp->query_vars['wb_news_year'])?intval($wp->query_vars['wb_news_year']):0;

		if ($year == 0) {
			$list = new WP_Query(
				array(
					'post_type'      => 'news',
					'post_status'    => 'publish',
					'order'          => 'DESC',
					'orderby'        => 'meta_value_num',
					'meta_key'       => 'news_date',
					'posts_per_page' => 10
				)
			);
		} else {
			$list = new WP_Query(
				array(
					'post_type'      => 'news',
					'post_status'    => 'publish',
					'order'          => 'DESC',
					'orderby'        => 'meta_value_num',
					'meta_key'       => 'news_date',
					'nopaging'       => true,
                    'meta_query'=>array(
	                    'key'     => 'news_date',
	                    'value'   => array( mktime(0,0,0,1,1,$year), mktime(23,59,59,12,31,$year)),
	                    'type'    => 'numeric',
	                    'compare' => 'BETWEEN',
                    )
				)
			);
        }
		$html = '';
		if ($list->have_posts()) {
            $html.= '<h1 class="text-center">News'.($year != 0?' Archive. Year '.$year:'').'</h1>';
            foreach ($list->posts as $p) {
                $url = '';
                $target = '_self';
                switch(get_post_meta($p->ID, 'news_content_type', true)) {
                    case 0: $url = get_permalink($p); break;
                    case 1: $url = get_post_meta($p->ID, 'news_url', true); $target = '_blank'; break;
                    case 2: $url = get_post_meta($p->ID, 'news_file', true); $target = '_blank'; break;
                    default: $url = get_permalink($p);
                }
                $html.= '
                '.date('F d,Y', get_post_meta($p->ID, 'news_date', true)).'<br>
                <a href="'.$url.'" target="'.$target.'"><b>'.$p->post_title.'</b></a><br><br>';
            }
		}

		$html.= $this->showYearsList();

		return $html;
	}


	public function activate() {}

	public function deactivate() {}

	public function registerPostType() {
		$labels = array(
				'name' => 'News',
				'singular_name' => 'News Item',
				'menu_name' => 'News',
				'name_admin_bar' => 'News',
				'add_new' => 'Add New Item',
				'add_new_item' => 'Add New Item',
				'new_item' => 'New Item',
				'edit_item' => 'Edit Item',
				'view_item' => 'View Item',
				'all_items' => 'All News',
				'search_items' => 'Search News',
				'parent_item_colon' => 'Parent News:',
				'not_found' => 'No News found.',
				'not_found_in_trash' => 'No News found in Trash.',
		);

		$args = array(
				'labels' => $labels,
				'public' => true,
				'publicly_queryable' => true,
				'show_ui' => true,
				'show_in_menu' => true,
				'capability_type' => 'post',
				'has_archive' => false,
				'hierarchical' => false,
				'menu_position' => null,
				'menu_icon' => 'dashicons-screenoptions',
				'supports' => array('title', 'editor', 'custom-fields'),
				'register_meta_box_cb'=> array($this, 'metaboxFieldsAddNews')
		);

		register_post_type('news', $args);
	}

	public function metaboxFieldsAddNews() {
		add_meta_box ( 'news_meta_left', 'Additional Parameters', array (
				$this,
				'showMetaBoxNewsLeft'
		), 'news', 'side' );
	}

	public function showMetaBoxNewsLeft($post, $metabox) {
		global $post;

		$date = get_post_meta($post->ID, 'news_date', true);
		if ($date == false)
			$date = time();
		$content_type = get_post_meta($post->ID, 'news_content_type', true);
		$url = get_post_meta($post->ID, 'news_url', true);
		$file = get_post_meta($post->ID, 'news_file', true);

		echo '<label>Date: <input type="text" class="datepicker" value="'.esc_attr(date('F j, Y',$date)).'" name="news_date"></label>
			<script>
				jQuery(document).ready(function(){
					jQuery(".datepicker").datepicker();	
				});
			</script>
				';
		echo '<br><br><b>Content type:</b><br>
				<label><input type="radio" name="news_content_type" value="0" '.($content_type == 0?'checked':'').'> HTML Page</label><br>
				<label><input type="radio" name="news_content_type" value="1" '.($content_type == 1?'checked':'').'> External URL</label><br>
				<label><input type="radio" name="news_content_type" value="2" '.($content_type == 2?'checked':'').'> File</label><br><br>
		';
		echo '<b>External URL</b><br><input type="text" name="news_url" value="'.esc_attr($url).'" style="width: 100%;"><br><br>';
		echo '<b>File</b><br>'.$this->showUploadControl('news_file', $file);
	}

	public function enqueue_date_picker(){
		wp_enqueue_script(
				'field-date-js',
				'/Field_Date.js',
				array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker'),
				time(),
				true
				);

		wp_enqueue_style( 'jquery-ui-datepicker' );

		$wp_scripts = $GLOBALS['wp_scripts'];
		wp_enqueue_style('plugin_name-admin-ui-css',
				'http://ajax.googleapis.com/ajax/libs/jqueryui/' . $wp_scripts->registered['jquery-ui-core']->ver . '/themes/smoothness/jquery-ui.css',
				false,
				false,
				false);
	}

	public function saveNews($post_ID, $post = false, $update = false) {
		if (isset($_POST['action']) && $_POST['action'] == 'editpost') {
			$date = $_POST['news_date'];
			if ($date != '')
				$ts = strtotime($date);
			else
				$ts = time();

			update_post_meta($post_ID, 'news_date', $ts);
			update_post_meta($post_ID, 'news_content_type', $_POST['news_content_type']);
			update_post_meta($post_ID, 'news_url', $_POST['news_url']);
			update_post_meta($post_ID, 'news_file', $_POST['news_file']);
		}
	}

	public function managePostColumns( $defaults ) {
		unset($defaults['date']);
		$defaults['newsdate'] = 'News Date';
		return $defaults;
	}

	public function showCustomPostColumns( $column, $post_id ) {
		switch ($column) {
			case 'newsdate' :
				echo date('F j, Y', get_post_meta($post_id, 'news_date', true));
				break;
			default:
		}
	}

	public function doRebuildQueryParams($query) {
		if (isset($query->query['post_type']) && $query->query['post_type'] == 'news' && $query->is_main_query()) {
			$query->set('order', 'DESC');
			$query->set('orderby', 'meta_value_num');
			$query->set('meta_key', 'news_date');
		}

		return $query;
	}

	public function rebuildContent($content) {
		global $post, $wp_query;

		if (isset($post->post_type) && $post->post_type == 'news' && is_single()) {

			$html = '
            <h1 class="text-center">'.get_the_title().'</h1><br>
            <b>'.date('F d, Y', get_post_meta($post->ID, 'news_date', true)).'</b><br><br>
            '.wpautop(get_the_content()).'
            <br><br><a href="'.get_permalink($this->getShortcodePage()).'newsyear/'.date('Y', get_post_meta($post->ID, 'news_date', true)).'/">« back to news page</a>';
			$content = $html;
		}

		return $content;
	}

	public function showYearsList() {
		global $post, $wpdb;
		$html = '';
        $dates = $wpdb->get_results("
                SELECT $wpdb->postmeta.meta_value
                FROM $wpdb->postmeta
                WHERE $wpdb->postmeta.meta_key = 'news_date'",
                ARRAY_A);

        $aYears = array();

        foreach ($dates as $date) {
            $year = date('Y', $date['meta_value']);
            if (!in_array($year, $aYears))
                $aYears[] = $year;
        }

        rsort($aYears);

        $selected_year = defined('WB_NEWS_YEAR')?WB_NEWS_YEAR:date('Y');

        $page = $this->getShortcodePage();

        $html.='
        <div id="news_archive">Archive: ';
        $aTmp = array();
        foreach ($aYears as $year) {
            $aTmp[] = '<a href="'.get_permalink($page).'newsyear/'.$year.'" class="'.($selected_year == $year?'selected':'').'">'.$year.'</a>';
        }
        $html.= implode(' | ', $aTmp);

        $html.= '</div>';


		return $html;
	}

	public function dateArchivesRewriteRules($wp_rewrite) {
		$rules = $this->generateDateArchives('news', $wp_rewrite);
		$wp_rewrite->rules = $rules + $wp_rewrite->rules;
		return $wp_rewrite;
	}

	public function generateDateArchives($cpt, $wp_rewrite) {
		$rules = array();

		$post_type = get_post_type_object($cpt);
		$slug_archive = $post_type->has_archive;
		if ($slug_archive === false) return $rules;
		if ($slug_archive === true) {
			$slug_archive = $post_type->name;
		}

		$dates = array(
				array(
						'rule' => "([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})",
						'vars' => array('year', 'monthnum', 'day')),
				array(
						'rule' => "([0-9]{4})/([0-9]{1,2})",
						'vars' => array('year', 'monthnum')),
				array(
						'rule' => "([0-9]{4})",
						'vars' => array('year'))
		);

		foreach ($dates as $data) {
			$query = 'index.php?post_type='.$cpt;
			$rule = $slug_archive.'/'.$data['rule'];

			$i = 1;
			foreach ($data['vars'] as $var) {
				$query.= '&'.$var.'='.$wp_rewrite->preg_index($i);
				$i++;
			}

			$rules[$rule."/?$"] = $query;
			$rules[$rule."/feed/(feed|rdf|rss|rss2|atom)/?$"] = $query."&feed=".$wp_rewrite->preg_index($i);
			$rules[$rule."/(feed|rdf|rss|rss2|atom)/?$"] = $query."&feed=".$wp_rewrite->preg_index($i);
			$rules[$rule."/page/([0-9]{1,})/?$"] = $query."&paged=".$wp_rewrite->preg_index($i);
		}

		return $rules;
	}

	private function showUploadControl($field, $value = '', $width = 200) {
		$html = '
		<input type="text" id="custom_control_' . $field . '" name="' . $field . '" value="' . esc_attr ( $value ) . '" readonly style="width: 100%;">
		
		<input type="button" class="button" value="Select/Upload File" id="custom_control_' . $field . '_button">
		<input type="button" class="button" value="Clear" onclick="$(\'#custom_control_' . $field . '\').val(\'\'); ">
		<script>
			var $ = jQuery.noConflict();
	
			jQuery(document).ready(function($){
				var _custom_media_' . $field . ' = true,
				_orig_send_attachment_' . $field . ' = wp.media.editor.send.attachment;
	
				$("#custom_control_' . $field . '_button").click(function(e) {
					var send_attachment_bkp = wp.media.editor.send.attachment;
					var button = $(this);
					var id = button.attr("id").replace("_button", "");
					_custom_media_' . $field . ' = true;
					wp.media.editor.send.attachment = function(props, attachment){
						if ( _custom_media_' . $field . ' ) {
							sReplacement = "' . get_bloginfo ( 'url' ) . '";
							sImageURL = attachment.url.toString().replace(sReplacement, "");
							$("#" + id).val(sImageURL);
						} else {
							return _orig_send_attachment_' . $field . '.apply( this, [props, attachment] );
						};
					}
	
					wp.media.editor.open(button);
					return false;
				});
	
				$(".add_media").on("click", function(){
					_custom_media_' . $field . ' = false;
				});
			});
	
			function parseURL' . $field . '(url) {
				var a = document.createElement("a");
				a.href = url;
				return {
					source : url,
					protocol : a.protocol.replace(":", ""),
					host : a.hostname,
					port : a.port,
					query : a.search,
					params : (function() {
						var ret = {}, seg = a.search.replace(/^\?/, "").split("&"), len = seg.length, i = 0, s;
						for (; i < len; i++) {
							if (!seg[i]) {
								continue;
							}
							s = seg[i].split("=");
							ret[s[0]] = s[1];
						}
						return ret;
					})(),
					file : (a.pathname.match(/\/([^\/?#]+)$/i) || [ , "" ])[1],
					hash : a.hash.replace("#", ""),
					path : a.pathname.replace(/^([^\/])/, "/$1"),
					relative : (a.href.match(/tps?:\/\/[^\/]+(.+)/) || [ , "" ])[1],
					segments : a.pathname.replace(/^\//, "").split("/")
				};
			}
		</script>';
		return $html;
	}
}

new WorkboxNews();