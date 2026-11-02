<?php
/**
 * On/off switches for what the theme shows, on Appearance > Aardling.
 *
 * How it works:
 * - Each switch is an option that is on until it's switched off: images in the reading boxes and
 *   the On this day box (inc/reads.php), the previous and next arrows, images in the arrows and
 *   instant loading (inc/nav.php), and the contents list (inc/toc.php).
 * - Every cached post page carries the old markup, so a change empties WP Super Cache and clears
 *   the whole site at Cloudflare.
 *
 * @package Aardling
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The switches and their labels on the settings page.
 */
function aardling_theme_switches() : array {
	return array(
		'reads_thumbs'    => 'Images in the reading boxes',
		'reads_onthisday' => 'The On this day box: posts from this date in earlier years',
		'nav_arrows'      => 'Previous and next arrows at the sides of each post, on screens from 1024 px wide',
		'nav_thumbs'      => 'Images in the arrows\' title card',
		'instant'         => 'Instant next post: fetch a post as soon as someone points at a link to it',
		'toc'             => 'A contents list in posts with three or more headings',
	);
}

/**
 * Is a switch on?
 *
 * @param string $name A key of aardling_theme_switches().
 */
function aardling_theme_switch( string $name ) : bool {
	return (bool) get_option( 'aardling_theme_' . $name, 1 );
}

function aardling_theme_switches_changed() : void {
	if ( function_exists( 'wp_cache_clear_cache' ) ) {
		wp_cache_clear_cache();
	}
	aardling_theme_cloudflare_queue( 'everything' );
}

foreach ( array_keys( aardling_theme_switches() ) as $aardling_theme_switch ) {
	add_action( 'update_option_aardling_theme_' . $aardling_theme_switch, function ( $old, $new ) {
		if ( (int) $old !== (int) $new ) {
			aardling_theme_switches_changed();
		}
	}, 10, 2 );
	// The first save adds the option; only switching off changes what visitors see.
	add_action( 'add_option_aardling_theme_' . $aardling_theme_switch, function ( $option, $value ) {
		if ( 1 !== (int) $value ) {
			aardling_theme_switches_changed();
		}
	}, 10, 2 );
}

add_action( 'admin_init', function () {
	foreach ( array_keys( aardling_theme_switches() ) as $name ) {
		register_setting( 'aardling_theme_switches', 'aardling_theme_' . $name, array(
			'type'              => 'integer',
			'show_in_rest'      => false,
			'sanitize_callback' => function ( $value ) {
				return empty( $value ) ? 0 : 1;
			},
		) );
	}
} );

// First on the settings page, after the update status.
add_action( 'aardling_theme_settings_page', function () {
	?>
	<h2>Images and arrows</h2>
	<form method="post" action="options.php">
		<?php settings_fields( 'aardling_theme_switches' ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">Show</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text">Show</legend>
						<?php foreach ( aardling_theme_switches() as $name => $label ) : ?>
							<label><input type="checkbox" name="aardling_theme_<?php echo esc_attr( $name ); ?>" value="1"<?php checked( aardling_theme_switch( $name ) ); ?>> <?php echo esc_html( $label ); ?></label><br>
						<?php endforeach; ?>
					</fieldset>
					<p class="description">Saving a change empties WP Super Cache and Cloudflare's cache, so every post shows it straight away. Images load only when they come into view, or for the arrows, when someone points at one.</p>
				</td>
			</tr>
		</table>
		<?php submit_button( 'Save' ); ?>
	</form>
	<?php
}, 5 );
