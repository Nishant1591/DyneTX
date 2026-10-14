<?php
	if ( ! defined( 'ABSPATH' ) ) exit;
		extract( $this->get_post_fields( get_the_ID() ) );
?>
<div id="content-mask-settings">
	<?php wp_nonce_field( 'save_post', 'content_mask_meta_nonce' ); ?>
	<div class="content-mask-enable-container">
		<label class="content-mask-checkbox" for="content_mask_enable">
			<span aria-label="Enable Content Mask"></span>
			<input type="checkbox" name="content_mask_enable" id="content_mask_enable" <?php if( filter_var( $this->issetor( $content_mask_enable ), FILTER_VALIDATE_BOOLEAN ) ){ echo 'checked="checked"'; } ?> />
			<span class="content-mask-check">
				<?php $this->echo_svg( 'checkmark', 'icon' ); ?>
			</span>
		</label>
	</div>
	<div class="content-mask-url-container">
		<div class="content-mask-text hide-overflow">
			<span aria-label="Content Mask URL"></span>
			<input type="text" class="widefat" name="content_mask_url" id="content_mask_url" placeholder="Content Mask URL" value="<?php echo esc_url( $this->issetor( $content_mask_url ) ); ?>" />
		</div>
	</div>
	<div class="content-mask-method-container">
		<div class="content-mask-select">
			<input type="radio" name="content_mask_method" class="content-mask-select-toggle">
			<?php $this->echo_svg( 'arrow-down', 'toggle' ); ?>
			<?php $this->echo_svg( 'arrow-up', 'toggle' ); ?>
			<span class="placeholder">Choose a Method...</span>
			<?php foreach( ['download', 'iframe', 'redirect'] as $method ){ ?>
				<label class="option">
					<input type="radio" <?php echo $this->issetor( $content_mask_method ) == $method ? 'checked="checked"' : '' ?> value="<?php echo esc_attr( $method ); ?>" name="content_mask_method">
					<span class="title"><?php echo $this->get_svg( sprintf( 'method-%s', $method ) ) . esc_html( ucwords( $method ) ); ?></span>
				</label>
			<?php } ?>
		</div>
	</div>
	<div class="content-mask-expiration-div">
		<h2 class="content-mask-expiration-header content-mask-box-header"><strong>Cache Expiration:</strong><br /><sup>(Download Method Only)</sup></h2>
		<div class="content-mask-expiration-container">
			<div class="content-mask-select">
				<?php $test = $this->time_to_seconds( $this->issetor( $content_mask_transient_expiration ) ); ?>
				<input type="radio" name="content_mask_transient_expiration" class="content-mask-select-toggle">
				<?php $this->echo_svg( 'arrow-down', 'toggle' ); ?>
				<?php $this->echo_svg( 'arrow-up', 'toggle' ); ?>
				<span class="placeholder">Cache Expiration:</span>
				<label class="option">
					<input type="radio" <?php echo $content_mask_transient_expiration == 'never' ? 'checked="checked"' : '' ?> value="never" name="content_mask_transient_expiration">
					<span class="title">Never Cache</span>
				</label>
				<?php
					$times = [];

					foreach( range(1, 12) as $hour ){ $times['hour'][] = $hour .' Hour'; }
					foreach( range(1, 6)  as $day ){  $times['day'][]  = $day .' Day'; }
					foreach( range(1, 4)  as $week ){ $times['week'][] = $week .' Week'; }

					foreach( $times as $time ){
						$i = 0;
						foreach( $time as $val ){ ?>
							<?php $s = $i++ == 0 ? '' : 's'; ?>
							<label class="option">
								<?php
									if( $content_mask_transient_expiration == '' && $val == '4 Hour' ){
										$checked = 'checked';
									} else if ( $content_mask_transient_expiration == $val ) {
										$checked = 'checked';
									} else {
										$checked = '';
									}
								?>
								<input type="radio" <?php echo $checked; ?> value="<?php echo esc_attr($val); ?>" name="content_mask_transient_expiration">
								<span class="title"><?php echo esc_html("$val$s"); ?></span>
							</label>
						<?php }
					}
				?>
			</div>
		</div>
	</div>
	<div class="content-mask-permissions grid" columns="1" gap>
		<div id="content-mask-single-role-permissions">
			<h2 class="content-mask-box-header"><strong>Hide This Masked Content from These Roles:</strong></h2>
			<div class="content-mask-text hide-overflow content-mask-permissions-checkboxes">
				<?php
					$roles = get_editable_roles();
					$roles = array_keys($roles);
					$role_permissions = $content_mask_role_permissions;
					$role_permissions = (is_string($role_permissions)) ? explode(',', $role_permissions) : $role_permissions;

					foreach( $roles as $role ){
						printf('<label>
							<input type="checkbox" name="content_mask_role_permissions[]" value="%s" %s />
							<span style="color: #fff;">%s</span>
						</label>', esc_attr($role), (is_array($role_permissions) && in_array($role, $role_permissions)) ? 'checked="checked"' : '', esc_html( ucwords( $role ) ) );
					}
				?>
			</div>
		</div>
		<div id="content-mask-single-role-permissions">
			<h2 class="content-mask-box-header"><strong>Hide This Masked Content Under These Conditions:</strong></h2>
			<div class="content-mask-text hide-overflow content-mask-permissions-checkboxes">
				<?php
					$condition_permissions = $content_mask_condition_permissions;

					$array = array();
					foreach( self::$conditions as $label => $function ){
						printf('<label style="margin-right: 18px;">
							<input type="checkbox" name="content_mask_condition_permissions[]" value="%s" %s />
							<span style="color: #fff;">%s</span>
						</label>', esc_attr($label), (is_array($condition_permissions) && in_array($label, $condition_permissions)) ? 'checked="checked"' : '', esc_html( ucwords( str_replace( '_', ' ', $label ) ) ) );
					}
				?>
			</div>
		</div>
	</div>
	<div class="content-mask-scripts-div grid" columns="2" gap>
		<div id="content-mask-single-header-scripts">
			<h2 class="content-mask-box-header"><strong>Header Scripts & Styles</strong></h2>
			<div class="content-mask-text hide-overflow">
				<span aria-label="Header Scripts and Styles"></span>
				<textarea type="text" class="widefat" rows="6" name="content_mask_header_scripts_styles" id="content_mask_header_scripts_styles" placeholder="Scripts and Styles placed here will show up before the closing </head> tag"><?php echo wp_unslash( html_entity_decode( $this->issetor( $content_mask_header_scripts_styles ) ) ); ?></textarea>
			</div>
		</div>
		<div id="content-mask-single-footer-scripts">
			<h2 class="content-mask-box-header"><strong>Footer Scripts</strong></h2>
			<div class="content-mask-text hide-overflow">
				<span aria-label="Footer Scripts"></span>
				<textarea type="text" class="widefat" rows="6" name="content_mask_footer_scripts" id="content_mask_footer_scripts" placeholder="Scripts and Styles placed here will show up before the closing </body> tag"><?php echo wp_unslash( html_entity_decode( $this->issetor( $content_mask_footer_scripts ) ) ); ?></textarea>
			</div>
		</div>
	</div>
</div>