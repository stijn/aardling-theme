<?php
/**
 * Aardling: child theme of Twenty Nineteen.
 *
 * @package Aardling
 */

define( 'AARDLING_THEME_VERSION', '1.1.0' );

require_once __DIR__ . '/inc/updater.php';

/**
 * Twenty Nineteen enqueues get_stylesheet_uri() as 'twentynineteen-style'.
 * In a child theme that would be this child's empty style.css, so point it
 * back at the parent stylesheet: same handle, same file, no extra request.
 */
add_filter(
	'stylesheet_uri',
	function () {
		return get_template_directory_uri() . '/style.css';
	}
);

/**
 * WP Mobile Bottom Menu loads all of Font Awesome (two stylesheets from
 * use.fontawesome.com plus webfonts) for three icons. Drop it and draw those
 * icons with inline SVG masks instead.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_dequeue_style( 'fa5' );
		wp_dequeue_style( 'fa5-v4-shims' );

		if ( wp_style_is( 'wp-bnav', 'enqueued' ) ) {
			wp_add_inline_style( 'wp-bnav', aardling_bottom_menu_icons_css() );
		}
	},
	100
);

/**
 * Build a CSS url() holding a 24x24 SVG, for use as a mask image.
 *
 * @param string $shapes  SVG child elements.
 * @param bool   $outline Draw shapes as 2px strokes instead of fills.
 * @return string
 */
function aardling_svg_mask( $shapes, $outline = false ) {
	$attrs = $outline ? " fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'" : '';
	$svg   = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'{$attrs}>{$shapes}</svg>";

	return 'url("data:image/svg+xml,' . rawurlencode( $svg ) . '")';
}

/**
 * CSS that turns the bottom menu's <i class="fa..."> elements into SVG icons.
 * The icons take the menu's own font-size and colour (normal, hover, active).
 *
 * @return string
 */
function aardling_bottom_menu_icons_css() {
	$house   = "<path d='M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z'/>";
	$needle  = 'M15.5 8.5l-2.1 4.9-4.9 2.1 2.1-4.9z';
	$icons   = array(
		'i.fa-home'         => aardling_svg_mask( $house ),
		'i.far.fa-home'     => aardling_svg_mask( $house, true ),
		'i.fa-compass'      => aardling_svg_mask( "<path fill-rule='evenodd' d='M12 2a10 10 0 1 0 0 20 10 10 0 1 0 0-20z{$needle}'/>" ),
		'i.far.fa-compass'  => aardling_svg_mask( "<circle cx='12' cy='12' r='9'/><path fill='black' stroke='none' d='{$needle}'/>", true ),
		'i.fa-user'         => aardling_svg_mask( "<circle cx='12' cy='8' r='5'/><path d='M3 22a9 9 0 0 1 18 0z'/>" ),
		'i.far.fa-user'     => aardling_svg_mask( "<circle cx='12' cy='8' r='4'/><path d='M4 21a8 8 0 0 1 16 0'/>", true ),
	);
	$wrapper = '.bnav_bottom_nav_wrapper .icon_wrapper ';

	$css = $wrapper . 'i{display:inline-block;width:1em;height:1em;vertical-align:middle;background-color:currentColor;'
		. '-webkit-mask:var(--aardling-icon) center/contain no-repeat;mask:var(--aardling-icon) center/contain no-repeat}';

	foreach ( $icons as $selector => $mask ) {
		$css .= $wrapper . $selector . '{--aardling-icon:' . $mask . '}';
	}

	return $css;
}

/**
 * Customizer settings (colours, logo, menus, Additional CSS, Options for
 * Twenty Nineteen) are stored per theme. Copy Twenty Nineteen's once, the
 * first time this child theme is activated, so the site looks the same.
 */
add_action(
	'after_switch_theme',
	function () {
		$child_key = 'theme_mods_' . get_stylesheet();
		$child     = get_option( $child_key, array() );
		$child     = is_array( $child ) ? $child : array();

		if ( ! empty( $child['aardling_copied_parent_mods'] ) ) {
			return;
		}

		$parent = get_option( 'theme_mods_' . get_template(), array() );
		$parent = is_array( $parent ) ? $parent : array();
		unset( $parent['sidebars_widgets'] );

		// Keys WordPress already set for the child (menu locations) win.
		$merged                                = array_merge( $parent, $child );
		$merged['aardling_copied_parent_mods'] = time();

		update_option( $child_key, $merged );
	}
);
