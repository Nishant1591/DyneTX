<?php
/*
 Plugin Name: Company Announcements
 Author: Workbox Inc.
 Version: 1.0
*/

class WorkboxCompanyAnnouncements {
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'registerNewPostsTypes'));
        add_action( 'pre_get_posts', array($this, 'doRebuildQueryParams'));

        add_filter('add_archive_custom_page', array($this, 'addBreadCrumbsArchivePage'));

        add_shortcode('wb_our_stories', array($this, 'doShortCode'));
        add_shortcode('wb_our_stories_list', array($this, 'doShortCode2'));
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
    
    public function doShortCode2() {

    $page = intval(get_query_var('page'));
		if ($page == 0) $page = 1;
		$per_page = 3;
    $fid = 0;
		$fpost = false;
    
		global $post;	
		$html = '';
    
		$args = array(
				'post_type'=>'comp_announcements',
				'post_status'=>'publish',
				'posts_per_page'=>'3',
				'paged'=>$page,
		);
    
    $list = new WP_Query($args);
    
		if ($list->have_posts()) {
			$GLOBALS['wb_comp_announcements'] = array(
					'type'=>'comp_announcements',
					'list'=>$list->posts,
					'page'=>$page,
					'per_page'=>$per_page,
					'fid'=>$fid,
					'total'=>sizeof($total->posts)
			);
		}
		
		if (isset($GLOBALS['wb_comp_announcements'])) {
			$html.= '
			<div class="container">
    			<div class="divider-2"></div>
				';
			
				foreach ($GLOBALS['wb_comp_announcements']['list'] as $p) {
					//$small_image = get_post_meta($p->ID, '_small_image', true);
          $small_image = wp_get_attachment_image_src( get_post_thumbnail_id($p->ID), 'large');
          $small_image = $small_image[0];
          //var_dump($p); die;
					$html.= '
					<div class="st-item">
				        <div class="st-pic-2"><a href="'.get_permalink($p->ID).'"><div class="pic-container" style="background-image: url(\''.$small_image.'\');"></div></a></div>
				        <div class="st-content">
				          <div class="st-title"><a href="'.get_permalink($p->ID).'">'.$p->post_title.'</a></div>
				          <div class="st-text">'.apply_filters('the_content',$p->post_excerpt).'</div>
				          <div class="st-signature"><a href="'.get_permalink($p->ID).'">'.($p->post_type == 'patient_stories'?$p->post_title:'Learn More').'</a></div>
				        </div>
					</div>';
				}
				
				$pages = ceil($GLOBALS['wb_comp_announcements']['total']/$GLOBALS['wb_comp_announcements']['per_page']);
				
				$html.= '<div class="pagination">';
				
				if ($GLOBALS['wb_comp_announcements']['page'] > 1) {
					$html.= '<a href="'.get_permalink($post->ID).($GLOBALS['wb_comp_announcements']['page']-1).'/">&lt;</a>';
				}
				
				for ($i = $GLOBALS['wb_comp_announcements']['page']-2; $i <= $GLOBALS['wb_comp_announcements']['page']+2; $i++) {
					if ($i>=1 && $i<= $pages) {
						$html.= '<a href="'.get_permalink($post->ID).$i.'/" '.($GLOBALS['wb_comp_announcements']['page'] == $i?'class="active"':'').'>'.$i.'</a>';
					}
				}
				
				if ($GLOBALS['wb_comp_announcements']['page'] < ($pages)) {
					$html.= '<a href="'.get_permalink($post->ID).($GLOBALS['wb_comp_announcements']['page']+1).'/">&gt;</a>';
				}
				
			    $html.= '</div>
			</div>'; 
		}
		
		return $html;
    }

    public function doShortCode() {
        $html = '<div class="plates updates">
                <div class="md-flex-container flex-wrap">';

        $filter = $this->__getFilter();

        $html.= '
        <!--<form method="get" id="filterForm" class="filter-form">
				
						<div class="col-xs-6 col-md-4 col-lg-3">
							<div>Sort By:</div> 
							<div class="select">
								<select name="sortby">
									<option value="date" '.($filter['sortby'] == 'date'?'selected':'').'>Date</option>
									<option value="title" '.($filter['sortby'] == 'title'?'selected':'').'>Title</option>
								</select>
							</div>
						</div>

						<div class="col-xs-6 col-md-4 col-lg-3">
							<div>Order:</div>
							<div class="select">
								<select name="order">
										<option value="desc" '.($filter['order'] == 'desc'?'selected':'').'>DESC</option>
										<option value="asc" '.($filter['order'] == 'asc'?'selected':'').'>ASC</option>
								</select>
							</div>
						</div>
						
						<div class="btn-line">
							<button class="btn btn-primary" type="submit" value="Show">Show</button>
						</div>
						
						
       </form>-->
        ';

        $html.= $this->__showAnnouncements();

        $html.= apply_filters('getPatientStories', '');
        $html.= apply_filters('getScienceStories', '');

        $html.= '</div></div>';

        return $html;
    }

    private function __showAnnouncements() {
        $html = '';

        $list = get_posts(
            array(
                'post_type'=>'comp_announcements',
                'post_status'=>'publish',
                'posts_per_page'=> '3',
                'nopaging'=>true
            )
        );

        if (is_array($list) && sizeof($list) > 0) {
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

        if ($post->post_type == 'comp_announcements') {
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
            'name' => 'Company Announcements',
            'singular_name' => 'Company Announcement',
            'menu_name' => 'Company Announcements',
            'name_admin_bar' => 'Company Announcements',
            'add_new' => 'Add New Company Announcement',
            'add_new_item' => 'Add New Company Announcement',
            'new_item' => 'New Company Announcement',
            'edit_item' => 'Edit Company Announcement',
            'view_item' => 'View Company Announcement',
            'all_items' => 'All Company Announcements',
            'search_items' => 'Search Company Announcements',
            'parent_item_colon' => 'Parent Company Announcements:',
            'not_found' => 'No Company Announcements found.',
            'not_found_in_trash' => 'No Company Announcements found in Trash.',
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

        register_post_type('comp_announcements', $args);
    }

    public function doRebuildQueryParams($query) {
        if (isset($query->query['post_type']) && $query->query['post_type'] == 'comp_announcements') {
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

new WorkboxCompanyAnnouncements();