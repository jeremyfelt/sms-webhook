<?php
/**
 * Initialize the plugin in WordPress.
 *
 * @package sms-webhook
 */

namespace SMSWebhook;

use SMSWebhook\Endpoint;

/**
 * Initialize the plugin and its interactions with WordPress.
 */
class Init {
	/**
	 * Add hooks.
	 */
	public static function add_hooks(): void {
		add_action( 'rest_api_init', [ __CLASS__, 'load_endpoint' ] );
	}

	/**
	 * Start the endpoint and register its routes.
	 */
	public static function load_endpoint(): void {
		$endpoint = new Endpoint();
		$endpoint->register_routes();
	}
}
