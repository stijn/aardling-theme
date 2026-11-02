<?php
/**
 * Reading boxes under each post: Suggested (related posts), Trending (most read this week, from
 * Cloudflare Web Analytics) and Recently (the newest posts).
 *
 * How it works:
 * - Suggested is part of the page: the posts that share the most tags, then posts from the same
 *   category.
 * - Trending and Recently change more often than Cloudflare's cached copy of a page (up to a
 *   month), so the page only carries their empty boxes. A few lines of script fill them from
 *   /aardling-reads.json, which Cloudflare caches too and the theme clears whenever the lists
 *   change. Without JavaScript the two boxes stay hidden.
 * - Recently is rebuilt when a post is published, updated or unpublished.
 * - Trending is rebuilt every hour from Cloudflare's GraphQL Analytics API: the most-loaded pages
 *   of the last 7 days, matched to published posts. It needs the account ID, the Web Analytics
 *   site tag and a token with only Account > Account Analytics > Read, from
 *   define( 'AARDLING_THEME_CLOUDFLARE_ACCOUNT', '...' );
 *   define( 'AARDLING_THEME_WEB_ANALYTICS_SITE', '...' ); and
 *   define( 'AARDLING_THEME_CLOUDFLARE_ANALYTICS_TOKEN', '...' ); in wp-config.php, or the fields
 *   on Appearance > Aardling. The token is never shown again after saving, logged or returned.
 *   Without all three, or after three days without a good answer, the Trending box stays hidden.
 *
 * @package Aardling
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const AARDLING_THEME_READS_PATH    = '/aardling-reads.json';
const AARDLING_THEME_READS_DAYS    = 7;
const AARDLING_THEME_READS_GRAPHQL = 'https://api.cloudflare.com/client/v4/graphql';
const AARDLING_THEME_READS_FORMAT  = 2; // Bump when the stored items change shape; Recently is then rebuilt.

/**
 * Box titles, in the site's language. Filter: aardling_theme_reads_labels.
 */
function aardling_theme_reads_labels() : array {
	$nl = 0 === strpos( get_locale(), 'nl' );

	return (array) apply_filters(
		'aardling_theme_reads_labels',
		array(
			'section'  => $nl ? 'Meer om te lezen' : 'More to read',
			'related'  => $nl ? 'Leestips' : 'Suggested reads',
			'trending' => $nl ? 'Populair' : 'Trending',
			'recent'   => $nl ? 'Recent' : 'Recently',
		)
	);
}

/**
 * A post as the boxes show it. Titles are plain text; the script puts them in as text, never HTML.
 *
 * @param WP_Post $post  Post.
 * @param bool    $thumb Add the thumbnail's address.
 */
function aardling_theme_reads_item( WP_Post $post, bool $thumb = false ) : array {
	$item = array(
		'id'    => (int) $post->ID,
		'url'   => (string) get_permalink( $post ),
		'title' => html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES, 'UTF-8' ),
		'date'  => (string) get_the_date( '', $post ),
		'iso'   => (string) get_the_date( 'c', $post ),
	);
	if ( $thumb ) {
		$item['thumb'] = (string) get_the_post_thumbnail_url( $post, 'medium' );
	}
	return $item;
}

/**
 * Published, public posts, newest first.
 *
 * @param array $args Extra get_posts() arguments.
 * @return WP_Post[]
 */
function aardling_theme_reads_posts( array $args ) : array {
	return get_posts(
		array_merge(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'has_password'        => false,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			),
			$args
		)
	);
}

/**
 * Term IDs of a post in one taxonomy (none on error).
 *
 * @param int    $post_id  Post ID.
 * @param string $taxonomy Taxonomy.
 */
function aardling_theme_reads_term_ids( int $post_id, string $taxonomy ) : array {
	$terms = get_the_terms( $post_id, $taxonomy );
	return is_array( $terms ) ? array_map( 'intval', wp_list_pluck( $terms, 'term_id' ) ) : array();
}

/**
 * Up to $count related posts: most shared tags first (newest first among equals), then filled up
 * with posts from the same categories.
 *
 * @param WP_Post $post  The post being read.
 * @param int     $count How many.
 * @return WP_Post[]
 */
function aardling_theme_reads_related( WP_Post $post, int $count = 4 ) : array {
	$picked = array();
	$tags   = aardling_theme_reads_term_ids( $post->ID, 'post_tag' );

	if ( $tags ) {
		$scores = array();
		foreach ( aardling_theme_reads_posts( array( 'numberposts' => 30, 'tag__in' => $tags, 'post__not_in' => array( $post->ID ) ) ) as $rank => $candidate ) {
			$shared = count( array_intersect( $tags, aardling_theme_reads_term_ids( $candidate->ID, 'post_tag' ) ) );
			$scores[] = array( $shared, -$rank, $candidate->ID );
		}
		rsort( $scores );
		foreach ( array_slice( $scores, 0, $count ) as $score ) {
			$picked[ $score[2] ] = get_post( $score[2] );
		}
	}

	$categories = aardling_theme_reads_term_ids( $post->ID, 'category' );
	if ( count( $picked ) < $count && $categories ) {
		$more = aardling_theme_reads_posts(
			array(
				'numberposts'  => $count - count( $picked ),
				'category__in' => $categories,
				'post__not_in' => array_merge( array( $post->ID ), array_keys( $picked ) ),
				'orderby'      => 'rand',
			)
		);
		foreach ( $more as $candidate ) {
			$picked[ $candidate->ID ] = $candidate;
		}
	}

	return array_values( $picked );
}

/**
 * The five newest posts (one more than a box shows, so the post being read can be left out).
 */
function aardling_theme_reads_recent() : array {
	return array_map(
		function ( $post ) {
			return aardling_theme_reads_item( $post, true );
		},
		aardling_theme_reads_posts( array( 'numberposts' => 5 ) )
	);
}

/**
 * The stored lists and Trending's status.
 */
function aardling_theme_reads_data() : array {
	$defaults = array(
		'recent'           => array(),
		'trending'         => array(),
		'trending_time'    => 0,
		'trending_ok_time' => 0,
		'trending_error'   => '',
		'format'           => 0,
	);
	$data     = get_option( 'aardling_theme_reads', array() );

	return is_array( $data ) ? array_merge( $defaults, $data ) : $defaults;
}

/**
 * Store changes; when a list visitors see changed, clear /aardling-reads.json at Cloudflare.
 *
 * @param array $changes Keys of aardling_theme_reads_data() to replace.
 */
function aardling_theme_reads_save( array $changes ) : void {
	$before = aardling_theme_reads_data();
	$after  = array_merge( $before, $changes );
	update_option( 'aardling_theme_reads', $after, false );

	if ( $before['recent'] !== $after['recent'] || $before['trending'] !== $after['trending'] ) {
		aardling_theme_cloudflare_queue_url( home_url( AARDLING_THEME_READS_PATH ) );
	}
}

/**
 * Account ID, site tag or token, from wp-config.php or the theme's options.
 *
 * @param string $name 'account', 'site' or 'token'.
 */
function aardling_theme_reads_setting( string $name ) : string {
	$constants = array(
		'account' => 'AARDLING_THEME_CLOUDFLARE_ACCOUNT',
		'site'    => 'AARDLING_THEME_WEB_ANALYTICS_SITE',
		'token'   => 'AARDLING_THEME_CLOUDFLARE_ANALYTICS_TOKEN',
	);
	if ( ! isset( $constants[ $name ] ) ) {
		return '';
	}
	if ( defined( $constants[ $name ] ) && constant( $constants[ $name ] ) ) {
		return (string) constant( $constants[ $name ] );
	}
	return (string) get_option( 'aardling_theme_reads_' . $name, '' );
}

function aardling_theme_reads_trending_configured() : bool {
	return '' !== aardling_theme_reads_setting( 'account' ) && '' !== aardling_theme_reads_setting( 'site' ) && '' !== aardling_theme_reads_setting( 'token' );
}

/**
 * Ask Cloudflare for the most-loaded paths of the last 7 days. Returns array( bool ok, array
 * rows, string error ); rows are array( path, count ). The error is Cloudflare's text and never
 * contains the token.
 */
function aardling_theme_reads_cloudflare_top() : array {
	$account = aardling_theme_reads_setting( 'account' );
	$site    = aardling_theme_reads_setting( 'site' );
	if ( ! preg_match( '/^[a-f0-9]{32}$/', $account ) || ! preg_match( '/^[A-Za-z0-9]{8,64}$/', $site ) ) {
		return array( false, array(), 'The account ID or site tag has the wrong format.' );
	}
	$since = gmdate( 'Y-m-d', time() - AARDLING_THEME_READS_DAYS * DAY_IN_SECONDS );

	// Leave out bots when the dataset knows them; older schemas don't, so retry without.
	foreach ( array( ', bot: 0', '' ) as $bots ) {
		$query    = sprintf(
			'{ viewer { accounts(filter: { accountTag: "%s" }) { rumPageloadEventsAdaptiveGroups(filter: { siteTag: "%s", date_geq: "%s"%s }, limit: 100, orderBy: [count_DESC]) { count dimensions { requestPath } } } } }',
			$account,
			$site,
			$since,
			$bots
		);
		$response = wp_remote_post(
			AARDLING_THEME_READS_GRAPHQL,
			array(
				'timeout' => 15,
				'headers' => array(
					'Authorization' => 'Bearer ' . aardling_theme_reads_setting( 'token' ),
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( array( 'query' => $query ) ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return array( false, array(), $response->get_error_message() );
		}

		$data   = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$errors = array();
		foreach ( ( is_array( $data ) && ! empty( $data['errors'] ) ) ? (array) $data['errors'] : array() as $error ) {
			$errors[] = is_array( $error ) && isset( $error['message'] ) ? (string) $error['message'] : 'unknown error';
		}
		if ( $errors && '' !== $bots && false !== stripos( implode( ' ', $errors ), 'bot' ) ) {
			continue;
		}
		if ( $errors ) {
			return array( false, array(), implode( '; ', $errors ) );
		}
		if ( ! is_array( $data ) || ! isset( $data['data']['viewer']['accounts'][0]['rumPageloadEventsAdaptiveGroups'] ) ) {
			return array( false, array(), 'HTTP ' . wp_remote_retrieve_response_code( $response ) . ': no Web Analytics data for this account and site tag.' );
		}

		$rows = array();
		foreach ( (array) $data['data']['viewer']['accounts'][0]['rumPageloadEventsAdaptiveGroups'] as $group ) {
			$rows[] = array( (string) ( $group['dimensions']['requestPath'] ?? '' ), (int) ( $group['count'] ?? 0 ) );
		}
		return array( true, $rows, '' );
	}

	return array( false, array(), 'No answer.' );
}

/**
 * Turn Cloudflare's top paths into the five most-read published posts.
 *
 * @param array $rows array( path, count ) pairs, most loaded first.
 */
function aardling_theme_reads_trending_from( array $rows ) : array {
	$items = array();
	foreach ( $rows as $row ) {
		$id   = '' !== $row[0] ? url_to_postid( home_url( $row[0] ) ) : 0;
		$post = $id ? get_post( $id ) : null;
		if ( ! $post || isset( $items[ $id ] ) || 'post' !== $post->post_type || 'publish' !== $post->post_status || '' !== $post->post_password ) {
			continue;
		}
		$items[ $id ] = array_intersect_key( aardling_theme_reads_item( $post ), array_flip( array( 'id', 'url', 'title' ) ) );
		if ( 5 === count( $items ) ) {
			break;
		}
	}
	return array_values( $items );
}

/**
 * Hourly: refresh Trending. On failure the old list stays for three days, then the box hides.
 */
function aardling_theme_reads_refresh_trending() : void {
	if ( ! aardling_theme_reads_trending_configured() ) {
		return;
	}
	list( $ok, $rows, $error ) = aardling_theme_reads_cloudflare_top();

	if ( $ok ) {
		aardling_theme_reads_save(
			array(
				'trending'         => aardling_theme_reads_trending_from( $rows ),
				'trending_time'    => time(),
				'trending_ok_time' => time(),
				'trending_error'   => '',
			)
		);
		return;
	}

	$data    = aardling_theme_reads_data();
	$changes = array(
		'trending_time'  => time(),
		'trending_error' => $error,
	);
	if ( (int) $data['trending_ok_time'] < time() - 3 * DAY_IN_SECONDS ) {
		$changes['trending'] = array();
	}
	aardling_theme_reads_save( $changes );
}

add_action( 'aardling_theme_reads_trending', 'aardling_theme_reads_refresh_trending' );

add_action( 'init', function () {
	if ( aardling_theme_reads_trending_configured() && ! wp_next_scheduled( 'aardling_theme_reads_trending' ) ) {
		wp_schedule_event( time() + MINUTE_IN_SECONDS, 'hourly', 'aardling_theme_reads_trending' );
	}
} );
add_action( 'switch_theme', function () {
	wp_clear_scheduled_hook( 'aardling_theme_reads_trending' );
} );

// Recently: rebuilt at the end of any request that published, changed or unpublished a post, after
// its featured image and terms are saved too, and before the Cloudflare purge goes out.
add_action( 'transition_post_status', function ( $new_status, $old_status, $post ) {
	if ( $post instanceof WP_Post && 'post' === $post->post_type && ( 'publish' === $new_status || 'publish' === $old_status ) ) {
		$GLOBALS['aardling_theme_reads_recent_changed'] = true;
	}
}, 10, 3 );
add_action( 'shutdown', function () {
	if ( ! empty( $GLOBALS['aardling_theme_reads_recent_changed'] ) ) {
		$GLOBALS['aardling_theme_reads_recent_changed'] = false;
		aardling_theme_reads_save( array( 'recent' => aardling_theme_reads_recent() ) );
	}
}, 0 );

// /aardling-reads.json: Recently and Trending for the script. Cloudflare's page rule caches it like a
// page; browsers check back each time (no-cache), so a cleared list shows up straight away.
add_action( 'init', function () {
	$path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( AARDLING_THEME_READS_PATH !== $path ) {
		return;
	}
	if ( AARDLING_THEME_READS_FORMAT !== (int) aardling_theme_reads_data()['format'] ) {
		aardling_theme_reads_save( array( 'recent' => aardling_theme_reads_recent(), 'format' => AARDLING_THEME_READS_FORMAT ) );
	}
	$data = aardling_theme_reads_data();
	$body = (string) wp_json_encode(
		array(
			'recent'   => $data['recent'],
			'trending' => $data['trending'],
		),
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	);

	status_header( 200 );
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Cache-Control: no-cache' );
	header( 'ETag: "' . md5( $body ) . '"' );
	header( 'X-Robots-Tag: noindex' );
	echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON.
	exit;
}, 0 );

/**
 * One box. Suggested arrives with its posts; Trending and Recently arrive empty and hidden.
 *
 * @param string    $list  'related', 'trending' or 'recent'.
 * @param WP_Post[] $posts Posts to show now.
 */
function aardling_theme_reads_box( string $list, array $posts = array() ) : string {
	$labels = aardling_theme_reads_labels();
	$tag    = 'trending' === $list ? 'ol' : 'ul';
	$items  = '';

	foreach ( $posts as $post ) {
		$item   = aardling_theme_reads_item( $post );
		$items .= sprintf(
			'<li class="aardling-reads__item"><time class="aardling-reads__date" datetime="%s">%s</time><a class="aardling-reads__title" href="%s">%s</a></li>',
			esc_attr( $item['iso'] ),
			esc_html( $item['date'] ),
			esc_url( $item['url'] ),
			esc_html( $item['title'] )
		);
	}

	return sprintf(
		'<div class="aardling-reads__box aardling-reads__box--%1$s"%2$s><h2 class="aardling-reads__label">%3$s</h2><%4$s class="aardling-reads__list">%5$s</%4$s></div>',
		esc_attr( $list ),
		'related' === $list ? '' : ' data-list="' . esc_attr( $list ) . '" hidden',
		esc_html( $labels[ $list ] ?? $list ),
		$tag,
		$items
	);
}

// The three boxes, after the post, its navigation and comments, before the site footer.
add_action( 'get_footer', function () {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post || post_password_required( $post ) ) {
		return;
	}

	$related = aardling_theme_reads_related( $post );
	printf(
		'<section class="aardling-reads" aria-label="%s" data-post="%d" data-src="%s">%s%s%s</section>' . "\n",
		esc_attr( aardling_theme_reads_labels()['section'] ),
		(int) $post->ID,
		esc_url( home_url( AARDLING_THEME_READS_PATH ) ),
		$related ? aardling_theme_reads_box( 'related', $related ) : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the box.
		aardling_theme_reads_box( 'trending' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		aardling_theme_reads_box( 'recent' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
	// noptimize: Autoptimize leaves it where it is, right after the boxes it fills.
	echo '<!--noptimize--><script>' . aardling_theme_reads_script() . '</script><!--/noptimize-->' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 10 );

/**
 * Fills Trending (at least 3 posts) and Recently (at least 1) from the JSON file, leaving out the
 * post being read; shows the first recent post's image above it. Text goes in as text.
 */
function aardling_theme_reads_script() : string {
	return <<<'JS'
(function(){var s=document.querySelector('.aardling-reads');if(!s||!window.fetch){return;}var cur=parseInt(s.getAttribute('data-post'),10);
fetch(s.getAttribute('data-src'),{cache:'no-cache',credentials:'omit'}).then(function(r){return r.ok?r.json():null;}).then(function(d){if(!d){return;}
[['trending',3],['recent',1]].forEach(function(k){var box=s.querySelector('[data-list="'+k[0]+'"]'),list=box&&box.querySelector('ol,ul');
var items=(d[k[0]]||[]).filter(function(p){return p&&p.id!==cur&&/^https?:\/\//.test(p.url||'');}).slice(0,4);if(!list||items.length<k[1]){return;}
items.forEach(function(p,i){var li=document.createElement('li'),a=document.createElement('a');li.className='aardling-reads__item';
if(p.thumb&&i===0){var im=new Image();im.src=p.thumb;im.alt='';im.loading='lazy';im.decoding='async';im.className='aardling-reads__thumb';li.appendChild(im);}
if(p.date){var t=document.createElement('time');t.className='aardling-reads__date';t.dateTime=p.iso||'';t.textContent=p.date;li.appendChild(t);}
a.className='aardling-reads__title';a.href=p.url;a.textContent=p.title||'';li.appendChild(a);list.appendChild(li);});box.hidden=false;});}).catch(function(){});})();
JS;
}

// Styles, on single posts only. Fonts and link colour come from Twenty Nineteen.
add_action( 'wp_head', function () {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	$sans = '-apple-system,BlinkMacSystemFont,"Segoe UI","Roboto","Oxygen","Ubuntu","Cantarell","Fira Sans","Droid Sans","Helvetica Neue",sans-serif';
	$css  = '.aardling-reads{margin:calc(2*1rem) 1rem;display:grid;gap:calc(3*1rem)}'
		. '@media (min-width:768px){.aardling-reads{margin:calc(4*1rem) calc(10% + 60px)}}'
		. '.aardling-reads__box{position:relative;border:1px solid #e6e6e6;background:#fff;padding:calc(2.5*1rem) 1.5rem 1.5rem}'
		. '.aardling-reads__box[hidden]{display:none}'
		. '.aardling-reads__label:before{display:none}'
		. '.aardling-reads__label{position:absolute;top:0;left:50%;transform:translate(-50%,-50%);margin:0;padding:.4rem 2rem;background:#fff;border:1px solid #e6e6e6;'
		. 'font-family:' . $sans . ';font-size:.7111em;font-weight:500;line-height:1.4;letter-spacing:.2em;text-transform:uppercase;color:#767676;white-space:nowrap}'
		. '.aardling-reads__list{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:1fr;gap:1.5rem 2rem}'
		. '@media (min-width:600px){.aardling-reads__list{grid-template-columns:repeat(2,minmax(0,1fr))}}'
		. '@media (min-width:1168px){.aardling-reads__list{grid-template-columns:repeat(4,minmax(0,1fr))}.aardling-reads__item+.aardling-reads__item{border-left:1px solid #e6e6e6;padding-left:2rem;margin-left:-1rem}}'
		. '.aardling-reads__item{margin:0;line-height:1.35;overflow-wrap:break-word;hyphens:manual}'
		. '.aardling-reads__date{display:block;white-space:nowrap;margin-bottom:.5rem;font-family:' . $sans . ';font-size:.6em;letter-spacing:.12em;text-transform:uppercase;color:#767676}'
		. '.aardling-reads__title{font-size:1.15em;text-decoration:none}'
		. '.aardling-reads__title:hover,.aardling-reads__title:focus{text-decoration:underline}'
		. '.aardling-reads__thumb{display:block;width:100%;height:auto;aspect-ratio:3/2;object-fit:cover;margin:0 0 .75rem}'
		. '.aardling-reads__box--trending{background:#fdf3e2;border-color:#f1cf91}'
		. '.aardling-reads__box--trending .aardling-reads__label{border-color:#f1cf91}'
		. '.aardling-reads__box--trending .aardling-reads__list{counter-reset:aardling-reads}'
		. '.aardling-reads__box--trending .aardling-reads__item{counter-increment:aardling-reads;border-color:#f1cf91}'
		. '.aardling-reads__box--trending .aardling-reads__item::before{content:counter(aardling-reads) ".";display:block;margin-bottom:.75rem;font-size:2.2em;font-weight:700;line-height:1;color:#e8890c}';

	echo '<style id="aardling-reads-css">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed CSS.
}, 100 );

// Appearance > Aardling: account ID, site tag, token, Trending's status and a refresh button.
add_action( 'admin_init', function () {
	$id = function ( string $option, string $pattern, string $message ) {
		return function ( $value ) use ( $option, $pattern, $message ) {
			$value = trim( (string) $value );
			if ( '__clear__' === $value ) {
				return '';
			}
			if ( '' === $value || ! preg_match( $pattern, $value ) ) {
				if ( '' !== $value ) {
					add_settings_error( $option, $option, $message );
				}
				return (string) get_option( $option, '' );
			}
			return $value;
		};
	};
	register_setting( 'aardling_theme_reads', 'aardling_theme_reads_account', array(
		'type'              => 'string',
		'show_in_rest'      => false,
		'sanitize_callback' => $id( 'aardling_theme_reads_account', '/^[a-f0-9]{32}$/', 'That isn\'t an account ID: it has 32 letters and digits, on the Overview page of aardling.com in Cloudflare.' ),
	) );
	register_setting( 'aardling_theme_reads', 'aardling_theme_reads_site', array(
		'type'              => 'string',
		'show_in_rest'      => false,
		'sanitize_callback' => $id( 'aardling_theme_reads_site', '/^[A-Za-z0-9]{8,64}$/', 'That isn\'t a site tag: copy the siteTag value from the address bar of aardling.com\'s Web Analytics page.' ),
	) );
	register_setting( 'aardling_theme_reads', 'aardling_theme_reads_token', array(
		'type'              => 'string',
		'show_in_rest'      => false,
		'sanitize_callback' => function ( $value ) {
			$value = trim( (string) $value );
			if ( '' === $value ) {
				return (string) get_option( 'aardling_theme_reads_token', '' ); // Empty field keeps the saved token.
			}
			if ( '__clear__' === $value ) {
				return '';
			}
			return sanitize_text_field( $value );
		},
	) );
} );

add_action( 'admin_post_aardling_theme_reads_refresh', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'This user may not change site settings.' );
	}
	check_admin_referer( 'aardling_theme_reads_refresh' );
	aardling_theme_reads_save( array( 'recent' => aardling_theme_reads_recent() ) );
	aardling_theme_reads_refresh_trending();
	wp_safe_redirect( admin_url( 'themes.php?page=aardling-theme' ) );
	exit;
} );

add_action( 'aardling_theme_settings_page', function () {
	$data   = aardling_theme_reads_data();
	$fields = array(
		'account' => array( 'Account ID', 'AARDLING_THEME_CLOUDFLARE_ACCOUNT', 'Cloudflare &rarr; aardling.com &rarr; Overview, in the API box on the right, under the zone ID.' ),
		'site'    => array( 'Web Analytics site tag', 'AARDLING_THEME_WEB_ANALYTICS_SITE', 'Cloudflare &rarr; Web Analytics &rarr; aardling.com: the value after <code>siteTag~in=</code> in the address bar.' ),
	);
	?>
	<h2>Reading boxes</h2>
	<p>Under each post: Suggested (related posts), Trending (most read in the last <?php echo (int) AARDLING_THEME_READS_DAYS; ?> days, from Cloudflare Web Analytics) and Recently.</p>
	<p>Recently: <?php echo esc_html( count( $data['recent'] ) . ' posts.' ); ?>
		Trending: <?php
		if ( ! aardling_theme_reads_trending_configured() ) {
			echo esc_html( 'off until the account ID, site tag and token are set.' );
		} else {
			echo esc_html(
				count( $data['trending'] ) . ' posts'
				. ( $data['trending_time'] ? ', checked ' . human_time_diff( (int) $data['trending_time'] ) . ' ago' : ', first check within the hour' )
				. ( '' !== $data['trending_error'] ? ', failed: ' . $data['trending_error'] : '' ) . '.'
				. ( count( $data['trending'] ) < 3 ? ' The box shows from 3 posts on.' : '' )
			);
		}
		?></p>
	<form method="post" action="options.php">
		<?php settings_fields( 'aardling_theme_reads' ); ?>
		<table class="form-table" role="presentation">
			<?php foreach ( $fields as $name => $field ) : ?>
				<tr>
					<th scope="row"><label for="aardling_theme_reads_<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $field[0] ); ?></label></th>
					<td>
						<?php if ( defined( $field[1] ) && constant( $field[1] ) ) : ?>
							<code><?php echo esc_html( aardling_theme_reads_setting( $name ) ); ?></code> (set in wp-config.php)
						<?php else : ?>
							<input type="text" id="aardling_theme_reads_<?php echo esc_attr( $name ); ?>" name="aardling_theme_reads_<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( aardling_theme_reads_setting( $name ) ); ?>" class="regular-text code" autocomplete="off">
							<p class="description"><?php echo wp_kses( $field[2], array( 'code' => array() ) ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			<tr>
				<th scope="row"><label for="aardling_theme_reads_token">Analytics API token</label></th>
				<td>
					<?php if ( defined( 'AARDLING_THEME_CLOUDFLARE_ANALYTICS_TOKEN' ) && AARDLING_THEME_CLOUDFLARE_ANALYTICS_TOKEN ) : ?>
						Set in wp-config.php.
					<?php else : ?>
						<input type="password" id="aardling_theme_reads_token" name="aardling_theme_reads_token" value="" class="regular-text" autocomplete="off" placeholder="<?php echo '' !== (string) get_option( 'aardling_theme_reads_token', '' ) ? esc_attr( 'Saved. Paste a new one to replace it.' ) : ''; ?>">
						<p class="description">A second token, not the cache one: My Profile &rarr; API Tokens &rarr; Create Token &rarr; Custom token, with only the permission Account &rarr; Account Analytics &rarr; Read, for your account. Leave empty to keep a saved token, or type __clear__ to remove it.</p>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php submit_button( 'Save reading box settings' ); ?>
	</form>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="aardling_theme_reads_refresh">
		<?php wp_nonce_field( 'aardling_theme_reads_refresh' ); ?>
		<?php submit_button( 'Refresh Recently and Trending now', 'secondary', 'submit', false ); ?>
	</form>
	<?php
} );
