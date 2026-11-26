<?php
/**
 * Plugin Name: KW Manager
 * Description: Editor visual de imágenes y textos para cualquier theme. Usa <kw-img> y <kw-text> en tus templates.
 * Version: 1.0.0
 * Author: KW
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ─── Output buffering: intercepta el HTML final ───────────────────────────────

add_action( 'template_redirect', function () {
    if ( ! is_admin() ) {
        ob_start( 'kw_process_output' );
    }
} );

function kw_process_output( $html ) {
    $values  = get_option( 'kw_values', [] );
    $fields  = get_option( 'kw_fields', [] );
    $changed = false;

    // <kw-img id="x" src="default.jpg" alt="..." class="..." style="..." />
    $html = preg_replace_callback(
        '/<kw-img\b([^>]*?)(?:\s*\/)?>/',
        function ( $m ) use ( $values, &$fields, &$changed ) {
            $a  = kw_parse_attrs( $m[1] );
            $id = $a['id'] ?? '';
            if ( ! $id ) return '';

            if ( ! isset( $fields[ $id ] ) ) {
                $fields[ $id ] = [
                    'type'    => 'image',
                    'default' => $a['src'] ?? '',
                    'label'   => kw_id_to_label( $id ),
                    'section' => kw_get_section( $id ),
                ];
                $changed = true;
            }

            $src = ! empty( $values[ $id ] ) ? esc_url( $values[ $id ] ) : esc_url( $a['src'] ?? '' );
            $out = '<img src="' . $src . '"';
            foreach ( [ 'alt', 'class', 'style', 'width', 'height', 'loading' ] as $attr ) {
                if ( isset( $a[ $attr ] ) && $a[ $attr ] !== '' ) {
                    $out .= ' ' . $attr . '="' . esc_attr( $a[ $attr ] ) . '"';
                }
            }
            return $out . ' />';
        },
        $html
    );

    // <kw-text id="x">texto por defecto</kw-text>
    $html = preg_replace_callback(
        '/<kw-text\b([^>]*?)>([\s\S]*?)<\/kw-text>/i',
        function ( $m ) use ( $values, &$fields, &$changed ) {
            $a       = kw_parse_attrs( $m[1] );
            $id      = $a['id'] ?? '';
            $default = trim( $m[2] );
            if ( ! $id ) return $default;

            if ( ! isset( $fields[ $id ] ) ) {
                $fields[ $id ] = [
                    'type'      => 'text',
                    'default'   => $default,
                    'label'     => kw_id_to_label( $id ),
                    'section'   => kw_get_section( $id ),
                    'multiline' => strlen( $default ) > 80,
                ];
                $changed = true;
            }

            return ! empty( $values[ $id ] ) ? esc_html( $values[ $id ] ) : $default;
        },
        $html
    );

    if ( $changed ) {
        update_option( 'kw_fields', $fields );
    }

    return $html;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function kw_parse_attrs( $str ) {
    $attrs = [];
    preg_match_all( '/(\w[\w-]*)=["\']([^"\']*)["\']/', $str, $matches, PREG_SET_ORDER );
    foreach ( $matches as $m ) {
        $attrs[ $m[1] ] = $m[2];
    }
    return $attrs;
}

function kw_get_section( $id ) {
    return explode( '-', $id )[0] ?? 'general';
}

function kw_id_to_label( $id ) {
    return ucwords( str_replace( [ '-', '_' ], ' ', $id ) );
}

// ─── Admin menu ───────────────────────────────────────────────────────────────

add_action( 'admin_menu', function () {
    add_menu_page(
        'KW Manager',
        'KW Manager',
        'manage_options',
        'kw-manager',
        'kw_admin_page',
        'dashicons-edit-page',
        60
    );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( $hook !== 'toplevel_page_kw-manager' ) return;
    wp_enqueue_media();
    wp_enqueue_script( 'kw-manager', plugin_dir_url( __FILE__ ) . 'kw-manager.js', [ 'jquery' ], '1.0', true );
    wp_enqueue_style( 'kw-manager', plugin_dir_url( __FILE__ ) . 'kw-manager.css', [], '1.0' );
} );

// ─── Save handler ─────────────────────────────────────────────────────────────

add_action( 'admin_post_kw_save', function () {
    check_admin_referer( 'kw_save' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No autorizado' );

    $fields = get_option( 'kw_fields', [] );
    $values = get_option( 'kw_values', [] );

    foreach ( $fields as $id => $field ) {
        if ( ! isset( $_POST['kw'][ $id ] ) ) continue;
        $raw = $_POST['kw'][ $id ];
        $values[ $id ] = $field['type'] === 'image'
            ? esc_url_raw( $raw )
            : sanitize_textarea_field( $raw );
    }

    update_option( 'kw_values', $values );
    wp_redirect( admin_url( 'admin.php?page=kw-manager&saved=1' ) );
    exit;
} );

// Reset field to default
add_action( 'admin_post_kw_reset_field', function () {
    check_admin_referer( 'kw_reset_field' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No autorizado' );

    $id = sanitize_key( $_POST['field_id'] ?? '' );
    if ( $id ) {
        $values = get_option( 'kw_values', [] );
        unset( $values[ $id ] );
        update_option( 'kw_values', $values );
    }
    wp_redirect( admin_url( 'admin.php?page=kw-manager&reset=1' ) );
    exit;
} );

// ─── Admin page ───────────────────────────────────────────────────────────────

function kw_admin_page() {
    $fields  = get_option( 'kw_fields', [] );
    $values  = get_option( 'kw_values', [] );

    $sections = [];
    foreach ( $fields as $id => $field ) {
        $sections[ $field['section'] ][ $id ] = $field;
    }
    ?>
    <div class="wrap kw-wrap">
        <div class="kw-header">
            <h1>KW Manager</h1>
            <p class="kw-subtitle">Editor de imágenes y textos del sitio</p>
        </div>

        <?php if ( isset( $_GET['saved'] ) ) : ?>
            <div class="notice notice-success is-dismissible"><p>✓ Cambios guardados correctamente.</p></div>
        <?php endif; ?>
        <?php if ( isset( $_GET['reset'] ) ) : ?>
            <div class="notice notice-info is-dismissible"><p>Campo restablecido al valor por defecto.</p></div>
        <?php endif; ?>

        <?php if ( empty( $fields ) ) : ?>
            <div class="kw-empty-state">
                <div class="kw-empty-icon">🔍</div>
                <h2>No hay campos registrados aún</h2>
                <p>Agrega etiquetas <code>&lt;kw-img&gt;</code> y <code>&lt;kw-text&gt;</code> en tu theme y visita el sitio. Los campos aparecerán aquí automáticamente.</p>
                <div class="kw-code-example">
                    <p><strong>Imágenes:</strong></p>
                    <code>&lt;kw-img id="hero-banner" src="/ruta/imagen.jpg" alt="Banner" /&gt;</code>
                    <p><strong>Textos:</strong></p>
                    <code>&lt;kw-text id="producto-nombre"&gt;Nombre del producto&lt;/kw-text&gt;</code>
                </div>
            </div>
        <?php else : ?>

        <form method="post" action="<?= esc_url( admin_url( 'admin-post.php' ) ) ?>">
            <?php wp_nonce_field( 'kw_save' ); ?>
            <input type="hidden" name="action" value="kw_save" />

            <?php foreach ( $sections as $section => $section_fields ) : ?>
            <div class="kw-section">
                <h2 class="kw-section-title"><?= esc_html( ucfirst( $section ) ) ?></h2>
                <div class="kw-fields-grid">
                    <?php foreach ( $section_fields as $id => $field ) :
                        $current = $values[ $id ] ?? '';
                        $default = $field['default'] ?? '';
                        $display = $current ?: $default;
                        $is_modified = ! empty( $current );
                    ?>
                    <div class="kw-field kw-field--<?= esc_attr( $field['type'] ) ?>">
                        <div class="kw-field-header">
                            <span class="kw-field-label"><?= esc_html( $field['label'] ) ?></span>
                            <?php if ( $is_modified ) : ?>
                                <span class="kw-badge kw-badge--modified">Modificado</span>
                            <?php else : ?>
                                <span class="kw-badge kw-badge--default">Por defecto</span>
                            <?php endif; ?>
                        </div>

                        <?php if ( $field['type'] === 'image' ) : ?>
                            <div class="kw-image-picker">
                                <div class="kw-image-preview">
                                    <?php if ( $display ) : ?>
                                        <img src="<?= esc_url( $display ) ?>" alt="" />
                                    <?php else : ?>
                                        <div class="kw-no-image">Sin imagen</div>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden"
                                    name="kw[<?= esc_attr( $id ) ?>]"
                                    value="<?= esc_attr( $display ) ?>"
                                    class="kw-image-url" />
                                <div class="kw-image-actions">
                                    <button type="button" class="button kw-select-image">
                                        📁 Cambiar imagen
                                    </button>
                                    <?php if ( $is_modified ) : ?>
                                        <button type="button"
                                            class="button kw-reset-btn"
                                            data-id="<?= esc_attr( $id ) ?>"
                                            data-default="<?= esc_attr( $default ) ?>">
                                            ↩ Restablecer
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                        <?php else : ?>
                            <?php if ( ! empty( $field['multiline'] ) ) : ?>
                                <textarea
                                    name="kw[<?= esc_attr( $id ) ?>]"
                                    class="kw-text-input"
                                    rows="3"><?= esc_textarea( $display ) ?></textarea>
                            <?php else : ?>
                                <input
                                    type="text"
                                    name="kw[<?= esc_attr( $id ) ?>]"
                                    value="<?= esc_attr( $display ) ?>"
                                    class="kw-text-input" />
                            <?php endif; ?>
                            <div class="kw-field-id"><code><?= esc_html( $id ) ?></code></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="kw-save-bar">
                <button type="submit" class="button button-primary button-hero">
                    Guardar todos los cambios
                </button>
                <span class="kw-field-count"><?= count( $fields ) ?> campos registrados</span>
            </div>
        </form>

        <?php endif; ?>
    </div>
    <?php
}
