<?php
/*
 Plugin Name: Workbox Careers Plugin (Greenhouse Integration)
 Author: Artemy Kudryavtsev (Workbox Inc.)
 Version: 1.0
 Description: Careers Manager.
*/


class WorkboxJobsGreenhouse {
	private $options;

	public function __construct()    {
		register_activation_hook(__FILE__, array($this, 'activate'));
		register_deactivation_hook(__FILE__, array($this, 'deactivate'));

		add_action('init', array($this, 'registerNewPostsTypes'));
		add_filter('save_post_jobs', array($this, 'saveJob'));

        add_shortcode('workbox_jobs', array($this, 'doShortCodeGreenHouse'));


		add_filter( 'manage_jobs_posts_columns', array($this, 'managePostColumns') );
		add_filter( 'manage_jobs_posts_custom_column', array($this, 'showCustomPostColumns'), 10, 2 );

		add_action( 'pre_get_posts', array($this, 'doRebuildQueryParams'));

		add_filter('getJobsCategories', array($this, 'getCategories'));
		add_filter('getJobsPageID', array($this, 'getJobsPageID'));
		add_filter('getJobsCategoryURL', array($this, 'getJobsCategoryURL'));


		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );

		$this->options = get_option( 'wb_harvest_board_token' );

		add_action('init', array($this, 'importData'));


		add_filter('get_careers_back_link', array($this, 'getBackLink'));
		add_filter('get_careers_next_link', array($this, 'getNextLink'));


		add_filter('get_parent_post_id', array($this, 'getCareersPagePostID'));

        add_action('wp_footer', array($this, 'footer'));
	}

	private function getShortcodePage() {
		global $wpdb;
		$sql = 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_type = "page" AND post_status="publish" AND post_content LIKE "%[workbox_jobs]%"';
		$id = $wpdb->get_var($sql);
		$page = get_post($id);
		return $page;
	}

	public function getCareersPagePostID($post_id) {
		global $post;
		if (isset($post->post_type) && $post->post_type == 'jobs') {
			$p = $this->getShortcodePage();
			if (isset($p->ID))
				return $p->ID;
		}

		return $post_id;
	}

	public function getBackLink($link) {
        $jobs_page = $this->getJobsPageID();
        if ($jobs_page) {
            $link = get_permalink($jobs_page);
        }

        return $link;
    }

    public function getNextLink($link) {
	    global $post;

        $list = new WP_Query( array(
            'post_type'   => 'jobs',
            'post_status' => 'publish',
            'meta_key'=>'_department',
            'nopaging'    => true,
            'orderby' => array( 'meta_value' => 'ASC', 'title' => 'ASC' ),
        ) );

        if ($list->have_posts()) {
            foreach ($list->posts as $k=>$item) {
                if ($item->ID == $post->ID) {
                    if (isset($list->posts[$k+1]))
                        $link = get_permalink($list->posts[$k+1]->ID);
                    else
                        $link = get_permalink($list->posts[0]->ID);
                }
            }
        }


        return $link;
    }

	public function updateBG($bg) {
	    $jobs_page = $this->getJobsPageID();
	    if ($jobs_page) {
	        $bg = wp_get_attachment_url( get_post_thumbnail_id($jobs_page) );
        }
	    return $bg;
    }

	public function importData() {
	    global $wpdb;
	    $last_ts = get_option('wb_harvest_api_last_update');
	    if (!$last_ts)
	        $last_ts = 0;

	    $is_in_update = get_transient('wb_harvest_refresh_process');
		//$is_in_update = false;

	    if (($this->options['wb_harvest_timeout'] * 60 + $last_ts) < time() && !$is_in_update) {
            set_transient('wb_harvest_refresh_process', 1, 120);
	        // time to renew data
		    $ch = curl_init();
		    curl_setopt( $ch, CURLOPT_URL, 'https://boards-api.greenhouse.io/v1/boards/'.$this->options['wb_harvest_board_token'].'/jobs?content=true' );

		    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		    curl_setopt( $ch, CURLINFO_HEADER_OUT, true );

		    $x = @curl_exec( $ch );
		    $list = @json_decode($x);


		    if (isset($_GET['artem_debug_jobs'])) {
		        __d('https://boards-api.greenhouse.io/v1/boards/'.$this->options['wb_harvest_board_token'].'/jobs?content=true');
		        __d($list);
            }

		    if (isset($list->jobs) && is_array($list->jobs)) {
		        $uid = md5(time());
		        // add jobs
		        foreach ($list->jobs as $job) {
                    // check if exists
                    $job_id = $job->internal_job_id;
                    $orig_job_id = $job->id;

			        $aPostData = array(
				        'post_content'=>isset($job->content)?html_entity_decode($job->content):'',
				        'post_title'=>$job->title,
				        'post_status'=>'publish',
				        'post_type'=>'jobs',
			        );

			        $check_result = $wpdb->get_row( "select post_id from ".$wpdb->postmeta." where meta_key = '_job_id_new' and meta_value='".$job_id."'" );

                    if (isset($_GET['artem_debug_jobs'])) {
                        echo '<h1>JOB-----------------------------------------------------------------</h1>';
                        echo 'Check result<br>';
                        __d($check_result);
                    }

			        if ($check_result) {
				        $aPostData['ID'] = $check_result->post_id;
				        $post_id = wp_update_post($aPostData);
			        } else {
				        $post_id = wp_insert_post($aPostData);
			        }

			        $aMetas = array(
				        '_job_refresh_uid'=>$uid,
				        '_job_id_new'=>$job_id,
				        '_orig_job_id'=>$orig_job_id,
				        '_department'=>(isset($job->departments[0]))?$job->departments[0]->name:'department unknown',
				        '_location'=>(isset($job->location->name))?$job->location->name:'location unknown',
                        '_apply_url'=>$job->absolute_url
			        );

                    if (isset($_GET['artem_debug_jobs'])) {
                        echo 'POST ID<br>';
                        __d($post_id);
                        echo 'METAS<br>';
                        __d($aMetas);
                    }

			        if (!is_wp_error($post_id)) {
				        foreach ($aMetas as $key=>$value) {
					        update_post_meta($post_id, $key, $value);
				        }


                        $url = 'https://boards-api.greenhouse.io/v1/boards/'.$this->options['wb_harvest_board_token'].'/jobs/'.$orig_job_id.'?questions=true';

                        $ch = curl_init();
                        curl_setopt( $ch, CURLOPT_URL, $url );
                        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
                        curl_setopt( $ch, CURLINFO_HEADER_OUT, true );

                        $x = @curl_exec( $ch );
                        $list = @json_decode($x);

                        if (isset($list->id)) {
                            update_post_meta($post_id, '_job_full_details', $list);
                        }
			        }
                }

                // remove old jobs
			    $records = new WP_Query(
				    array(
					    'post_type'=>'jobs',
					    'nopaging'=>true,
					    'meta_query'=>array(
						    array(
							    'key'=>'_job_refresh_uid',
							    'value'=>$uid,
							    'compare'=>'!='
						    )
					    )
				    )
			    );

                if (isset($_GET['artem_debug_jobs'])) {
                    __d($records);
                }

			    if ($records->post_count > 0) {
				    foreach ($records->posts as $item)
					    wp_delete_post($item->ID, true);
			    }



            }

		    delete_transient('wb_harvest_refresh_process');
            update_option('wb_harvest_api_last_update', time());
        }
    }

	/**
	 * Add options page
	 */
	public function add_plugin_page()
	{
		// This page will be under "Settings"
		add_options_page(
			'Harvest API',
			'Harvest API',
			'manage_options',
			'workbox_harvest_api',
			array( $this, 'create_admin_page' )
		);
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page()
	{
		// Set class property
		?>
        <div class="wrap">
            <h1>Careers Plugin Settings</h1>
            <form method="post" action="options.php">
				<?php
				// This prints out all hidden setting fields
				settings_fields( 'wb_harvest_api_option_group' );
				do_settings_sections( 'workbox_harvest_api' );
				submit_button();
				?>
            </form>
        </div>
		<?php
	}

	/**
	 * Register and add settings
	 */
	public function page_init()
	{
		register_setting(
			'wb_harvest_api_option_group', // Option group
			'wb_harvest_board_token', // Option name
			array( $this, 'sanitize' ) // Sanitize
		);

		add_settings_section(
			'wb_harvest_api_setting_section_id', // ID
			'Harvest API Settings', // Title
			array( $this, 'print_section_info' ), // Callback
			'workbox_harvest_api' // Page
		);

		add_settings_field(
			'wb_harvest_board_token',
			'Board Token',
			array( $this, 'board_token_callback' ),
			'workbox_harvest_api',
			'wb_harvest_api_setting_section_id'
		);

		add_settings_field(
			'wb_harvest_timeout',
			'Refresh Interval (minutes)',
			array( $this, 'refresh_interval_callback' ),
			'workbox_harvest_api',
			'wb_harvest_api_setting_section_id'
		);
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 */
	public function sanitize( $input )
	{
		$new_input = array();

		if( isset( $input['wb_harvest_board_token'] ) )
			$new_input['wb_harvest_board_token'] = sanitize_text_field( $input['wb_harvest_board_token'] );

		if( isset( $input['wb_harvest_timeout'] ) )
			$new_input['wb_harvest_timeout'] = intval( $input['wb_harvest_timeout'] );

		return $new_input;
	}

	/**
	 * Print the Section text
	 */
	public function print_section_info()
	{
		print 'Enter your settings below:';
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function board_token_callback()
	{
		printf(
			'<input type="text" id="wb_harvest_board_token" name="wb_harvest_board_token[wb_harvest_board_token]" value="%s" style="width: 100%%;" />',
			isset( $this->options['wb_harvest_board_token'] ) ? esc_attr( $this->options['wb_harvest_board_token']) : ''
		);
	}

    public function refresh_interval_callback()
	{
		printf(
			'<input type="text" id="wb_harvest_timeout" name="wb_harvest_board_token[wb_harvest_timeout]" value="%s"  />',
			isset( $this->options['wb_harvest_timeout'] ) ? intval( $this->options['wb_harvest_timeout']) : ''
		);
	}


	public function activate() {
	}

	public function deactivate() {

	}

	public function registerNewPostsTypes() {
		$labels = array(
			'name' => 'Jobs',
			'singular_name' => 'Job',
			'menu_name' => 'Jobs',
			'name_admin_bar' => 'Jobs',
			'add_new' => 'Add New Job',
			'add_new_item' => 'Add New Job',
			'new_item' => 'New Job',
			'edit_item' => 'Edit Job',
			'view_item' => 'View Job',
			'all_items' => 'All Jobs',
			'search_items' => 'Search Jobs',
			'parent_item_colon' => 'Parent Jobs:',
			'not_found' => 'No Jobs found.',
			'not_found_in_trash' => 'No Jobs found in Trash.',
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
			'register_meta_box_cb'=> array($this, 'metaboxFieldsAddJob'),
		);

		register_post_type('jobs', $args);


		/*register_taxonomy('jobs_category', array('jobs'), array(
			'label'                 => '',
			'labels'                => array(
				'name'              => 'Jobs Categories',
				'singular_name'     => 'Job Category',
				'search_items'      => 'Search Categories',
				'all_items'         => 'All Categories',
				'view_item '        => 'View Category',
				'parent_item'       => 'Parent Category',
				'parent_item_colon' => 'Parent Category:',
				'edit_item'         => 'Edit Category',
				'update_item'       => 'Update Category',
				'add_new_item'      => 'Add New Category',
				'new_item_name'     => 'New Category Name',
				'menu_name'         => 'Jobs Categories',
			),
			'description'           => '',
			'public'                => false,
			'publicly_queryable'    => null,
			'show_in_nav_menus'     => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_tagcloud'         => true,
			'show_in_rest'          => null,
			'rest_base'             => null,
			'hierarchical'          => true,
			'rewrite'               => true,
			'capabilities'          => array(),
			'meta_box_cb'           => null,
			'show_admin_column'     => true,
			'show_in_quick_edit'    => false, // по умолчанию значение show_ui
		) );*/
	}

	public function metaboxFieldsAddJob() {
		add_meta_box ( 'jobs_meta_bottom', 'Additional Parameters', array (
			$this,
			'showMetaBoxJobsBottom'
		), 'jobs', 'normal' );
	}

	public function showMetaBoxJobsBottom($post, $metabox) {
		$department = get_post_meta($post->ID, '_department', true);
		$location = get_post_meta($post->ID, '_location', true);

		?>
		<b>Department:</b><br>
		<input type="text"  value="<?php echo esc_attr($department); ?>" name="_department" style="width: 100%;">
		<br><br>

		<b>Location:</b><br>
		<input type="text" name="_location" value="<?php echo esc_attr($location); ?>" style="width:100%"><br><br>
		<?php
	}

	public function saveJob($post_ID, $post = false, $update = false) {
		if (isset($_POST['action']) && $_POST['action'] == 'editpost') {
			update_post_meta($post_ID, '_department', isset($_POST['_department'])?stripslashes($_POST['_department']):'');
			update_post_meta($post_ID, '_location', isset($_POST['_location'])?stripslashes($_POST['_location']):'');
		}
	}

	public function getCategories() {
		/*$terms = get_terms($args = array(
			'taxonomy' => 'jobs_category',
		));*/
		$cats = array();
		$jobs = new WP_Query(array('post_type'=>'jobs', 'post_status'=>'publish', 'nopaging'=>true));
		if ($jobs->have_posts()) {
			foreach ( $jobs->posts as $post ) {
                $cats[] = get_post_meta($post->ID, '_department', true);
		    }
        }

        $cats = array_unique($cats);
		sort($cats);
		return $cats;
	}

    public function footer() {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $(".cards_filter").on("change", function() {
                    var department = $("#department").val();
                    var location = $("#location").val();

                    console.log(department, location);

                    $(".card-all").show();

                    if (department != "") {
                        $(".card-all").each(function() {
                            if (!$(this).hasClass("department-" + department)) {
                                $(this).hide();
                            }
                        });
                    }

                    if (location != "") {
                        $(".card-all").each(function() {
                            if (!$(this).hasClass("location-" + location)) {
                                $(this).hide();
                            }
                        });
                    }

                })
            });
        </script>
        <?php
    }

    public function doShortCodeGreenHouse($atts = '') {
		//$html = '<!-- JB - '. $this->options['wb_harvest_board_token'].' -->';
        $html = '';
		$category = isset($_GET['jobs_category'])?($_GET['jobs_category']):'';
        if ($category) {
	        $list = new WP_Query( array(
		        'post_type'   => 'jobs',
		        'post_status' => 'publish',
		        'nopaging'    => true,
		        'meta_query'   => array(
			        array(
				        'key' => '_department',
				        'value'    => $category,
			        ),
		        ),
                'order'=>'ASC',
                'orderby'=>'post_title'
	        ) );

	        if (!$list->have_posts()) {
		        $list = new WP_Query( array(
			        'post_type'   => 'jobs',
			        'post_status' => 'publish',
			        'nopaging'    => true,
			        'order'=>'ASC',
			        'orderby'=>'post_title'
		        ) );
            }
        } else {
	        $list = new WP_Query( array(
			        'post_type'   => 'jobs',
			        'post_status' => 'publish',
			        //'meta_key'=>'_department',
			        'nopaging'    => true,
              //'orderby' => array( 'meta_value' => 'ASC', 'title' => 'ASC' ),
                    'orderby' => 'meta_value',
                    'meta_key'=>'_department',
                    'order'=>'ASC'
		        ) );
        }

        $sorted_jobs = $list->posts;
        usort($sorted_jobs, array($this, 'sortJobsByNormalizedDepartment'));



        // load all departments
        $departments = array();
        $locations = array();

        $full_list = new WP_Query([
            'post_type'   => 'jobs',
            'post_status' => 'publish',
            'nopaging'    => true,
        ]);

        foreach ($full_list->posts as $item) {
            $departments[] = get_post_meta($item->ID, '_department', true);
            $locations[] = get_post_meta($item->ID, '_location', true);
        }

        $departments = array_unique($departments);
        $locations = array_unique($locations);

		// add keys to departments, remove prefixes and sort alphabetically by visible name
		$sorted_departments = array();
		foreach ($departments as $department) {
			$sorted_departments[md5($department)] = $this->normalizeDepartmentName($department);
		}
		natcasesort($sorted_departments);

        $department = '';

        $html.= '
        <div class="container-fluid section positions_filter">
			<div class="container">
				<div class="row">					
					<div class="col-6">
					    <label for="department">Department</label>
					    <select id="department" class="cards_filter">
					        <option value="">ALL</option>
        ';
		foreach ($sorted_departments as $key => $department) {
            $html.= '<option value="'.$key.'">'.$department.'</option>';
        }
        $html.= '
                        </select>
                    </div>
                    <div class="col-6">
                        <label for="location">Location</label>
                        <select id="location" class="cards_filter">
                            <option value="">ALL</option>';

        foreach ($locations as $location) {
            $html.= '<option value="'.md5($location).'">'.$location.'</option>';
        }

        $html.= '
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        ';

        $html.= '<div class="container-fluid section positions">
			<div class="container">
				<div class="row">					
					<div class="col-12">
						<!--Accordion wrapper-->
						<div class="accordion md-accordion" id="accordionPos" role="tablist" aria-multiselectable="true"><!-- Accordion card -->';



		$prev_department = '';
		foreach ( $sorted_jobs as $i=>$post ) {
            $department = get_post_meta($post->ID, '_department', true);
            $location = get_post_meta($post->ID, '_location', true);
			$department_name = $this->normalizeDepartmentName($department);

			if ($department_name != $prev_department) {

                $html.= '<h3 class="category card-all department-'.md5($department).'">'.strtoupper($department_name).'</h3>';
				$prev_department = $department_name;
            }

            $html .= '
                
                <div class="card card-all department-'.md5($department).' location-'.md5($location).'">                
								<!-- Card header -->
								<div class="card-header" role="tab" id="headingOne'.$i.'">
									<a class="collapsed" href="'.get_permalink($post).'" >
										<h5 class="mb-0">'.$post->post_title.'</h5>
										<div class="btn btn-primary btn-plus"><i></i></div>
									</a>
								</div>';/*

								<!-- Card body -->
								<div id="collapseOne'.$i.'" class="collapse /show" role="tabpanel" aria-labelledby="headingOne'.$i.'" data-parent="#accordionPos">
									<div class="card-body">
                            '.$post->post_content.'
                          </div></div>*/
            $html.= '
							</div>';
              /*
            $_d = get_post_meta($post->ID,'_department', true);
            if ($_d != $department) {

                $html.= '
                    <tr>
                        <th class="tal">'.$_d.'</th>
                        <th class="tal">Title</th>
                        <th class="tal">Location</th>
                    </tr>';

                $department = $_d;
            }

            $html.= '
                <tr>
                    <td>&nbsp;</td>
                    <td ><a class="has-over" href = "'.get_permalink($post->ID).'" > '.$post->post_title.'</a ></td >
                    <td>'.get_post_meta($post->ID,'_location', true).'</td >
                </tr >';
                */
          $i++;
        }

        $html.= '<!-- Accordion card --></div>
						<!-- Accordion wrapper -->					
					</div>
				</div>
			</div>
		</div>';

		return $html;
  }

	public function doShortCodeWidget($atts = '') {
	    $html = '';

		$list = new WP_Query( array(
			'post_type'   => 'jobs',
			'post_status' => 'publish',
			'posts_per_page'    => 3,
			'order'=>'DESC',
			'orderby'=>'ID'

		) );

	    $html.= '<div class="section-head">Most Recent Job Opportunities</div>';
		foreach ( $list->posts as $post ) {
            $html.= '<div class="item">
                    <div class="title"><a href="'.get_permalink($post->ID).'">'.$post->post_title.'</a></div>
                    <div class="department"><a href="'.get_permalink($post->ID).'">'.get_post_meta($post->ID, '_department', true).'</a></div>
                </div>';
	    }

	    return $html;
    }

	public function doShortCode($atts = '') {
		$html = '';
		$category = isset($_GET['jobs_category'])?($_GET['jobs_category']):'';
        if ($category) {
	        $list = new WP_Query( array(
		        'post_type'   => 'jobs',
		        'post_status' => 'publish',
		        'nopaging'    => true,
		        'meta_query'   => array(
			        array(
				        'key' => '_department',
				        'value'    => $category,
			        ),
		        ),
                'order'=>'ASC',
                'orderby'=>'post_title'
	        ) );

	        if (!$list->have_posts()) {
		        $list = new WP_Query( array(
			        'post_type'   => 'jobs',
			        'post_status' => 'publish',
			        'nopaging'    => true,
			        'order'=>'ASC',
			        'orderby'=>'post_title'
		        ) );
            }
        } else {
	        $list = new WP_Query( array(
			        'post_type'   => 'jobs',
			        'post_status' => 'publish',
			        'meta_key'=>'_department',
			        'nopaging'    => true,
                    'orderby' => array( 'meta_value' => 'ASC', 'title' => 'ASC' ),
		        ) );
        }

        $department = '';

        $html.= '<div class="table"><div class="wrap"><table class="department">';

        foreach ( $list->posts as $post ) {
            $_d = get_post_meta($post->ID,'_department', true);
            if ($_d != $department) {

                $html.= '
                    <tr>
                        <th class="tal">'.$_d.'</th>
                        <th class="tal">Title</th>
                        <th class="tal">Location</th>
                    </tr>';

                $department = $_d;
            }

            $html.= '
                <tr>
                    <td>&nbsp;</td>
                    <td ><a class="has-over" href = "'.get_permalink($post->ID).'" > '.$post->post_title.'</a ></td >
                    <td>'.get_post_meta($post->ID,'_location', true).'</td >
                </tr >';
        }

        $html.= '</table></div></div>';

		return $html;
	}


	public function managePostColumns( $defaults ) {
		unset($defaults['date']);
		$defaults['department'] = 'Department';
		$defaults['location'] = 'Location';

		return $defaults;
	}

	public function showCustomPostColumns( $column, $post_id ) {
	    switch ($column) {
            case 'department':
                echo get_post_meta($post_id, '_department', true);
                break;
            case 'location':
                echo get_post_meta($post_id, '_location', true);
                break;
        }
	}

	public function doRebuildQueryParams($query) {
		/*if (isset($query->query['post_type']) && $query->query['post_type'] == 'jobs') {
			$query->set('order', 'ASC');
			$query->set('orderby', 'post_title');
		}*/

		return $query;
	}

	public function getJobsPageID() {
		global $wpdb;
		$sql = 'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_type = "page" AND post_status="publish" AND post_content LIKE "%[workbox_jobs]%"';
		$id = $wpdb->get_var($sql);
		return $id;
	}

	public function getJobsCategoryURL() {
        global $post;
		$page_url = '';
        if (isset($post->post_type) && $post->post_type == 'jobs') {
            $page_url = get_page_link($this->getJobsPageID());

            // get post taxonomy
	        $cur_terms = get_the_terms($post->ID, 'jobs_category');
	        if( is_array( $cur_terms ) ){
	            $page_url.='?jobs_category='.$this->encodeURIComponent(html_entity_decode($cur_terms[0]->name));
	        }
        }

        return $page_url;
    }

	private function encodeURIComponent($str) {
		$revert = array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')');
		return strtr(rawurlencode($str), $revert);
	}

	private function normalizeDepartmentName($department) {
		$department = (string) $department;
		$normalized = preg_replace('/^\s*[0-9]+[A-Z]*\s*-\s*/i', '', $department);

		if ($normalized === null || $normalized === '') {
			return trim($department);
		}

		return trim($normalized);
	}

	private function sortJobsByNormalizedDepartment($a, $b) {
		$department_a = $this->normalizeDepartmentName(get_post_meta($a->ID, '_department', true));
		$department_b = $this->normalizeDepartmentName(get_post_meta($b->ID, '_department', true));

		$department_cmp = strcasecmp($department_a, $department_b);
		if ($department_cmp !== 0) {
			return $department_cmp;
		}

		$title_a = isset($a->post_title) ? (string) $a->post_title : '';
		$title_b = isset($b->post_title) ? (string) $b->post_title : '';

		return strcasecmp($title_a, $title_b);
	}
}

new WorkboxJobsGreenhouse();