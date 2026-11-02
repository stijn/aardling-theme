<?php
/**
 * Clears pages from Cloudflare's cache when they change.
 *
 * How it works:
 * - A Cloudflare Cache Rule keeps aardling.com's pages at the edge. Without APO, nothing tells
 *   Cloudflare when a page changes, so this file does: publishing, editing or unpublishing a
 *   post, changing its terms or approving a comment queues that post, the homepage, its term,
 *   author and year archives (with their /page/N/ and /feed/), and the post's own feed.
 *   Menus, the Customizer, widgets, the site title, a theme update and a full WP Super Cache
 *   clear queue the whole site.
 * - The queue goes to Cloudflare once, at the end of the request: one call for URLs, one for
 *   prefixes, 100 items each. More than 300 items in one request, as in a bulk edit, becomes a
 *   single purge of everything. The Free plan allows 5 prefix or everything calls a minute,
 *   with bursts of 25.
 * - Needs the zone ID and an API token with only Zone > Cache Purge, from
 *   define( 'AARDLING_THEME_CLOUDFLARE_ZONE', '...' ); and
 *   define( 'AARDLING_THEME_CLOUDFLARE_TOKEN', '...' ); in wp-config.php, or the fields on
 *   Appearance > Aardling. The token is never shown again after saving, logged or returned.
 * - Without both, nothing is sent.
 *
 * @package Aardling
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const AARDLING_THEME_CLOUDFLARE_API       = 'https://api.cloudflare.com/client/v4/zones/';
const AARDLING_THEME_CLOUDFLARE_BATCH     = 100;
const AARDLING_THEME_CLOUDFLARE_MAX_ITEMS = 300;

function aardling_theme_cloudflare_zone() : string {
	if ( defined( 'AARDLING_THEME_CLOUDFLARE_ZONE' ) && AARDLING_THEME_CLOUDFLARE_ZONE ) {
		return (string) AARDLING_THEME_CLOUDFLARE_ZONE;
	}
	return (string) get_option( 'aardling_theme_cloudflare_zone', '' );
}

/**
 * Where the Cloudflare token comes from: 'theme-config', 'theme-option' or '' (none).
 */
function aardling_theme_cloudflare_token_source() : string {
	if ( defined( 'AARDLING_THEME_CLOUDFLARE_TOKEN' ) && AARDLING_THEME_CLOUDFLARE_TOKEN ) {
		return 'theme-config';
	}
	if ( '' !== (string) get_option( 'aardling_theme_cloudflare_token', '' ) ) {
		return 'theme-option';
	}
	return '';
}

function aardling_theme_cloudflare_token() : string {
	switch ( aardling_theme_cloudflare_token_source() ) {
		case 'theme-config':
			return (string) AARDLING_THEME_CLOUDFLARE_TOKEN;
		case 'theme-option':
			return (string) get_option( 'aardling_theme_cloudflare_token', '' );
	}
	return '';
}

function aardling_theme_cloudflare_configured() : bool {
	return '' !== aardling_theme_cloudflare_zone() && '' !== aardling_theme_cloudflare_token();
}

/**
 * The purge queue for this request. 'files' and 'prefixes' add an item, 'everything' marks
 * the whole site, 'reset' empties it. Always returns the queue.
 */
function aardling_theme_cloudflare_queue( string $type = '', string $item = '' ) : array {
	static $queue = array( 'everything' => false, 'files' => array(), 'prefixes' => array() );

	if ( 'everything' === $type ) {
		$queue['everything'] = true;
	} elseif ( ( 'files' === $type || 'prefixes' === $type ) && '' !== $item ) {
		$queue[ $type ][ $item ] = true;
	} elseif ( 'reset' === $type ) {
		$queue = array( 'everything' => false, 'files' => array(), 'prefixes' => array() );
	}

	return $queue;
}

/**
 * Only URLs on the site's own host; Cloudflare refuses anything outside the zone.
 */
function aardling_theme_cloudflare_on_site( $url ) : bool {
	$host = wp_parse_url( (string) $url, PHP_URL_HOST );
	return is_string( $host ) && strtolower( $host ) === strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
}

function aardling_theme_cloudflare_queue_url( $url ) : void {
	if ( is_string( $url ) && aardling_theme_cloudflare_on_site( $url ) ) {
		aardling_theme_cloudflare_queue( 'files', set_url_scheme( $url, 'https' ) );
	}
}

/**
 * Queue a path and everything below it (its /page/N/ and /feed/). Cloudflare takes prefixes
 * as host + path, without the scheme.
 */
function aardling_theme_cloudflare_queue_prefix( $url ) : void {
	if ( ! is_string( $url ) || ! aardling_theme_cloudflare_on_site( $url ) ) {
		return;
	}
	$path = rtrim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
	if ( '' === $path ) {
		return; // The bare host would clear the whole site; that's 'everything'.
	}
	aardling_theme_cloudflare_queue( 'prefixes', strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) ) . $path );
}

/**
 * Queue a post's own page and every listing it shows up on.
 *
 * @param int|WP_Post $post Post.
 */
function aardling_theme_cloudflare_queue_post( $post ) : void {
	$post = get_post( $post );
	if ( ! $post || wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) || ! is_post_type_viewable( $post->post_type ) ) {
		return;
	}

	aardling_theme_cloudflare_queue_prefix( get_permalink( $post ) );
	aardling_theme_cloudflare_queue_url( home_url( '/' ) );
	aardling_theme_cloudflare_queue_url( get_feed_link() );
	aardling_theme_cloudflare_queue_prefix( home_url( '/' . $GLOBALS['wp_rewrite']->pagination_base . '/' ) );

	foreach ( get_object_taxonomies( $post->post_type ) as $taxonomy ) {
		if ( ! is_taxonomy_viewable( $taxonomy ) ) {
			continue;
		}
		$terms = get_the_terms( $post, $taxonomy );
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			$link = get_term_link( $term );
			if ( ! is_wp_error( $link ) ) {
				aardling_theme_cloudflare_queue_prefix( $link );
			}
		}
	}

	aardling_theme_cloudflare_queue_prefix( get_author_posts_url( (int) $post->post_author ) );

	if ( 'post' === $post->post_type ) {
		aardling_theme_cloudflare_queue_prefix( get_year_link( (int) get_the_date( 'Y', $post ) ) );
		if ( get_option( 'page_for_posts' ) ) {
			aardling_theme_cloudflare_queue_prefix( get_permalink( (int) get_option( 'page_for_posts' ) ) );
		}
	} else {
		aardling_theme_cloudflare_queue_prefix( get_post_type_archive_link( $post->post_type ) );
	}
}

function aardling_theme_cloudflare_queue_term( $term ) : void {
	$link = get_term_link( $term );
	if ( ! is_wp_error( $link ) ) {
		aardling_theme_cloudflare_queue_prefix( $link );
	}
}

function aardling_theme_cloudflare_is_published( $post ) : bool {
	$post = get_post( $post );
	return $post && 'publish' === $post->post_status && is_post_type_viewable( $post->post_type );
}

/**
 * Send one purge call. Returns array( bool ok, string message ); the message is Cloudflare's
 * error text and never contains the token.
 *
 * @param array $body purge_cache request body.
 */
function aardling_theme_cloudflare_request( array $body ) : array {
	$response = wp_remote_post(
		AARDLING_THEME_CLOUDFLARE_API . rawurlencode( aardling_theme_cloudflare_zone() ) . '/purge_cache',
		array(
			'timeout' => 10,
			'headers' => array(
				'Authorization' => 'Bearer ' . aardling_theme_cloudflare_token(),
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
		)
	);

	if ( is_wp_error( $response ) ) {
		return array( false, $response->get_error_message() );
	}

	$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	if ( is_array( $data ) && ! empty( $data['success'] ) ) {
		return array( true, '' );
	}

	$errors = array();
	foreach ( ( is_array( $data ) && isset( $data['errors'] ) ) ? (array) $data['errors'] : array() as $error ) {
		$errors[] = ( isset( $error['code'] ) ? $error['code'] . ': ' : '' ) . ( isset( $error['message'] ) ? $error['message'] : '' );
	}
	return array( false, $errors ? implode( '; ', $errors ) : 'HTTP ' . wp_remote_retrieve_response_code( $response ) );
}

/**
 * Send the queue: everything, or URLs and prefixes in batches of 100. Keeps the outcome for
 * Appearance > Aardling.
 */
function aardling_theme_cloudflare_flush() : void {
	$queue = aardling_theme_cloudflare_queue();
	aardling_theme_cloudflare_queue( 'reset' );

	$files    = array_keys( $queue['files'] );
	$prefixes = array_keys( $queue['prefixes'] );
	if ( ! $queue['everything'] && ! $files && ! $prefixes ) {
		return;
	}
	if ( ! aardling_theme_cloudflare_configured() ) {
		return;
	}

	$calls = array();
	if ( $queue['everything'] || count( $files ) + count( $prefixes ) > AARDLING_THEME_CLOUDFLARE_MAX_ITEMS ) {
		$what    = 'the whole site';
		$calls[] = array( 'purge_everything' => true );
	} else {
		$what = sprintf( '%d URLs and %d paths', count( $files ), count( $prefixes ) );
		foreach ( array_chunk( $files, AARDLING_THEME_CLOUDFLARE_BATCH ) as $chunk ) {
			$calls[] = array( 'files' => $chunk );
		}
		foreach ( array_chunk( $prefixes, AARDLING_THEME_CLOUDFLARE_BATCH ) as $chunk ) {
			$calls[] = array( 'prefixes' => $chunk );
		}
	}

	// Answer the visitor or editor first when PHP-FPM allows it; WordPress's wp-cron.php does the same.
	if ( function_exists( 'fastcgi_finish_request' ) ) {
		fastcgi_finish_request();
	}

	$errors = array();
	foreach ( $calls as $body ) {
		list( $ok, $message ) = aardling_theme_cloudflare_request( $body );
		if ( ! $ok ) {
			$errors[] = $message;
		}
	}

	aardling_theme_cloudflare_remember( $what, $errors );
}

function aardling_theme_cloudflare_remember( string $what, array $errors ) : void {
	update_option(
		'aardling_theme_cloudflare_last',
		array(
			'time'  => time(),
			'what'  => $what,
			'ok'    => ! $errors,
			'error' => implode( ' | ', array_unique( $errors ) ),
		),
		false
	);
}

add_action( 'shutdown', 'aardling_theme_cloudflare_flush', PHP_INT_MAX );

// Posts: publishing, editing, unpublishing, scheduled posts going live, trashing, slug changes.
add_action( 'transition_post_status', function ( $new_status, $old_status, $post ) {
	if ( 'publish' === $new_status || 'publish' === $old_status ) {
		aardling_theme_cloudflare_queue_post( $post );
	}
}, 10, 3 );

// Before an update or trash changes the slug, so the old address is cleared too.
add_action( 'pre_post_update', function ( $post_id ) {
	if ( aardling_theme_cloudflare_is_published( $post_id ) ) {
		aardling_theme_cloudflare_queue_post( $post_id );
	}
} );
add_action( 'wp_trash_post', function ( $post_id ) {
	if ( aardling_theme_cloudflare_is_published( $post_id ) ) {
		aardling_theme_cloudflare_queue_post( $post_id );
	}
} );

// Terms added to or removed from a published post: the post and the old and new term archives.
add_action( 'set_object_terms', function ( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
	if ( ! aardling_theme_cloudflare_is_published( $object_id ) || ! is_taxonomy_viewable( $taxonomy ) ) {
		return;
	}
	aardling_theme_cloudflare_queue_post( $object_id );
	foreach ( array_unique( array_merge( (array) $tt_ids, (array) $old_tt_ids ) ) as $tt_id ) {
		$term = get_term_by( 'term_taxonomy_id', (int) $tt_id, $taxonomy );
		if ( $term ) {
			aardling_theme_cloudflare_queue_term( $term );
		}
	}
}, 10, 6 );

// A term's name, slug or description changed, or it's about to be deleted.
add_action( 'edited_term', function ( $term_id, $tt_id, $taxonomy ) {
	if ( is_taxonomy_viewable( $taxonomy ) ) {
		aardling_theme_cloudflare_queue_term( get_term( (int) $term_id, $taxonomy ) );
	}
}, 10, 3 );
add_action( 'pre_delete_term', function ( $term_id, $taxonomy ) {
	if ( is_taxonomy_viewable( $taxonomy ) ) {
		aardling_theme_cloudflare_queue_term( get_term( (int) $term_id, $taxonomy ) );
	}
}, 10, 2 );

// Comments that appear or disappear: the post page and the homepage (comment counts).
$aardling_theme_cloudflare_comment = function ( $comment ) {
	$comment = get_comment( $comment );
	if ( $comment && aardling_theme_cloudflare_is_published( (int) $comment->comment_post_ID ) ) {
		aardling_theme_cloudflare_queue_prefix( get_permalink( (int) $comment->comment_post_ID ) );
		aardling_theme_cloudflare_queue_url( home_url( '/' ) );
	}
};
add_action( 'transition_comment_status', function ( $new_status, $old_status, $comment ) use ( $aardling_theme_cloudflare_comment ) {
	if ( 'approved' === $new_status || 'approved' === $old_status ) {
		$aardling_theme_cloudflare_comment( $comment );
	}
}, 10, 3 );
add_action( 'comment_post', function ( $comment_id, $approved ) use ( $aardling_theme_cloudflare_comment ) {
	if ( 1 === $approved || '1' === $approved ) {
		$aardling_theme_cloudflare_comment( $comment_id );
	}
}, 10, 2 );
add_action( 'edit_comment', function ( $comment_id ) use ( $aardling_theme_cloudflare_comment ) {
	if ( 'approved' === wp_get_comment_status( $comment_id ) ) {
		$aardling_theme_cloudflare_comment( $comment_id );
	}
} );

// Changes that show on every page.
$aardling_theme_cloudflare_everything = function () {
	aardling_theme_cloudflare_queue( 'everything' );
};
foreach ( array(
	'switch_theme',
	'customize_save_after',
	'wp_update_nav_menu',
	'wp_cache_cleared',                // WP Super Cache emptied (its Delete Cache button, Autoptimize's clear).
	'autoptimize_action_cachepurged',  // Autoptimize's CSS and JS files were replaced.
	'update_option_blogname',
	'update_option_blogdescription',
	'update_option_site_icon',
	'update_option_sidebars_widgets',
	'rest_after_save_widget',
) as $aardling_theme_cloudflare_action ) {
	add_action( $aardling_theme_cloudflare_action, $aardling_theme_cloudflare_everything );
}
add_action( 'upgrader_process_complete', function ( $upgrader, $extra ) {
	if ( isset( $extra['type'] ) && 'theme' === $extra['type'] ) {
		aardling_theme_cloudflare_queue( 'everything' );
	}
}, 10, 2 );

// Appearance > Aardling: zone ID, token, last purge, and two buttons.
add_action( 'admin_init', function () {
	register_setting( 'aardling_theme_cloudflare', 'aardling_theme_cloudflare_zone', array(
		'type'              => 'string',
		'show_in_rest'      => false,
		'sanitize_callback' => function ( $value ) {
			$value = strtolower( trim( (string) $value ) );
			if ( '__clear__' === $value ) {
				return '';
			}
			if ( '' === $value || ! preg_match( '/^[a-f0-9]{32}$/', $value ) ) {
				if ( '' !== $value ) {
					add_settings_error( 'aardling_theme_cloudflare_zone', 'aardling_theme_cloudflare_zone', 'That isn\'t a zone ID: it has 32 letters and digits, on the Overview page of aardling.com in Cloudflare.' );
				}
				return (string) get_option( 'aardling_theme_cloudflare_zone', '' );
			}
			return $value;
		},
	) );
	register_setting( 'aardling_theme_cloudflare', 'aardling_theme_cloudflare_token', array(
		'type'              => 'string',
		'show_in_rest'      => false,
		'sanitize_callback' => function ( $value ) {
			$value = trim( (string) $value );
			if ( '' === $value ) {
				return (string) get_option( 'aardling_theme_cloudflare_token', '' ); // Empty field keeps the saved token.
			}
			if ( '__clear__' === $value ) {
				return '';
			}
			return sanitize_text_field( $value );
		},
	) );
} );

$aardling_theme_cloudflare_button = function ( string $what, array $body ) {
	return function () use ( $what, $body ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'This user may not change site settings.' );
		}
		check_admin_referer( 'aardling_theme_cloudflare_purge' );
		if ( aardling_theme_cloudflare_configured() ) {
			list( $ok, $message ) = aardling_theme_cloudflare_request( $body );
			aardling_theme_cloudflare_remember( $what, $ok ? array() : array( $message ) );
		}
		wp_safe_redirect( admin_url( 'themes.php?page=aardling-theme' ) );
		exit;
	};
};
add_action( 'admin_post_aardling_theme_cloudflare_test', $aardling_theme_cloudflare_button( 'the homepage (test)', array( 'files' => array( set_url_scheme( home_url( '/' ), 'https' ) ) ) ) );
add_action( 'admin_post_aardling_theme_cloudflare_purge_all', $aardling_theme_cloudflare_button( 'the whole site (button)', array( 'purge_everything' => true ) ) );

add_action( 'aardling_theme_settings_page', function () {
	$source = aardling_theme_cloudflare_token_source();
	$zone   = aardling_theme_cloudflare_zone();
	$last   = get_option( 'aardling_theme_cloudflare_last', array() );
	?>
	<h2>Cloudflare cache</h2>
	<p>When a post, its terms or its comments change, the theme clears that post, the homepage and its archive pages from Cloudflare. Menu, widget, Customizer and theme changes clear the whole site.</p>
	<p>Status: <?php echo esc_html( aardling_theme_cloudflare_configured() ? 'on.' : 'off until both the zone ID and the token are set.' ); ?>
	<?php if ( is_array( $last ) && ! empty( $last['time'] ) ) : ?>
		Last purge: <?php echo esc_html( $last['what'] . ', ' . human_time_diff( (int) $last['time'] ) . ' ago, ' . ( $last['ok'] ? 'OK.' : 'failed: ' . $last['error'] ) ); ?>
	<?php endif; ?></p>
	<form method="post" action="options.php">
		<?php settings_fields( 'aardling_theme_cloudflare' ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="aardling_theme_cloudflare_zone">Zone ID</label></th>
				<td>
					<?php if ( defined( 'AARDLING_THEME_CLOUDFLARE_ZONE' ) && AARDLING_THEME_CLOUDFLARE_ZONE ) : ?>
						<code><?php echo esc_html( $zone ); ?></code> (set in wp-config.php)
					<?php else : ?>
						<input type="text" id="aardling_theme_cloudflare_zone" name="aardling_theme_cloudflare_zone" value="<?php echo esc_attr( $zone ); ?>" class="regular-text code" autocomplete="off">
						<p class="description">Cloudflare &rarr; aardling.com &rarr; Overview, in the API box on the right.</p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="aardling_theme_cloudflare_token">API token</label></th>
				<td>
					<?php if ( 'theme-config' === $source ) : ?>
						Set in wp-config.php.
					<?php else : ?>
						<input type="password" id="aardling_theme_cloudflare_token" name="aardling_theme_cloudflare_token" value="" class="regular-text" autocomplete="off" placeholder="<?php echo 'theme-option' === $source ? esc_attr( 'Saved. Paste a new one to replace it.' ) : ''; ?>">
						<p class="description">My Profile &rarr; API Tokens &rarr; Create Token &rarr; Custom token, with only the permission Zone &rarr; Cache Purge &rarr; Purge, for aardling.com. Leave empty to keep a saved token, or type __clear__ to remove it.</p>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php submit_button( 'Save Cloudflare settings' ); ?>
	</form>
	<?php if ( aardling_theme_cloudflare_configured() ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:8px">
			<input type="hidden" name="action" value="aardling_theme_cloudflare_test">
			<?php wp_nonce_field( 'aardling_theme_cloudflare_purge' ); ?>
			<?php submit_button( 'Test: clear the homepage', 'secondary', 'submit', false ); ?>
		</form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block">
			<input type="hidden" name="action" value="aardling_theme_cloudflare_purge_all">
			<?php wp_nonce_field( 'aardling_theme_cloudflare_purge' ); ?>
			<?php submit_button( 'Clear the whole site', 'secondary', 'submit', false ); ?>
		</form>
	<?php endif; ?>
	<?php
} );
