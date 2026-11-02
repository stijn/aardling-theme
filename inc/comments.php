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
 * - Once, after this theme version arrives, every post, page and attachment is closed in the
 *   database too, and so are the defaults for new posts, so they stay closed under any theme. The
 *   update goes straight to the table: no modified date changes and no cache needs clearing.
 *
 * @package Aardling
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function aardling_theme_comments_closed() : bool {
	return (bool) apply_filters( 'aardling_theme_close_comments', true );
}

// The one-time close. The option records when it ran and how many rows changed.
add_action( 'init', function () {
	if ( ! aardling_theme_comments_closed() || false !== get_option( 'aardling_theme_comments_closed_db' ) ) {
		return;
	}
	global $wpdb;
	$comments = (int) $wpdb->query( "UPDATE {$wpdb->posts} SET comment_status = 'closed' WHERE comment_status <> 'closed'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$pings    = (int) $wpdb->query( "UPDATE {$wpdb->posts} SET ping_status = 'closed' WHERE ping_status <> 'closed'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	update_option( 'default_comment_status', 'closed' );
	update_option( 'default_ping_status', 'closed' );
	if ( wp_using_ext_object_cache() ) {
		wp_cache_flush();
	}
	update_option( 'aardling_theme_comments_closed_db', array( 'time' => time(), 'comments' => $comments, 'pings' => $pings ), true );
} );

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
