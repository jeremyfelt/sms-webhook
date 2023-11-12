<?php
/**
 * Provide the endpoint on which SMS webhooks are received.
 *
 * @package sms-webhook
 */

namespace SMSWebhook;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Provide the endpoint on which SMS webhooks are received.
 */
class Endpoint {

	/**
	 * Constructor.
	 */
	public function __construct() {}

	/**
	 * Register the REST routes used by this plugin.
	 */
	public function register_routes(): void {

		register_rest_route(
			'smswebhook/v1',
			'sms',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ __CLASS__, 'handle_sms' ],
					'permission_callback' => '__return_true', // Handle permissions in the callback.
				),
			)
		);
	}

	/**
	 * Handle an incoming SMS message.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 * @return WP_REST_Response The response.
	 */
	public static function handle_sms( WP_REST_Request $request ): WP_REST_Response {

		$auth_token  = apply_filters( 'sms_webhook_auth_token', '' );
		$auth_header = apply_filters( 'sms_webhook_auth_header', 'smswebhook-verify' );
		$auth_secret = apply_filters( 'sms_webhook_auth_secret', '' );

		$verify_header = $request->get_header( $auth_header );
		$match_hash    = hash_hmac( 'sha1', $auth_secret, $auth_token );

		if ( $verify_header !== $match_hash ) {
			return new \WP_REST_Response(
				'Check your token, header, and secret.',
				403
			);
		}

		$message = json_decode( $request->get_body() );

		if ( ! isset( $message->To ) || ! isset( $message->Body ) || ! isset( $message->From ) ) {
			return new WP_REST_Response(
				'Required data missing',
				500
			);
		}

		ob_start();
		?>
		This message was sent to <?php echo esc_html( $message->To ); ?>

		Message text: <?php echo esc_html( $message->Body ); ?>
		<?php
		$email = ob_get_clean();
		$sent  = false;

		if ( $email ) {
			add_filter( 'wp_mail_from', [ __CLASS__, 'set_from_email' ] );
			add_filter( 'wp_mail_from_name', [ __CLASS__, 'set_from_name' ] );

			$sent = wp_mail(
				apply_filters( 'sms_webhook_email_to', get_option( 'admin_email' ) ),
				'Text message received from ' . esc_html( $message->From ),
				$email
			);

			remove_filter( 'wp_mail_from', [ __CLASS__, 'set_from_email' ] );
			remove_filter( 'wp_mail_from_name', [ __CLASS__, 'set_from_name' ] );
		}

		if ( $sent ) {
			return new \WP_REST_Response( 'Message sent', 200 );
		}

		return new \WP_REST_Response( 'Message not sent', 500 );
	}

	/**
	 * Set the from email address for the SMS webhook email.
	 *
	 * @param string $email The email address.
	 * @return string The email address.
	 */
	public static function set_from_email( string $email ): string {
		return apply_filters( 'sms_webhook_email_from', $email );
	}

	/**
	 * Set the from name for the SMS webhook email.
	 *
	 * @param string $name The name.
	 * @return string The name.
	 */
	public static function set_from_name( string $name ): string {
		return apply_filters( 'sms_webhook_email_from_name', $name );
	}
}
