<?php
/**
 * Images in post content.
 *
 * Old addresses on the site's own domain that still start with http:// are written as https://
 * when the page is built. Browsers upgraded those images anyway, but reported mixed content
 * (Lighthouse's Best Practices). The posts themselves aren't changed.
 *
 * Cover blocks with a fixed (parallax) or repeated background show their photo as a CSS
 * background, and browsers load backgrounds straight away, wherever they are on the page:
 * "Patriarchal Panic" made a phone download ten section photos, 2.4 MB, before the reader
 * scrolled, and its first photo had to share the connection with all of them. The theme moves
 * each photo into a CSS variable and a few lines of script switch it on when the cover comes
 * within a screen's height of view. Without script the photos show as before.
 *
 * @package Aardling
 */

/**
 * http://aardling.com/… (or www.) becomes https://aardling.com/… in post content.
 *
 * @param string $content Post content.
 */
function aardling_theme_https_content( $content ) {
	$home = home_url();
	if ( 0 !== strpos( $home, 'https://' ) || false === stripos( (string) $content, 'http://' ) ) {
		return $content;
	}
	$host = (string) wp_parse_url( $home, PHP_URL_HOST );
	$bare = (string) preg_replace( '/^www\./i', '', $host );
	return (string) preg_replace( '#http://(?:www\.)?' . preg_quote( $bare, '#' ) . '(?![\w.-])#i', 'https://' . $host, (string) $content );
}
add_filter( 'the_content', 'aardling_theme_https_content', 8 );

/** True once a page has a cover whose photo waits for the script. */
function aardling_theme_lazy_backgrounds_used( bool $set = false ) : bool {
	static $used = false;
	if ( $set ) {
		$used = true;
	}
	return $used;
}

// background-image:url(…) in a cover block becomes --aardling-bg:url(…) plus a class.
add_filter(
	'render_block_core/cover',
	function ( $html ) {
		if ( false === stripos( (string) $html, 'background-image' ) || is_admin() || is_feed() || wp_is_json_request() || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $html;
		}
		$tags    = new WP_HTML_Tag_Processor( (string) $html );
		$changed = false;
		while ( $tags->next_tag() ) {
			$style = (string) $tags->get_attribute( 'style' );
			if ( ! preg_match( '/background-image\s*:\s*url\(/i', $style ) ) {
				continue;
			}
			$tags->set_attribute( 'style', (string) preg_replace( '/background-image(\s*:\s*url\()/i', '--aardling-bg$1', $style ) );
			$tags->add_class( 'aardling-lazy-bg' );
			$changed = true;
		}
		if ( ! $changed ) {
			return $html;
		}
		aardling_theme_lazy_backgrounds_used( true );
		return $tags->get_updated_html();
	}
);

add_action(
	'wp_footer',
	function () {
		if ( ! aardling_theme_lazy_backgrounds_used() ) {
			return;
		}
		echo '<style id="aardling-lazy-bg-css">.aardling-lazy-bg.is-bg-on{background-image:var(--aardling-bg)}</style>' . "\n";
		echo '<noscript><style>.aardling-lazy-bg{background-image:var(--aardling-bg)}</style></noscript>' . "\n";
		echo '<script id="aardling-lazy-bg-js">' . aardling_theme_lazy_backgrounds_script() . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed script.
	}
);

/**
 * Switches a cover's photo on once the cover is within a screen's height of view; all at once
 * in browsers without IntersectionObserver.
 */
function aardling_theme_lazy_backgrounds_script() : string {
	return <<<'JS'
(function(){var l=[].slice.call(document.querySelectorAll('.aardling-lazy-bg'));function on(e){e.classList.add('is-bg-on');}
if(!window.IntersectionObserver){l.forEach(on);return;}
var o=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){on(e.target);o.unobserve(e.target);}});},{rootMargin:'100% 0px'});
l.forEach(function(e){o.observe(e);});})();
JS;
}
