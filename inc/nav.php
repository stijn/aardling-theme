<?php
/**
 * Previous and next arrows at the sides of each post, on screens from 1024 px wide.
 *
 * How it works:
 * - A thin < at the left edge leads to the older post, a > at the right edge to the newer one.
 *   Pointing at one, or tabbing to it, opens a card with that post's title, and with its image
 *   when that switch is on (Appearance > Aardling, see inc/switches.php).
 * - The card is display:none until then and its image is loading="lazy", so the image downloads
 *   only when someone points at the arrow. No script.
 * - Twenty Nineteen's links under the post stay, for narrower screens.
 * - The posts beside a changed post show its title and image, in the arrows and in Twenty
 *   Nineteen's links, so their cached pages are cleared too (WP Super Cache and Cloudflare): the
 *   ones beside it before the change, as a new date moves it away, and after it.
 *
 * @package Aardling
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The published posts right before and after a post, as visitors see them in Twenty Nineteen's
 * links under it.
 *
 * @param WP_Post $post Post.
 * @return WP_Post[] 'prev' (older) and 'next' (newer), where there is one.
 */
function aardling_theme_nav_adjacent( WP_Post $post ) : array {
	$adjacent = array();
	foreach ( array( 'prev' => array( 'before', 'DESC' ), 'next' => array( 'after', 'ASC' ) ) as $rel => $way ) {
		$found = get_posts(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'numberposts'         => 1,
				'post__not_in'        => array( $post->ID ),
				'date_query'          => array( array( $way[0] => $post->post_date, 'inclusive' => false ) ),
				'orderby'             => 'date',
				'order'               => $way[1],
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);
		if ( $found ) {
			$adjacent[ $rel ] = $found[0];
		}
	}
	return $adjacent;
}

/**
 * One arrow with its card.
 *
 * @param WP_Post $post The post it leads to.
 * @param string  $rel  'prev' or 'next'.
 */
function aardling_theme_nav_link( WP_Post $post, string $rel ) : string {
	$item  = aardling_theme_reads_item( $post, aardling_theme_switch( 'nav_thumbs' ) );
	$label = 'prev' === $rel ? __( 'Previous Post', 'twentynineteen' ) : __( 'Next Post', 'twentynineteen' );

	return sprintf(
		'<a class="aardling-nav__link aardling-nav__link--%1$s" href="%2$s" rel="%1$s">'
		. '<svg class="aardling-nav__arrow" viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" focusable="false"><path d="%3$s"/></svg>'
		. '<span class="screen-reader-text">%4$s: %5$s</span>'
		. '<span class="aardling-nav__card" aria-hidden="true">%6$s<span class="aardling-nav__label">%4$s</span><span class="aardling-nav__title">%5$s</span></span></a>',
		$rel,
		esc_url( $item['url'] ),
		'prev' === $rel ? 'M15 4l-8 8 8 8' : 'M9 4l8 8-8 8',
		esc_html( $label ),
		esc_html( $item['title'] ),
		aardling_theme_reads_thumb_html( $item, 'aardling-nav__thumb' )
	);
}

add_action( 'wp_footer', function () {
	if ( ! aardling_theme_switch( 'nav_arrows' ) || ! is_singular( 'post' ) ) {
		return;
	}
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return;
	}
	$links = '';
	foreach ( aardling_theme_nav_adjacent( $post ) as $rel => $adjacent ) {
		$links .= aardling_theme_nav_link( $adjacent, $rel );
	}
	if ( '' !== $links ) {
		printf(
			'<nav class="aardling-nav" aria-label="%s">%s</nav>' . "\n",
			esc_attr( 0 === strpos( get_locale(), 'nl' ) ? 'Vorig en volgend bericht' : 'Previous and next post' ),
			$links // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the link.
		);
	}
}, 5 );

// Styles, on single posts while the arrows are on. Colours are variables, switched to
// Deluminator's palette (--dl-*) in its dark mode; they sit on the arrow and the card's text, not
// on the link, which Deluminator colours.
add_action( 'wp_head', function () {
	if ( ! aardling_theme_switch( 'nav_arrows' ) || ! is_singular( 'post' ) ) {
		return;
	}
	$sans = '-apple-system,BlinkMacSystemFont,"Segoe UI","Roboto","Oxygen","Ubuntu","Cantarell","Fira Sans","Droid Sans","Helvetica Neue",sans-serif';
	$css  = '.aardling-nav{--aardling-nav-bg:#fff;--aardling-nav-line:#e6e6e6;--aardling-nav-muted:#767676;--aardling-nav-text:#111;display:none}'
		. 'html.deluminator-palette.deluminator-dark .aardling-nav{--aardling-nav-bg:var(--dl-surface);--aardling-nav-line:var(--dl-line);--aardling-nav-muted:var(--dl-muted);--aardling-nav-text:var(--dl-text)}'
		. '@media screen and (min-width:1024px){.aardling-nav{display:block}}'
		. '.aardling-nav__link{position:fixed;z-index:10;top:50%;display:flex;align-items:center;height:80px;margin-top:-40px;padding:0 10px;text-decoration:none}'
		. '.aardling-nav__link--prev{left:0}'
		. '.aardling-nav__link--next{right:0;flex-direction:row-reverse}'
		. '.aardling-nav__arrow{flex:none;color:var(--aardling-nav-muted);transition:color .15s}'
		. '.aardling-nav__arrow path{fill:none;stroke:currentColor;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round}'
		. '.aardling-nav__link:hover .aardling-nav__arrow,.aardling-nav__link:focus .aardling-nav__arrow{color:var(--aardling-nav-text)}'
		// The card: 200 px inside, the medium image's own width. Taller cards stick out of the link
		// above and below, centred on the arrow.
		. '.aardling-nav__card{display:none;box-sizing:content-box;width:200px;margin:0 6px;padding:15px;border:1px solid var(--aardling-nav-line);background:var(--aardling-nav-bg);box-shadow:0 2px 12px rgba(0,0,0,.12);line-height:1.35}'
		. '.aardling-nav__link:hover .aardling-nav__card,.aardling-nav__link:focus-visible .aardling-nav__card{display:block}'
		. '.aardling-nav__thumb{display:block;max-width:100%;height:auto;margin:0 0 .75rem;border-radius:10px}'
		. '.aardling-nav__label{display:block;margin-bottom:.4rem;font-family:' . $sans . ';font-size:.6em;letter-spacing:.12em;text-transform:uppercase;color:var(--aardling-nav-muted)}'
		. '.aardling-nav__title{display:block;font-size:.8em;overflow-wrap:break-word;color:var(--aardling-nav-text)}'
		. '.aardling-nav__link:hover .aardling-nav__title{text-decoration:underline}';

	echo '<style id="aardling-nav-css">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed CSS.
}, 100 );

// The neighbours' cached pages: before a change to a published post, and at the end of any request
// that published, changed or unpublished one (after its featured image is saved), before the
// Cloudflare purge goes out.
$GLOBALS['aardling_theme_nav_forget'] = array();
$GLOBALS['aardling_theme_nav_moved']  = array();
add_action( 'pre_post_update', function ( $post_id ) {
	$post = get_post( $post_id );
	if ( $post instanceof WP_Post && 'post' === $post->post_type && 'publish' === $post->post_status ) {
		foreach ( aardling_theme_nav_adjacent( $post ) as $adjacent ) {
			$GLOBALS['aardling_theme_nav_forget'][ $adjacent->ID ] = true;
		}
	}
} );
add_action( 'transition_post_status', function ( $new_status, $old_status, $post ) {
	if ( $post instanceof WP_Post && 'post' === $post->post_type && ( 'publish' === $new_status || 'publish' === $old_status ) ) {
		$GLOBALS['aardling_theme_nav_moved'][ $post->ID ] = true;
	}
}, 10, 3 );
add_action( 'shutdown', function () {
	foreach ( array_keys( $GLOBALS['aardling_theme_nav_moved'] ) as $id ) {
		$post = get_post( $id );
		foreach ( $post instanceof WP_Post ? aardling_theme_nav_adjacent( $post ) : array() as $adjacent ) {
			$GLOBALS['aardling_theme_nav_forget'][ $adjacent->ID ] = true;
		}
	}
	foreach ( array_keys( $GLOBALS['aardling_theme_nav_forget'] ) as $id ) {
		$url = (string) get_permalink( $id );
		aardling_theme_cloudflare_queue_url( $url );
		if ( function_exists( 'wpsc_delete_url_cache' ) ) {
			wpsc_delete_url_cache( $url );
		}
	}
	$GLOBALS['aardling_theme_nav_forget'] = array();
	$GLOBALS['aardling_theme_nav_moved']  = array();
}, 0 );
