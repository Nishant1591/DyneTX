<?php
	if ( ! defined( 'ABSPATH' ) ) exit;

	// Define Query Args for Pages/Posts/CPTs with Content Masks defined
	$load = 20;

	// Current admin tab. Read-only UI state for a GET view, so no nonce is required.
	$active_tab     = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab selection for display; no state is changed.
	$toggle_options = array();
	$tracking_checked = '';

	$args = array(
		'post_status' => ['publish', 'draft', 'pending', 'private'],
		'post_type'   => get_post_types( '', 'names' ),
		'meta_query'  => [[
			'key'	  	=> 'content_mask_url',
			'value'   	=> '',
			'compare' 	=> '!=',
		]],
		'posts_per_page' => $load
	);

	// Force only own posts if applicable
	if( ! current_user_can( 'edit_others_posts' ) )
		$args['perm'] = 'editable';

	// Initialize Query
	$query = new WP_Query( $args );

	// Define Table Columns
	$columns = array(
		'Method',
		'Status',
		'Info',
		'Type',
		'Views',
		'Non-User',
		'Unique',
		'More'
	);


	if( current_user_can('manage_options') ){
		// Define Togglable Options
		$toggle_options = array(
			'tracking',
			'user_agent_header',
			'include_return_link',
			'disable_iframe_title',
			'allow_scripts_download',
			'allow_styles_download',
			'allow_scripts_iframe',
			'allow_styles_iframe',
			'allow_footer_scripts_iframe',
			'allow_footer_scripts_download',
			'allow_standard_wp_head_iframe',
			'disable_iframe_query_parameter_identifier',
			'disable_iframe_query_parameter_passthrough'
		);

		$roles = get_editable_roles();
		$roles = array_keys($roles);

		global $current_user;
		$user_roles = $current_user->roles;

		// Add User Roles
		foreach( $roles as $role ){
			if( !in_array($role, $user_roles ) ){
				$toggle_options[] = sprintf(
					'role_disable_%s',
					str_replace('-', '_', sanitize_title($role))
				);
			}
		}

		$post_types = get_post_types( array( 'public' => true ) );
		
		// Add Post Types
		foreach( $post_types as $post_type ){
			$toggle_options[] = sprintf(
				'post_type_disable_%s',
				esc_html( $post_type )
			);
		}
	}

	// Set Parameters based on Boolean Toggle Values
	foreach( $toggle_options as $option ){
		$option = esc_html( $option );
		if( filter_var( get_option( "content_mask_$option" ), FILTER_VALIDATE_BOOLEAN ) ){
			${$option.'_checked'} = 'checked="checked"';
			${$option.'_enabled'} = 'enabled';
		} else {
			${$option.'_checked'} = '';
			${$option.'_enabled'} = 'disabled';
		}
	}
?>
<div class="_content-mask-affect">
	<div id="content-mask" class="wrap">
		<h1 class="headline"><?php $this->echo_svg( 'content-mask' ); ?> <span>Content</span> <strong>Mask</strong> <span class="version-number">v<?php echo esc_html($this->get_content_mask_data()['Version']); ?></span> <span id="mobile-nav-toggle"><?php $this->echo_svg( 'menu' ); ?></span><span id="header-nav" class="alignright"><?php require_once dirname(__FILE__).'/admin-buttons.php'; ?></span></h1>
		<div class="inner">
			<nav class="sub-menu">
				<li><a data-target="content-mask-pages" href="#" class="<?php echo ( '' === $active_tab ) ? 'active' : ''; ?>"><span>List View</span></a></li>
				<?php if( current_user_can('manage_options') ){ ?>
					<li><a data-target="content-mask-options" href="#" class="<?php echo ( 'options' === $active_tab ) ? 'active' : ''; ?>"><span>Options</span></a></li>
					<li><a data-target="content-mask-scripts-styles" href="#" class="<?php echo ( 'scripts-styles' === $active_tab ) ? 'active' : ''; ?>"><span>Scripts & Styles</span></a></li>
				<?php } ?>
			</nav>
			
			<!-- List of Content Masked Pages/Posts/CPTs -->
			<div id="content-mask-pages" class="content-mask-panel <?php echo ( '' === $active_tab || !current_user_can('manage_options') ) ? 'active' : ''; ?> <?php if( ! empty( $tracking_checked ) ){ echo 'visitor-tracking'; } ?> ">
				<table>
					<?php
						echo "<tr data-attr-id='0' data-attr-state='' class='new-mask'>";
							// Move $method to the end, unset others
							$new_columns = $columns;
							unset( $new_columns[7] );
							unset( $new_columns[6] );
							unset( $new_columns[5] );
							unset( $new_columns[4] );
							unset( $new_columns[0] );

							$new_columns[] = 'Method';

							foreach( $new_columns as $column ){
								$column = sanitize_title( $column );
								$column = ($column=='method') ? 'method-like' : $column;

								printf( "<td class='%s'%s>", $column, ($column=='info')?'colspan="2"':'' );
									echo '<div>';
										$this->content_mask_display_column_new_mask( $column );
									echo '</div>';
								echo '</td>';
							}
						echo '</tr>';

						$count = 0;
						if( $query->have_posts() ){
							while( $query->have_posts() ){ $count++;
								$query->the_post();
								$post_id     = get_the_ID();
								$post_fields = $this->get_post_fields( $post_id );

								extract( $post_fields );

								$state = filter_var( $content_mask_enable, FILTER_VALIDATE_BOOLEAN ) ? 'enabled' : 'disabled';

								printf(
									'<tr data-attr-id="%d" data-attr-state="%s" class="%s">',
									absint( $post_id ),
									esc_attr( $state ),
									esc_attr( $state )
								);
									foreach( $columns as $column ){
										$column = sanitize_title( $column );

										printf( '<td class="%s">', $column) ;
											printf( '<div%s>', ($column=='more') ? ' style="width: 100%;"' : '' );
												$this->content_mask_display_column( $column, $post_id, $post_fields );
											echo '</div>';
										echo '</td>';
									}
								echo '</tr>';
							}
						} else {
							echo "<tr><td><div>No Content Masks Found</div></td></tr>";
						}
					?>
				</table>
				<?php if( $count == $load ) echo '<button id="load-more-masks">Load More Content Masks</button>'; ?>
			</div>
			
			<?php if( current_user_can('manage_options') ){ ?>
				<!-- Content Mask Options and Settings -->
				<div id="content-mask-options" class="content-mask-panel <?php echo ( 'options' === $active_tab ) ? 'active' : ''; ?>">
					<?php
						$check_options = array(
							array(
								'name'  => 'tracking',
								'label' => 'Visitor Tracking',
								'help'  => 'Vistor Tracking will give you a rough estimate of how many views your Content Masked Pages are getting.'
							),
							array(
								'name'  => 'user_agent_header',
								'label' => 'HTTP Headers',
								'help'  => 'If you\'re getting errors, especially \'403 Forbidden\' errors, when using the Download Method, try enabling this option.'
							),
							array(
								'name'  => 'include_return_link',
								'label' => 'Include [Go Back] Link on Masked Pages',
								'help'  => 'Content Mask embeds external content completely. Turn this option on to add a floating "return" button to let users return to their previous page (if applicable)'
							),
							array(
								'name'  => 'return_link_label',
								'type'  => 'text',
								'label' => '[Go Back] button label',
								'help'  => 'Default is "Go Back", change it to "Return" or whatever you\'d like.'
							),
							array(
								'name'  => 'disable_iframe_title',
								'label' => 'Disable Iframe Title',
								'help'  => 'Some themes don\'t handle implicit wp_titles for iframed pages. Enable this option to use the Page Title instead of the generated title for iframed pages.'
							),
							array(
								'name'  => 'disable_iframe_query_parameter_passthrough',
								'label' => 'Disable Iframe Query Parameter Passthrough',
								'help'  => 'Only toggle this option on if directed by support.'
							),
							array(
								'name'  => 'disable_iframe_query_parameter_identifier',
								'label' => 'Disable Iframe Query Parameter Identifier',
								'help'  => 'Only toggle this option on if directed by support.'
							),
							array(
								'name'  => 'allow_standard_wp_head_iframe',
								'label' => 'Allow Standard <code>wp_head()</code> call on Iframes',
								'help'  => 'Allows all original functions on wp_head() to fire (this may cause issues with the iframe display).'
							)
						);

						foreach( $check_options as $option ){
							$option = (object) $option;
							$option->type = $option->type ?? 'checkbox'; ?>
							<?php if( $option->type == 'checkbox' ){ ?>
								<div class="content-mask-option">
									<label class="content-mask-checkbox" for="content_mask_<?php echo esc_attr( $option->name ); ?>" data-attr="<?php echo esc_attr( ${$option->name.'_enabled'} ); ?>">
										<span class="display-name" aria-label="Enable <?php echo esc_attr( $option->label ); ?>"></span>
										<input type="checkbox" name="content_mask_<?php echo esc_attr( $option->name ); ?>" id="content_mask_<?php echo esc_attr( $option->name ); ?>" <?php echo esc_html( ${$option->name.'_checked'} ); ?> />
										<span class="content-mask-check" style="width: 32px; height: 32px;">
											<span class="content-mask-check_ajax">
												<?php $this->echo_svg( 'checkmark', 'icon' ); ?>
											</span>
										</span>
									</label>
									<span class="content-mask-option_label"><?php echo esc_html( $option->label ); ?>: <strong class="content-mask-value"><?php echo esc_html( ucwords( ${$option->name.'_enabled'} ) ); ?></strong> <span class="content-mask-hover-help" data-help="<?php echo esc_attr( $option->help ); ?>">?</span></span>
								</div><br>
							<?php } else if( $option->type == 'text' ){ ?>
								<div class="content-mask-option inset">
									<span class="content-mask-option_label"><?php echo esc_html( $option->label ); ?>: <strong class="content-mask-value"><?php echo esc_html( ucwords( ${$option->name.'_enabled'} ) ); ?></strong> <span class="content-mask-hover-help" data-help="<?php echo esc_attr( $option->help ); ?>">?</span></span>
									<br />
									<label class="content-mask-input" for="content_mask_<?php echo esc_attr( $option->name ); ?>" data-attr="<?php echo esc_attr( ${$option->name.'_enabled'} ); ?>">
										<span class="display-name" aria-label="Enable <?php echo esc_attr( $option->label ); ?>"></span>
										<input type="text" name="content_mask_<?php echo esc_attr( $option->name ); ?>" id="content_mask_<?php echo esc_attr( $option->name ); ?>" value="<?php echo esc_attr( get_option('content_mask_'.$option->name) ?? 'Go Back' ); ?>" />
										<button style="background: #0095ee;color: #fff;border: none;height: 100%;display: inline-flex;flex-grow: 0;padding: 16px;margin-left: 16px;">Update</button>
									</label>
								</div><br>
							<?php } ?>
						<?php }

						echo '<div style="display: flex; flex-wrap: wrap;">';
							if( current_user_can('manage_options') ){
								$options = array(
									'Roles' => array(),
									'Post Types' => array()
								);

								$roles = get_editable_roles();
								$roles = array_keys($roles);
								foreach( $roles as $option ){
									if( !in_array($option, $user_roles) ){
										$options['Roles'][] = array(
											'name'  => sprintf( 'role_disable_%s', str_replace('-','_',sanitize_title($option)) ),
											'label' => $option,
											'help'  => ''
										);
									}
								}

								// 8.1.2.4, add Post Types to this as well
								$post_types = get_post_types( array('public' => true));
								foreach( $post_types as $option ){
									$options['Post Types'][] = array(
										'name'  => sprintf( 'post_type_disable_%s', esc_html($option) ),
										'label' => $option,
										'help'  => ''
									);
								}
								

								foreach( $options as $option_key => $option_array ){
									printf( '<h2 style="width: 100%%;"><span style="color:#d44;">Disable</span> Content Mask for the following %s:</h2>', esc_html($option_key) );
									foreach( $option_array as $option ){
										if( !in_array($option, $user_roles ) ){
											$option = (object) $option; ?>
											<div class="content-mask-option reverse" style="margin: 0 6px 6px 0; padding: 8px; background: #fff; border-radius: 10px; text-transform: capitalize; font-size: 16px;">
												<label class="content-mask-checkbox" for="content_mask_<?php echo esc_attr( $option->name ); ?>" data-attr="<?php echo esc_attr( ${$option->name.'_enabled'} ?? 'disabled'); ?>">
													<span class="display-name" aria-label="Enable <?php echo esc_attr( $option->label ); ?>"></span>
													<input style="width: 1px; height: 1px; box-sizing: border-box; margin: 0; position: absolute; opacity: 0; pointer-events: none;" type="checkbox" name="content_mask_<?php echo esc_attr($option->name); ?>" id="content_mask_<?php echo esc_attr($option->name); ?>" <?php echo isset(${$option->name.'_checked'}) ? esc_attr(${$option->name.'_checked'}) : ''; ?> />
													<span class="content-mask-check" style="width: 32px; height: 32px;">
														<span class="content-mask-check_ajax">
															<?php $this->echo_svg( 'x', 'icon' ); ?>
														</span>
													</span>
													<span class="content-mask-option_label"><?php echo esc_html($option->label); ?> <?php if( !empty($option->help) ){ ?><span class="content-mask-hover-help" data-help="<?php echo esc_attr($option->help); ?>">?</span></span><?php } ?>
												</label>
											</div>
										<?php }
									}
								}
							}
						echo '</div>';
					?>
				</div>

				<!-- Content Masked Advanced Features and Scripts -->
				<div id="content-mask-scripts-styles" class="content-mask-panel <?php echo ( 'scripts-styles' === $active_tab ) ? 'active' : ''; ?>">
					<div class="grid" columns="3" gap>
						<?php
							$method_types = array( 'download', 'iframe' );

							$code_types = array(
								array(
									'name'   => 'scripts',
									'editor' => 'text/html',
									'mode'   => 'htmlmixed',
									'notes'  => '(Include <pre style="display:inline;">&lt;script&gt;</pre> tags)',
								),
								array(
									'name'   => 'footer_scripts',
									'editor' => 'text/html',
									'mode'   => 'htmlmixed',
									'notes'  => '(Include <pre style="display:inline;">&lt;script&gt;</pre> tags)',
								),
								array(
									'name'   => 'styles',
									'editor' => 'text/css',
									'mode'   => 'css',
									'notes'  => '(Do NOT include <pre style="display:inline;">&lt;style&gt;</pre> tags)',
								)
							);

							$count = 0;

							foreach( $method_types as $method ){
								foreach( $code_types as $type ){
									$type = (object) $type; $count++; ?>
										<div class="option">
											<div class="code-edit-wrapper">
												<label class="content-mask-textarea" for="content_mask_custom_<?php echo esc_attr( $type->name.'_'.$method ); ?>">
													<strong class="display-name">Custom <?php echo esc_html( ucwords( str_replace('_',' ',$type->name) ) ); ?> (<?php echo esc_html( ucwords( $method ) ); ?> Method)</strong> <span><?php echo $type->notes; ?></span><br>
													<textarea id="content_mask_custom_<?php echo esc_attr( $type->name.'_'.$method ); ?>" rows="4" data-type="<?php echo esc_attr($type->editor); ?>" data-mode="<?php echo esc_attr($type->mode); ?>" name="content_mask_custom_<?php echo esc_attr($type->name.'_'.$method); ?>" class="widefat textarea code-editor"><?php echo wp_unslash( html_entity_decode( get_option( 'content_mask_custom_'.$type->name.'_'.$method ) ) ); ?></textarea>
													<button id="save-scripts" data-target="content_mask_custom_<?php echo esc_attr($type->name.'_'.$method); ?>" data-editor="editor_<?php echo esc_attr( absint( $count ) ); ?>" class="wp-core-ui button button-primary">Save <span style="display: none;"><?php echo esc_html( ucwords( $method ).' '. ucwords( $type->name ) ); ?></span></button>
												</label>
											</div>
											<div class="content-mask-option">
												<label class="content-mask-checkbox" for="content_mask_allow_<?php echo esc_attr( $type->name.'_'.$method ); ?>" data-attr="<?php echo esc_attr( ${'allow_'.$type->name.'_'.$method.'_enabled'} ); ?>">
													<span class="display-name" aria-label="Custom <?php echo esc_attr( ucwords( $type->name ) ); ?> for <?php echo esc_attr( ucwords( $method ) ); ?> Method"></span>
													<input type="checkbox" name="content_mask_allow_<?php echo esc_attr( $type->name.'_'.$method ); ?>" id="content_mask_allow_<?php echo esc_attr( $type->name.'_'.$method ); ?>" <?php echo esc_html( ${'allow_'.$type->name.'_'.$method.'_checked'} ); ?> />
													<span class="content-mask-check">
														<span class="content-mask-check_ajax">
															<?php $this->echo_svg( 'checkmark', 'icon' ); ?>
														</span>
													</span>
												</label>
												<span class="content-mask-option_label">Custom <?php echo esc_html( ucwords( str_replace('_',' ',$type->name) ) ); ?> for <?php echo esc_html( ucwords( $method ) ); ?> Method: <strong class="content-mask-value"><?php echo esc_html( ucwords( ${'allow_'.$type->name.'_'.$method.'_enabled'} ) ); ?></strong> <span class="content-mask-hover-help" data-help="Add custom <?php echo esc_attr( $type->name ); ?> to pages masked with the <?php echo esc_attr( ucwords( $method ) ); ?> method. Useful if you would like to add Analytics. Note: These <?php echo esc_attr( ucwords( $type->name ) ); ?> will apply to all pages masked with the <?php echo esc_attr( ucwords( $method ) ); ?> method.">?</span></span>
											</div>
										</div>
									<?php
								}
							}
						?>
					</div>
				</div>
			<?php } ?>
		</div>
	</div>
</div>