<?php
/**
 * Loads GA4 through Google tag gateway: the Google tag comes from a path on aardling.com
 * instead of googletagmanager.com.
 *
 * How it works:
 * - The gateway itself lives at Cloudflare (set up from GA4 with Cloudflare). Requests to
 *   aardling.com/ptp3/ go to Google from there; WordPress never sees them.
 * - If Site Kit places its Analytics code, only the address of its script changes, so Site Kit's
 *   own events keep working. Otherwise the theme prints the standard Google tag itself.
 * - Cloudflare also puts its own loader (<script async src="/ptp3/">) at the top of every page it
 *   sends to a browser. That loader sends nothing until the theme's gtag('config') runs, and with
 *   the theme's own script the browser downloaded the 190 KB tag twice. So the theme loads the
 *   script only when Cloudflare's loader isn't on the page; either way one copy runs.
 * - Logged-in users aren't tracked, as with Site Kit's default.
 * - Nothing loads from googletagmanager.com any more, so its DNS prefetch hint goes too.
 * - The measurement ID and the path can be overridden with
 *   define( 'AARDLING_THEME_GA4_ID', 'G-...' ); and define( 'AARDLING_THEME_GA4_PATH', '/.../' );
 *   in wp-config.php. An empty ID turns the theme's tag off.
 *
 * @package Aardling
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The GA4 measurement ID and the gateway's address.
 *
 * @return array{id: string, url: string}
 */
function aardling_theme_ga4() {
	$id   = defined( 'AARDLING_THEME_GA4_ID' ) ? (string) AARDLING_THEME_GA4_ID : 'G-F9EKVHYXXG';
	$path = defined( 'AARDLING_THEME_GA4_PATH' ) ? (string) AARDLING_THEME_GA4_PATH : '/ptp3/';

	return array(
		'id'  => $id,
		'url' => '' === trim( $path, '/' ) ? '' : home_url( '/' . trim( $path, '/' ) . '/' ),
	);
}

/**
 * Site Kit's Analytics code: load the same script from the gateway.
 */
add_filter(
	'script_loader_src',
	function ( $src, $handle ) {
		$ga4 = aardling_theme_ga4();
		if ( 'google_gtagjs' !== $handle || '' === $ga4['url'] ) {
			return $src;
		}

		return str_replace( 'https://www.googletagmanager.com/gtag/js?', $ga4['url'] . '?', (string) $src );
	},
	10,
	2
);

/**
 * Drop the dns-prefetch and preconnect hints for googletagmanager.com that WordPress or Site
 * Kit still print: the tag comes from the gateway.
 */
add_filter(
	'wp_resource_hints',
	function ( $urls, $relation_type ) {
		$ga4 = aardling_theme_ga4();
		if ( '' === $ga4['url'] || ! in_array( $relation_type, array( 'dns-prefetch', 'preconnect' ), true ) ) {
			return $urls;
		}

		return array_values(
			array_filter(
				(array) $urls,
				function ( $url ) {
					$href = is_array( $url ) ? (string) ( $url['href'] ?? '' ) : (string) $url;
					return false === strpos( $href, 'googletagmanager.com' );
				}
			)
		);
	},
	PHP_INT_MAX,
	2
);

/**
 * No Site Kit Analytics code: print the Google tag. Runs after wp_enqueue_scripts (wp_head 1),
 * so Site Kit's script is already enqueued when it places one.
 */
add_action(
	'wp_head',
	function () {
		$ga4 = aardling_theme_ga4();
		if ( '' === $ga4['id'] || '' === $ga4['url'] || is_user_logged_in() || is_customize_preview() ) {
			return;
		}
		if ( wp_script_is( 'google_gtagjs', 'enqueued' ) || wp_script_is( 'google_gtagjs', 'done' ) ) {
			return;
		}

		// Cloudflare's loader sits above this in <head>, so the check sees it. Any script from the
		// gateway path counts: the theme's own isn't on the page yet.
		// noptimize: Autoptimize leaves the tag exactly as it is.
		printf(
			"<!--noptimize--><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config',%s);if(!document.querySelector(%s)){var s=document.createElement('script');s.async=true;s.src=%s;document.head.appendChild(s);}</script><!--/noptimize-->\n",
			wp_json_encode( $ga4['id'] ),
			wp_json_encode( 'script[src*="' . wp_parse_url( $ga4['url'], PHP_URL_PATH ) . '"]' ),
			wp_json_encode( esc_url_raw( add_query_arg( 'id', rawurlencode( $ga4['id'] ), $ga4['url'] ) ) )
		);
	},
	2
);
