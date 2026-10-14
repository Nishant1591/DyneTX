<?php
/*
 Plugin Name: Workbox Jobs
 Author: Workbox Inc.
 Version: 1.0
*/

class WorkboxJobs {
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'registerNewPostsTypes'));
        add_action( 'pre_get_posts', array($this, 'doRebuildQueryParams'));

        add_filter('add_archive_custom_page', array($this, 'addBreadCrumbsArchivePage'));

        //add_shortcode('wb_quote_indicators', array($this, 'doShortCode'));
        add_shortcode('wb_jobs_list', array($this, 'doShortCode'));
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
    
    
    public function doShortCode() {
      global $post;	
      $html = '';
      
      $args = array(
          'post_type'=>'wb_jobs',
          'post_status'=>'publish',
          'posts_per_page' => '999',
          'orderby' => 'date'
      );
      
      $list = new WP_Query($args);
      
      if ($list->have_posts()) {
        $GLOBALS['wb_comp_announcements'] = array(
            'type'=>'wb_jobs',
            'list'=>$list->posts,
            'page'=>$page,
            'per_page'=>$per_page,
            'fid'=>$fid,
            'total'=>sizeof($total->posts)
        );
      }
      
      $html = '<div class="container-fluid section positions">
			<div class="container">
				<div class="row">					
					<div class="col-12">
						<!--Accordion wrapper-->
						<div class="accordion md-accordion" id="accordionPos" role="tablist" aria-multiselectable="true">';
      
      if (isset($GLOBALS['wb_comp_announcements'])) {
        $i = 0;
          foreach ($GLOBALS['wb_comp_announcements']['list'] as $p) {
            $small_image = wp_get_attachment_image_src( get_post_thumbnail_id($p->ID), 'large');
            $small_image = $small_image[0];
            $html .= '<!-- Accordion card -->
							<div class="card">                
								<!-- Card header -->
								<div class="card-header" role="tab" id="headingOne'.$i.'">
									<a class="collapsed" data-toggle="collapse" data-parent="#accordionPos" href="#collapseOne'.$i.'" aria-expanded="true" aria-controls="collapseOne'.$i.'">
										<h5 class="mb-0">'.$p->post_title.'</h5>
										<div class="btn btn-primary btn-plus"><i></i></div>
									</a>
								</div>

								<!-- Card body -->
								<div id="collapseOne'.$i.'" class="collapse /show" role="tabpanel" aria-labelledby="headingOne'.$i.'"	data-parent="#accordionPos">
									'.$p->post_content.'
								</div>

							</div>
							<!-- Accordion card -->';
            /*$html .= '
								<li data-target="#empl-quotes" data-slide-to="'.$i.'" class="img-container active">
									<img src="/wp-content/themes/dynetxdev2020/images/square-shape.png" alt="Card image cap" style="background-image: url('.$small_image.')">
								</li>';*/                
          $i++;
          }
      }
      $html .= '</div>
						<!-- Accordion wrapper -->					
					</div>
				</div>
			</div>
		</div>';
     return $html;
    }


    public function activate() {}

    public function deactivate() {}

    public function registerNewPostsTypes() {
        $labels = array(
            'name' => 'Jobs',
            'singular_name' => 'Jobs',
            'menu_name' => 'Jobs',
            'name_admin_bar' => 'Jobs',
            'add_new' => 'Add New Job',
            'add_new_item' => 'Add New Job',
            'new_item' => 'New Job',
            'edit_item' => 'Edit Job',
            'view_item' => 'View Job',
            'all_items' => 'All Jobs',
            'search_items' => 'Search Jobs',
            'parent_item_colon' => 'Parent Job:',
            'not_found' => 'No Jobs found.',
            'not_found_in_trash' => 'No Jobs found in Trash.',
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

        register_post_type('wb_jobs', $args);
    }

    public function doRebuildQueryParams($query) {
        if (isset($query->query['post_type']) && $query->query['post_type'] == 'wb_jobs') {
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

new WorkboxJobs();