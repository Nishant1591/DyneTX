<?php 
/**
 * CTF License Tier
 * 
 * @since 6.3
 */

namespace TwitterFeed;

use Smashballoon\TwitterFeed\Vendor\Smashballoon\Framework\Packages\License_Tier\License_Tier;

class CTF_License_Tier extends License_Tier 
{

    /**
     * License key 
     */
    public $license_key_option_name = 'ctf_license_key';

    /**
     * License status
     */
    public $license_status_option_name = 'ctf_license_status';

    /**
     * License data
     */
    public $license_data_option_name = 'ctf_license_data';

    /**
     * Item IDs
     */
    public $item_id_basic = 33604; // Item id for the basic tier.
    public $item_id_plus = 33748; // Item id for the plus tier.
    public $item_id_elite = 33751; // Item id for the elite tier.
    public $item_id_all_access = 789157; // This is the all access item id, no need to change.
    
    /**
     * Legacy item IDs
     */
    public $item_id_personal = 177805; // Item id for the personal tier.
    public $item_id_business = 188603; // Item id for the business tier.
    public $item_id_developer = 188605; // Item id for the developer tier.
    
    /**
     * Tier names
     */
    public $license_tier_basic_name = 'basic'; // Basic tier name.
    public $license_tier_plus_name = 'plus'; // Plus tier name.
    public $license_tier_elite_name = 'elite'; // Elite tier name.

    /**
     * Legacy tier names
     */
    public $license_tier_personal_name = 'personal'; // Personal tier name.
    public $license_tier_business_name = 'business'; // Business tier name.
    public $license_tier_developer_name = 'developer'; // Developer tier name.

    /**
     * Constructor
     */
    public function __construct()
	{
		parent::__construct();
	}

    /**
     * This defines the features list of the plugin
     *
     * @return void
     */
    public function features_list()
    {
        $features_list = [
            'basic' => [
                // List of features for basic tier.
                '12_daily_updates',
                'unlimited_pro_feeds',
                'usertimeline_feeds',
                'photos_videos',
                'popup_lightbox',
                'moderate_tweets',
                'multi_column_masonry_layout',
                'carousel',
                'visual_twitter_link_cards',
                'feed_customizer',
                'downtime_prevention_system',
                'gdpr_compliant',
                'display_account_info',
            ],
            'plus'  => [
                // List of features for plus tier.
                'hashtag_feeds',
                'feed_templates',
            ],
            'elite' => [
                // List of features for elite tier.
                'search_feeds',
                'autoscroll_loading',
            ],
        ];

        $this->plugin_features = $features_list;
    }

    /**
     * This defines features for legacy tiers
     *
     * @return void
     */
    public function legacy_features_list()
    {
        $legacy_features = [
            'personal'  => [
                // List of features for personal tier.
                '12_daily_updates',
                'unlimited_pro_feeds',
                'usertimeline_feeds',
                'photos_videos',
                'popup_lightbox',
                'moderate_tweets',
                'multi_column_masonry_layout',
                'carousel',
                'visual_twitter_link_cards',
                'feed_customizer',
                'downtime_prevention_system',
                'gdpr_compliant',
                'display_account_info',
                'hashtag_feeds',
                'feed_templates',
                'search_feeds',
                'autoscroll_loading',
            ],
            'business'  => [
                // List of features for business tier.
                '12_daily_updates',
                'unlimited_pro_feeds',
                'usertimeline_feeds',
                'photos_videos',
                'popup_lightbox',
                'moderate_tweets',
                'multi_column_masonry_layout',
                'carousel',
                'visual_twitter_link_cards',
                'feed_customizer',
                'downtime_prevention_system',
                'gdpr_compliant',
                'display_account_info',
                'hashtag_feeds',
                'feed_templates',
                'search_feeds',
                'autoscroll_loading',
            ],
            'developer' => [
                // List of features for developer tier.
                '12_daily_updates',
                'unlimited_pro_feeds',
                'usertimeline_feeds',
                'photos_videos',
                'popup_lightbox',
                'moderate_tweets',
                'multi_column_masonry_layout',
                'carousel',
                'visual_twitter_link_cards',
                'feed_customizer',
                'downtime_prevention_system',
                'gdpr_compliant',
                'display_account_info',
                'hashtag_feeds',
                'feed_templates',
                'search_feeds',
                'autoscroll_loading',
            ],
        ];

        $this->legacy_features = $legacy_features;
    }

    /**
     * Pro features list
     *
     * @return array
     */
    public function pro_features_list()
    {
        $pro_features_list = [
            __('Display Hashtag & Tagged feeds', 'custom-twitter-feeds'),
            __('Powerful visual moderation', 'custom-twitter-feeds'),
            __('Comments and Likes', 'custom-twitter-feeds'),
            __('Highlight specific posts', 'custom-twitter-feeds'),
            __('Multiple layout options', 'custom-twitter-feeds'),
            __('Popup photo/video lightbox', 'custom-twitter-feeds'),
            __('Instagram Stories', 'custom-twitter-feeds'),
            __('Shoppable feeds', 'custom-twitter-feeds'),
            __('Pro support', 'custom-twitter-feeds'),
            __('Post captions', 'custom-twitter-feeds'),
            __('Combine multiple feed types', 'custom-twitter-feeds'),
            __('30 day money back guarantee', 'custom-twitter-feeds'),
        ];

        return $pro_features_list;
    }

    /**
     * Plus features list
     *
     * @return array
     */
    public function plus_features_list()
    {
        $plus_features_list = [
            __('Powerful visual moderation', 'custom-twitter-feeds'),
            __('Filter posts', 'custom-twitter-feeds'),
            __('Display Hashtag feeds', 'custom-twitter-feeds'),
            __('Feed templates', 'custom-twitter-feeds'),
            __('Combine multiple feed types', 'custom-twitter-feeds'),
            __('Standard support', 'custom-twitter-feeds'),
            __('30 day money back guarantee', 'custom-twitter-feeds'),
        ];

        return $plus_features_list;
    }

    /**
     * Elite features list
     *
     * @return array
     */
    public function elite_features_list()
    {
        $elite_features_list = [
            __('Tagged feeds', 'custom-twitter-feeds'),
            __('Shoppable feeds', 'custom-twitter-feeds'),
            __('Feed Themes', 'custom-twitter-feeds'),
            __('Priority support', 'custom-twitter-feeds'),
            __('30 day money back guarantee', 'custom-twitter-feeds'),
        ];

        return $elite_features_list;
    }
}