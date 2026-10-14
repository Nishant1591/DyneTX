<?php
/*
 Plugin Name: Workbox Quotes
 Author: Workbox Inc.
 Version: 1.0
*/

class WorkboxQuotes {
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'registerNewPostsTypes'));
        add_action( 'pre_get_posts', array($this, 'doRebuildQueryParams'));

        add_filter('add_archive_custom_page', array($this, 'addBreadCrumbsArchivePage'));

        add_shortcode('wb_quote_indicators', array($this, 'doShortCode'));
        add_shortcode('wb_quote_list', array($this, 'doShortCode2'));
    }

    private function __getFilter() {
        $sortby = isset($_GET['sortby'])?$_GET['sortby']:'date';
        $order = isset($_GET['order'])?$_GET['order']:'DESC';

        if (!in_array($sortby, array('date','title')))
            $sortby = 'date';

        if (!in_array($order, array('desc','asc')))
            $order = 'desc';


        return array('sortby'=>$sortby, 'order'=>$order);
    }
    
    public function GetSidebar() {
      $args = array(
          'post_type'=>'wb_quotes',
          'post_status'=>'publish',
          /*'posts_per_page'=>'5',
          'paged'=>$page,*/
      );      
      
      $html = '';
      $list = new WP_Query($args);

		if ($list->have_posts()) {
			$GLOBALS['wb_comp_announcements'] = array(
					'type'=>'wb_quotes',
					'list'=>$list->posts,
					'page'=>$page,
					'per_page'=>$per_page,
					'fid'=>$fid,
					'total'=>count($list->posts)
			);
		}
      
      //var_dump(sizeof($list)); die;
      foreach ($GLOBALS['wb_comp_announcements']['list'] as $p) {
      $active = '';
      if (strpos(get_permalink($p->ID), $_SERVER['REQUEST_URI']) > 0) $active = ' class="current-menu-item" ';
      $html .= '<li '.$active.'><a href="'.get_permalink($p->ID).'">'.$p->post_title.'</a></li>';
        //$html .= $_SERVER['REQUEST_URI'].'@@@'.get_permalink($p->ID);
      }
      return $html;
    }
    
    public function doShortCode() {
      global $post;	
      $html = '';
      
      $args = array(
          'post_type'=>'wb_quotes',
          'post_status'=>'publish',
      );
      
      $list = new WP_Query($args);
      
      if ($list->have_posts()) {
        $GLOBALS['wb_comp_announcements'] = array(
            'type'=>'wb_quotes',
            'list'=>$list->posts,
            'page'=>$page,
            'per_page'=>$per_page,
            'fid'=>$fid,
            'total'=>count($list->posts)
        );
      }
      
      if (isset($GLOBALS['wb_comp_announcements'])) {
        $i = 0;
          foreach ($GLOBALS['wb_comp_announcements']['list'] as $p) {
            $small_image = wp_get_attachment_image_src( get_post_thumbnail_id($p->ID), 'large');
            $small_image = $small_image[0];
            $html .= '
								<li data-target="#empl-quotes" data-slide-to="'.$i.'" class="img-container active">
									<img src="/wp-content/themes/dynetxdev2020/images/square-shape.png" alt="Card image cap" style="background-image: url('.$small_image.')">
								</li>';
          $i++;
          }
      }
     return $html;
    }
    
    public function doShortCode2() {

    $page = intval(get_query_var('page'));
		if ($page == 0) $page = 1;
		$per_page = 5;
    $fid = 0;
		$fpost = false;
    
		global $post;	
		$html = '';
    
		$args = array(
				'post_type'=>'wb_quotes',
				'post_status'=>'publish',
				//'posts_per_page'=>'5',
				//'paged'=>$page,
		);
    
    $list = new WP_Query($args);
        
		if ($list->have_posts()) {
            //var_dump($list->posts);
			$GLOBALS['wb_comp_announcements'] = array(
					'type'=>'wb_quotes',
					'list'=>$list->posts,
					'page'=>$page,
					'per_page'=>$per_page,
					'fid'=>$fid,
					'total'=>count($list->posts)
			);
		}
		
		if (isset($GLOBALS['wb_comp_announcements'])) {
            $i = 0;
			foreach ($GLOBALS['wb_comp_announcements']['list'] as $p) {
				//$small_image = get_post_meta($p->ID, '_small_image', true);
                $small_image = wp_get_attachment_image_src( get_post_thumbnail_id($p->ID), 'large');
                $small_image = $small_image[0];
                //var_dump($p); die;
                $html .= '
							<div class="carousel-item '.($i == 0 ? ' active ' : '').'">
								<div class="row card">
									<div class="col-11 col-sm-5 ml-sm-auto">
										<div class="img-container rot-130">
											<img class="card-img-top" src="/wp-content/themes/dynetxdev2020/images/square-shape.png" alt="Card image cap" style="background-image: url('.$small_image.')">
										</div>
									</div>
									<div class="col-12 col-sm-6 card-body">
										<h4 class="card-title">'.$p->post_content.'</h4>
										<p class="card-subtitle">– '.$p->post_title.'</p>
									</div>
								</div>
							</div>';
                $i++;
			}
            return $html;
      }
    }
    
    
    private function __showAnnouncements() {
        $html = '';

        $list = get_posts(
            array(
                'post_type'=>'wb_quotes',
                'post_status'=>'publish',
                'posts_per_page'=> '3',
                'nopaging'=>true
            )
        );

        if (is_array($list) && count($list) > 0) {
            $html.= '
                    <h2 class="col-xs-12 col-md-12">Company Announcements</h2>
            ';

            foreach ($list as $item) {
                $post_image = '';
                $post_image =  wp_get_attachment_image_src( get_post_thumbnail_id( $item->ID ), 'full' );
                if (is_array($post_image))
                    $post_image = $post_image[0];

                $html.= '
                <div class="plate col-md-4 link-block">
                    <div class="pic" style="background-image:url('.$post_image.')" onclick="window.location.href=\''.get_permalink($item).'\'"></div>
                    <div class="title"><a href="'.get_permalink($item).'">'.$item->post_title.'</a></div>
                    <div class="text">'.apply_filters('the_content',$item->post_excerpt).'</div>
                    <div class="name"><a href="'.get_permalink($item).'">Learn More</a></div>
                </div>
                ';
            }
            
            $html .= '<div class="plate btn-line text-center space-bot" style="display: flex; flex-basis: 100%;"><a target="_blank" href="/company-announcements/" class="btn btn-primary">See all Company Announcements</a></div>';

        }

        return $html;
    }

    public function addBreadCrumbsArchivePage($aBreads) {
        global $wpdb, $post;
        $html = '';

        $shortcode = 'noshortcode';

        if ($post->post_type == 'wb_quotes') {
            $shortcode = 'wb_our_stories';
        }

        $page = $wpdb->get_row('SELECT * FROM '.$wpdb->posts.' WHERE post_content LIKE "%['.$shortcode.']%" AND post_parent = 0');

        if ($page) {
            $aBreads[] = array('title'=>$page->post_title, 'url'=>get_permalink($page->ID));
        }

        return $aBreads;
    }

    public function activate() {}

    public function deactivate() {}

    public function registerNewPostsTypes() {
        $labels = array(
            'name' => 'Quotes',
            'singular_name' => 'Quotes',
            'menu_name' => 'Quotes',
            'name_admin_bar' => 'Quotes',
            'add_new' => 'Add New Quote',
            'add_new_item' => 'Add New Quote',
            'new_item' => 'New Quote',
            'edit_item' => 'Edit Quote',
            'view_item' => 'View Quote',
            'all_items' => 'All Quotes',
            'search_items' => 'Search Quotes',
            'parent_item_colon' => 'Parent Quote:',
            'not_found' => 'No Quotes found.',
            'not_found_in_trash' => 'No Quotes found in Trash.',
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => null,
            'menu_icon' => 'dashicons-screenoptions',
            'supports' => array('title', 'editor', 'custom-fields', 'thumbnail', 'excerpt'),
            //'register_meta_box_cb'=> array($this, 'metaboxFieldsCompanyAnnouncements'),
        );

        register_post_type('wb_quotes', $args);
    }

    public function doRebuildQueryParams($query) {
        if (isset($query->query['post_type']) && $query->query['post_type'] == 'wb_quotes') {
            $filter = $this->__getFilter();
            if ($filter['sortby'] == 'date') {
                $query->set('orderby', 'date');
            } else {
                $query->set('orderby', 'title');
            }

            $query->set('order', $filter['order'] == 'asc'?'ASC':'DESC');

        }
        return $query;
    }


}

new WorkboxQuotes();