<?php
/**
 * Loads GA4 after the page has finished loading, so the 190 KB Google tag never competes with
 * the page itself on a slow connection.
 *
 * How it works:
 * - The theme prints the standard gtag('config') line in <head>; it only queues the page view.
 *   The Google tag that sends it is added once the page's load event has fired.
 * - While Cloudflare's Google tag gateway is on, Cloudflare puts its own loader
 *   (<script async src="/ptp3/">) at the top of every page it sends to a browser, and that one
 *   downloads straight away; the theme then adds nothing, so one copy runs. With the gateway
 *   switched off, the theme's delayed tag from googletagmanager.com takes over.
 * - Visitors who leave before the page has loaded aren't counted; everyone else is, with the
 *   same reports as before.
 * - Logged-in users aren't tracked, as with Site Kit's default. If Site Kit places its own
 *   Analytics code, the theme prints nothing and Site Kit's code loads as Site Kit decides.
 * - No DNS prefetch or preconnect for googletagmanager.com: nothing from there is needed early.
 * - Override in wp-config.php: AARDLING_THEME_GA4_ID ('G-...'; empty turns the theme's tag off),
 *   AARDLING_THEME_GA4_PATH (the gateway path whose loader counts as "already there", default
 *   /ptp3/) and AARDLING_THEME_GA4_SRC (the address of the delayed tag).
 *
 * @package Aardling
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The GA4 measurement ID, the gateway path and the address of the delayed tag.
 *
 * @return array{id: string, path: string, src: string}
 */
function aardling_theme_ga4() {
	$id   = defined( 'AARDLING_THEME_GA4_ID' ) ? (string) AARDLING_THEME_GA4_ID : 'G-F9EKVHYXXG';
	$path = defined( 'AARDLING_THEME_GA4_PATH' ) ? (string) AARDLING_THEME_GA4_PATH : '/ptp3/';
	$src  = defined( 'AARDLING_THEME_GA4_SRC' ) ? (string) AARDLING_THEME_GA4_SRC : 'https://www.googletagmanager.com/gtag/js';

	return array(
		'id'   => $id,
		'path' => '' === trim( $path, '/' ) ? '' : '/' . trim( $path, '/' ) . '/',
		'src'  => $src,
	);
}

/**
 * Drop the dns-prefetch and preconnect hints for googletagmanager.com that WordPress or Site
 * Kit still print: the tag only comes after the page has loaded.
 */
add_filter(
	'wp_resource_hints',
	function ( $urls, $relation_type ) {
		if ( ! in_array( $relation_type, array( 'dns-prefetch', 'preconnect' ), true ) ) {
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
		if ( '' === $ga4['id'] || '' === $ga4['src'] || is_user_logged_in() || is_customize_preview() ) {
			return;
		}
		if ( wp_script_is( 'google_gtagjs', 'enqueued' ) || wp_script_is( 'google_gtagjs', 'done' ) ) {
			return;
		}

		// A loader already on the page (Cloudflare's, from the gateway path, or any gtag.js) wins.
		$present = 'script[src*="/gtag/js"]' . ( '' === $ga4['path'] ? '' : ',script[src*="' . $ga4['path'] . '"]' );
		// noptimize: Autoptimize leaves the tag exactly as it is.
		printf(
			"<!--noptimize--><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config',%s);(function(){function l(){if(document.querySelector(%s)){return;}var s=document.createElement('script');s.async=true;s.src=%s;document.head.appendChild(s);}if(document.readyState==='complete'){setTimeout(l,0);}else{window.addEventListener('load',function(){setTimeout(l,0);});}})();</script><!--/noptimize-->\n",
			wp_json_encode( $ga4['id'] ),
			wp_json_encode( $present ),
			wp_json_encode( esc_url_raw( add_query_arg( 'id', rawurlencode( $ga4['id'] ), $ga4['src'] ) ) )
		);
	},
	2
);
