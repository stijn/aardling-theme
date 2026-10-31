<?php
/**
 * Self-updating from the public GitHub repo stijn/aardling-theme.
 *
 * How it works:
 * - plugin-update-checker (MIT, vendored) looks at the latest GitHub release of the repo
 *   and offers its "aardling.zip" asset as a theme update.
 * - The repo is public, so no token is needed. A token only lifts GitHub's rate limit of 60
 *   anonymous requests an hour, which every site on a shared server's IP draws from. In order
 *   of preference: define( 'AARDLING_THEME_GITHUB_TOKEN', '...' ); in wp-config.php, the field
 *   on Appearance > Aardling, or the token Aardling Schuif already uses (fine-grained tokens can
 *   always read public repos). The token is never shown again after saving and is never
 *   returned by any ability.
 * - WordPress auto-updates are switched on for this theme only.
 * - Two abilities (theme-status, self-update) let the MCP connector check and apply an update
 *   right away instead of waiting for the twice-daily WordPress cron.
 *
 * @package Aardling
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __DIR__ ) . '/vendor/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

const AARDLING_THEME_REPO = 'https://github.com/stijn/aardling-theme/';
const AARDLING_THEME_SLUG = 'aardling';

/**
 * Where the GitHub token comes from: 'theme-config', 'theme-option', 'schuif' or '' (none).
 */
function aardling_theme_github_token_source() : string {
	if ( defined( 'AARDLING_THEME_GITHUB_TOKEN' ) && AARDLING_THEME_GITHUB_TOKEN ) {
		return 'theme-config';
	}
	if ( '' !== (string) get_option( 'aardling_theme_github_token', '' ) ) {
		return 'theme-option';
	}
	if ( ( defined( 'AARDLING_SCHUIF_GITHUB_TOKEN' ) && AARDLING_SCHUIF_GITHUB_TOKEN ) || '' !== (string) get_option( 'aardling_schuif_github_token', '' ) ) {
		return 'schuif';
	}
	return '';
}

function aardling_theme_github_token() : string {
	switch ( aardling_theme_github_token_source() ) {
		case 'theme-config':
			return (string) AARDLING_THEME_GITHUB_TOKEN;
		case 'theme-option':
			return (string) get_option( 'aardling_theme_github_token', '' );
		case 'schuif':
			if ( defined( 'AARDLING_SCHUIF_GITHUB_TOKEN' ) && AARDLING_SCHUIF_GITHUB_TOKEN ) {
				return (string) AARDLING_SCHUIF_GITHUB_TOKEN;
			}
			return (string) get_option( 'aardling_schuif_github_token', '' );
	}
	return '';
}

function aardling_theme_update_checker() {
	static $checker = null;
	if ( null === $checker ) {
		$checker = PucFactory::buildUpdateChecker( AARDLING_THEME_REPO, dirname( __DIR__ ) . '/functions.php', AARDLING_THEME_SLUG, 6 );
		$token   = aardling_theme_github_token();
		if ( '' !== $token ) {
			$checker->setAuthentication( $token );
		}
		$checker->getVcsApi()->enableReleaseAssets( '/aardling\.zip($|[?&#])/i' );
	}
	return $checker;
}
aardling_theme_update_checker();

// Auto-update this theme only.
add_filter( 'auto_update_theme', function ( $update, $item ) {
	if ( isset( $item->theme ) && AARDLING_THEME_SLUG === $item->theme ) {
		return true;
	}
	return $update;
}, 10, 2 );

// Appearance > Aardling: token field, status and a check button.
add_action( 'admin_init', function () {
	register_setting( 'aardling_theme', 'aardling_theme_github_token', array(
		'type'              => 'string',
		'show_in_rest'      => false,
		'sanitize_callback' => function ( $value ) {
			$value = trim( (string) $value );
			if ( '' === $value ) {
				return (string) get_option( 'aardling_theme_github_token', '' ); // Empty field keeps the saved token.
			}
			if ( '__clear__' === $value ) {
				return '';
			}
			return sanitize_text_field( $value );
		},
	) );
} );

add_action( 'admin_post_aardling_theme_check_updates', function () {
	if ( ! current_user_can( 'update_themes' ) ) {
		wp_die( 'This user may not update themes.' );
	}
	check_admin_referer( 'aardling_theme_check_updates' );
	aardling_theme_update_checker()->checkForUpdates();
	wp_safe_redirect( admin_url( 'themes.php?page=aardling-theme' ) );
	exit;
} );

add_action( 'admin_menu', function () {
	add_theme_page( 'Aardling', 'Aardling', 'manage_options', 'aardling-theme', function () {
		$source = aardling_theme_github_token_source();
		$labels = array(
			'theme-config' => 'set in wp-config.php (AARDLING_THEME_GITHUB_TOKEN).',
			'theme-option' => 'saved on this page.',
			'schuif'       => 'borrowed from Aardling Schuif, which lifts GitHub\'s rate limit.',
			''             => 'none. Updates still work, but anonymous requests from a shared server can hit GitHub\'s rate limit.',
		);
		$state  = aardling_theme_update_checker()->getUpdateState();
		$update = aardling_theme_update_checker()->getUpdate();
		?>
		<div class="wrap">
			<h1>Aardling</h1>
			<p>Installed version: <strong><?php echo esc_html( AARDLING_THEME_VERSION ); ?></strong>.
			<?php if ( $update ) : ?>
				Available: <strong><?php echo esc_html( $update->version ); ?></strong> (installs automatically).
			<?php else : ?>
				No newer release found<?php echo $state && $state->getLastCheck() ? ' (last check ' . esc_html( human_time_diff( $state->getLastCheck() ) ) . ' ago)' : ''; ?>.
			<?php endif; ?></p>
			<p>Updates come from the public GitHub repo <code>stijn/aardling-theme</code> (latest release, asset <code>aardling.zip</code>). Auto-updates are on for this theme.</p>
			<p>GitHub token: <?php echo esc_html( $labels[ $source ] ); ?></p>
			<?php if ( 'theme-config' !== $source ) : ?>
				<form method="post" action="options.php">
					<?php settings_fields( 'aardling_theme' ); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="aardling_theme_github_token">GitHub token</label></th>
							<td>
								<input type="password" id="aardling_theme_github_token" name="aardling_theme_github_token" value="" class="regular-text" autocomplete="off" placeholder="<?php echo 'theme-option' === $source ? esc_attr( 'Saved. Paste a new one to replace it.' ) : ''; ?>">
								<p class="description">Optional: the repo is public, and Aardling Schuif's token is used when this field is empty. A token only lifts GitHub's rate limit; any fine-grained token works. Leave empty to keep a saved token, or type __clear__ to remove it.</p>
							</td>
						</tr>
					</table>
					<?php submit_button( 'Save token' ); ?>
				</form>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="aardling_theme_check_updates">
				<?php wp_nonce_field( 'aardling_theme_check_updates' ); ?>
				<?php submit_button( 'Check for updates now', 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	} );
} );

// Abilities: status and immediate self-update, for the MCP connector.
add_action( 'wp_abilities_api_categories_init', function () {
	if ( ! function_exists( 'wp_register_ability_category' ) ) {
		return;
	}
	wp_register_ability_category( 'aardling-theme', array(
		'label'       => 'Aardling theme',
		'description' => 'Update status and self-update of the Aardling child theme. Touches no content, settings, plugins or other themes.',
	) );
} );

add_action( 'wp_abilities_api_init', function () {
	if ( ! function_exists( 'wp_register_ability' ) ) {
		return;
	}

	$can_update = function () {
		if ( ! current_user_can( 'update_themes' ) ) {
			return new WP_Error( 'aardling_theme_forbidden', 'This user may not update themes.' );
		}
		return true;
	};

	$status = function ( bool $check_now ) : array {
		$checker = aardling_theme_update_checker();
		if ( $check_now ) {
			$checker->checkForUpdates();
		}
		$update = $checker->getUpdate();
		$state  = $checker->getUpdateState();
		$errors = array();
		foreach ( (array) $checker->getLastRequestApiErrors() as $e ) {
			if ( isset( $e['error'] ) && is_wp_error( $e['error'] ) ) {
				$errors[] = $e['error']->get_error_message();
			}
		}
		return array(
			'installed_version' => AARDLING_THEME_VERSION,
			'available_version' => $update ? (string) $update->version : null,
			'token_configured'  => '' !== aardling_theme_github_token(),
			'token_source'      => aardling_theme_github_token_source(),
			'auto_update'       => true,
			'last_check'        => ( $state && $state->getLastCheck() ) ? gmdate( 'c', (int) $state->getLastCheck() ) : null,
			'errors'            => $errors,
		);
	};

	$status_schema = array(
		'type'       => 'object',
		'properties' => array(
			'installed_version' => array( 'type' => 'string' ),
			'available_version' => array( 'type' => array( 'string', 'null' ) ),
			'token_configured'  => array( 'type' => 'boolean' ),
			'token_source'      => array( 'type' => 'string' ),
			'auto_update'       => array( 'type' => 'boolean' ),
			'last_check'        => array( 'type' => array( 'string', 'null' ) ),
			'errors'            => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
			'updated'           => array( 'type' => 'boolean' ),
			'message'           => array( 'type' => 'string' ),
		),
	);

	wp_register_ability( 'aardling-theme/theme-status', array(
		'label'               => 'Aardling theme: update status',
		'description'         => 'Shows the installed version of the Aardling child theme and checks GitHub for a newer release. Never returns the token.',
		'category'            => 'aardling-theme',
		'input_schema'        => array(
			'type'                 => 'object',
			'properties'           => array( 'check_now' => array( 'type' => 'boolean', 'description' => 'Ask GitHub now instead of using the cached result.' ) ),
			'additionalProperties' => false,
		),
		'output_schema'       => $status_schema,
		'permission_callback' => $can_update,
		'execute_callback'    => function ( $input = null ) use ( $status ) {
			return $status( ! empty( $input['check_now'] ) );
		},
		'meta'                => array(
			'mcp'          => array( 'public' => true ),
			'show_in_rest' => true,
			'annotations'  => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ),
		),
	) );

	wp_register_ability( 'aardling-theme/self-update', array(
		'label'               => 'Aardling theme: install update now',
		'description'         => 'Checks GitHub for a newer release of THIS theme (aardling) and installs it right away. Touches no content, setting, plugin or other theme.',
		'category'            => 'aardling-theme',
		'input_schema'        => array( 'type' => 'object', 'properties' => new stdClass(), 'additionalProperties' => false ),
		'output_schema'       => $status_schema,
		'permission_callback' => $can_update,
		'execute_callback'    => function ( $input = null ) use ( $status ) {
			$before = $status( true );
			if ( empty( $before['available_version'] ) || version_compare( $before['available_version'], AARDLING_THEME_VERSION, '<=' ) ) {
				return array_merge( $before, array( 'updated' => false, 'message' => 'Already up to date.' ) );
			}
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/misc.php';
			require_once ABSPATH . 'wp-admin/includes/theme.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			wp_update_themes(); // Refresh the update transient so the upgrader sees the new package.
			$upgrader = new Theme_Upgrader( new Automatic_Upgrader_Skin() );
			$result   = $upgrader->upgrade( AARDLING_THEME_SLUG );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			if ( ! $result ) {
				$messages = $upgrader->skin->get_upgrade_messages();
				return new WP_Error( 'aardling_theme_update_failed', 'Update failed: ' . implode( ' | ', array_slice( (array) $messages, -3 ) ) );
			}
			return array_merge( $before, array( 'updated' => true, 'message' => 'Installed ' . $before['available_version'] . '. The new code runs from the next request.' ) );
		},
		'meta'                => array(
			'mcp'          => array( 'public' => true ),
			'show_in_rest' => true,
			'annotations'  => array( 'readonly' => false, 'destructive' => false, 'idempotent' => true ),
		),
	) );
} );
