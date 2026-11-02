<?php
/**
 * Twenty Nineteen options in the Customizer (Appearance > Customize > Aardling), taking over from
 * the plugin Options for Twenty Nineteen.
 *
 * How it works:
 * - Its free options keep the plugin's setting names (theme mods), so the values already saved
 *   carry over unchanged. Its premium options are written here from scratch under new names.
 * - Almost everything is CSS, printed in the head only for options that are set. A few options
 *   work on the server: the footer credit, breadcrumbs, archive descriptions, full posts in
 *   archives, the posts page header and the scroll arrow. No scripts or requests are added; the
 *   only exception is the Dashicons font, loaded when a Dashicon is chosen for the scroll arrow.
 * - Stays off while Options for Twenty Nineteen is active, so the two never fight. Deactivate the
 *   plugin and these take over with the same values.
 * - Jetpack's Featured Content isn't offered: it needs Jetpack plus its own templates. Infinite
 *   scroll only does something when Jetpack is active.
 *
 * @package Aardling
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'aardling_theme_settings_page', function () {
	?>
	<h2>Twenty Nineteen options</h2>
	<?php if ( class_exists( 'options_for_twenty_nineteen_class' ) ) : ?>
		<p>The plugin Options for Twenty Nineteen is active, so the theme's own options stay off. Deactivate the plugin and <strong>Appearance &rarr; Customize &rarr; Aardling</strong> takes over with the same values.</p>
	<?php else : ?>
		<p>In <a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[panel]=aardling' ) ); ?>">Appearance &rarr; Customize &rarr; Aardling</a>, plus four colour options under Colors.</p>
	<?php endif; ?>
	<?php
} );

if ( class_exists( 'options_for_twenty_nineteen_class' ) ) {
	return;
}

/**
 * Every option: id => section, type, label, description, default and, per type, min/max/step or choices.
 * Ranges for the colour levels store the value plus 1, as the plugin did, so 0 can be chosen.
 */
function aardling_theme_options() : array {
	$align = array( '' => 'Left', 'center' => 'Center', 'right' => 'Right' );

	return array(
		// General.
		'body_font_size'                    => array( 'general', 'range', 'Font size', 'Base font size, in thousandths of the default (1000 = unchanged).', 1000, 500, 2000, 25 ),
		'mobile_font_size'                  => array( 'general', 'range', 'Mobile font size', 'Percentage of the font size on screens narrower than 768px.', 100, 1, 100, 1 ),
		'tablet_font_size'                  => array( 'general', 'range', 'Tablet font size', 'Percentage of the font size on screens from 768px to 1168px.', 100, 1, 100, 1 ),
		'hide_dashes'                       => array( 'general', 'checkbox', 'Hide dashes', 'Hide the short dashes before headings, the site description and post titles.', false ),

		// Header.
		'logo_width'                        => array( 'header', 'range', 'Logo width', 'Width of the logo on screens 768px and wider, in pixels.', 64, 1, 190, 1 ),
		'remove_logo_background'            => array( 'header', 'checkbox', 'Remove logo background', 'No white circle behind the logo on posts with a featured image.', false ),
		'remove_logo_border_radius'         => array( 'header', 'checkbox', 'Remove logo circle', 'Show the logo in its own shape instead of a circle.', false ),
		'reposition_logo'                   => array( 'header', 'checkbox', 'Logo above the title', 'Put the logo above the site title, at full size.', false ),
		'logo_align'                        => array( 'header', 'select', 'Logo alignment', 'With the logo above the title only.', '', $align ),
		'site_title_align'                  => array( 'header', 'select', 'Site title alignment', '', '', $align ),
		'site_description_align'            => array( 'header', 'select', 'Site description alignment', '', '', $align ),
		'hide_site_title'                   => array( 'header', 'checkbox', 'Hide site title', 'Hidden on screen, still read out by screen readers and search engines.', false ),
		'hide_site_description'             => array( 'header', 'checkbox', 'Hide site description', 'Hidden on screen, still read out by screen readers.', false ),
		'scroll_to_content'                 => array( 'header', 'checkbox', 'Scroll-to-content arrow', 'An arrow at the bottom of the large featured-image header of posts and pages.', false ),
		'bounce_scroll_to_content_arrow'    => array( 'header', 'checkbox', 'Bounce the arrow', 'Not for visitors who asked their device for reduced motion.', false ),
		'scroll_to_content_dashicon'        => array( 'header', 'text', 'Arrow Dashicon', 'Leave empty for the theme\'s own arrow, or name a Dashicon such as arrow-down-alt2 (loads the Dashicons font).', '' ),
		'show_title_on_posts_page'          => array( 'header', 'checkbox', 'Title on the posts page', 'When a page is set as the posts page, show its title above the posts.', false ),
		'show_featured_image_on_posts_page' => array( 'header', 'checkbox', 'Featured image on the posts page', 'When a page is set as the posts page, show its featured image above the posts.', false ),

		// Navigation.
		'navigation_font_size'              => array( 'navigation', 'range', 'Menu font size', 'In thousandths of a rem (1125 = unchanged).', 1125, 550, 2250, 25 ),
		'sub_nav_font_size'                 => array( 'navigation', 'range', 'Submenu font size', 'Child items of the main menu.', 1125, 550, 2250, 25 ),
		'sub_sub_nav_font_size'             => array( 'navigation', 'range', 'Sub-submenu font size', 'Grandchild items of the main menu.', 1125, 550, 2250, 25 ),

		// Content.
		'page_title_font_weight'            => array( 'content', 'range', 'Title font weight', 'Post and page titles.', 700, 100, 900, 100 ),
		'page_title_text_transform'         => array( 'content', 'select', 'Title case', '', '', array( '' => 'As written', 'capitalize' => 'Capitalise', 'uppercase' => 'Uppercase', 'lowercase' => 'Lowercase' ) ),
		'post_meta_location'                => array( 'content', 'select', 'Date and author line', 'Where single posts show the date and author.', '', array( '' => 'Above and below', 'above' => 'Above the post', 'below' => 'Below the post', 'none' => 'Nowhere' ) ),
		'remove_posted_on'                  => array( 'content', 'checkbox', 'Hide post dates', '', false ),
		'remove_author'                     => array( 'content', 'checkbox', 'Hide post author', '', false ),
		'hide_featured_image_single'        => array( 'content', 'checkbox', 'Hide featured images on posts and pages', 'Archives keep them.', false ),
		'hide_title_single'                 => array( 'content', 'checkbox', 'Hide titles on posts and pages', 'Hidden on screen, still read out by screen readers and search engines.', false ),
		'show_archive_description'          => array( 'content', 'checkbox', 'Category and tag descriptions', 'Show the description under the heading of category and tag archives.', false ),
		'show_full_content_in_archive'      => array( 'content', 'checkbox', 'Full posts in archives', 'Show whole posts (up to a More block) instead of excerpts on archive pages.', false ),
		'implement_breadcrumbs'             => array( 'content', 'select', 'Breadcrumbs', 'Rank Math or Yoast SEO breadcrumbs on posts and pages (switch them on in the SEO plugin first).', '', array( '' => 'Off', 'above' => 'Above the content', 'below' => 'Below the content', 'both' => 'Above and below' ) ),
		'infinite_scroll'                   => array( 'content', 'checkbox', 'Infinite scroll', 'Only works with Jetpack. Footer widgets become unreachable.', false ),

		// Footer.
		'footer_background_color'           => array( 'footer', 'color', 'Footer background colour', '', '' ),
		'remove_powered_by_wordpress'       => array( 'footer', 'checkbox', 'Remove "Proudly powered by WordPress"', 'Removes the credit and the comma before it.', false ),
		'replace_powered_by_wordpress'      => array( 'footer', 'html', 'Replace the credit with', 'Your own text instead of the credit; links allowed. Overrides the option above.', '' ),

		// Colors (core section).
		'body_background_color'             => array( 'colors', 'color', 'Background colour', '', '#ffffff' ),
		'hex_primary_color'                 => array( 'colors', 'color', 'Primary colour (hex)', 'Sets Twenty Nineteen\'s custom primary colour from a hex value; the levels below fine-tune it.', '' ),
		'opacity_level'                     => array( 'colors', 'range', 'Featured image overlay opacity', '', 71, 1, 101, 1 ),
		'saturation_level'                  => array( 'colors', 'range', 'Primary colour saturation', '', 101, 1, 101, 1 ),
		'lightness_level'                   => array( 'colors', 'range', 'Primary colour lightness', '', 34, 1, 101, 1 ),
		'hover_lightness_level'             => array( 'colors', 'range', 'Hover lightness', '', 24, 1, 101, 1 ),
		'selection_saturation_level'        => array( 'colors', 'range', 'Text selection saturation', '', 51, 1, 101, 1 ),
		'selection_lightness_level'         => array( 'colors', 'range', 'Text selection lightness', '', 91, 1, 101, 1 ),
	);
}

function aardling_theme_option_sanitizer( string $id ) : callable {
	$option = aardling_theme_options()[ $id ];
	switch ( $option[1] ) {
		case 'range':
			return function ( $value ) use ( $option ) {
				return min( (int) $option[6], max( (int) $option[5], absint( $value ) ) );
			};
		case 'checkbox':
			return function ( $value ) {
				return (bool) $value;
			};
		case 'select':
			return function ( $value ) use ( $option ) {
				return array_key_exists( (string) $value, $option[5] ) ? (string) $value : $option[4];
			};
		case 'color':
			return 'sanitize_hex_color';
		case 'html':
			return 'wp_kses_post';
	}
	return function ( $value ) {
		return preg_replace( '/[^a-z0-9-]/', '', strtolower( (string) $value ) );
	};
}

add_action( 'customize_register', function ( WP_Customize_Manager $wp_customize ) {
	$wp_customize->add_panel( 'aardling', array(
		'title'       => 'Aardling',
		'description' => 'Twenty Nineteen options of the Aardling theme.',
		'priority'    => 30,
	) );
	foreach ( array( 'general' => 'General', 'header' => 'Header', 'navigation' => 'Menu', 'content' => 'Content', 'footer' => 'Footer' ) as $section => $title ) {
		$wp_customize->add_section( 'aardling_' . $section, array( 'title' => $title, 'panel' => 'aardling' ) );
	}

	foreach ( aardling_theme_options() as $id => $option ) {
		list( $section, $type, $label, $description, $default ) = $option;
		$wp_customize->add_setting( $id, array(
			'type'              => 'theme_mod',
			'default'           => $default,
			'transport'         => 'refresh',
			'sanitize_callback' => aardling_theme_option_sanitizer( $id ),
		) );
		$args = array(
			'label'       => $label,
			'description' => $description,
			'section'     => 'colors' === $section ? 'colors' : 'aardling_' . $section,
		);
		if ( 'color' === $type ) {
			$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $id, $args ) );
			continue;
		}
		$args['type'] = 'html' === $type ? 'textarea' : $type;
		if ( 'range' === $type ) {
			$args['input_attrs'] = array( 'min' => $option[5], 'max' => $option[6], 'step' => $option[7] );
		} elseif ( 'select' === $type ) {
			$args['choices'] = $option[5];
		}
		$wp_customize->add_control( $id, $args );
	}
}, 20 );

// Logos of any size (Twenty Nineteen allows only a 190px square).
add_action( 'after_setup_theme', function () {
	add_theme_support( 'custom-logo', array(
		'height'      => 190,
		'width'       => 190,
		'flex-width'  => true,
		'flex-height' => true,
	) );

	if ( get_theme_mod( 'infinite_scroll' ) && class_exists( 'Jetpack' ) ) {
		add_theme_support( 'infinite-scroll', array(
			'container' => 'main',
			'footer'    => 'page',
			'wrapper'   => false,
			'render'    => function () {
				while ( have_posts() ) {
					the_post();
					get_template_part( 'template-parts/content/content', is_archive() || is_search() ? 'excerpt' : '' );
				}
			},
		) );
	}
}, 11 );

/**
 * Twenty Nineteen's custom primary colour from a hex value: hue, saturation and lightness.
 *
 * @return array|null array( hue 0-360, saturation 0-100, lightness 0-100 ), or null without a valid hex.
 */
function aardling_theme_hex_to_hsl( $hex ) {
	$hex = ltrim( (string) sanitize_hex_color( $hex ), '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) ) {
		return null;
	}
	list( $r, $g, $b ) = array_map( function ( $pair ) {
		return hexdec( $pair ) / 255;
	}, str_split( $hex, 2 ) );

	$max = max( $r, $g, $b );
	$min = min( $r, $g, $b );
	$l   = ( $max + $min ) / 2;
	$h   = 0;
	$s   = 0;
	if ( $max !== $min ) {
		$d = $max - $min;
		$s = $l > 0.5 ? $d / ( 2 - $max - $min ) : $d / ( $max + $min );
		if ( $max === $r ) {
			$h = ( $g - $b ) / $d + ( $g < $b ? 6 : 0 );
		} elseif ( $max === $g ) {
			$h = ( $b - $r ) / $d + 2;
		} else {
			$h = ( $r - $g ) / $d + 4;
		}
		$h /= 6;
	}
	return array( (int) round( $h * 360 ), (int) round( $s * 100 ), (int) round( $l * 100 ) );
}

// Primary colour: the hex value sets Twenty Nineteen's custom hue; the level sliders win over it.
add_filter( 'theme_mod_primary_color', function ( $value ) {
	return aardling_theme_hex_to_hsl( get_theme_mod( 'hex_primary_color' ) ) ? 'custom' : $value;
} );
add_filter( 'theme_mod_primary_color_hue', function ( $value ) {
	$hsl = aardling_theme_hex_to_hsl( get_theme_mod( 'hex_primary_color' ) );
	return $hsl ? $hsl[0] : $value;
} );
foreach ( array(
	'saturation'           => array( 'saturation_level', 1 ),
	'lightness'            => array( 'lightness_level', 2 ),
	'lightness_hover'      => array( 'hover_lightness_level', null ),
	'saturation_selection' => array( 'selection_saturation_level', null ),
	'lightness_selection'  => array( 'selection_lightness_level', null ),
) as $aardling_theme_filter => $aardling_theme_level ) {
	add_filter( 'twentynineteen_custom_colors_' . $aardling_theme_filter, function ( $value ) use ( $aardling_theme_level ) {
		list( $mod, $hsl_index ) = $aardling_theme_level;
		if ( get_theme_mod( $mod ) ) {
			return absint( get_theme_mod( $mod ) ) - 1;
		}
		$hsl = null === $hsl_index ? null : aardling_theme_hex_to_hsl( get_theme_mod( 'hex_primary_color' ) );
		return $hsl ? $hsl[ $hsl_index ] : $value;
	} );
}

/**
 * Visually hidden but still in the page for screen readers and search engines.
 */
function aardling_theme_visually_hidden( string $selector ) : string {
	return $selector . '{position:absolute!important;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}';
}

/**
 * The CSS for every option that is set. Matches what Options for Twenty Nineteen printed for the
 * same values.
 */
function aardling_theme_options_css() : string {
	$mod    = function ( string $id ) {
		return get_theme_mod( $id );
	};
	$mobile = absint( $mod( 'mobile_font_size' ) ) / 100;
	$tablet = absint( $mod( 'tablet_font_size' ) ) / 100;
	$sizes  = function ( string $selector, float $size, string $unit ) use ( $mobile, $tablet ) {
		$css = "$selector{font-size:{$size}{$unit}}";
		if ( $mobile ) {
			$css .= '@media only screen and (max-width:768px){' . $selector . '{font-size:' . ( $size * $mobile ) . $unit . '}}';
		}
		if ( $tablet ) {
			$css .= '@media only screen and (min-width:768px) and (max-width:1168px){' . $selector . '{font-size:' . ( $size * $tablet ) . $unit . '}}';
		}
		return $css;
	};

	$css = '.site-logo .custom-logo-link{height:auto}.site-logo .custom-logo-link .custom-logo{display:block}';

	if ( $mod( 'body_font_size' ) ) {
		$css .= $sizes( 'body', absint( $mod( 'body_font_size' ) ) / 1000, 'em' );
	}
	if ( $mod( 'hide_dashes' ) ) {
		$css .= '.site-description:before,h1:not(.site-title):before,h2:before,.entry .entry-title:before,.comments-area .comments-title-wrap .comments-title:before,.post-navigation .nav-links .nav-previous .meta-nav:before{display:none}';
	}

	// Header.
	if ( $mod( 'logo_width' ) && ! $mod( 'reposition_logo' ) ) {
		$css .= '@media only screen and (min-width:768px){.site-logo .custom-logo-link{width:' . absint( $mod( 'logo_width' ) ) . 'px}}';
	}
	if ( $mod( 'remove_logo_background' ) ) {
		$css .= '.site-header.featured-image .custom-logo-link{background:none}';
	}
	if ( $mod( 'remove_logo_border_radius' ) ) {
		$css .= '.site-logo .custom-logo-link,.site-logo .custom-logo-link .custom-logo{border-radius:0}';
	}
	if ( $mod( 'reposition_logo' ) ) {
		$css .= '.site-logo{position:static;float:none;margin-bottom:1rem;text-align:' . ( $mod( 'logo_align' ) ? sanitize_key( $mod( 'logo_align' ) ) : 'left' ) . '}'
			. '.site-logo .custom-logo-link{display:inline-block;width:auto;height:auto}'
			. '.site-logo .custom-logo-link .custom-logo{width:auto;max-width:100%;height:auto;max-height:none}';
	}
	foreach ( array( 'site_title_align' => '.site-title,.featured-image .site-title', 'site_description_align' => '.site-description' ) as $id => $selector ) {
		if ( in_array( $mod( $id ), array( 'center', 'right' ), true ) ) {
			$css .= "$selector{display:block;text-align:" . $mod( $id ) . '}';
		}
	}
	if ( $mod( 'hide_site_title' ) ) {
		$css .= aardling_theme_visually_hidden( '.site-title' );
	}
	if ( $mod( 'hide_site_description' ) ) {
		$css .= aardling_theme_visually_hidden( '.site-description' );
	}
	if ( $mod( 'scroll_to_content' ) ) {
		$css .= '.aardling-scroll-down{position:absolute;z-index:1000;left:50%;top:calc(90vh - 4rem);transform:translateX(-50%);display:block;width:3rem;height:3rem;line-height:3rem;text-align:center;color:#fff;opacity:.85}'
			. '.aardling-scroll-down:hover,.aardling-scroll-down:focus{opacity:1;color:#fff}'
			. '.aardling-scroll-down svg{width:2rem;height:2rem;vertical-align:middle;fill:currentColor}'
			. '.aardling-scroll-down .dashicons{width:2rem;height:2rem;font-size:2rem;vertical-align:middle}'
			. '@media only screen and (min-width:768px){.aardling-scroll-down{top:calc(100vh - 4.5rem)}}'
			. '.admin-bar .aardling-scroll-down{margin-top:32px}';
		if ( $mod( 'bounce_scroll_to_content_arrow' ) ) {
			$css .= '@keyframes aardling-bounce{0%,100%{transform:translate(-50%,0)}50%{transform:translate(-50%,.6rem)}}'
				. '@media (prefers-reduced-motion:no-preference){.aardling-scroll-down{animation:aardling-bounce 2s ease-in-out infinite}}';
		}
	}

	// Menu.
	foreach ( array( 'navigation_font_size' => '.main-navigation', 'sub_nav_font_size' => '.main-navigation div>ul>li>ul>li', 'sub_sub_nav_font_size' => '.main-navigation div>ul>li>ul>li>ul>li' ) as $id => $selector ) {
		if ( $mod( $id ) ) {
			$css .= $sizes( $selector, absint( $mod( $id ) ) / 1000, 'rem' );
		}
	}

	// Content.
	if ( $mod( 'page_title_font_weight' ) ) {
		$css .= '.entry .entry-title{font-weight:' . absint( $mod( 'page_title_font_weight' ) ) . '}';
	}
	if ( $mod( 'page_title_text_transform' ) ) {
		$css .= '.entry .entry-title{text-transform:' . sanitize_key( $mod( 'page_title_text_transform' ) ) . '}';
	}
	if ( in_array( $mod( 'post_meta_location' ), array( 'none', 'above' ), true ) ) {
		$css .= '.single-post .entry-footer{display:none}';
	}
	if ( in_array( $mod( 'post_meta_location' ), array( 'none', 'below' ), true ) ) {
		$css .= '.single-post .entry-meta{display:none}';
	}
	foreach ( array( 'remove_posted_on' => 'posted-on', 'remove_author' => 'byline' ) as $id => $class ) {
		if ( $mod( $id ) ) {
			$css .= ".entry .entry-meta>.$class,.entry .entry-footer>.$class,.site-header.featured-image .site-featured-image .entry-header .entry-meta>.$class{display:none}";
		}
	}
	if ( $mod( 'hide_title_single' ) ) {
		$css .= aardling_theme_visually_hidden( '.singular .entry-header .entry-title' );
	}
	if ( $mod( 'implement_breadcrumbs' ) ) {
		$css .= '.aardling-breadcrumbs{font-size:.8em;margin:0 0 1.5rem}.aardling-breadcrumbs-below{margin:1.5rem 0 0}';
	}

	// Footer and colours.
	if ( $mod( 'footer_background_color' ) ) {
		$css .= '.site-footer{background-color:' . sanitize_hex_color( $mod( 'footer_background_color' ) ) . '}';
	}
	if ( $mod( 'remove_powered_by_wordpress' ) && ! $mod( 'replace_powered_by_wordpress' ) ) {
		$css .= '.site-name{margin-right:1rem}';
	}
	if ( $mod( 'body_background_color' ) ) {
		$css .= 'body{background-color:' . sanitize_hex_color( $mod( 'body_background_color' ) ) . '}';
	}
	if ( $mod( 'opacity_level' ) ) {
		$css .= '.site-header.featured-image:after{opacity:' . number_format( absint( $mod( 'opacity_level' ) ) / 100 - 0.01, 2, '.', '' ) . '}';
	}

	return $css;
}

add_action( 'wp_head', function () {
	echo '<style id="aardling-options">' . wp_strip_all_tags( aardling_theme_options_css() ) . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 100 );

/**
 * Footer credit: removed with its comma, or replaced. Twenty Nineteen's footer.php has no filter
 * for it, so the footer's HTML is caught between get_footer and wp_footer and edited on the server.
 */
function aardling_theme_footer_credit( string $html ) : string {
	$replacement = trim( (string) get_theme_mod( 'replace_powered_by_wordpress' ) );
	$imprint     = '#<a [^>]*class="imprint"[^>]*>.*?</a>#s';

	if ( '' !== $replacement ) {
		return (string) preg_replace( $imprint, '<span class="imprint">' . wp_kses_post( $replacement ) . '</span>', $html, 1 );
	}
	if ( get_theme_mod( 'remove_powered_by_wordpress' ) ) {
		$html = (string) preg_replace( '#(<a class="site-name"[^>]*>.*?</a>)\s*,\s*(?=<a [^>]*class="imprint")#s', '$1 ', $html, 1 );
		return (string) preg_replace( '#\s*' . substr( $imprint, 1, -2 ) . '#s', '', $html, 1 );
	}
	return $html;
}

add_action( 'get_footer', function () {
	if ( get_theme_mod( 'remove_powered_by_wordpress' ) || get_theme_mod( 'replace_powered_by_wordpress' ) ) {
		$GLOBALS['aardling_theme_footer_buffer'] = ob_start();
	}
}, PHP_INT_MAX );
add_action( 'wp_footer', function () {
	if ( ! empty( $GLOBALS['aardling_theme_footer_buffer'] ) ) {
		$GLOBALS['aardling_theme_footer_buffer'] = false;
		echo aardling_theme_footer_credit( (string) ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the theme's own footer HTML.
	}
}, 0 );

// Featured images and titles of single posts and pages.
add_filter( 'twentynineteen_can_show_post_thumbnail', function ( $show ) {
	return ( get_theme_mod( 'hide_featured_image_single' ) && is_singular() ) ? false : $show;
} );

// Scroll-to-content arrow in the large featured-image header.
add_action( 'wp_body_open', function () {
	if ( ! get_theme_mod( 'scroll_to_content' ) || ! is_singular() || ! function_exists( 'twentynineteen_can_show_post_thumbnail' ) || ! twentynineteen_can_show_post_thumbnail() ) {
		return;
	}
	$dashicon = (string) get_theme_mod( 'scroll_to_content_dashicon' );
	$icon     = '' !== $dashicon
		? '<span class="dashicons dashicons-' . esc_attr( $dashicon ) . '" aria-hidden="true"></span>'
		: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16.5 4.5 9l1.4-1.4 6.1 6.1 6.1-6.1L19.5 9z"/></svg>';
	echo '<a class="aardling-scroll-down" href="#content">' . $icon . '<span class="screen-reader-text">Scroll to the content</span></a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} );
add_action( 'wp_enqueue_scripts', function () {
	if ( get_theme_mod( 'scroll_to_content' ) && '' !== (string) get_theme_mod( 'scroll_to_content_dashicon' ) && is_singular() ) {
		wp_enqueue_style( 'dashicons' );
	}
} );

// Breadcrumbs from Rank Math or Yoast SEO, above and/or below single posts and pages.
add_filter( 'the_content', function ( $content ) {
	$where = (string) get_theme_mod( 'implement_breadcrumbs' );
	if ( '' === $where || ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$crumbs = '';
	if ( function_exists( 'rank_math_get_breadcrumbs' ) ) {
		$crumbs = (string) rank_math_get_breadcrumbs();
	} elseif ( function_exists( 'yoast_breadcrumb' ) ) {
		$crumbs = (string) yoast_breadcrumb( '', '', false );
	}
	if ( '' === trim( wp_strip_all_tags( $crumbs ) ) ) {
		return $content;
	}
	$above = in_array( $where, array( 'above', 'both' ), true ) ? '<div class="aardling-breadcrumbs">' . $crumbs . '</div>' : '';
	$below = in_array( $where, array( 'below', 'both' ), true ) ? '<div class="aardling-breadcrumbs aardling-breadcrumbs-below">' . $crumbs . '</div>' : '';
	return $above . $content . $below;
}, 20 );

// Archives: category and tag descriptions, posts page header, and full posts instead of excerpts.
add_action( 'loop_start', function ( $query ) {
	static $done = false;
	if ( $done || is_admin() || ! $query->is_main_query() ) {
		return;
	}
	$done = true;

	if ( get_theme_mod( 'show_archive_description' ) && ( is_category() || is_tag() ) ) {
		$description = term_description();
		if ( '' !== trim( wp_strip_all_tags( (string) $description ) ) ) {
			echo '<div class="page-header aardling-term-description" style="margin-top:0">' . wp_kses_post( $description ) . '</div>';
		}
	}

	$posts_page = (int) get_option( 'page_for_posts' );
	if ( $posts_page && is_home() && ! is_front_page() && ! is_paged() ) {
		$header = '';
		if ( get_theme_mod( 'show_featured_image_on_posts_page' ) && has_post_thumbnail( $posts_page ) ) {
			$header .= '<figure class="post-thumbnail">' . get_the_post_thumbnail( $posts_page, 'post-thumbnail' ) . '</figure>';
		}
		if ( get_theme_mod( 'show_title_on_posts_page' ) ) {
			$header .= '<h1 class="page-title">' . esc_html( get_the_title( $posts_page ) ) . '</h1>';
		}
		if ( '' !== $header ) {
			echo '<header class="page-header">' . $header . '</header>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
} );
add_filter( 'the_excerpt', function ( $excerpt ) {
	if ( ! get_theme_mod( 'show_full_content_in_archive' ) || ! is_archive() || ! in_the_loop() || ! is_main_query() ) {
		return $excerpt;
	}
	return apply_filters( 'the_content', get_the_content() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core hook.
}, 20 );
