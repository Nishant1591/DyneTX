<?php
/**
 * Plugin Name:     OneTrust WebForm Embed
 * Plugin URI:      https://www.realchemistry.com/
 * Description:     Embeds the OneTrust consent collection point form on any page via shortcode.
 *                  Configurable from Settings > OT WebForm — no code changes needed.
 * Version:         1.2.0
 * Author:          GTO-RC
 * License:         MIT
 *
 * Usage:
 *   1. Upload this plugin folder to /wp-content/plugins/ot-webform/
 *   2. Activate in WordPress Plugins screen
 *   3. Configure at Settings > OT WebForm
 *   4. Add [ot_webform] shortcode to any page — with optional per-form DataSource:
 *        [ot_webform data_source="Webinar June 2026" data_source_stage="Webinar June 2026 test"]
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'OT_WEBFORM_VERSION', '1.2.0' );
define( 'OT_WEBFORM_OPTION',  'ot_webform_options' );

// ────────────────────────────────────────────────────────────
//  ADMIN MENU
// ────────────────────────────────────────────────────────────
add_action( 'admin_menu', function () {
    add_options_page(
        'OneTrust WebForm Settings',
        'OT WebForm',
        'manage_options',
        'ot-webform',
        'ot_webform_render_settings_page'
    );
} );

// ────────────────────────────────────────────────────────────
//  SETTINGS REGISTRATION
// ────────────────────────────────────────────────────────────
add_action( 'admin_init', function () {
    register_setting( 'ot_webform_group', OT_WEBFORM_OPTION, 'ot_webform_sanitize' );

    // Section 1: OneTrust IDs
    add_settings_section( 'ot_section_ids', '1. OneTrust Collection Point IDs',
        function () { echo '<p class="description">Paste the GUIDs from your OneTrust portal.</p>'; },
        'ot-webform' );

    ot_add_field( 'prod_id', 'Production GUID', 'ot_section_ids',
        'The live site <code>data-id</code> from OneTrust.', 'text', '5c7d29d6-xxxx-xxxx-xxxx-xxxxxxxxxxxx' );
    ot_add_field( 'staging_id', 'Staging GUID', 'ot_section_ids',
        'Test/staging <code>data-id</code> — usually ends in <code>-test</code>.', 'text', '5c7d29d6-xxxx-xxxx-xxxx-xxxxxxxxxxxx-test' );
    ot_add_field( 'staging_keywords', 'Staging Hostname Keywords (comma-separated)', 'ot_section_ids',
        'If the hostname contains any of these, the staging GUID is used automatically.', 'text', 'dev,stage,staging,test,demo,uat,localhost,dynetxstaging' );

    // Section 2: Hidden Form Fields (site-wide defaults)
    add_settings_section( 'ot_section_fields', '2. Hidden Form Field Values (Site-wide Defaults)',
        function () {
            echo '<p class="description">'
               . 'Global defaults for <code>sourcePlatform</code> and <code>dataSource</code>. '
               . 'These are injected into every OT form and hidden from visitors.<br/>'
               . '<strong>Per-form override:</strong> Use the shortcode attribute '
               . '<code>data_source="Your Event Name"</code> on individual pages to override DataSource '
               . 'without changing these global defaults.'
               . '</p>';
        },
        'ot-webform' );

    ot_add_field( 'source_platform', 'Source Platform (URL)', 'ot_section_fields',
        'Injected into <code>sourcePlatform</code>. Applies to all forms site-wide.', 'url', get_site_url() );
    ot_add_field( 'data_source_prod', 'Data Source — Production (default)', 'ot_section_fields',
        'Default <code>dataSource</code> value for production. Override per-form with <code>[ot_webform data_source="..."]</code>.', 'text', 'Conference March 2026' );
    ot_add_field( 'data_source_stage', 'Data Source — Staging (default)', 'ot_section_fields',
        'Default <code>dataSource</code> value for staging. Override per-form with <code>[ot_webform data_source_stage="..."]</code>.', 'text', 'Conference March 2026 test' );

    // Section 3: Advanced
    add_settings_section( 'ot_section_advanced', '3. Advanced Settings', null, 'ot-webform' );

    ot_add_field( 'gtm_event', 'GTM Event Name', 'ot_section_advanced',
        'Event pushed to <code>window.dataLayer</code> on form submission.', 'text', 'onetrust_form_submitted' );
    ot_add_field( 'thank_you_id', 'Thank-You Element ID', 'ot_section_advanced',
        'HTML element ID revealed after submission. Leave blank to disable.', 'text', 'thank-you-message' );
    ot_add_field( 'debug', 'Debug Mode', 'ot_section_advanced',
        'Enables <code>[OT-Embed]</code> console logging. <strong>Disable on production.</strong>', 'checkbox' );
} );

function ot_add_field( $key, $label, $section, $description = '', $type = 'text', $placeholder = '' ) {
    add_settings_field( 'ot_field_' . $key, $label,
        function () use ( $key, $description, $type, $placeholder ) {
            $options = get_option( OT_WEBFORM_OPTION, [] );
            $val     = $options[ $key ] ?? '';
            if ( $type === 'checkbox' ) {
                printf( '<label><input type="checkbox" name="%s[%s]" value="1" %s /> Enable</label>',
                    OT_WEBFORM_OPTION, esc_attr( $key ), checked( $val, '1', false ) );
            } else {
                printf( '<input type="%s" name="%s[%s]" value="%s" placeholder="%s" class="regular-text" style="width:420px" />',
                    esc_attr( $type ), OT_WEBFORM_OPTION, esc_attr( $key ), esc_attr( $val ), esc_attr( $placeholder ) );
            }
            if ( $description ) {
                echo '<p class="description">' . wp_kses( $description, [ 'code' => [], 'strong' => [], 'br' => [] ] ) . '</p>';
            }
        },
        'ot-webform', $section );
}

function ot_webform_sanitize( $input ) {
    $clean = [];
    foreach ( [ 'prod_id', 'staging_id', 'staging_keywords', 'data_source_prod', 'data_source_stage', 'gtm_event', 'thank_you_id' ] as $f ) {
        $clean[ $f ] = sanitize_text_field( $input[ $f ] ?? '' );
    }
    $clean['source_platform'] = esc_url_raw( $input['source_platform'] ?? '' );
    $clean['debug']           = isset( $input['debug'] ) ? '1' : '0';
    return $clean;
}

// ────────────────────────────────────────────────────────────
//  SETTINGS PAGE
// ────────────────────────────────────────────────────────────
function ot_webform_render_settings_page() { ?>
<div class="wrap">
    <h1 style="display:flex;align-items:center;gap:10px;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="4" fill="#006EFF"/>
        <path d="M12 5C8.13 5 5 8.13 5 12s3.13 7 7 7 7-3.13 7-7-3.13-7-7-7zm0 12.6c-3.09 0-5.6-2.51-5.6-5.6S8.91 6.4 12 6.4s5.6 2.51 5.6 5.6-2.51 5.6-5.6 5.6zm.7-9.1h-1.4v4.2l3.68 2.21.7-1.15-2.98-1.77V8.5z" fill="white"/></svg>
        OneTrust WebForm Settings
    </h1>
    <?php if ( isset( $_GET['settings-updated'] ) ) : ?>
    <div class="notice notice-success is-dismissible"><p><strong>Settings saved.</strong></p></div>
    <?php endif; ?>
    <form method="post" action="options.php">
        <?php settings_fields( 'ot_webform_group' ); do_settings_sections( 'ot-webform' ); submit_button( 'Save Settings' ); ?>
    </form>
    <hr style="margin:30px 0"/>
    <h2>Shortcode Reference</h2>
    <table class="widefat" style="max-width:750px">
        <thead><tr><th style="width:40%">Shortcode / Attribute</th><th>Description</th></tr></thead>
        <tbody>
            <tr><td><code>[ot_webform]</code></td><td>Basic embed — uses global WP settings for DataSource</td></tr>
            <tr style="background:#fff8e1">
                <td><code>data_source="Webinar June 2026"</code></td>
                <td><strong>Per-form override</strong> — sets a unique DataSource for this page only (production)</td>
            </tr>
            <tr style="background:#fff8e1">
                <td><code>data_source_stage="Webinar June 2026 test"</code></td>
                <td><strong>Per-form override</strong> — sets a unique DataSource for this page only (staging)</td>
            </tr>
            <tr><td><code>thank_you_text="Thanks!"</code></td><td>Custom message shown after submission</td></tr>
            <tr><td><code>thank_you_id="my-thanks"</code></td><td>Custom ID for the thank-you element</td></tr>
        </tbody>
    </table>
    <br/>
    <h2>Per-form DataSource Examples</h2>
    <table class="widefat" style="max-width:750px">
        <thead><tr><th>Page / Form</th><th>Shortcode</th></tr></thead>
        <tbody>
            <tr><td>Global default (set once in Settings)</td><td><code>[ot_webform]</code></td></tr>
            <tr><td>Webinar June 2026</td><td><code>[ot_webform data_source="Webinar June 2026" data_source_stage="Webinar June 2026 test"]</code></td></tr>
            <tr><td>Product Launch Form</td><td><code>[ot_webform data_source="Product Launch Q3 2026" data_source_stage="Product Launch Q3 2026 test"]</code></td></tr>
            <tr><td>Contact Page</td><td><code>[ot_webform data_source="Contact Form 2026"]</code></td></tr>
        </tbody>
    </table>
    <br/>
    <h2>Hidden Fields (auto-managed)</h2>
    <table class="widefat" style="max-width:750px">
        <thead><tr><th>OT Field</th><th>Source</th></tr></thead>
        <tbody>
            <tr><td><code>sourcePlatform</code></td><td>Source Platform setting (global)</td></tr>
            <tr style="background:#fff8e1"><td><code>dataSource</code></td><td>WP Settings default <em>or</em> <code>data_source</code> shortcode attribute (per-form override)</td></tr>
            <tr><td><code>utmSource</code></td><td><code>?utm_source=</code> URL parameter</td></tr>
            <tr><td><code>utmMedium</code></td><td><code>?utm_medium=</code> URL parameter</td></tr>
            <tr><td><code>utmCampaign</code></td><td><code>?utm_campaign=</code> URL parameter</td></tr>
            <tr><td><code>utmTerm</code></td><td><code>?utm_term=</code> URL parameter</td></tr>
            <tr><td><code>utmContent</code></td><td><code>?utm_content=</code> URL parameter</td></tr>
        </tbody>
    </table>
</div>
<?php }

// ────────────────────────────────────────────────────────────
//  ENQUEUE SCRIPT — loads on all pages once prod_id is set
//  The JS MutationObserver handles finding the OT form div.
// ────────────────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', function () {
    $options = get_option( OT_WEBFORM_OPTION, [] );
    if ( empty( $options['prod_id'] ) ) return;

    wp_enqueue_script( 'ot-webform', plugin_dir_url( __FILE__ ) . 'ot-webform.js', [], OT_WEBFORM_VERSION, false );

    // Global config — DataSource here is the site-wide default only.
    // Per-form overrides are injected inline by the shortcode below.
    wp_localize_script( 'ot-webform', 'OT_WP_CONFIG', [
        'prod_id'           => $options['prod_id']           ?? '',
        'staging_id'        => $options['staging_id']        ?? '',
        'staging_keywords'  => $options['staging_keywords']  ?? 'dev,stage,staging,test,demo,uat,localhost',
        'source_platform'   => $options['source_platform']   ?? get_site_url(),
        'data_source_prod'  => $options['data_source_prod']  ?? '',
        'data_source_stage' => $options['data_source_stage'] ?? '',
        'gtm_event'         => $options['gtm_event']         ?? 'onetrust_form_submitted',
        'thank_you_id'      => $options['thank_you_id']      ?? 'thank-you-message',
        'debug'             => ( ( $options['debug'] ?? '0' ) === '1' ) ? 'true' : 'false',
    ] );
} );

// ────────────────────────────────────────────────────────────
//  HELPER: detect staging from server hostname
// ────────────────────────────────────────────────────────────
function ot_webform_is_staging() {
    $options  = get_option( OT_WEBFORM_OPTION, [] );
    $keywords = array_map( 'trim', explode( ',', $options['staging_keywords'] ?? 'dev,stage,staging,test,demo,uat,localhost' ) );
    $hostname = strtolower( $_SERVER['HTTP_HOST'] ?? '' );
    foreach ( $keywords as $kw ) {
        if ( $kw && str_contains( $hostname, $kw ) ) return true;
    }
    return false;
}

// ────────────────────────────────────────────────────────────
//  SHORTCODE: [ot_webform]
//
//  Attributes:
//    data_source="..."        Per-form DataSource override (production)
//    data_source_stage="..."  Per-form DataSource override (staging)
//    thank_you_text="..."     Custom thank-you message
//    thank_you_id="..."       Custom thank-you element ID
//
//  Priority chain for DataSource:
//    1. Shortcode attribute (data_source / data_source_stage)  ← highest
//    2. WP Settings global default                             ← fallback
// ────────────────────────────────────────────────────────────
add_shortcode( 'ot_webform', function ( $atts ) {
    $options    = get_option( OT_WEBFORM_OPTION, [] );
    $is_staging = ot_webform_is_staging();

    $atts = shortcode_atts( [
        'data_source'       => '',   // per-form production DataSource
        'data_source_stage' => '',   // per-form staging DataSource
        'thank_you_text'    => 'Thank you! Your submission has been received.',
        'thank_you_id'      => $options['thank_you_id'] ?? 'thank-you-message',
    ], $atts, 'ot_webform' );

    // Resolve which DataSource value to inject for this specific form:
    // shortcode attribute wins over global WP setting
    $ds_prod  = ! empty( $atts['data_source'] )
                    ? sanitize_text_field( $atts['data_source'] )
                    : ( $options['data_source_prod'] ?? '' );

    $ds_stage = ! empty( $atts['data_source_stage'] )
                    ? sanitize_text_field( $atts['data_source_stage'] )
                    : ( $options['data_source_stage'] ?? '' );

    // The value actually used on this env right now
    $resolved = $is_staging ? $ds_stage : $ds_prod;

    ob_start(); ?>
    <div id="<?php echo esc_attr( $atts['thank_you_id'] ); ?>" style="display:none;margin-top:1rem;">
        <p><?php echo esc_html( $atts['thank_you_text'] ); ?></p>
    </div>
    <?php if ( ! empty( $ds_prod ) || ! empty( $ds_stage ) ) : ?>
    <script>
    /**
     * OT_PAGE_CONFIG — per-page DataSource override.
     *
     * WHY a separate object instead of patching OT_WP_CONFIG:
     *   ot-webform.js loads in <head> and starts watching for the OT form immediately.
     *   The OT form can inject and trigger populateForm() before any inline <script>
     *   in the <body> has run — so patching OT_WP_CONFIG in the body is a race condition.
     *
     * HOW this is safe:
     *   WordPress renders the page body top-to-bottom. This <script> block is output
     *   by the shortcode, which sits ABOVE the OT container div in the HTML stream.
     *   OT_PAGE_CONFIG is therefore defined before the browser parses the container div
     *   and before OT's script can inject the form — so resolveDataSource() always
     *   finds it in time.
     *
     *   Priority in ot-webform.js: OT_PAGE_CONFIG > OT_WP_CONFIG > drupalSettings > defaults
     */
    window.OT_PAGE_CONFIG = {
        data_source_prod:  <?php echo json_encode( $ds_prod ); ?>,
        data_source_stage: <?php echo json_encode( $ds_stage ); ?>
    };
    </script>
    <?php endif; ?>
    <?php
    return ob_get_clean();
} );
