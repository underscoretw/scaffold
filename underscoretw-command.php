<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

if ( ! defined( 'WPCLI_UNDERSCORETW_VERSION' ) ) {
	define( 'WPCLI_UNDERSCORETW_VERSION', '1.0.2' );
}

$wpcli_underscoretw_autoloader = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $wpcli_underscoretw_autoloader ) ) {
	require_once $wpcli_underscoretw_autoloader;
}

if ( ! function_exists( 'wpcli_underscoretw_check_update' ) ) {
	/**
	 * Check for a newer release of underscoretw/scaffold on GitHub.
	 */
	function wpcli_underscoretw_check_update() {
		try {
			// Skip if not running in a terminal.
			if ( function_exists( 'posix_isatty' ) && ! posix_isatty( STDOUT ) ) {
				return;
			}

			$cache     = WP_CLI::get_cache();
			$cache_key = 'underscoretw-scaffold/update-check';
			$cached    = $cache->read( $cache_key, 86400 );

			if ( is_string( $cached ) ) {
				$latest = trim( $cached );
			} else {
				$response = WP_CLI\Utils\http_request(
					'GET',
					'https://api.github.com/repos/underscoretw/scaffold/releases/latest',
					null,
					array(),
					array(
						'halt_on_error' => false,
						'max_retries'   => 1,
					)
				);

				if ( ! $response instanceof \WpOrg\Requests\Response || 200 !== (int) $response->status_code ) {
					WP_CLI::debug( 'underscoretw/scaffold update check: unexpected response', 'underscoretw' );
					return;
				}

				$body = json_decode( $response->body, true );
				if ( ! is_array( $body ) || ! isset( $body['tag_name'] ) || ! is_string( $body['tag_name'] ) ) {
					WP_CLI::debug( 'underscoretw/scaffold update check: no tag_name in response', 'underscoretw' );
					return;
				}

				$latest = ltrim( $body['tag_name'], 'v' );
				$cache->write( $cache_key, $latest );
			}

			if ( ! class_exists( 'Composer\Semver\Comparator' ) ) {
				return;
			}

			if ( Composer\Semver\Comparator::greaterThan( $latest, WPCLI_UNDERSCORETW_VERSION ) ) {
				$notice = WP_CLI::colorize( '%CNotice:%n' );
				WP_CLI::log(
					sprintf(
						"%s A new version of underscoretw/scaffold is available: %s → %s\n%s Please run `wp package update underscoretw/scaffold` to update!",
						$notice,
						WPCLI_UNDERSCORETW_VERSION,
						$latest,
						$notice
					)
				);
			}
		} catch ( \Exception $exception ) {
			WP_CLI::debug( 'underscoretw/scaffold update check failed: ' . $exception->getMessage(), 'underscoretw' );
		}
	}

	WP_CLI::add_hook( 'after_invoke:scaffold _tw', 'wpcli_underscoretw_check_update' );
	WP_CLI::add_hook( 'after_invoke:scaffold underscoretw', 'wpcli_underscoretw_check_update' );
}

WP_CLI::add_command( 'scaffold _tw', 'UnderscoreTW_Command' );
WP_CLI::add_command( 'scaffold underscoretw', 'UnderscoreTW_Command' );
