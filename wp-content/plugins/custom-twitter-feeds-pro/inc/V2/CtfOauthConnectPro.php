<?php

/**
 * Overrides the default class to use v2 endpoints.
 *
 * @package twitter-api-v2
 */

namespace TwitterFeed\V2;

// Don't load directly.
if (! defined('ABSPATH')) {
	die('-1');
}

/**
 * CtfOauthConnectPro class.
 */
class CtfOauthConnectPro extends CtfOauthConnect
{
	/**
	 * Sets the complete url for our API endpoint. GET fields will be added later
	 */
	public function setUrlBase()
	{
		switch ($this->feed_type) {
			case 'hometimeline':
				$this->base_url = 'https://api.twitter.com/1.1/statuses/home_timeline.json';
				break;
			case 'search':
			case 'hashtag':
				$this->base_url = 'https://api.twitter.com/2/tweets/search/recent';
				break;
			case 'mentionstimeline':
				// NOT SUPPORTED.
				$this->base_url = 'https://api.twitter.com/1.1/statuses/mentions_timeline.json';
				break;
			case 'lists':
				// NOT SUPPORTED.
				$this->base_url = 'https://api.twitter.com/1.1/lists/statuses.json';
				break;
			case 'listsmeta':
				// NOT SUPPORTED.
				$this->base_url = 'https://api.twitter.com/1.1/lists/list.json';
				break;
			case 'listlookup':
				// NOT SUPPORTED.
				$this->base_url = 'https://api.twitter.com/1.1/lists/show.json';
				break;
			case 'accountlookup':
				// NOT SUPPORTED.
				$this->base_url = 'https://api.twitter.com/1.1/account/verify_credentials.json';
				break;
			case 'userslookup':
				$this->base_url = 'https://api.twitter.com/2/users/by/username/:id';
				break;
			default:
				$this->base_url = 'https://api.twitter.com/2/users/:id/tweets';
		}
	}
}
