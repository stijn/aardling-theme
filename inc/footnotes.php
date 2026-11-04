<?php
/**
 * Footnotes: WordPress's own footnotes (the Footnotes block) and the notes Modern Footnotes used
 * to render ([mfn]…[/mfn] and the editor button's <mfn>…</mfn>) look and work the same. A blue
 * number in the text, the notes in a list at the end of the post, and tapping a number opens
 * its note in a pop-up beside it, so nobody has to scroll down and back up. Without script the
 * number still jumps to the list, and the arrow there jumps back.
 *
 * The theme renders Modern Footnotes' shortcodes itself, so that plugin, and the jQuery its
 * script needs, can go. While it's still active its hooks are taken off.
 *
 * @package Aardling
 */

/**
 * Notes collected while one post's content is rendered. A stack, in case content is rendered
 * inside content.
 *
 * @return array Reference to the stack.
 */
function &aardling_theme_footnotes_stack() : array {
	static $stack = array();
	return $stack;
}

/** True once a page has printed footnotes; the pop-up script and styles follow. */
function aardling_theme_footnotes_used( bool $set = false ) : bool {
	static $used = false;
	if ( $set ) {
		$used = true;
	}
	return $used;
}

// Modern Footnotes' own hooks off; its shortcodes are rendered here instead.
add_action(
	'init',
	function () {
		$hooks = array(
			array( 'the_content', 'modern_footnotes_reset_count', 10 ),
			array( 'the_content', 'modern_footnotes_display_after_content', 11 ),
			array( 'the_content', 'modern_footnotes_execute_mfn_list_shortcode', 12 ),
			array( 'the_content', 'modern_footnotes_replace_mfn_tag_with_shortcode', 10 ),
			array( 'wp_trim_words', 'modern_footnotes_strip_rendered_mfn_tag', 10 ),
			array( 'the_excerpt_rss', 'modern_footnotes_remove_from_rss_feed', 10 ),
			array( 'the_content_feed', 'modern_footnotes_remove_from_rss_content_feed', 10 ),
			array( 'the_post', 'modern_footnotes_check_post_query', 10 ),
			array( 'wp_enqueue_scripts', 'modern_footnotes_register_scripts_styles', 10 ),
		);
		foreach ( $hooks as $hook ) {
			remove_filter( $hook[0], $hook[1], $hook[2] );
		}

		$tags     = array( 'mfn', 'modern_footnote' );
		$settings = get_option( 'modern_footnotes_settings' );
		if ( is_array( $settings ) && ! empty( $settings['modern_footnotes_custom_shortcode'] ) ) {
			$tags[] = sanitize_key( $settings['modern_footnotes_custom_shortcode'] );
		}
		foreach ( $tags as $tag ) {
			add_shortcode( $tag, 'aardling_theme_footnote_shortcode' );
		}
		add_shortcode( 'mfn_list', '__return_empty_string' ); // The list always goes at the end.
	},
	20
);

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_dequeue_script( 'modern_footnotes' );
		wp_dequeue_style( 'modern_footnotes' );
	},
	100
);

/**
 * The editor button stores one note as several <mfn> pieces when formatting crosses it:
 * <mfn>A </mfn><em><mfn>B</mfn></em>. Turn the tags into [mfn]…[/mfn], joining pieces that only
 * have tags between them, as Modern Footnotes did.
 */
function aardling_theme_footnote_tags( string $html ) : string {
	if ( ! preg_match( '/<\/?mfn\b/i', $html ) ) {
		return $html;
	}
	$parts = preg_split( '/<\/?mfn\s*>/i', $html );
	$n     = count( $parts );
	$in    = array();
	for ( $i = 0; $i < $n; $i++ ) {
		$in[ $i ] = 1 === $i % 2;
	}
	for ( $i = 2; $i < $n - 1; $i += 2 ) {
		if ( '' === preg_replace( '/<\/?[a-z][^>]*>/i', '', $parts[ $i ] ) ) {
			$in[ $i ] = true;
		}
	}
	$out  = '';
	$open = false;
	for ( $i = 0; $i < $n; $i++ ) {
		if ( $in[ $i ] !== $open ) {
			$out .= $in[ $i ] ? '[mfn]' : '[/mfn]';
			$open = $in[ $i ];
		}
		$out .= $parts[ $i ];
	}
	return $open ? $out . '[/mfn]' : $out;
}

// Each rendering of a post's content collects its own notes...
add_filter(
	'the_content',
	function ( $content ) {
		$stack   = &aardling_theme_footnotes_stack();
		$stack[] = array();
		return $content;
	},
	1
);

// ...<mfn> tags become shortcodes before shortcodes run (11)...
add_filter(
	'the_content',
	function ( $content ) {
		return false === stripos( (string) $content, 'mfn' ) ? $content : aardling_theme_footnote_tags( (string) $content );
	},
	10
);

// ...and the notes go in a list at the end, marked up as the Footnotes block does.
add_filter(
	'the_content',
	function ( $content ) {
		$stack = &aardling_theme_footnotes_stack();
		$notes = (array) array_pop( $stack );
		if ( ! $notes || doing_filter( 'get_the_excerpt' ) ) {
			return $content;
		}
		$list = '';
		foreach ( $notes as $i => $note ) {
			/* translators: %d: Number of the footnote. */
			$back  = sprintf( __( 'Jump to footnote reference %1$d' ), $i + 1 );
			$list .= sprintf( '<li id="%1$s">%2$s <a href="#%1$s-link" aria-label="%3$s">↩︎</a></li>', esc_attr( $note['id'] ), $note['html'], esc_attr( $back ) );
		}
		aardling_theme_footnotes_used( true );
		return $content . '<ol class="wp-block-footnotes">' . $list . '</ol>';
	},
	12
);

/**
 * [mfn]note[/mfn]: a numbered marker; the note goes in the list at the end.
 *
 * @param array|string $atts    referencenumber sets the marker's label, as in Modern Footnotes.
 * @param string       $content The note.
 */
function aardling_theme_footnote_shortcode( $atts, $content = '' ) : string {
	$stack = &aardling_theme_footnotes_stack();
	if ( '' === trim( (string) $content ) || doing_filter( 'get_the_excerpt' ) || ! $stack ) {
		return '';
	}
	$html = do_shortcode( (string) $content );
	$html = preg_replace( '#</p>\s*<p\b[^>]*>#i', '<br><br>', $html );
	$html = trim( (string) preg_replace( '#</?p\b[^>]*>#i', '', $html ) );

	$top   = count( $stack ) - 1;
	$n     = count( $stack[ $top ] ) + 1;
	$atts  = is_array( $atts ) ? $atts : array();
	$label = isset( $atts['referencenumber'] ) && '' !== $atts['referencenumber'] ? (string) $atts['referencenumber'] : (string) $n;
	$id    = 'mfn-' . (int) get_the_ID() . '-' . $n;

	$stack[ $top ][] = array(
		'id'   => $id,
		'html' => $html,
	);
	return sprintf( '<sup data-fn="%1$s" class="fn"><a href="#%1$s" id="%1$s-link">%2$s</a></sup>', esc_attr( $id ), esc_html( $label ) );
}

add_filter(
	'render_block_core/footnotes',
	function ( $html ) {
		if ( '' !== trim( (string) $html ) ) {
			aardling_theme_footnotes_used( true );
		}
		return $html;
	}
);

/**
 * True when the post shown on this page has footnotes of either kind, so the styles can go in
 * the head.
 */
function aardling_theme_footnotes_on_page() : bool {
	if ( ! is_singular() ) {
		return false;
	}
	$post = get_queried_object();
	return $post instanceof WP_Post && (bool) preg_match( '/data-fn=|<!-- wp:footnotes|\[mfn\b|<mfn\b|\[modern_footnote\b/i', (string) $post->post_content );
}

function aardling_theme_footnotes_css() : string {
	$sans = '-apple-system,BlinkMacSystemFont,"Segoe UI","Roboto","Oxygen","Ubuntu","Cantarell","Fira Sans","Droid Sans","Helvetica Neue",sans-serif';
	return ':root{--aardling-fn-bg:#fff;--aardling-fn-line:#e6e6e6;--aardling-fn-text:#111;--aardling-fn-muted:#767676;--aardling-fn-mark:#4169e1;--aardling-fn-mark-text:#fff}'
		. 'html.deluminator-palette.deluminator-dark{--aardling-fn-bg:var(--dl-surface);--aardling-fn-line:var(--dl-line);--aardling-fn-text:var(--dl-text);--aardling-fn-muted:var(--dl-muted);--aardling-fn-mark:var(--dl-accent);--aardling-fn-mark-text:var(--dl-accent-text)}'
		// The marker: the blue pill the site had with Modern Footnotes.
		. 'sup.fn,.aardling-fn-pop__n{background-color:var(--aardling-fn-mark);border-radius:16px;font-family:"Courier New",Courier,monospace;padding:.1rem .1618rem;line-height:initial;vertical-align:0}'
		// Deluminator's dark mode colours every link in a post with a stronger selector; the number
		// keeps its own colours there, the accent pair Deluminator gives footnote markers.
		. 'sup.fn a,.entry .entry-content sup.fn a,html.deluminator-palette.deluminator-dark sup.fn a,html.deluminator-palette.deluminator-dark .entry .entry-content sup.fn a,html.deluminator-palette.deluminator-dark .entry .entry-content sup.fn a:visited,html.deluminator-palette.deluminator-dark .entry .entry-content sup.fn a:hover{color:var(--aardling-fn-mark-text);font-weight:700;text-decoration:none}'
		. 'sup.fn a:focus-visible{outline:2px solid var(--aardling-fn-mark);outline-offset:3px}'
		// The list at the end.
		. '.entry .entry-content .wp-block-footnotes{margin-top:2em;padding-top:1em;border-top:1px solid var(--aardling-fn-line);font-family:' . $sans . ';font-size:.71111em;line-height:1.6;color:var(--aardling-fn-muted)}'
		. '.entry .entry-content .wp-block-footnotes li{margin-bottom:.4em;overflow-wrap:anywhere}'
		. '.entry .entry-content .wp-block-footnotes li:target{color:var(--aardling-fn-text)}'
		. '.entry .entry-content .wp-block-footnotes a[href$="-link"]{text-decoration:none}'
		// The pop-up.
		. '.aardling-fn-pop{position:absolute;inset:auto;margin:0;box-sizing:border-box;width:max-content;max-width:min(24rem,calc(100vw - 32px));padding:.75rem 1rem;border:1px solid var(--aardling-fn-line);border-radius:10px;background:var(--aardling-fn-bg);color:var(--aardling-fn-text);box-shadow:0 10px 30px rgba(0,0,0,.18);font-family:' . $sans . ';font-size:.8rem;line-height:1.5;overflow-wrap:anywhere;hyphens:none}'
		. '.aardling-fn-pop a{color:inherit;text-decoration:underline}'
		. '.aardling-fn-pop__n{display:inline-block;margin-right:.5em;color:var(--aardling-fn-mark-text);font-size:.85em;font-weight:700}'
		. '.aardling-fn-pop:popover-open{opacity:1;transform:none;transition:opacity .15s,transform .15s}'
		. '@starting-style{.aardling-fn-pop:popover-open{opacity:0;transform:translateY(.25rem)}}'
		. '@media (prefers-reduced-motion:reduce){.aardling-fn-pop:popover-open{transition:none}}';
}

/**
 * Tapping a marker opens its note beside it in a pop-up (the browser's own popover: Escape or
 * a tap elsewhere closes it, and it scrolls with the page). It reads the note from the list,
 * measures the marker once and places the pop-up below it, or above when there's no room.
 * Browsers without popovers follow the link to the list.
 */
function aardling_theme_footnotes_script() : string {
	return <<<'JS'
(function(){var p=document.createElement('div');if(!p.showPopover||!document.addEventListener){return;}
p.className='aardling-fn-pop';p.setAttribute('popover','');p.setAttribute('role','note');p.tabIndex=-1;document.body.appendChild(p);
var cur=null,gone=null,at=0;
function place(){if(!cur){return;}var r=cur.getBoundingClientRect(),vw=document.documentElement.clientWidth,w=p.offsetWidth,h=p.offsetHeight,g=8;
var x=Math.min(Math.max(16,r.left+r.width/2-w/2),vw-16-w),y=(r.bottom+g+h<=window.innerHeight||r.top-g-h<0)?r.bottom+g:r.top-g-h;
p.style.left=Math.round(x+window.pageXOffset)+'px';p.style.top=Math.round(y+window.pageYOffset)+'px';}
document.addEventListener('click',function(e){var a=e.target.closest&&e.target.closest('sup.fn a[href^="#"]');if(!a){return;}
var li=document.getElementById(decodeURIComponent(a.hash.slice(1)));if(!li){return;}e.preventDefault();
if(gone===a&&Date.now()-at<400){gone=null;return;}
var c=li.cloneNode(true);[].forEach.call(c.querySelectorAll('a[href$="-link"]'),function(x){if(x.getAttribute('href').charAt(0)==='#'){x.parentNode.removeChild(x);}});
var n=document.createElement('span');n.className='aardling-fn-pop__n';n.textContent=a.textContent;
p.textContent='';p.appendChild(n);while(c.firstChild){p.appendChild(c.firstChild);}
if(cur){cur.setAttribute('aria-expanded','false');}cur=a;a.setAttribute('aria-expanded','true');
if(!p.matches(':popover-open')){p.showPopover();}place();p.focus({preventScroll:true});});
p.addEventListener('beforetoggle',function(e){if(e.newState==='closed'&&cur){var back=p.contains(document.activeElement)||document.activeElement===p;cur.setAttribute('aria-expanded','false');gone=cur;at=Date.now();if(back){cur.focus({preventScroll:true});}cur=null;}});
window.addEventListener('resize',place);})();
JS;
}

add_action(
	'wp_head',
	function () {
		if ( aardling_theme_footnotes_on_page() ) {
			echo '<style id="aardling-footnotes-css">' . aardling_theme_footnotes_css() . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed CSS.
		}
	},
	20
);

add_action(
	'wp_footer',
	function () {
		if ( ! aardling_theme_footnotes_used() ) {
			return;
		}
		if ( ! aardling_theme_footnotes_on_page() ) {
			echo '<style id="aardling-footnotes-css">' . aardling_theme_footnotes_css() . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed CSS.
		}
		echo '<script id="aardling-footnotes-js">' . aardling_theme_footnotes_script() . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed script.
	}
);
