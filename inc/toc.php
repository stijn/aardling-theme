<?php
/**
 * A contents list for long posts: those with three or more h2 headings.
 *
 * How it works:
 * - Built from the post's h2 headings, with their h3s below them, when the page is made, so it's
 *   part of the cached page.
 * - Narrower screens: it sits after the introduction, in a box readers can fold shut: before the
 *   top-level block that holds the first h2, so a heading inside a cover or group doesn't get the
 *   box inside it. Blocks are counted as they render to know which ones are top-level.
 * - From 1168 px wide: a small bar of lines in the left margin instead, one line per heading,
 *   that stays in view while the post's sections scroll past (position: sticky, so it starts at
 *   the first heading and leaves with the end of the post; Twenty Nineteen's overflow: hidden on
 *   .site-content becomes overflow: clip there, which looks the same but lets it stick). Pointing at it, or tabbing into it,
 *   opens the list beside it. A few lines of script mark the section being read; without them
 *   the list still works.
 * - Headings without an id get one from their text; the block editor usually gives them one.
 * - Colours follow Deluminator's dark palette. Has its own switch on Appearance > Aardling
 *   (inc/switches.php).
 *
 * @package Aardling
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const AARDLING_THEME_TOC_MIN  = 3;
const AARDLING_THEME_TOC_MARK = '<!--aardling-toc-->';

function aardling_theme_toc_wanted() : bool {
	return aardling_theme_switch( 'toc' ) && is_singular( 'post' ) && in_the_loop() && is_main_query() && ! is_feed();
}

/**
 * The lists for a post's content, and the content with an id on every heading. Returns
 * array( content, lists ); the lists are empty below AARDLING_THEME_TOC_MIN h2 headings. They go
 * where AARDLING_THEME_TOC_MARK is, or else right before the first h2.
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

	$label = 0 === strpos( get_locale(), 'nl' ) ? 'Inhoud' : 'Contents';
	$box   = '';
	$lines = '';
	$float = '';
	$i     = 0;
	foreach ( $items as $item ) {
		$sub = '';
		foreach ( array_merge( array( $item ), $item[2] ) as $n => $entry ) {
			$class  = $n ? ' class="aardling-toc-float__sub"' : '';
			$lines .= '<span class="aardling-toc-float__line' . ( $n ? ' aardling-toc-float__line--sub' : '' ) . '"></span>';
			$float .= sprintf( '<li%s style="--i:%d"><a href="#%s">%s</a></li>', $class, $i++, esc_attr( $entry[0] ), esc_html( $entry[1] ) );
			if ( $n ) {
				$sub .= sprintf( '<li><a href="#%s">%s</a></li>', esc_attr( $entry[0] ), esc_html( $entry[1] ) );
			}
		}
		$box .= sprintf( '<li><a href="#%s">%s</a>%s</li>', esc_attr( $item[0] ), esc_html( $item[1] ), '' !== $sub ? '<ol>' . $sub . '</ol>' : '' );
	}
	$lists = sprintf(
		'<nav class="aardling-toc-float" aria-label="%1$s"><div class="aardling-toc-float__inner"><span class="aardling-toc-float__lines" aria-hidden="true">%2$s</span>'
		. '<div class="aardling-toc-float__panel"><div class="aardling-toc-float__card"><span class="aardling-toc-float__label">%1$s</span><ol class="aardling-toc-float__list">%3$s</ol></div></div></div></nav>' . "\n"
		. '<details class="aardling-toc" open><summary class="aardling-toc__label">%1$s</summary><ol class="aardling-toc__list">%4$s</ol></details>' . "\n",
		esc_html( $label ),
		$lines,
		$float,
		$box
	);

	$mark = strpos( $out, AARDLING_THEME_TOC_MARK );
	if ( false !== $mark ) {
		return array( substr( $out, 0, $mark ) . $lists . substr( $out, $mark + strlen( AARDLING_THEME_TOC_MARK ) ), $lists );
	}
	return array( substr( $out, 0, $first ) . $lists . substr( $out, $first ), $lists );
}

// Mark the first top-level block with an h2 while the post's blocks render (the_content runs
// do_blocks at priority 9). render_block_data and render_block come in pairs for every block, inner
// ones in between, so depth 1 is a top-level block.
$GLOBALS['aardling_theme_toc_depth'] = 0;
add_filter( 'the_content', function ( $content ) {
	$GLOBALS['aardling_theme_toc_marked'] = false;
	return $content;
}, 8 );
add_filter( 'render_block_data', function ( $block ) {
	$GLOBALS['aardling_theme_toc_depth']++;
	return $block;
} );
add_filter( 'render_block', function ( $content, $block ) {
	$depth = $GLOBALS['aardling_theme_toc_depth']--;
	if ( 1 === $depth && empty( $GLOBALS['aardling_theme_toc_marked'] ) && false !== stripos( (string) $content, '<h2' ) && aardling_theme_toc_wanted() ) {
		$GLOBALS['aardling_theme_toc_marked'] = true;
		// Classic HTML (no block) can hold the introduction too: there the first h2 is the place.
		return empty( $block['blockName'] ) ? $content : AARDLING_THEME_TOC_MARK . $content;
	}
	return $content;
}, 10, 2 );

// After blocks, paragraphs and shortcodes are done; only the post being read.
add_filter( 'the_content', function ( $content ) {
	if ( ! aardling_theme_toc_wanted() ) {
		return str_replace( AARDLING_THEME_TOC_MARK, '', (string) $content );
	}
	list( $content, $lists ) = aardling_theme_toc( (string) $content );
	if ( '' !== $lists ) {
		$GLOBALS['aardling_theme_toc_shown'] = true;
	}
	return str_replace( AARDLING_THEME_TOC_MARK, '', $content );
}, 20 );

// Marks the section being read: the last heading above the line where the bar sits.
add_action( 'wp_footer', function () {
	if ( empty( $GLOBALS['aardling_theme_toc_shown'] ) ) {
		return;
	}
	// noptimize: Autoptimize leaves it as it is.
	echo '<!--noptimize--><script>' . aardling_theme_toc_script() . '</script><!--/noptimize-->' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 20 );

function aardling_theme_toc_script() : string {
	return <<<'JS'
(function(){var n=document.querySelector('.aardling-toc-float');if(!n){return;}
var a=[].slice.call(n.querySelectorAll('.aardling-toc-float__list a')),l=[].slice.call(n.querySelectorAll('.aardling-toc-float__line'));
var h=a.map(function(x){return document.getElementById(decodeURIComponent(x.hash.slice(1)));}),w=false;
function mark(){w=false;var c=-1,y=window.innerHeight*0.3+2;h.forEach(function(e,i){if(e&&e.getBoundingClientRect().top<=y){c=i;}});
a.forEach(function(x,i){var on=i===c;x.classList.toggle('is-current',on);if(on){x.setAttribute('aria-current','true');}else{x.removeAttribute('aria-current');}if(l[i]){l[i].classList.toggle('is-current',on);}});}
window.addEventListener('scroll',function(){if(!w){w=true;window.requestAnimationFrame(mark);}},{passive:true});mark();})();
JS;
}

// Styles, on single posts while the switch is on. Colours follow the reading boxes and Deluminator;
// the bar's rules start with .entry .entry-content to outrank Twenty Nineteen's and Deluminator's
// link colours.
add_action( 'wp_head', function () {
	if ( ! aardling_theme_switch( 'toc' ) || ! is_singular( 'post' ) ) {
		return;
	}
	$sans = '-apple-system,BlinkMacSystemFont,"Segoe UI","Roboto","Oxygen","Ubuntu","Cantarell","Fira Sans","Droid Sans","Helvetica Neue",sans-serif';
	$f    = '.entry .entry-content .aardling-toc-float';
	$dark = 'html.deluminator-palette.deluminator-dark .entry .entry-content .aardling-toc-float';
	$css  = '.aardling-toc{--aardling-toc-bg:#fff;--aardling-toc-line:#e6e6e6;--aardling-toc-muted:#767676;border:1px solid var(--aardling-toc-line);background:var(--aardling-toc-bg);padding:1rem 1.5rem;line-height:1.35;hyphens:manual}'
		. 'html.deluminator-palette.deluminator-dark .aardling-toc{--aardling-toc-bg:var(--dl-surface);--aardling-toc-line:var(--dl-line);--aardling-toc-muted:var(--dl-muted)}'
		. '.aardling-toc__label{cursor:pointer;font-family:' . $sans . ';font-size:.7111em;font-weight:500;letter-spacing:.2em;text-transform:uppercase;color:var(--aardling-toc-muted)}'
		. '.aardling-toc__list{margin:.75rem 0 0;padding-left:1.4em;font-size:.9em}'
		. '.aardling-toc__list li{margin:.3em 0}'
		. '.aardling-toc__list ol{margin:.3em 0 0;padding-left:1.2em;font-size:.9em;list-style:lower-alpha}'
		. '.aardling-toc a{text-decoration:none}'
		. '.aardling-toc a:hover,.aardling-toc a:focus{text-decoration:underline}'
		. '.entry-content h2[id],.entry-content h3[id]{scroll-margin-top:1.5rem}'
		// The bar: hidden below 1168 px, where the box shows instead. A zero-height block before the
		// first heading, sticky at 30% of the screen; its lines hang in the margin to the left.
		. '.aardling-toc-float{display:none}'
		. '@media screen and (min-width:1168px){.aardling-toc{display:none}.site-content:has(.aardling-toc-float){overflow:clip;display:flow-root}'
		. $f . '{display:block;position:sticky;top:30vh;z-index:20;height:0;margin:0;max-width:none;'
		. '--tf-bg:#fff;--tf-line:#e3e6e8;--tf-hover:#f1f2f3;--tf-bar:#bcbfc3;--tf-text:#6a737c;--tf-strong:#0c0d0e;font-family:' . $sans . ';line-height:1.35;hyphens:manual}}'
		. $dark . '{--tf-bg:var(--dl-surface);--tf-line:var(--dl-line);--tf-hover:var(--dl-surface-2);--tf-bar:var(--dl-muted);--tf-text:var(--dl-muted);--tf-strong:var(--dl-strong)}'
		. $f . ' .aardling-toc-float__inner{position:absolute;top:0;right:100%;margin-right:1.5rem}'
		. $f . ' .aardling-toc-float__lines{display:flex;flex-direction:column;align-items:flex-start;gap:9px;padding:16px;border-radius:12px;cursor:pointer;transition:background-color .2s ease}'
		. $f . ' .aardling-toc-float__line{display:block;width:22px;height:3px;border-radius:2px;background:var(--tf-bar);transition:width .3s cubic-bezier(.2,.8,.2,1),background-color .3s ease}'
		. $f . ' .aardling-toc-float__line--sub{width:13px;margin-left:9px}'
		. $f . ' .aardling-toc-float__line.is-current{width:28px;background:var(--tf-strong)}'
		. $f . ' .aardling-toc-float__line--sub.is-current{width:19px}'
		. $f . ' .aardling-toc-float__inner:hover .aardling-toc-float__lines,' . $f . ' .aardling-toc-float__inner:focus-within .aardling-toc-float__lines{background:var(--tf-hover)}'
		// The list opens beside the lines: it fades in, slides a few pixels and its entries follow one
		// after another. The panel's padding bridges the gap so the pointer can cross it.
		. $f . ' .aardling-toc-float__panel{position:absolute;top:-8px;left:100%;padding-left:10px;visibility:hidden;opacity:0;transform:translateX(-8px) scale(.98);transform-origin:left top;'
		. 'transition:opacity .18s ease,transform .24s cubic-bezier(.2,.8,.2,1),visibility 0s linear .24s}'
		. $f . ' .aardling-toc-float__inner:hover .aardling-toc-float__panel,' . $f . ' .aardling-toc-float__inner:focus-within .aardling-toc-float__panel{visibility:visible;opacity:1;transform:none;transition-delay:0s}'
		. $f . ' .aardling-toc-float__card{box-sizing:border-box;width:max-content;min-width:220px;max-width:340px;max-height:60vh;overflow:auto;padding:20px 24px;border:1px solid var(--tf-line);border-radius:12px;background:var(--tf-bg);'
		. 'box-shadow:0 1px 2px rgba(0,0,0,.05),0 8px 24px rgba(0,0,0,.12)}'
		. $f . ' .aardling-toc-float__label{display:block;margin:0 0 .5rem;font-size:13px;font-weight:500;letter-spacing:.1em;text-transform:uppercase;color:var(--tf-strong)}'
		. $f . ' .aardling-toc-float__list{list-style:none;margin:0;padding:0;font-size:16px}'
		. $f . ' .aardling-toc-float__list li{margin:0;opacity:0;transform:translateX(-4px);transition:opacity .2s ease,transform .26s cubic-bezier(.2,.8,.2,1);transition-delay:calc(var(--i,0) * 25ms)}'
		. $f . ' .aardling-toc-float__inner:hover li,' . $f . ' .aardling-toc-float__inner:focus-within li{opacity:1;transform:none}'
		. $f . ' .aardling-toc-float__list a{display:block;padding:6px 0;color:var(--tf-text);text-decoration:none;transition:color .15s ease}'
		. $dark . ' .aardling-toc-float__list a{color:var(--tf-text)}'
		. $f . ' .aardling-toc-float__sub a{padding-left:1em;font-size:.92em}'
		. $f . ' .aardling-toc-float__list a:hover,' . $f . ' .aardling-toc-float__list a:focus,' . $f . ' .aardling-toc-float__list a.is-current,'
		. $dark . ' .aardling-toc-float__list a:hover,' . $dark . ' .aardling-toc-float__list a:focus,' . $dark . ' .aardling-toc-float__list a.is-current{color:var(--tf-strong)}'
		. '@media (prefers-reduced-motion:reduce){' . $f . ' *{transition:none!important}}'
		. '@media (prefers-reduced-motion:no-preference){html:has(.aardling-toc-float){scroll-behavior:smooth}}';

	echo '<style id="aardling-toc-css">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed CSS.
}, 100 );
