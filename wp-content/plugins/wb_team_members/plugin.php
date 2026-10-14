<?php
/*
 Plugin Name: Workbox Team Plugin 
 Author: Artemy Kudryavtsev (Workbox Inc.)
 Editor: Artem Pavlenko (Workbox Inc.)
 Version: 4.0
 Description: Team Manager.
*/


class WorkboxTeam {
	public function __construct()    {
		register_activation_hook(__FILE__, array($this, 'activate'));
		register_deactivation_hook(__FILE__, array($this, 'deactivate'));

		add_action('init', array($this, 'registerNewPostsTypes'));
		add_filter('save_post_team', array($this, 'saveTeam'));

		add_shortcode('workbox_team_inpost2', array($this, 'doShortCode'));
        add_shortcode('workbox-leadership-team', array($this, 'getLeadershipTeam'));
        add_shortcode('workbox-board-of-directors', array($this, 'getBoardDirectors'));
        add_shortcode('workbox-founders', array($this, 'getFounders'));
        add_shortcode('workbox-team-members', array($this, 'getTeamShortCode'));

		add_action('wp_footer', array($this, 'showPopupTemplate'));

		add_action('wp', array($this, 'importData'));

		add_filter( 'manage_team_posts_columns', array($this, 'managePostColumns') );
		add_filter( 'manage_team_posts_custom_column', array($this, 'showCustomPostColumns'), 10, 2 );

		add_action( 'pre_get_posts', array($this, 'doRebuildQueryParams'));

		add_action( 'team_category_add_form_fields', array($this, 'taxonomyAddFields'), 10, 2 );
		add_action( 'team_category_edit_form_fields', array($this, 'taxonomyEditFields'), 10 );

		add_action( 'edited_team_category', array($this, 'taxonomySaveFields') );
		add_action( 'create_team_category', array($this, 'taxonomySaveFields') );
	}

	public function taxonomyAddFields( $term ) {
		?>
        <div class="form-field">
            <label for="_order_no">Sort Order</label>
            <input type="text" name="_order_no" id="_order_no" value="0" size="5">
        </div>
		<?php
	}

	public function taxonomyEditFields( $term ) {
		// put the term ID into a variable
		$t_id = $term->term_id;

		$sort_order = get_term_meta( $t_id, '_order_no', true );
		?>
        <tr class="form-field">
            <th><label for="_order_no">Sort Order</label></th>
            <td>
                <input type="text" name="_order_no" id="_order_no" value="<?php echo intval( $sort_order ) ? intval( $sort_order ) : 0; ?>">
            </td>
        </tr>
		<?php
	}

	function taxonomySaveFields( $term_id ) {
		if ( isset( $_POST['_order_no'] ) ) {
			$sort_order = isset($_POST['_order_no'])?intval($_POST['_order_no']):-1;
			if( $sort_order >= 0 ) {
				update_term_meta( $term_id, '_order_no', $sort_order );
			}
		}
	}




	public function activate() {
	}

	public function deactivate() {

	}

	public function registerNewPostsTypes() {
		$labels = array(
			'name' => 'Team Members',
			'singular_name' => 'Team Member',
			'menu_name' => 'Team Members',
			'name_admin_bar' => 'Team Members',
			'add_new' => 'Add New Team Member',
			'add_new_item' => 'Add New Team Member',
			'new_item' => 'New Team Member',
			'edit_item' => 'Edit Team Member',
			'view_item' => 'View Team Member',
			'all_items' => 'All Team Members',
			'search_items' => 'Search Team Members',
			'parent_item_colon' => 'Parent Team Members:',
			'not_found' => 'No Team Members found.',
			'not_found_in_trash' => 'No Team Members found in Trash.',
		);

		$args = array(
			'labels' => $labels,
			'public' => false,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'capability_type' => 'post',
			'has_archive' => false,
			'hierarchical' => false,
			'menu_position' => null,
			'menu_icon' => 'dashicons-screenoptions',
			'supports' => array('title', 'editor', 'page-attributes', 'custom-fields', 'thumbnail'),
			'register_meta_box_cb'=> array($this, 'metaboxFieldsAddTeam'),
		);

		register_post_type('team', $args);


		register_taxonomy('team_category', array('team'), array(
			'label'                 => '',
			'labels'                => array(
				'name'              => 'Team Categories',
				'singular_name'     => 'Team Category',
				'search_items'      => 'Search Categories',
				'all_items'         => 'All Categories',
				'view_item '        => 'View Category',
				'parent_item'       => 'Parent Category',
				'parent_item_colon' => 'Parent Category:',
				'edit_item'         => 'Edit Category',
				'update_item'       => 'Update Category',
				'add_new_item'      => 'Add New Category',
				'new_item_name'     => 'New Category Name',
				'menu_name'         => 'Team Categories',
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
			'hierarchical'          => false,
			'rewrite'               => true,
			'capabilities'          => array(),
			'meta_box_cb'           => null,
			'show_admin_column'     => true,
			'show_in_quick_edit'    => false, // по умолчанию значение show_ui
		) );
	}

	public function metaboxFieldsAddTeam() {
		add_meta_box ( 'team_meta_bottom', 'Additional Parameters', array (
			$this,
			'showMetaBoxTeamBottom'
		), 'team', 'normal' );
	}

	public function showMetaBoxTeamBottom($post, $metabox) {
		$position = get_post_meta($post->ID, '_position', true);
        $positionInfo = get_post_meta($post->ID, '_position_info', true);
        ?>
		<b>Position:</b><br>
		<input type="text" name="_position" value="<?php echo esc_attr($position); ?>" style="width:100%"><br><br>
        <b>Position Info:</b><br>
        <input type="text" name="_position_info" value="<?php echo esc_attr($positionInfo); ?>" style="width:100%"><br><br>
        <?php
	}

	public function saveTeam($post_ID, $post = false, $update = false) {
		if (isset($_POST['action']) && $_POST['action'] == 'editpost') {
			update_post_meta($post_ID, '_position', isset($_POST['_position'])?stripslashes($_POST['_position']):'');
            update_post_meta($post_ID, '_position_info', isset($_POST['_position_info'])?stripslashes($_POST['_position_info']):'');
		}
	}

	public static function getTeamShortCode($atts) {
        $params = shortcode_atts( array(
            'leaders_slug' => '',
            'senior_slug' => '',
            'bod_slug' => '',
            'founders_slug' => ''
        ), $atts );
        $html = '            
           <section class="bg-blueDark2 text-white spaceIn-top-lg spaceIn-bot-lg sn-leadership">
		   
                <a id="leadership" class="anchor"></a>
				<div class="container container-sm">
				
                    '.do_shortcode('[workbox-leadership-team slug="'.$params['leaders_slug'].'"]').'
                </div>
				<a id="" class="anchor"></a>
                <div class="container">
                    '.do_shortcode('[workbox-leadership-team slug="'.$params['senior_slug'].'"]').'
                </div>
            </section>
        
            
           <section class="section board sn-directors">
            <a id="bod" class="anchor"></a>
                <div class="container container-sm">
                    '.do_shortcode('[workbox-board-of-directors slug="'.$params['bod_slug'].'"]').'
                </div>
            </section>
            <a id="advisory" class="anchor"></a>			
            <section class="section-st bg-grey board advisory" style="display: none;">
                <div class="container">                    
                        '.do_shortcode('[workbox-founders slug="'.$params['founders_slug'].'"]').'                    
                </div>
            </section>
        ';
        return $html;
    }

	public function showPopupTemplate() {
		?>
        <!-- BIO Modal -->
        <div class="modal fade" id="modalTeam" tabindex="-1" role="dialog" aria-labelledby="modalTeamTitle"  aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 col-sm-4 card image_card">
                                <img class="card-img-top" src="" alt="Card image cap">
                                <div class="card-body">
                                    <h4 class="card-title d-none d-sm-block card-title-1" id="modalTeamTitle">card-title-1</h4>
                                    <p class="card-text d-none d-sm-block card-position-1">card-position-1</p>
                                </div>
                            </div>
                            <div class="col-12 col-sm-8 description">
                                <h4 class="card-title-2">card-title-2</h4>
                                <h5 class="card-position-2">card-position-2</h5>
                                <p class="card-description">card-description</p>
                            </div>
                        </div>
                        <button type="button" class="btn close" data-mdb-dismiss="modal" aria-label="Close"><i></i><span class="sr-only">Close modal window</span></button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary btn-arrow-left"  data-item-id="0"><span class="sr-only">Previous</span></button>
                        <button type="button" class="btn btn-primary btn-arrow-right" data-item-id="0"><span class="sr-only">Next</span></button>
                    </div>
                </div>
            </div>
        </div>
		<?php
	}

	public static function getLeadershipTeam($atts) {

        /* MAIN LEADERSHIP TEAM */
        $params = shortcode_atts( array('slug' => 'leadership-main'), $atts );
        $html = '';
        $terms = get_terms(array('taxonomy'=>'team_category', 'meta_key' => '_order_no', 'orderby' => 'meta_value_num', 'order' => 'ASC'));
        $taxId = 0;
        $taxTitle = '';
        $taxDescription = '';
        foreach($terms as $cat_item) {
            if ($params['slug'] == $cat_item->slug) {
                $taxId = $cat_item->term_id;
                $taxTitle = $cat_item->name;
                $taxDescription = $cat_item->description;
            }
        }
        $list = new WP_Query(array('post_type'=>'team','post_status'=>'publish', 'nopaging'=>true, 'tax_query' => array(
            array(
                'taxonomy' => 'team_category',
                'field'    => 'id',
                'terms'    => $taxId,
            ),
        )));

        if ($list->have_posts()) {
            $html .= '<div class="container-fluid section leadership-team">
                <div class="container">
                  <div class="row">';
            $i = 0;
            foreach($list->posts as $item) {
                $aImg = wp_get_attachment_image_src( get_post_thumbnail_id($item->ID), 'full' );
                $image = isset($aImg[0])?$aImg[0]:'';
                $position = get_post_meta($item->ID, '_position', true);
                //$positionInfo = get_post_meta($item->ID, '_position_info', true);

                if ($i+1 < count($list->posts)) {
                    $nextItemId = $list->posts[$i+1]->ID;
                } else {
                    $nextItemId = $list->posts[0]->ID;
                }
                if ($i-1 >= 0) {
                    $prevItemId = $list->posts[$i-1]->ID;
                } else {
                    $prevItemId = $list->posts[count($list->posts) - 1]->ID;
                }
                $i++;
                $html .= '
                  <div class="col-12 col-sm-6 col-md-4 card">
                    <img class="card-img-top" src="'.$image.'" alt="'.$item->post_title.' picture">
                    <div class="card-body">
                      <button type="button" class="btn btn-primary btn-plus stretched-link waves-effect waves-light lst-'.$item->ID.'" 
                        onclick="setModalContent(this)"
												data-mdb-modal-init
												data-mdb-target="#modalTeam"
                        data-mdb-imgpath="'.$image.'" 
                        data-mdb-name="'.$item->post_title.'" 
                        data-mdb-position="'.esc_attr($position).'" 
                        data-mdb-bio="'.esc_attr($item->post_content).'"
                        data-prev = "team_member_'.$prevItemId.'"
                        data-next = "team_member_'.$nextItemId.'"
                        id="team_member_'.$item->ID.'"
                        ><i></i><span class="sr-only">Open '.$item->post_title.' modal</span></button>
                      <h4 class="card-title">'.$item->post_title.'</h4>
                      <p class="card-text">'.$position.'</p>
                    </div>
                  </div>';
            }
            $html .= '</div>
                  </div>
                </div>';
        }

        /* EXTENDED LEADERSHIP TEAM */
        $params = shortcode_atts( array('slug' => 'leadership'), $atts );
        $terms = get_terms(array('taxonomy'=>'team_category', 'meta_key' => '_order_no', 'orderby' => 'meta_value_num', 'order' => 'ASC'));
        $taxId = 0;
        $taxTitle = '';
        $taxDescription = '';
        foreach($terms as $cat_item) {
            if ($params['slug'] == $cat_item->slug) {
                $taxId = $cat_item->term_id;
                $taxTitle = $cat_item->name;
                $taxDescription = $cat_item->description;
            }
        }
        $list = new WP_Query(array('post_type'=>'team','post_status'=>'publish', 'nopaging'=>true, 'tax_query' => array(
            array(
                'taxonomy' => 'team_category',
                'field'    => 'id',
                'terms'    => $taxId,
            ),
        )));


        if ($list->have_posts()) {
            $html .= '<div class="container-fluid section leadership-team pt-0">
                <div class="container">
                    <div class="row">					
                        <div class="col-12">
                            <div class="collapse-hdr">
                                <div class="red-label">'.$taxTitle.' </div>	
                                <button class="btn btn-primary btn-plus-collapse stretched-link waves-effect waves-light collapsed" type="button" data-mdb-collapse-init  data-mdb-target="#collapseLeadership" aria-expanded="false" aria-controls="collapseLeadership"><i></i><span class="sr-only">Collapse the list</span></button>
                            </div>
                            <div class="row collapse" id="collapseLeadership" style="">';
            $i = 0;
            foreach ( $list->posts as $item ) {
                $aImg         = wp_get_attachment_image_src( get_post_thumbnail_id( $item->ID ), 'full' );
                $image        = isset( $aImg[0] ) ? $aImg[0] : '';
                $position     = get_post_meta( $item->ID, '_position', true );
                //$positionInfo = get_post_meta( $item->ID, '_position_info', true );

	            if ($i+1 < count($list->posts)) {
		            $nextItemId = $list->posts[$i+1]->ID;
	            } else {
		            $nextItemId = $list->posts[0]->ID;
	            }
	            if ($i-1 >= 0) {
		            $prevItemId = $list->posts[$i-1]->ID;
	            } else {
		            $prevItemId = $list->posts[count($list->posts) - 1]->ID;
	            }
	            $i++;

                $html .= '
                <div class="col-12 col-sm-6 col-md-4 card">
                    <img class="card-img-top" src="' . $image . '" alt="Card image cap">
                    <div class="card-body">
                        <button type="button" class="btn btn-primary btn-plus stretched-link waves-effect waves-light lst-' . $item->ID . '" 
                        onclick="setModalContent(this)"
												data-mdb-modal-init
												data-mdb-target="#modalTeam"
                        data-mdb-imgpath="'.$image.'" 
                        data-mdb-name="'.$item->post_title.'" 
                        data-mdb-position="'.esc_attr($position).'" 
                        data-mdb-bio="'.esc_attr($item->post_content).'"
                        data-prev = "team_member_'.$prevItemId.'"
                        data-next = "team_member_'.$nextItemId.'"
                        id="team_member_'.$item->ID.'"
                        ><i></i><span class="sr-only">Open bio window</span></button>
                        <h4 class="card-title">' . $item->post_title . '</h4>
                        <p class="card-text">' . $position . '</p>
                    </div>
                </div>';
            }
            $html .= '</div>
                  </div>
                </div>
              </div>
            </div>';
        }

        return $html;
    }

    public static function getBoardDirectors($atts) {
        $params = shortcode_atts( array('slug' => 'board-of-directors'), $atts );
        $html = '';
        $terms = get_terms( array('taxonomy'=>'team_category' , 'meta_key' => '_order_no',    'orderby' => 'meta_value_num',   'order' => 'ASC'));
        $taxId = 0;
        $taxTitle = '';
        $taxDescription = '';
        foreach($terms as $cat_item) {
            if ($params['slug'] == $cat_item->slug) {
                $taxId = $cat_item->term_id;
                $taxTitle = $cat_item->name;
                $taxDescription = $cat_item->description;
            }
        }
        $list = new WP_Query(array('post_type'=>'team','post_status'=>'publish', 'nopaging'=>true, 'tax_query' => array(
            array(
                'taxonomy' => 'team_category',
                'field'    => 'id',
                'terms'    => $taxId,
            ),
        )));
        if ($list->have_posts()) {

            $html .= '
                <div class="container-fluid section boards">
                    <div class="container">
                        <div class="row">
                            <div class="col-12">
                                <div class="collapse-hdr">
                                    <h2>'.$taxTitle.'</h2>
                                    <button class="btn btn-primary btn-plus-collapse stretched-link collapsed action-bod" type="button" data-mdb-collapse-init  data-mdb-target="#collapseDirectors" aria-expanded="false" aria-controls="collapseDirectors"><i></i><span class="sr-only">Collapse the list</span></button>
                                </div>
                
                                <div class="row collapse" id="collapseDirectors">';
            $i = 0;
            foreach($list->posts as $item) {
                $position = get_post_meta($item->ID, '_position', true);
                $positionInfo = get_post_meta($item->ID, '_position_info', true);
                $aImg = wp_get_attachment_image_src( get_post_thumbnail_id($item->ID), 'full' );
                $image = isset($aImg[0])?$aImg[0]:'';

                if ($i+1 < count($list->posts)) {
                    $nextItemId = $list->posts[$i+1]->ID;
                } else {
                    $nextItemId = $list->posts[0]->ID;
                }
                if ($i-1 >= 0) {
                    $prevItemId = $list->posts[$i-1]->ID;
                } else {
                    $prevItemId = $list->posts[count($list->posts) - 1]->ID;
                }
                $i++;

                $html .= '
                    <!-- Card -->
                    <div class="col-12 col-sm-6 col-md-4 card">
                      <div class="card-body">
                        <h4 class="card-title">'.$item->post_title.'</h4>
                        <p class="card-text">'.$position.'</p>
                      </div>
                      <button type="button" class="btn btn-primary btn-plus stretched-link bod-'.$item->ID.'" 
                        onclick="setModalContent(this)"
												data-mdb-modal-init
												data-mdb-target="#modalTeam"
                        data-mdb-imgpath="'.$image.'" 
                        data-mdb-name="'.$item->post_title.'" 
                        data-mdb-position="'.esc_attr($position).'" 
                        data-mdb-bio="'.esc_attr($item->post_content).'"
                        data-prev = "team_member_'.$prevItemId.'"
                        data-next = "team_member_'.$nextItemId.'"
                        data-class = true
                        id="team_member_'.$item->ID.'"
                        ><i></i><span class="sr-only">Open bio window</span></button>
                    </div>
                    <!-- Card -->
                ';
            }
            $html .= '
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
            ';
        }
        return $html;
    }

    public static function getFounders($atts) {$params = shortcode_atts( array('slug' => 'scientific-advisory-board'), $atts );
        $html = '';
        $terms = get_terms( array('taxonomy'=>'team_category' , 'meta_key' => '_order_no',    'orderby' => 'meta_value_num',   'order' => 'ASC'));
        $taxId = 0;
        $taxTitle = '';
        foreach($terms as $cat_item) {
            if ($params['slug'] == $cat_item->slug) {
                $taxId = $cat_item->term_id;
                $taxTitle = $cat_item->name;
            }
        }
        $list = new WP_Query(array('post_type'=>'team','post_status'=>'publish', 'nopaging'=>true, 'tax_query' => array(
            array(
                'taxonomy' => 'team_category',
                'field'    => 'id',
                'terms'    => $taxId,
            ),
        )));
        if ($list->have_posts()) {
            $html .= '
		<div class="container-fluid section boards">
		<div class="container">
			<div class="row">
				<div class="col-12">

					<div class="collapse-hdr">
						<h2>'.$taxTitle.'</h2>
						<button class="btn btn-primary btn-plus-collapse stretched-link collapsed action-sab" type="button" data-mdb-collapse-init  data-mdb-target="#collapseAdvisory" aria-expanded="false" aria-controls="collapseAdvisory"><i></i><span class="sr-only">Collapse the list</span></button>
					</div>
						
					<div class="row collapse" id="collapseAdvisory">
            ';
            $i = 0;
            foreach($list->posts as $item) {
                $position = get_post_meta($item->ID, '_position', true);
                //$positionInfo = get_post_meta($item->ID, '_position_info', true);
                $aImg = wp_get_attachment_image_src( get_post_thumbnail_id($item->ID), 'full' );
                $image = isset($aImg[0])?$aImg[0]:'';
                if ($i+1 < count($list->posts)) {
                    $nextItemId = $list->posts[$i+1]->ID;
                } else {
                    $nextItemId = $list->posts[0]->ID;
                }
                if ($i-1 >= 0) {
                    $prevItemId = $list->posts[$i-1]->ID;
                } else {
                    $prevItemId = $list->posts[count($list->posts) - 1]->ID;
                }
                $i++;

                $html .= '
                            <!-- Card -->
                            <div class="col-12 col-sm-6 col-md-4 card">
                              <div class="card-body">
                                <h4 class="card-title">'.$item->post_title.'</h4>
                                <p class="card-text">'.$position.'</p>
                              </div>
                              <button type="button" class="btn btn-primary btn-plus stretched-link sab-'.$item->ID.'" 
                                onclick="setModalContent(this)"
																data-mdb-modal-init
																data-mdb-target="#modalTeam"
                                data-mdb-imgpath="'.$image.'" 
                                data-mdb-name="'.$item->post_title.'" 
                                data-mdb-position="'.esc_attr($position).'" 
                                data-mdb-bio="'.esc_attr($item->post_content).'"
                                data-prev = "team_member_'.$prevItemId.'"
                                data-next = "team_member_'.$nextItemId.'"
                                data-class = true
                                id="team_member_'.$item->ID.'"
                              ><i></i><span class="sr-only">Open bio window</span></button>
                            </div>
                            <!-- Card -->';


            }
            $html .= '
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            ';
        }
        return $html;
    }

	public function doShortCode($atts = '') {
		$html = '';

		$terms = get_terms( array('taxonomy'=>'team_category' , 'meta_key' => '_order_no',    'orderby' => 'meta_value_num',   'order' => 'ASC'));

		$html = '<a name="top"></a>';
		$html.= '<div class="search-results">';
		$html .= '<table class="p_list">';
		$html .= '<tr>';
		foreach($terms as $cat_item)
		{
			$html .= '<th align="left">'.$cat_item->name.'</th>';
		}
		$html .= '</tr>';
		$html .= '<tr>';
		foreach($terms as $cat_item)
		{
			$html .= '<td><p>';

			$list = new WP_Query(array('post_type'=>'team','post_status'=>'publish', 'nopaging'=>true, 'tax_query' => array(
				array(
					'taxonomy' => 'team_category',
					'field'    => 'id',
					'terms'    => $cat_item->term_id,
				),
			)));

			if ($list->have_posts())
			{
				foreach($list->posts as $item)
				{
					$html .= '<a href="#'.sanitize_title($item->post_title).'">'.$item->post_title.'</a><br>';
				}
			}
			$html .= '</p></td>';
		}
		$html .= '</tr>';
		$html .= '</table><hr class="hr_separator" />';
		foreach($terms as $cat_item)
		{

            $html .= '<p style="text-align: left;">
                <strong>
                    <span style="font-size: 110%;">
                        '.$cat_item->name.'
                    </span>
                </strong>
            </p>';

			$list = new WP_Query(array('post_type'=>'team','post_status'=>'publish', 'nopaging'=>true, 'tax_query' => array(
				array(
					'taxonomy' => 'team_category',
					'field'    => 'id',
					'terms'    => $cat_item->term_id,
				),
			)));

            foreach($list->posts as $item)
            {
                $position = get_post_meta($item->ID, '_position', true);
                $html.= '
                    <a name="'.sanitize_title($item->post_title).'"></a>
            
                <div class="item1">';

                $html .='<strong>'.$item->post_title.'</strong> '.($position != ''?'<br /> '.$position.'<br />':'').wpautop($item->post_content).'
                </div>
                <hr class="hr_separator" />
            ';
            }
			$html .= '';
		}

		$html.= '</div>';

		return $html;
	}

	public function importData() {
		global $wpdb;
		if (isset($_GET['importteam'])) {
			include_once( ABSPATH . 'wp-admin/includes/image.php' );
			$sSQL = 'select * from wb_teams_cat order by order_no';
			$cats = $wpdb->get_results($sSQL);
			$aTmp = array();
			foreach ( $cats as $cat ) {
                $aTmp[$cat->id] = $cat;
			}

			$cats = $aTmp;

            $sSQL = 'select * from wb_teams where is_live=1  and (vsys_parent_id=0 or vsys_parent_id = -id) order by order_no';
			$result = $wpdb->get_results($sSQL);

			// clean all posts
			$list = new WP_Query(array('post_type'=>'team', 'nopaging'=>true));
			foreach ($list->posts as $post) {
				wp_delete_post($post->ID, true);
			}


			foreach ($result as $item) {
			    $cat_id = $item->category;
				$exists = get_term_by('name', esc_html($cats[$cat_id]->category),'team_category');

				$new_cat_id = 0;
				if ($exists) {
                    $new_cat_id = $exists->term_id;
                } else {
					$term_id = wp_insert_term(
						$cats[$cat_id]->category, // the term
						'team_category'
					);

					if (!is_wp_error($term_id)) {
						$new_cat_id = $term_id['term_id'];
						//update_term_meta( $term_id, 'order_no', $cats[$cat_id]->order_no );
					}
				}

				// add new posts
				$data = array(
					'post_content' =>$item->description,
					'post_title'=>$item->title,
					'post_status'=>'publish',
					'post_type'=>'team',
					'menu_order'=>$item->order_no,
					'tax_input'=>array('team_category'=>array($new_cat_id))
				);

				$post_id = wp_insert_post(
					$data
				);

				if (!is_wp_error($post_id)) {
					update_post_meta($post_id, '_position', $item->position);
				}

			}

			die('Import done');
		}
	}

	public function managePostColumns( $defaults ) {
		unset($defaults['date']);
		$defaults['position'] = 'Position';
		return $defaults;
	}

	public function showCustomPostColumns( $column, $post_id ) {
		switch ($column) {
			case 'position' :
				echo get_post_meta($post_id, '_position', true);
				break;
			default:
		}
	}

	public function doRebuildQueryParams($query) {
		if (isset($query->query['post_type']) && $query->query['post_type'] == 'team') {
			$query->set('order', 'ASC');
			$query->set('orderby', 'menu_order');
		}

		return $query;
	}
}

new WorkboxTeam();