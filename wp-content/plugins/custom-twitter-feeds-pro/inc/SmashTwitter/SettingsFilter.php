<?php
/**
 * Class SettingsFilter
 *
 *
 *
 * @since 2.1
 */
namespace TwitterFeed\SmashTwitter;

class SettingsFilter
{
	private $settings;

	private $feed_type_and_terms;

	private $max_types_and_terms;

	private $supported_types;

	private $statuses;

	public function __construct() {
		$this->max_types_and_terms = 10;
		$this->supported_types = array( 'usertimeline', 'hashtag', 'search' );
		$this->statuses = array( 'feed_types_and_terms_removed' => array() );
	}

	public function set_settings( $settings ) {
		$this->settings = $settings;
	}

	public function set_feed_type_and_terms( $feed_type_and_terms ) {
		$this->feed_type_and_terms = $feed_type_and_terms;
	}

	public function get_settings() {
		return $this->settings;
	}

	public function get_feed_type_and_terms() {
		return $this->feed_type_and_terms;
	}

	public function filter_feed_type_and_terms() {
		if ( empty( $this->feed_type_and_terms ) ) {
			if ( (! empty( $this->settings['feed'] ) && $this->settings['feed'] === 'legacy') || ! empty( $this->settings['is_legacy'] ) ) {
				if ( $this->settings['type'] === 'usertimeline' ) {
					$this->feed_type_and_terms = array();
					$terms = explode( ',', str_replace( ' ', '', $this->settings['usertimeline_text'] ) );

				} elseif ( $this->settings['type'] === 'hashtag' ) {
					$terms = explode( ',', str_replace( ' ', '', $this->settings['hashtag_text'] ) );

				} elseif ( $this->settings['type'] === 'search' ) {
					$terms = explode( ',', str_replace( ' ', '', $this->settings['serach_text'] ) );
				} else {
					if ( ! empty( $this->settings['usertimeline_text'] ) ) {
						$this->settings['type'] = 'usertimeline';
						$terms = explode( ',', str_replace( ' ', '', $this->settings['usertimeline_text'] ) );
					} elseif ( ! empty( $this->settings['hashtag_text'] ) ) {
						$this->settings['type'] = 'hashtag';
						$terms = explode( ',', str_replace( ' ', '', $this->settings['hashtag_text'] ) );
					} elseif ( ! empty( $this->settings['search_text'] ) ) {
						$this->settings['type'] = 'search';
						$terms = explode( ',', str_replace( ' ', '', $this->settings['serach_text'] ) );
					}
				}

				if ( ! empty( $terms ) ) {
					$this->feed_type_and_terms = array();

					foreach ( $terms as $term ) {
						$this->feed_type_and_terms[] = array( $this->settings['type'], $term );
					}
				} else {
					return;
				}
			} else {
				return;
			}
		}


		$final_types_and_terms = array();
		foreach ( $this->feed_type_and_terms as $feed_type_and_term ) {
			// invalid
			if ( empty( $feed_type_and_term[1] ) ) {
				continue;
			}
			if ( $feed_type_and_term[1] === ' -filter:retweets' ) {
				continue;
			}
			// legacy removed types and terms
			if ( count( $final_types_and_terms ) >= $this->max_types_and_terms ) {
				$this->statuses['feed_types_and_terms_removed'] = $feed_type_and_term;
				continue;
			}
			if ( ! in_array( $feed_type_and_term[0], $this->supported_types, true ) ) {
				$this->statuses['feed_types_and_terms_removed'] = $feed_type_and_term;
				continue;
			}

			if ( $feed_type_and_term[0] === 'hashtag' ) {
				$final_types_and_terms[] = array( 'search', trim( $feed_type_and_term[1] ), 'hashtag' );
				$this->settings['type'] = 'search';
			} elseif ( $feed_type_and_term[0] === 'search' ) {
				$final_types_and_terms[] = array( 'search', trim( $feed_type_and_term[1] ) );
				$this->settings['type'] = 'search';
			} else {
				$final_types_and_terms[] = array( $feed_type_and_term[0], trim( $feed_type_and_term[1] ) );
				$this->settings['type'] = 'usertimeline';
			}
		}

		$this->feed_type_and_terms = $final_types_and_terms;
	}

	public function maybe_add_hashtag_includewords() {
		// do not override an existing includewords/excludewords
		if ( ! empty( $this->settings['includewords'] ) || ! empty( $this->settings['excludewords'] ) ) {
			return false;
		}

		$hashtags_included = array();
		foreach ( $this->feed_type_and_terms as $type_and_term ) {
			if ( $type_and_term[0] !== 'search' ) {
				return false;
			} else {
				if ( ! empty( $type_and_term[2] ) && $type_and_term[2] === 'hashtag' ) {
					$hashtag_no_retweet_filter = str_replace( ' -filter:retweets', '', strtolower( $type_and_term[1] ) );
					$hashtag_no_or = str_replace( ' or ', ' ', $hashtag_no_retweet_filter );

					$split_up = explode( ' ', $hashtag_no_or );
					foreach ( $split_up as $single_hashtag ) {
						$hashtags_included[] = '#' . str_replace( '#', '', $single_hashtag );

					}
				} else {
					return false;
				}
			}
		}

		if ( ! empty( $hashtags_included ) ) {
			$this->settings['includewords'] = implode( ',', $hashtags_included );
			$this->settings['includeanyall'] = 'any';
			$this->settings['filterandor'] = 'or';
			return true;
		}
		return false;
	}
}