<?php
/**
 * A contents list for long posts: those with three or more h2 headings.
 *
 * How it works:
 * - Built from the post's h2 headings, with their h3s below them, when the page is made, so it's
 *   part of the cached page and needs no script.
 * - It sits right before the first h2, after the introduction, in a box readers can fold shut.
 * - Headings without an id get one from their text; the block editor usually gives them one.
 * - Has its own switch on Appearance > Aardling (inc/switches.php).
 *
 * @package Aardling
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const AARDLING_THEME_TOC_MIN = 3;

/**
 * The list for a post's content, and the content with an id on every heading. Returns
 * array( content, list ); the list is empty below AARDLING_THEME_TOC_MIN h2 headings.
 *
 * @param string $content Post content, as the_content made it.
 */
function aardling_theme_toc( string $content ) : array {
	if ( preg_match_all( '/<h2[\s>]/i', $content ) < AARDLING_THEME_TOC_MIN || ! preg_match_all( '/<h([23])(\s[^>]*)?>(.*?)<\/h\1>/is', $content, $headings, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		return array( $content, '' );
	}
	preg_match_all( '/\sid=(["\'])(.*?)\1/i', $content, $ids );
	$used = array_fill_keys( $ids[2], true );

	$out   = '';
	$from  = 0;
	$first = null;
	$items = array();
	foreach ( $headings as $heading ) {
		list( $tag, $at ) = $heading[0];
		$level            = (int) $heading[1][0];
		$attrs            = isset( $heading[2] ) ? (string) $heading[2][0] : '';
		$text             = trim( html_entity_decode( wp_strip_all_tags( $heading[3][0] ), ENT_QUOTES, 'UTF-8' ) );
		if ( 2 === $level && null === $first ) {
			$first = strlen( $out ) + ( $at - $from );
		}
		if ( '' === $text || null === $first ) {
			continue; // Empty, or an h3 in the introduction.
		}
		if ( preg_match( '/\sid=(["\'])(.*?)\1/i', $attrs, $id ) ) {
			$anchor = $id[2];
		} else {
			$base   = sanitize_title( $text );
			$base   = '' !== $base ? $base : 'kop';
			$anchor = $base;
			for ( $n = 2; isset( $used[ $anchor ] ); $n++ ) {
				$anchor = $base . '-' . $n;
			}
			$used[ $anchor ] = true;
			$out            .= substr( $content, $from, $at - $from ) . '<h' . $level . ' id="' . esc_attr( $anchor ) . '"' . $attrs . substr( $tag, strpos( $tag, '>' ) );
			$from            = $at + strlen( $tag );
		}
		if ( 2 === $level ) {
			$items[] = array( $anchor, $text, array() );
		} elseif ( $items ) {
			$items[ count( $items ) - 1 ][2][] = array( $anchor, $text );
		}
	}
	$out .= substr( $content, $from );

	$list = '';
	foreach ( $items as $item ) {
		$sub = '';
		foreach ( $item[2] as $child ) {
			$sub .= sprintf( '<li><a href="#%s">%s</a></li>', esc_attr( $child[0] ), esc_html( $child[1] ) );
		}
		$list .= sprintf( '<li><a href="#%s">%s</a>%s</li>', esc_attr( $item[0] ), esc_html( $item[1] ), '' !== $sub ? '<ol>' . $sub . '</ol>' : '' );
	}
	$list = sprintf(
		'<details class="aardling-toc" open><summary class="aardling-toc__label">%s</summary><ol class="aardling-toc__list">%s</ol></details>' . "\n",
		esc_html( 0 === strpos( get_locale(), 'nl' ) ? 'Inhoud' : 'Contents' ),
		$list
	);

	return array( substr( $out, 0, $first ) . $list . substr( $out, $first ), $list );
}

// After blocks, paragraphs and shortcodes are done; only the post being read.
add_filter( 'the_content', function ( $content ) {
	if ( ! aardling_theme_switch( 'toc' ) || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() || is_feed() ) {
		return $content;
	}
	return aardling_theme_toc( (string) $content )[0];
}, 20 );

// Styles, on single posts while the switch is on. Colours follow the reading boxes and Deluminator.
add_action( 'wp_head', function () {
	if ( ! aardling_theme_switch( 'toc' ) || ! is_singular( 'post' ) ) {
		return;
	}
	$sans = '-apple-system,BlinkMacSystemFont,"Segoe UI","Roboto","Oxygen","Ubuntu","Cantarell","Fira Sans","Droid Sans","Helvetica Neue",sans-serif';
	$css  = '.aardling-toc{--aardling-toc-bg:#fff;--aardling-toc-line:#e6e6e6;--aardling-toc-muted:#767676;border:1px solid var(--aardling-toc-line);background:var(--aardling-toc-bg);padding:1rem 1.5rem;line-height:1.35;hyphens:manual}'
		. 'html.deluminator-palette.deluminator-dark .aardling-toc{--aardling-toc-bg:var(--dl-surface);--aardling-toc-line:var(--dl-line);--aardling-toc-muted:var(--dl-muted)}'
		. '.aardling-toc__label{cursor:pointer;font-family:' . $sans . ';font-size:.7111em;font-weight:500;letter-spacing:.2em;text-transform:uppercase;color:var(--aardling-toc-muted)}'
		. '.aardling-toc__list{margin:.75rem 0 0;padding-left:1.4em;font-size:.9em}'
		. '.aardling-toc__list li{margin:.3em 0}'
		. '.aardling-toc__list ol{margin:.3em 0 0;padding-left:1.2em;font-size:.9em;list-style:lower-alpha}'
		. '.aardling-toc a{text-decoration:none}'
		. '.aardling-toc a:hover,.aardling-toc a:focus{text-decoration:underline}'
		. '.entry-content h2[id],.entry-content h3[id]{scroll-margin-top:1.5rem}';

	echo '<style id="aardling-toc-css">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed CSS.
}, 100 );
