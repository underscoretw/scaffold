<?php

use WP_CLI\Utils;

/**
 * Generates starter themes from underscoretw.com.
 *
 * @when after_wp_load
 */
class UnderscoreTW_Command extends WP_CLI_Command {

	private const TOKEN_PATTERN = '/^[a-z_]\w*$/i';

	/**
	 * Generates a starter theme from underscoretw.com. Omit the slug to use the wizard.
	 *
	 * ## OPTIONS
	 *
	 * [<slug>]
	 * : The slug for the new theme.
	 *
	 * [--theme_name=<title>]
	 * : What to put in the 'Theme Name' header. Derived from the slug when left empty.
	 *
	 * [--prefix=<prefix>]
	 * : Function prefix for the theme. Derived from the slug when left empty.
	 *
	 * [--theme_uri=<uri>]
	 * : What to put in the 'Theme URI' header.
	 *
	 * [--author=<full-name>]
	 * : What to put in the 'Author' header.
	 *
	 * [--author_uri=<uri>]
	 * : What to put in the 'Author URI' header.
	 *
	 * [--description=<text>]
	 * : What to put in the 'Description' header.
	 *
	 * [--activate]
	 * : Activate the newly downloaded theme.
	 *
	 * [--enable-network]
	 * : Enable the newly downloaded theme for the entire network.
	 *
	 * [--force]
	 * : Overwrite the theme if it already exists.
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate a theme using the interactive wizard
	 *     $ wp scaffold _tw
	 *
	 *     # Generate a theme called "My Theme"
	 *     $ wp scaffold _tw my-theme --theme_name="My Theme"
	 *
	 *     # Generate and activate a theme
	 *     $ wp scaffold _tw my-theme --activate
	 *
	 *     # Generate with all options
	 *     $ wp scaffold _tw my-theme --theme_name="My Theme" --prefix=mytheme --author="Jane Doe" --author_uri=example.com --description="A custom theme"
	 *
	 */
	public function __invoke( $args, $assoc_args ) {
		if ( empty( $args ) ) {
			list( $args, $wizard_assoc_args ) = $this->interactive_init( $assoc_args );
			$assoc_args                       = array_merge( $assoc_args, $wizard_assoc_args );
		}

		$theme_slug = sanitize_title( $args[0] );

		// Validate slug.
		if ( ! preg_match( self::TOKEN_PATTERN, str_replace( '-', '_', $theme_slug ) ) ) {
			WP_CLI::error( 'Invalid theme slug. Theme slugs can contain only letters, numbers, underscores and hyphens, and must start with a letter or underscore.' );
		}

		// Default theme name to ucfirst of slug.
		$theme_name = Utils\get_flag_value( $assoc_args, 'theme_name' );
		if ( empty( $theme_name ) ) {
			$theme_name = ucwords( str_replace( '-', ' ', $theme_slug ) );
		}

		// Check if theme directory already exists.
		$themes_dir = WP_CONTENT_DIR . '/themes';
		$theme_path = $themes_dir . '/' . $theme_slug;

		// Ensure themes directory exists before resolving paths.
		wp_mkdir_p( $themes_dir );

		// Verify the resolved path is inside the themes directory.
		$resolved_parent = realpath( dirname( $theme_path ) );
		$resolved_themes = realpath( $themes_dir );
		if ( false === $resolved_parent || false === $resolved_themes || $resolved_parent !== $resolved_themes ) {
			WP_CLI::error( 'Invalid theme slug. The resolved path is not inside the themes directory.' );
		}

		$force = Utils\get_flag_value( $assoc_args, 'force', false );

		if ( is_dir( $theme_path ) && ! $force ) {
			WP_CLI::error( "The theme directory '{$theme_slug}' already exists. Use --force to overwrite." );
		}

		// Build POST data.
		$post_body = [
			'underscoretw_generate' => '1',
			'underscoretw_name'     => $theme_name,
			'underscoretw_slug'     => $theme_slug,
		];

		$prefix = Utils\get_flag_value( $assoc_args, 'prefix' );
		if ( ! empty( $prefix ) ) {
			$prefix = sanitize_title( $prefix );
			if ( ! preg_match( self::TOKEN_PATTERN, str_replace( '-', '_', $prefix ) ) ) {
				WP_CLI::error( 'Invalid function prefix. Prefixes can contain only letters, numbers, underscores and hyphens, and must start with a letter or underscore.' );
			}
			$post_body['underscoretw_prefix'] = $prefix;
		}

		$author = Utils\get_flag_value( $assoc_args, 'author' );
		if ( ! empty( $author ) ) {
			$post_body['underscoretw_author'] = $author;
		}

		$description = Utils\get_flag_value( $assoc_args, 'description' );
		if ( ! empty( $description ) ) {
			$post_body['underscoretw_description'] = $description;
		}

		// Strip protocol from URIs — the generator prepends https://.
		$theme_uri = Utils\get_flag_value( $assoc_args, 'theme_uri' );
		if ( ! empty( $theme_uri ) ) {
			$result                        = preg_replace( '#^https?://#', '', $theme_uri );
			$post_body['underscoretw_uri'] = null !== $result ? $result : $theme_uri;
		}

		$author_uri = Utils\get_flag_value( $assoc_args, 'author_uri' );
		if ( ! empty( $author_uri ) ) {
			$result                               = preg_replace( '#^https?://#', '', $author_uri );
			$post_body['underscoretw_author_uri'] = null !== $result ? $result : $author_uri;
		}

		// Build the request URL.
		$url = 'https://underscoretw.com/';

		// Include CLI flag so the server can distinguish CLI requests from website requests.
		$post_body['underscoretw_cli'] = '1';

		WP_CLI::log( 'Downloading theme from underscoretw.com...' );

		// Download to a temp file.
		$tmpfname = wp_tempnam( $url );
		$response = wp_remote_post(
			$url,
			[
				'timeout'  => 60,
				'stream'   => true,
				'filename' => $tmpfname,
				'body'     => $post_body,
			]
		);

		// Validate response.
		if ( is_wp_error( $response ) ) {
			unlink( $tmpfname );
			WP_CLI::error( 'Could not download theme: ' . $response->get_error_message() );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== (int) $status_code ) {
			unlink( $tmpfname );
			WP_CLI::error( "Received unexpected HTTP status code: {$status_code}." );
		}

		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		if ( ! is_string( $content_type ) || false === strpos( $content_type, 'application/zip' ) ) {
			unlink( $tmpfname );
			WP_CLI::error( 'The response was not a ZIP file. The generator may be temporarily unavailable.' );
		}

		// Initialize WP_Filesystem for directory operations and unzipping.
		global $wp_filesystem;
		if ( ! WP_Filesystem() ) {
			unlink( $tmpfname );
			WP_CLI::error( 'Could not initialize the WordPress filesystem.' );
		}

		// When --force is set and the theme directory exists, unzip to a staging
		// directory first so the existing theme is preserved if the unzip fails.
		if ( is_dir( $theme_path ) && $force ) {
			$staging_dir = $themes_dir . '/.underscoretw-staging-' . wp_generate_uuid4();
			wp_mkdir_p( $staging_dir );

			WP_CLI::log( 'Extracting theme...' );

			$unzip_result = unzip_file( $tmpfname, $staging_dir );
			unlink( $tmpfname );

			if ( is_wp_error( $unzip_result ) ) {
				$wp_filesystem->delete( $staging_dir, true );
				WP_CLI::error( 'Could not extract theme: ' . $unzip_result->get_error_message() );
			}

			$staged_theme = $staging_dir . '/' . $theme_slug;
			if ( ! is_dir( $staged_theme ) ) {
				$wp_filesystem->delete( $staging_dir, true );
				WP_CLI::error( 'Could not extract theme: the archive did not contain the expected directory.' );
			}

			WP_CLI::log( "Removing existing theme directory '{$theme_slug}'..." );
			if ( ! $wp_filesystem->delete( $theme_path, true ) ) {
				$wp_filesystem->delete( $staging_dir, true );
				WP_CLI::error( "Could not remove existing theme directory '{$theme_slug}'." );
			}

			if ( ! $wp_filesystem->move( $staged_theme, $theme_path ) ) {
				$wp_filesystem->delete( $staging_dir, true );
				WP_CLI::error( 'Could not move new theme into place. The previous theme has been removed but the new theme is not installed. Try running the command again without --force.' );
			}

			$wp_filesystem->delete( $staging_dir, true );
		} else {
			WP_CLI::log( 'Extracting theme...' );

			$unzip_result = unzip_file( $tmpfname, $themes_dir );
			unlink( $tmpfname );

			if ( is_wp_error( $unzip_result ) ) {
				WP_CLI::error( 'Could not extract theme: ' . $unzip_result->get_error_message() );
			}
		}

		WP_CLI::success( "Created theme '{$theme_name}'." );

		// Activate if requested. Network-enable takes precedence as it is a superset of activation.
		if ( Utils\get_flag_value( $assoc_args, 'enable-network', false ) ) {
			WP_CLI::run_command( [ 'theme', 'enable', $theme_slug . '/theme' ], [ 'network' => true ] );
		} elseif ( Utils\get_flag_value( $assoc_args, 'activate', false ) ) {
			WP_CLI::run_command( [ 'theme', 'activate', $theme_slug . '/theme' ] );
		}
	}

	/**
	 * Runs the interactive wizard when no slug is provided.
	 *
	 * @param array<string, bool|string> $assoc_args CLI arguments used as prompt defaults.
	 * @return array{0: string[], 1: array<string, string>} [ $args, $assoc_args ] to feed back into __invoke().
	 */
	private function interactive_init( $assoc_args = [] ) {
		WP_CLI::log( 'This utility will walk you through generating a theme from underscoretw.com.' );
		WP_CLI::log( 'All values except the theme name can be left blank.' );
		WP_CLI::log( '' );
		WP_CLI::log( 'Press ^C at any time to quit.' );
		WP_CLI::log( '' );

		// 1. Theme Name (always has a default).
		$default_theme_name = (string) Utils\get_flag_value( $assoc_args, 'theme_name', '_tw' );
		\cli\out( "Theme Name ({$default_theme_name}): " );
		$theme_name = trim( \cli\input() );
		if ( '' === $theme_name ) {
			$theme_name = $default_theme_name;
		}

		// 2. Theme Slug (derived from name).
		$default_slug = sanitize_title( $theme_name );
		while ( true ) {
			\cli\out( "Theme Slug ({$default_slug}): " );
			$theme_slug = sanitize_title( trim( \cli\input() ) );
			if ( '' === $theme_slug ) {
				$theme_slug = sanitize_title( $default_slug );
			}

			if ( preg_match( self::TOKEN_PATTERN, str_replace( '-', '_', $theme_slug ) ) ) {
				break;
			}
			WP_CLI::warning( 'Invalid theme slug. Theme slugs can contain only letters, numbers, underscores and hyphens, and must start with a letter or underscore.' );
		}

		// 3. Function Prefix (derived from slug).
		$arg_prefix     = (string) Utils\get_flag_value( $assoc_args, 'prefix', '' );
		$default_prefix = '' !== $arg_prefix ? $arg_prefix : str_replace( '-', '_', $theme_slug );
		while ( true ) {
			\cli\out( "Function Prefix ({$default_prefix}): " );
			$prefix = sanitize_title( trim( \cli\input() ) );
			if ( '' === $prefix ) {
				$prefix = $default_prefix;
			}

			if ( preg_match( self::TOKEN_PATTERN, str_replace( '-', '_', $prefix ) ) ) {
				break;
			}
			WP_CLI::warning( 'Invalid function prefix. Prefixes can contain only letters, numbers, underscores and hyphens, and must start with a letter or underscore.' );
		}

		// 4-7. Optional fields.
		$default_author      = (string) Utils\get_flag_value( $assoc_args, 'author', '' );
		$default_author_uri  = (string) Utils\get_flag_value( $assoc_args, 'author_uri', '' );
		$default_theme_uri   = (string) Utils\get_flag_value( $assoc_args, 'theme_uri', '' );
		$default_description = (string) Utils\get_flag_value( $assoc_args, 'description', '' );

		\cli\out( '' !== $default_author ? "Author ({$default_author}): " : 'Author: ' );
		$author = trim( \cli\input() );
		if ( '' === $author ) {
			$author = $default_author;
		}

		\cli\out( '' !== $default_author_uri ? "Author URI ({$default_author_uri}): " : 'Author URI: ' );
		$author_uri = trim( \cli\input() );
		if ( '' === $author_uri ) {
			$author_uri = $default_author_uri;
		}

		\cli\out( '' !== $default_theme_uri ? "Theme URI ({$default_theme_uri}): " : 'Theme URI: ' );
		$theme_uri = trim( \cli\input() );
		if ( '' === $theme_uri ) {
			$theme_uri = $default_theme_uri;
		}

		\cli\out( '' !== $default_description ? "Description ({$default_description}): " : 'Description: ' );
		$description = trim( \cli\input() );
		if ( '' === $description ) {
			$description = $default_description;
		}

		// Print summary.
		WP_CLI::log( '' );
		WP_CLI::log( 'About to generate a theme with the following settings:' );
		WP_CLI::log( '' );
		WP_CLI::log( "  Theme Name:      {$theme_name}" );
		WP_CLI::log( "  Theme Slug:      {$theme_slug}" );
		WP_CLI::log( "  Function Prefix: {$prefix}" );

		if ( '' !== $author ) {
			WP_CLI::log( "  Author:          {$author}" );
		}
		if ( '' !== $author_uri ) {
			WP_CLI::log( "  Author URI:      {$author_uri}" );
		}
		if ( '' !== $theme_uri ) {
			WP_CLI::log( "  Theme URI:       {$theme_uri}" );
		}
		if ( '' !== $description ) {
			WP_CLI::log( "  Description:     {$description}" );
		}
		WP_CLI::log( '' );

		// Confirm.
		\cli\out( 'Is this OK? (yes) ' );
		$confirm = strtolower( trim( \cli\input() ) );
		if ( '' === $confirm ) {
			$confirm = 'yes';
		}
		if ( ! in_array( $confirm, [ 'y', 'yes' ], true ) ) {
			WP_CLI::log( 'Aborted.' );
			WP_CLI::halt( 1 );
		}

		// Build return values.
		$wizard_args       = [ $theme_slug ];
		$wizard_assoc_args = [
			'theme_name' => $theme_name,
		];

		if ( '' !== $prefix ) {
			$wizard_assoc_args['prefix'] = $prefix;
		}
		if ( '' !== $author ) {
			$wizard_assoc_args['author'] = $author;
		}
		if ( '' !== $author_uri ) {
			$wizard_assoc_args['author_uri'] = $author_uri;
		}
		if ( '' !== $theme_uri ) {
			$wizard_assoc_args['theme_uri'] = $theme_uri;
		}
		if ( '' !== $description ) {
			$wizard_assoc_args['description'] = $description;
		}
		return [ $wizard_args, $wizard_assoc_args ];
	}
}
