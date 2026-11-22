<?php
/**
 * Admin settings screen for Modern Tabby Tabs.
 *
 * @package ModernTabby
 */

declare( strict_types = 1 );

namespace ModernTabby;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers Settings → Tabby Tabs.
 */
final class Admin {

	private const SLUG  = 'mrt-settings';
	private const GROUP = 'mrt-settings';

	public static function boot(): void {
		add_action( 'admin_menu',            [ __CLASS__, 'menu' ] );
		add_action( 'admin_init',            [ __CLASS__, 'settings' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
	}

	public static function menu(): void {
		add_options_page(
			page_title: __( 'Modern Tabby Tabs', 'modern-tabby-tabs' ),
			menu_title: __( 'Tabby Tabs',        'modern-tabby-tabs' ),
			capability: 'manage_options',
			menu_slug:  self::SLUG,
			callback:   [ __CLASS__, 'render' ],
		);
	}

	public static function assets( string $hook ): void {
		if ( 'settings_page_' . self::SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			handle: 'mrt-admin',
			src:    Plugin::url( 'assets/css/admin.css' ),
			deps:   [],
			ver:    MTT_VERSION,
		);
	}

	public static function settings(): void {
		register_setting(
			option_group: self::GROUP,
			option_name:  Plugin::OPT_DEFAULT_STYLES,
			args: [
				'type'              => 'boolean',
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
			],
		);

		register_setting(
			option_group: self::GROUP,
			option_name:  Plugin::OPT_TITLE_ELEMENT,
			args: [
				'type'              => 'string',
				'default'           => TitleElement::H2->value,
				'sanitize_callback' => [ \ModernTabby\Plugin::class, 'sanitize_title_element' ],
			],
		);

		register_setting(
			option_group: self::GROUP,
			option_name:  Plugin::OPT_FA_STYLE,
			args: [
				'type'              => 'string',
				'default'           => FaStyle::Regular->value,
				'sanitize_callback' => static fn ( mixed $value ): string => FaStyle::fromOption( $value )->value,
			],
		);

		register_setting(
			option_group: self::GROUP,
			option_name:  Plugin::OPT_THEME_MODE,
			args: [
				'type'              => 'string',
				'default'           => ThemeMode::Auto->value,
				'sanitize_callback' => [ \ModernTabby\Plugin::class, 'sanitize_theme_mode' ],
			],
		);

		register_setting(
			option_group: self::GROUP,
			option_name:  Plugin::OPT_CSS_MODE,
			args: [
				'type'              => 'string',
				'default'           => CssMode::File->value,
				'sanitize_callback' => [ \ModernTabby\Plugin::class, 'sanitize_css_mode' ],
			],
		);

		register_setting(
			option_group: self::GROUP,
			option_name:  Plugin::OPT_PANEL_STYLE,
			args: [
				'type'              => 'string',
				'default'           => PanelStyle::Box->value,
				'sanitize_callback' => [ \ModernTabby\Plugin::class, 'sanitize_panel_style' ],
			],
		);
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$default_styles = (int) get_option( Plugin::OPT_DEFAULT_STYLES, 1 );
		$title_element  = TitleElement::fromOption( get_option( Plugin::OPT_TITLE_ELEMENT, 'h2' ) )->value;
		$fa_style       = FaStyle::fromOption( get_option( Plugin::OPT_FA_STYLE, 'regular' ) )->value;
		$theme_mode     = ThemeMode::fromOption( get_option( Plugin::OPT_THEME_MODE, ThemeMode::Auto->value ) )->value;
		$css_mode       = CssMode::fromOption( get_option( Plugin::OPT_CSS_MODE, CssMode::File->value ) )->value;
		$panel_style    = PanelStyle::fromOption( get_option( Plugin::OPT_PANEL_STYLE, PanelStyle::Box->value ) )->value;
		?>
		<div class="wrap tabby-admin">
			<h1><?php esc_html_e( 'Modern Tabby Tabs', 'modern-tabby-tabs' ); ?></h1>

			<div class="tabby-admin__grid">

				<div class="card">
					<h2><?php esc_html_e( 'Внешний вид', 'modern-tabby-tabs' ); ?></h2>

					<form method="post" action="options.php">
						<?php settings_fields( self::GROUP ); ?>

						<table class="form-table" role="presentation">
							<tr>
								<th scope="row">
									<label for="tabby-default-styles">
										<?php esc_html_e( 'Стандартные стили', 'modern-tabby-tabs' ); ?>
									</label>
								</th>
								<td>
									<label>
										<input type="checkbox"
											id="tabby-default-styles"
											name="<?php echo esc_attr( Plugin::OPT_DEFAULT_STYLES ); ?>"
											value="1" <?php checked( 1, $default_styles ); ?> />
										<?php esc_html_e( 'Подключать CSS плагина на сайте.', 'modern-tabby-tabs' ); ?>
									</label>
									<p class="description">
										<?php esc_html_e( 'Снимите флажок, если задаёте свои стили табов в дочерней теме.', 'modern-tabby-tabs' ); ?>
									</p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="tabby-css-mode">
										<?php esc_html_e( 'Доставка CSS', 'modern-tabby-tabs' ); ?>
									</label>
								</th>
								<td>
									<select id="tabby-css-mode"
										name="<?php echo esc_attr( Plugin::OPT_CSS_MODE ); ?>">
										<?php foreach ( CssMode::options() as $value => $label ) : ?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $css_mode, $value ); ?>>
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="tabby-theme-mode">
										<?php esc_html_e( 'Цветовая схема', 'modern-tabby-tabs' ); ?>
									</label>
								</th>
								<td>
									<select id="tabby-theme-mode"
										name="<?php echo esc_attr( Plugin::OPT_THEME_MODE ); ?>">
										<?php foreach ( ThemeMode::options() as $value => $label ) : ?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $theme_mode, $value ); ?>>
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="tabby-panel-style">
										<?php esc_html_e( 'Стиль панели', 'modern-tabby-tabs' ); ?>
									</label>
								</th>
								<td>
									<select id="tabby-panel-style"
										name="<?php echo esc_attr( Plugin::OPT_PANEL_STYLE ); ?>">
										<?php foreach ( PanelStyle::options() as $value => $label ) : ?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $panel_style, $value ); ?>>
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<p class="description">
										<?php esc_html_e( 'Оформление блока вокруг содержимого таба. По умолчанию — рамка. «Плоский» сливает содержимое со страницей и подходит для длинных блоков: индикаторов компрометации, правил YARA, сигнатур IDS.', 'modern-tabby-tabs' ); ?>
									</p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="tabby-title-element">
										<?php esc_html_e( 'Элемент заголовка таба', 'modern-tabby-tabs' ); ?>
									</label>
								</th>
								<td>
									<select id="tabby-title-element"
										name="<?php echo esc_attr( Plugin::OPT_TITLE_ELEMENT ); ?>">
										<?php foreach ( TitleElement::values() as $el ) : ?>
											<option value="<?php echo esc_attr( $el ); ?>" <?php selected( $title_element, $el ); ?>>
												<?php echo esc_html( $el ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<p class="description">
										<?php esc_html_e( 'HTML-элемент заголовка таба. По умолчанию: h2.', 'modern-tabby-tabs' ); ?>
									</p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="tabby-fa-style">
										<?php esc_html_e( 'Стиль иконок Font Awesome', 'modern-tabby-tabs' ); ?>
									</label>
								</th>
								<td>
									<select id="tabby-fa-style"
										name="<?php echo esc_attr( Plugin::OPT_FA_STYLE ); ?>">
										<?php foreach ( FaStyle::options() as $value => $label ) : ?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $fa_style, $value ); ?>>
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<p class="description">
										<?php esc_html_e( 'Font Awesome подключается отдельно: темой, плагином или дополнением для настроек.', 'modern-tabby-tabs' ); ?>
									</p>
								</td>
							</tr>
						</table>

						<?php submit_button(); ?>
					</form>
				</div>

				<div class="card">
					<h2><?php esc_html_e( 'Использование', 'modern-tabby-tabs' ); ?></h2>

					<p>
						<?php
						printf(
							/* translators: 1: [tabby], 2: [tabbyending] */
							esc_html__( 'Открывает таб %1$s, закрывает группу %2$s.', 'modern-tabby-tabs' ),
							'<code>[tabby]</code>',
							'<code>[tabbyending]</code>'
						);
						?>
					</p>

					<p>
						<?php esc_html_e( 'Оставляйте пустую строку до и после каждого шорткода, чтобы wpautop не вставлял лишние теги абзацев.', 'modern-tabby-tabs' ); ?>
					</p>

<pre>
[tabby title="First Tab"]

<?php esc_html_e( 'Содержимое первого таба.', 'modern-tabby-tabs' ); ?>

[tabby title="Second Tab"]

<?php esc_html_e( 'Содержимое второго таба.', 'modern-tabby-tabs' ); ?>

[tabbyending]</pre>

					<p class="description">
						<?php esc_html_e( 'Атрибуты: title, open="yes", icon, ico, class.', 'modern-tabby-tabs' ); ?>
					</p>
				</div>
			</div>
		</div>
		<?php
	}
}