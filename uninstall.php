<?php
/**
 * Removes plugin data on uninstall.
 *
 * Only the plugin's own mrt_* options are deleted; nothing else in the
 * database is touched. On multisite every site stores its own copy of the
 * options, so each site is cleaned in turn.
 *
 * @package ModernTabby
 */

declare( strict_types = 1 );

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! function_exists( 'mtt_uninstall_site' ) ) {
	/**
	 * Deletes the plugin options of the current site.
	 *
	 * The option names are literals on purpose: uninstall.php runs on its own,
	 * before the plugin classes are loaded.
	 */
	function mtt_uninstall_site(): void {
		delete_option( 'mrt_default_styles' );
		delete_option( 'mrt_tab_title_element' );
		delete_option( 'mrt_fa_icon_style' );
		delete_option( 'mrt_theme_mode' );
		delete_option( 'mrt_css_mode' );
		delete_option( 'mrt_panel_style' );

		// Update-check cache created by the GitHub updater.
		delete_transient( 'mtt_github_release' );
		delete_transient( 'mtt_github_release_error' );
	}
}

if ( is_multisite() ) {
	$mtt_sites = get_sites(
		[
			'fields' => 'ids',
			'number' => 0,
		]
	);

	foreach ( (array) $mtt_sites as $mtt_site_id ) {
		switch_to_blog( (int) $mtt_site_id );
		mtt_uninstall_site();
		restore_current_blog();
	}
} else {
	mtt_uninstall_site();
}
