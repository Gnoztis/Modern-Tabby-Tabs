<?php
/**
 * Plugin Name:       Modern Tabby Tabs
 * Description:       Адаптивные табы и аккордеон на шорткодах. Чистый JS, поддержка тёмной темы.
 * Version:           1.4.5
 * Requires at least: 7.0
 * Requires PHP:      8.4
 * Tested up to:      7.1
 * Author:            Gnostis
 * Author URI:        https://github.com/Gnoztis
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       modern-tabby-tabs
 * Update URI:        https://github.com/Gnoztis/Modern-Tabby-Tabs
 *
 * @package ModernTabby
 */

declare( strict_types = 1 );

namespace ModernTabby;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Plugin constants. The version is declared once here and repeated only in the
 * plugin header above; both must match the release tag.
 */
define( 'MTT_VERSION', '1.4.5' );
define( 'MTT_PLUGIN_FILE', __FILE__ );
define( 'MTT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Font Awesome icon style supported by the plugin.
 */
enum FaStyle: string {
	case Regular = 'regular';
	case Solid   = 'solid';
	case Light   = 'light';
	case Thin    = 'thin';
	case Duotone = 'duotone';

	public function prefix(): string {
		return match ( $this ) {
			self::Regular => 'fa-regular',
			self::Solid   => 'fa-solid',
			self::Light   => 'fa-light',
			self::Thin    => 'fa-thin',
			self::Duotone => 'fa-duotone',
		};
	}

	public static function fromOption( mixed $value ): self {
		if ( ! is_string( $value ) ) {
			return self::Regular;
		}
		return self::tryFrom( $value ) ?? self::Regular;
	}

	/** @return array<string,string> */
	public static function options(): array {
		return [
			self::Regular->value => __( 'Обычные', 'modern-tabby-tabs' ),
			self::Solid->value   => __( 'Залитые', 'modern-tabby-tabs' ),
			self::Light->value   => __( 'Тонкие', 'modern-tabby-tabs' ),
			self::Thin->value    => __( 'Сверхтонкие', 'modern-tabby-tabs' ),
			self::Duotone->value => __( 'Двухцветные', 'modern-tabby-tabs' ),
		];
	}
}

/**
 * HTML element allowed for the tab title wrapper.
 */
enum TitleElement: string {
	case H1   = 'h1';
	case H2   = 'h2';
	case H3   = 'h3';
	case H4   = 'h4';
	case H5   = 'h5';
	case H6   = 'h6';
	case P    = 'p';
	case Div  = 'div';
	case Span = 'span';

	public static function fromOption( mixed $value ): self {
		if ( ! is_string( $value ) ) {
			return self::H2;
		}
		return self::tryFrom( strtolower( $value ) ) ?? self::H2;
	}

	/** @return list<string> */
	public static function values(): array {
		return array_map(
			static fn ( self $case ): string => $case->value,
			self::cases(),
		);
	}
}

/**
 * Colour scheme used by the tab group.
 */
enum ThemeMode: string {
	case Auto   = 'auto';
	case Light  = 'light';
	case Dark   = 'dark';
	case System = 'system';

	public static function fromOption( mixed $value ): self {
		if ( ! is_string( $value ) ) {
			return self::Auto;
		}
		return self::tryFrom( $value ) ?? self::Auto;
	}

	public function cssClass(): string {
		return 'mrt-theme-' . $this->value;
	}

	/** @return array<string,string> */
	public static function options(): array {
		return [
			self::Auto->value   => __( 'Авто — как в активной теме сайта', 'modern-tabby-tabs' ),
			self::Light->value  => __( 'Всегда светлая',                   'modern-tabby-tabs' ),
			self::Dark->value   => __( 'Всегда тёмная',                    'modern-tabby-tabs' ),
			self::System->value => __( 'Системная — как в настройках ОС',  'modern-tabby-tabs' ),
		];
	}
}

/**
 * How the front-end stylesheet is delivered.
 */
enum CssMode: string {
	case File   = 'file';
	case Inline = 'inline';

	public static function fromOption( mixed $value ): self {
		if ( ! is_string( $value ) ) {
			return self::File;
		}
		return self::tryFrom( $value ) ?? self::File;
	}

	/** @return array<string,string> */
	public static function options(): array {
		return [
			self::File->value   => __( 'Отдельный файл — кэшируется, рекомендуется для сайта', 'modern-tabby-tabs' ),
			self::Inline->value => __( 'Инлайн — внутри <head>, без отдельного запроса',       'modern-tabby-tabs' ),
		];
	}
}

/**
 * Visual style of the panel that holds the tab content.
 *
 * Order matters: Box is the default (original bordered look), the rest are
 * opt-in alternatives.
 */
enum PanelStyle: string {
	case Box  = 'box';    // Classic bordered box (default).
	case Flat = 'flat';   // No background, no borders — content blends with page.
	case Left = 'left';   // Thin accent bar on the left edge.
	case Soft = 'soft';   // Soft tinted background, no border.

	public static function fromOption( mixed $value ): self {
		if ( ! is_string( $value ) ) {
			return self::Box;
		}
		return self::tryFrom( $value ) ?? self::Box;
	}

	public function cssClass(): string {
		return 'mrt-panel-' . $this->value;
	}

	/** @return array<string,string> */
	public static function options(): array {
		return [
			self::Box->value  => __( 'Рамка (по умолчанию, классическая карточка)',     'modern-tabby-tabs' ),
			self::Flat->value => __( 'Плоский — без фона и рамки',                       'modern-tabby-tabs' ),
			self::Left->value => __( 'Акцентная полоса слева',                           'modern-tabby-tabs' ),
			self::Soft->value => __( 'Мягкий фон без рамки',                             'modern-tabby-tabs' ),
		];
	}
}

/**
 * Main plugin controller.
 */
final class Plugin {

	/* -------- Option keys (stable across versions) -------- */
	public const OPT_DEFAULT_STYLES = 'mrt_default_styles';
	public const OPT_TITLE_ELEMENT  = 'mrt_tab_title_element';
	public const OPT_FA_STYLE       = 'mrt_fa_icon_style';
	public const OPT_THEME_MODE     = 'mrt_theme_mode';
	public const OPT_CSS_MODE       = 'mrt_css_mode';
	public const OPT_PANEL_STYLE    = 'mrt_panel_style';

	/* -------- Singleton -------- */
	private static ?self $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	/* -------- Shortcode state -------- */
	private static int $group_seq = 0;

	private bool    $inside_group  = false;
	private int     $tab_index     = 0;
	private string  $group_uid     = '';
	private ?string $group_classes = null;

	/* ---------------------------------------------------------------------
	 *  Bootstrap
	 * ------------------------------------------------------------------- */

	private function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue' ], 20 );

		add_shortcode( 'tabby',       [ $this, 'shortcode_tab' ] );
		add_shortcode( 'tabbyending', [ $this, 'shortcode_end' ] );

		add_filter( 'the_content', [ $this, 'clean_wpautop' ], 12 );

		add_filter(
			'plugin_action_links_' . plugin_basename( __FILE__ ),
			[ $this, 'action_links' ],
		);
	}

	/* ---------------------------------------------------------------------
	 *  URLs & asset versions
	 * ------------------------------------------------------------------- */

	public static function url( string $path = '' ): string {
		return plugin_dir_url( __FILE__ ) . ltrim( $path, '/' );
	}

	/**
	 * Cache-busting version for a bundled asset.
	 *
	 * Bumping MTT_VERSION invalidates browser and CDN caches for every
	 * installation at once. The plugin version is the only signal — no mtime,
	 * no file lookups.
	 */
	private static function asset_version( string $relative_path = '' ): string {
		return MTT_VERSION;
	}

	/* ---------------------------------------------------------------------
	 *  Assets
	 * ------------------------------------------------------------------- */

	public function register_assets(): void {
		wp_register_style(
			'mrt-tabs-print',
			self::url( 'assets/css/tabs-print.css' ),
			[],
			self::asset_version( 'assets/css/tabs-print.css' ),
			'print',
		);

		wp_register_script(
			handle: 'mrt-tabs',
			src:    self::url( 'assets/js/tabs.js' ),
			deps:   [],
			ver:    self::asset_version( 'assets/js/tabs.js' ),
			args:   [ 'in_footer' => true, 'strategy' => 'defer' ],
		);
	}

	public function maybe_enqueue(): void {
		if ( ! is_singular() ) {
			return;
		}

		$post = get_post();
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if ( has_shortcode( $post->post_content, 'tabby' ) ) {
			$this->enqueue_assets();
		}
	}

	private function enqueue_assets(): void {
		if ( 1 === (int) get_option( self::OPT_DEFAULT_STYLES, 1 ) ) {
			$mode = CssMode::fromOption( get_option( self::OPT_CSS_MODE, CssMode::File->value ) );

			if ( $mode === CssMode::Inline ) {
				$this->enqueue_css_inline();
			} else {
				$this->enqueue_css_file();
			}
		}

		wp_enqueue_style( 'mrt-tabs-print' );
		wp_enqueue_script( 'mrt-tabs' );

		do_action( 'mrt_enqueue_assets' );
	}

	private function enqueue_css_file(): void {
		wp_register_style(
			'mrt-tabs',
			self::url( 'assets/css/tabs.css' ),
			[],
			self::asset_version( 'assets/css/tabs.css' ),
		);
		wp_enqueue_style( 'mrt-tabs' );
	}

	private function enqueue_css_inline(): void {
		wp_register_style(
			'mrt-tabs',
			false,
			[],
			self::asset_version( 'assets/css/tabs.css' ),
		);
		wp_enqueue_style( 'mrt-tabs' );

		$css = $this->read_css( 'assets/css/tabs.css' );
		if ( $css !== '' ) {
			wp_add_inline_style( 'mrt-tabs', $css );
		}
	}

	private function read_css( string $relative_path ): string {
		$file = __DIR__ . '/' . ltrim( $relative_path, '/' );
		if ( ! is_readable( $file ) ) {
			return '';
		}
		$contents = file_get_contents( $file ); // phpcs:ignore -- plugin-internal path built from __DIR__.
		return $contents === false ? '' : $contents;
	}

	/* ---------------------------------------------------------------------
	 *  Shortcodes
	 * ------------------------------------------------------------------- */

	/**
	 * @param array<string,string>|string $atts
	 */
	public function shortcode_tab( $atts = [], ?string $content = null ): string {
		$atts = shortcode_atts(
			[
				'title' => '',
				'open'  => '',
				'icon'  => '',
				'ico'   => '',
				'class' => '',
			],
			$atts,
			'tabby'
		);

		if ( ! $this->inside_group ) {
			$this->inside_group = true;
			$this->tab_index    = 0;
			$this->group_uid    = 'mrt-' . ( ++self::$group_seq );

			$theme = ThemeMode::fromOption(
				get_option( self::OPT_THEME_MODE, ThemeMode::Auto->value )
			);
			$panel = PanelStyle::fromOption(
				get_option( self::OPT_PANEL_STYLE, PanelStyle::Box->value )
			);

			$this->group_classes = $theme->cssClass() . ' ' . $panel->cssClass();
		}

		$this->tab_index++;
		$index  = $this->tab_index;
		$active = $this->resolve_active_tab(
			title: (string) $atts['title'],
			open:  (string) $atts['open'],
			index: $index,
		);

		$custom = sanitize_html_class( (string) $atts['class'] );
		$title  = TitleElement::fromOption( get_option( self::OPT_TITLE_ELEMENT, 'h2' ) )->value;

		$title_class = 'tabtitle' . ( $custom !== '' ? " {$custom}" : '' );
		$panel_class = 'tabcontent' . ( $custom !== '' ? " {$custom}-content" : '' );

		if ( $active ) {
			$panel_class .= ' responsive-tabs__panel--active';
		}

		$tab_id   = "{$this->group_uid}-tab-{$index}";
		$panel_id = "{$this->group_uid}-panel-{$index}";

		$html = '';
		if ( $index === 1 ) {
			$html .= sprintf(
				'<div class="responsive-tabs %s" data-mrt-group="%s">' . "\n",
				esc_attr( (string) $this->group_classes ),
				esc_attr( $this->group_uid ),
			);
		} else {
			$html .= "</div>\n";
		}

		$html .= sprintf(
			'<%1$s class="%2$s" id="%3$s">%4$s</%1$s>' . "\n",
			$title,
			esc_attr( $title_class ),
			esc_attr( $tab_id ),
			$this->title_html( (string) $atts['title'], (string) $atts['icon'], (string) $atts['ico'] )
		);

		$html .= sprintf(
			'<div class="%1$s" id="%2$s" role="tabpanel" aria-labelledby="%3$s" aria-hidden="%4$s">' . "\n",
			esc_attr( $panel_class ),
			esc_attr( $panel_id ),
			esc_attr( $tab_id ),
			$active ? 'false' : 'true'
		);

		return $html;
	}

	/**
	 * @param array<string,string>|string $atts
	 */
	public function shortcode_end( $atts = [], ?string $content = null ): string {
		if ( ! $this->inside_group ) {
			return '';
		}

		$this->enqueue_assets();

		$this->inside_group  = false;
		$this->tab_index     = 0;
		$this->group_uid     = '';
		$this->group_classes = null;

		return "</div></div>\n";
	}

	/* ---------------------------------------------------------------------
	 *  Helpers
	 * ------------------------------------------------------------------- */

	private function resolve_active_tab( string $title, string $open, int $index ): bool {
		// Read-only filter parameter: it changes no state, so it needs no nonce.
		// The value is unslashed and slugged before it is compared below.
		$target_raw = $_GET['target'] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only parameter, sanitized below.

		if ( is_string( $target_raw ) && $target_raw !== '' ) {
			$target  = sanitize_title_with_dashes( wp_unslash( $target_raw ) );
			$current = sanitize_title_with_dashes(
				remove_accents( wp_kses_decode_entities( $title ) )
			);
			return $target === $current;
		}

		if ( strtolower( $open ) === 'yes' ) {
			return true;
		}

		return $index === 1;
	}

	private function title_html( string $title, string $icon, string $ico ): string {
		$allowed = [
			'br'     => [],
			'strong' => [],
			'em'     => [],
			'i'      => [],
			'span'   => [ 'class' => [] ],
			'small'  => [],
			'sub'    => [],
			'sup'    => [],
		];

		return $this->build_icon_html( $icon, $ico ) . wp_kses( $title, $allowed );
	}

	private function build_icon_html( string $icon, string $ico ): string {
		if ( $icon !== '' ) {
			$style  = FaStyle::fromOption( get_option( self::OPT_FA_STYLE, 'regular' ) );
			$prefix = $style->prefix();
			return sprintf(
				'<span class="%s" aria-hidden="true"></span>',
				esc_attr( $prefix . ' fa-' . sanitize_html_class( $icon ) )
			);
		}

		if ( $ico !== '' ) {
			return sprintf(
				'<span class="%s" aria-hidden="true"></span>',
				esc_attr( sanitize_html_class( $ico ) )
			);
		}

		return '';
	}

	public static function sanitize_title_element( mixed $value ): string {
		return TitleElement::fromOption( $value )->value;
	}

	public static function sanitize_theme_mode( mixed $value ): string {
		return ThemeMode::fromOption( $value )->value;
	}

	public static function sanitize_css_mode( mixed $value ): string {
		return CssMode::fromOption( $value )->value;
	}

	public static function sanitize_panel_style( mixed $value ): string {
		return PanelStyle::fromOption( $value )->value;
	}

	/* ---------------------------------------------------------------------
	 *  Plugins list — Settings link
	 * ------------------------------------------------------------------- */

	/**
	 * @param list<string> $links
	 * @return list<string>
	 */
	public function action_links( array $links ): array {
		$url   = admin_url( 'options-general.php?page=mrt-settings' );
		$label = esc_html__( 'Настройки', 'modern-tabby-tabs' );

		array_unshift(
			$links,
			sprintf( '<a href="%s">%s</a>', esc_url( $url ), $label ),
		);

		return $links;
	}

	/* ---------------------------------------------------------------------
	 *  wpautop cleanup
	 * ------------------------------------------------------------------- */

	public function clean_wpautop( string $content ): string {
		if ( ! str_contains( $content, '<div class="responsive-tabs' ) ) {
			return $content;
		}

		$patterns = [
			'#<p>\s*</p>\s*(<div class="responsive-tabs)#i' => '$1',
			'#<p>\s*(<(?:h[1-6]|p|div)[^>]*\bclass="(?:tabtitle|tabcontent)[^"]*")#i' => '$1',
			'#(class="(?:tabtitle|tabcontent)[^"]*"[^>]*>)\s*</p>#i' => '$1',
			'#(<div class="tabcontent[^"]*"[^>]*>)\s*<p>\s*</p>#i' => '$1',
			'#</p>\s*(</div>\s*<(?:h[1-6]|p|div)[^>]*\bclass="tabtitle)#i' => '$1',
			'#(<div class="responsive-tabs[^"]*"[^>]*>)\s*<p>\s*</p>#i' => '$1',
		];

		return (string) preg_replace( array_keys( $patterns ), array_values( $patterns ), $content );
	}
}

/* -------------------------------------------------------------------------
 *  Boot
 * ---------------------------------------------------------------------- */

require_once MTT_PLUGIN_DIR . 'includes/admin.php';
require_once MTT_PLUGIN_DIR . 'includes/class-mtt-github-updater.php';

Admin::boot();
Plugin::instance();

// Updates arrive through the native WordPress mechanism; the updater is the
// shared template generated into includes/ (global class, not namespaced).
( new \MTT_GitHub_Updater() )->register_hooks();