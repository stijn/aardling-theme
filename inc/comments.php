<?php
/**
 * Comments are closed on the whole site, whatever each post's own setting says.
 *
 * How it works:
 * - New posts were already closed, but about a thousand older posts and one page still had their
 *   own "allow comments" ticked. comments_open and pings_open now answer no everywhere, so the
 *   form, reply links, pingbacks and wp-comments-post.php all refuse.
 * - Existing comments stay visible (under the reading boxes, see inc/reads.php). Twenty Nineteen's
 *   "Comments are closed." line under them is hidden.
 * - Nothing in the database changes: switch themes, or return false from the
 *   aardling_theme_close_comments filter, and each post's own setting applies again.
 *
 * @package Aardling
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function aardling_theme_comments_closed() : bool {
	return (bool) apply_filters( 'aardling_theme_close_comments', true );
}

add_filter( 'comments_open', function ( $open ) {
	return aardling_theme_comments_closed() ? false : $open;
}, 20 );
add_filter( 'pings_open', function ( $open ) {
	return aardling_theme_comments_closed() ? false : $open;
}, 20 );

// Twenty Nineteen prints "Comments are closed." under the comments of every closed post.
add_action( 'wp_head', function () {
	if ( aardling_theme_comments_closed() && is_singular() ) {
		echo '<style id="aardling-comments-css">.comments-area .no-comments{display:none}</style>' . "\n";
	}
}, 100 );
