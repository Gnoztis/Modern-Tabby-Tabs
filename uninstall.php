<?php
/**
 * Clean up options when the plugin is deleted.
 *
 * @package ModernTabby
 */

declare( strict_types = 1 );

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'mrt_default_styles' );
delete_option( 'mrt_tab_title_element' );
delete_option( 'mrt_fa_icon_style' );
delete_option( 'mrt_theme_mode' );
delete_option( 'mrt_css_mode' );
delete_option( 'mrt_panel_style' );