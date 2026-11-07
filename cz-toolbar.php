<?php
/**
 * CZ Toolbar — barra flottante in fondo alla pagina condivisa dai plugin Cigno Zen.
 *
 * Caricata da mu-plugins/cz-core.php. La toolbar è di proprietà di cz-core:
 * i plugin vi aggiungono i propri pulsanti in PHP, sull'hook `cz_toolbar_buttons`,
 * e ne gestiscono il comportamento nel proprio JavaScript cercandoli per
 * `[data-cz-toolbar="<id>"]`.
 *
 *     add_action( 'cz_toolbar_buttons', function () {
 *         cz_toolbar_add_button( 'bookmark', [
 *             'label' => 'Salva',
 *             'icon'  => '<svg …>…</svg>',
 *             'zone'  => 'right',   // left | center | right
 *             'order' => 10,        // ordine crescente nella zona
 *         ] );
 *     } );
 *
 * Pulsanti di base: "home" (sinistra, 10), "top" (centro, 20), "print" (centro, 30).
 * L'indice di cz-outline si inserisce al centro con order 10, a sinistra di "Su".
 *
 * Stampa: il click emette su `document` l'evento annullabile `cz-toolbar:print`;
 * se nessuno lo annulla parte window.print() (il tema lo annulla per i post paginati).
 *
 * Filtri:
 * - `cz_toolbar_enabled` (bool, default is_singular( 'post' )): dove mostrare la toolbar.
 *
 * @package CZ_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CZ_TOOLBAR_VERSION', '1.0.0' );

final class CZ_Toolbar {

	/** @var array<string,array> Pulsanti registrati, per id. */
	private static $buttons = [];

	/** @var bool|null */
	private static $enabled = null;

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
		add_action( 'wp_footer', [ __CLASS__, 'render' ], 5 );
		add_action( 'cz_toolbar_buttons', [ __CLASS__, 'register_core_buttons' ], 5 );
	}

	public static function is_enabled(): bool {
		if ( null === self::$enabled ) {
			self::$enabled = (bool) apply_filters( 'cz_toolbar_enabled', is_singular( 'post' ) );
		}
		return self::$enabled;
	}

	/**
	 * @param string $id   Identificativo univoco (diventa data-cz-toolbar="<id>").
	 * @param array  $args {
	 *     @type string $label Testo per aria-label e title.
	 *     @type string $icon  SVG dell'icona (markup fidato, non viene filtrato).
	 *     @type string $zone  left | center | right. Default right.
	 *     @type int    $order Ordine crescente nella zona. Default 50.
	 *     @type string $class Classi CSS aggiuntive.
	 *     @type array  $attrs Attributi HTML aggiuntivi (nome => valore; true = attributo booleano).
	 * }
	 */
	public static function add_button( string $id, array $args ): void {
		$id = sanitize_key( $id );
		if ( '' === $id ) {
			return;
		}

		$args = wp_parse_args( $args, [
			'label' => '',
			'icon'  => '',
			'zone'  => 'right',
			'order' => 50,
			'class' => '',
			'attrs' => [],
		] );

		if ( ! in_array( $args['zone'], [ 'left', 'center', 'right' ], true ) ) {
			$args['zone'] = 'right';
		}
		$args['order'] = (int) $args['order'];

		self::$buttons[ $id ] = $args;
	}

	public static function remove_button( string $id ): void {
		unset( self::$buttons[ sanitize_key( $id ) ] );
	}

	public static function register_core_buttons(): void {
		self::add_button( 'home', [
			'label' => __( 'Home', 'cz-core' ),
			'zone'  => 'left',
			'order' => 10,
			'icon'  => '<svg width="24" height="24" viewBox="0 0 24 24" focusable="false"><path d="M4 10.5L12 4l8 6.5V20a2 2 0 0 1-2 2h-4v-6H10v6H6a2 2 0 0 1-2-2v-9.5Z" fill="currentColor"/></svg>',
			'attrs' => [ 'data-href' => home_url( '/' ) ],
		] );

		self::add_button( 'top', [
			'label' => __( 'Su', 'cz-core' ),
			'zone'  => 'center',
			'order' => 20,
			'icon'  => '<svg width="24" height="24" viewBox="0 0 24 24" focusable="false"><path d="M12 5l7 7-1.41 1.41L13 8.83V20h-2V8.83l-4.59 4.58L5 12l7-7Z" fill="currentColor"/></svg>',
		] );

		self::add_button( 'print', [
			'label' => __( 'Stampa', 'cz-core' ),
			'zone'  => 'center',
			'order' => 30,
			'icon'  => '<svg width="24" height="24" viewBox="0 0 24 24" focusable="false"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z" fill="currentColor"/></svg>',
		] );
	}

	public static function enqueue_assets(): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		$dir = __DIR__;
		$url = plugins_url( '', __FILE__ );

		list( $css_url, $css_ver ) = cz_get_asset( $dir, $url, 'assets/cz-toolbar.css', CZ_TOOLBAR_VERSION );
		list( $js_url, $js_ver )   = cz_get_asset( $dir, $url, 'assets/cz-toolbar.js', CZ_TOOLBAR_VERSION );

		wp_enqueue_style( 'cz-toolbar', $css_url, [], $css_ver );
		wp_enqueue_script( 'cz-toolbar', $js_url, [], $js_ver, true );
	}

	public static function render(): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		do_action( 'cz_toolbar_buttons' );

		if ( empty( self::$buttons ) ) {
			return;
		}

		$zones = [ 'left' => [], 'center' => [], 'right' => [] ];
		foreach ( self::$buttons as $id => $button ) {
			$zones[ $button['zone'] ][ $id ] = $button;
		}

		echo '<div class="cz-toolbar" role="toolbar" aria-label="' . esc_attr__( 'Strumenti articolo', 'cz-core' ) . '">';
		foreach ( $zones as $zone => $buttons ) {
			uasort( $buttons, static function ( $a, $b ) {
				return $a['order'] <=> $b['order'];
			} );

			echo '<div class="cz-toolbar__zone cz-toolbar__zone--' . esc_attr( $zone ) . '">';
			foreach ( $buttons as $id => $button ) {
				echo self::render_button( $id, $button ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
			}
			echo '</div>';
		}
		echo '</div>';
	}

	private static function render_button( string $id, array $button ): string {
		$attrs = [
			'type'            => 'button',
			'class'           => trim( 'cz-toolbar__btn ' . $button['class'] ),
			'data-cz-toolbar' => $id,
		];
		if ( '' !== $button['label'] ) {
			$attrs['aria-label'] = $button['label'];
			$attrs['title']      = $button['label'];
		}
		$attrs = array_merge( $attrs, (array) $button['attrs'] );

		$html = '<button';
		foreach ( $attrs as $name => $value ) {
			if ( false === $value || null === $value ) {
				continue;
			}
			$name = preg_replace( '/[^a-zA-Z0-9_:-]/', '', (string) $name );
			$html .= true === $value ? ' ' . $name : ' ' . $name . '="' . esc_attr( (string) $value ) . '"';
		}
		$html .= '><span class="cz-toolbar__icon" aria-hidden="true">' . $button['icon'] . '</span></button>';

		return $html;
	}
}

/**
 * Aggiunge (o sostituisce) un pulsante nella toolbar. Da chiamare sull'hook
 * `cz_toolbar_buttons`. Vedi CZ_Toolbar::add_button() per gli argomenti.
 */
function cz_toolbar_add_button( string $id, array $args ): void {
	CZ_Toolbar::add_button( $id, $args );
}

/**
 * Rimuove un pulsante (anche uno di base: 'home', 'top', 'print').
 * Da chiamare sull'hook `cz_toolbar_buttons` con priorità > 5.
 */
function cz_toolbar_remove_button( string $id ): void {
	CZ_Toolbar::remove_button( $id );
}

CZ_Toolbar::init();
