<?php
/**
 * Class FeedNoticeService
 *
 * Tracks errors and creates messages.
 *
 * @since 2.2.4
 */
namespace TwitterFeed\SmashTwitter\Services;

class FeedNoticeService {

	public function init_hooks() {
		add_action( 'ctf_before_feed_start', array( $this, 'maybe_add_promoted_tweet_notice' ), 5, 1 );
	}

	public function maybe_add_promoted_tweet_notice( $ctf_feed ) {
		if ( ! ctf_current_user_can( 'manage_twitter_feed_options' ) ) {
			return;
		}

		if ( ! ctf_doing_customizer( $ctf_feed->feed_options ) ) {
			return;
		}

		if ( $ctf_feed->feed_options['type'] !== 'search' ) {
			return;
		}
		?>
		<div class="ctf-error ctf_smash_error">
			<div class="ctf-error-user">
				<div class="ctf-error-admin">
					<strong><?php _e( 'This message is only visible to admins:', 'custom-twitter-feeds' ) ?></strong>
					<p>
						<?php _e( 'Hashtag and Search feeds may contain "promoted" tweets that do not fit your feed settings. Remove these using the feed "filters" tools found on the Settings tab', 'custom-twitter-feeds' ) ?>
					</p>
				</div>
			</div>
		</div>
		<?php

	}
}