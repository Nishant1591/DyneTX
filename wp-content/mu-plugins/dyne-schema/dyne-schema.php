<?php
/**
 * Plugin Name: Dyne Therapeutics — Structured Data
 * Description: Injects Dyne's Organization graph and page-level entity nodes into the Yoast SEO JSON-LD graph. All content lives in json/global.json and json/pages.json — edit those, not this file.
 * Version:     1.0.0
 * Author:      Dyne Therapeutics SEO
 *
 * INSTALL: copy this file and the json/ folder into wp-content/mu-plugins/dyne-schema/
 *          then add wp-content/mu-plugins/dyne-schema-loader.php containing:
 *              <?php require __DIR__ . '/dyne-schema/dyne-schema.php';
 *          (mu-plugins are only auto-loaded one level deep, hence the loader.)
 *
 * Requires Yoast SEO. Without it the wpseo_schema_graph filter never fires and
 * nothing is output — see dyne_schema_standalone_output() at the bottom for the
 * no-Yoast fallback.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DYNE_SCHEMA_DIR', __DIR__);
define('DYNE_SCHEMA_ORG_ID', 'https://www.dyne-tx.com/#organization');
define('DYNE_SCHEMA_SITE', 'https://www.dyne-tx.com');

/**
 * Read and cache one of the JSON files.
 *
 * @param string $name 'global' or 'pages'
 * @return array
 */
function dyne_schema_data($name)
{
    static $cache = [];

    if (isset($cache[$name])) {
        return $cache[$name];
    }

    $file = DYNE_SCHEMA_DIR . '/json/' . $name . '.json';
    $data = [];

    if (is_readable($file)) {
        $decoded = json_decode(file_get_contents($file), true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }

    $cache[$name] = $data;

    return $data;
}

/**
 * Path of the URL currently being rendered, normalised to a leading and
 * trailing slash, e.g. "/pipeline/". Returns '' when it cannot be determined.
 *
 * @return string
 */
function dyne_schema_current_path()
{
    if (is_front_page() || is_home()) {
        return '/';
    }

    $url = '';

    if (is_singular()) {
        $url = get_permalink();
    } elseif (function_exists('wpseo_replace_vars')) {
        $url = home_url(add_query_arg([], $GLOBALS['wp']->request));
    }

    if (!$url) {
        return '';
    }

    return dyne_schema_norm_path($url);
}

/**
 * Normalise anything URL-ish to a comparable path: "/pipeline/".
 *
 * Accepts a full URL or a bare path. Returns '' when there is no path to read.
 * Used to match Yoast's WebPage node against our page keys without depending
 * on the domain, so the same files work on staging and production.
 *
 * @param string $value
 * @return string
 */
function dyne_schema_norm_path($value)
{
    $path = parse_url((string) $value, PHP_URL_PATH);

    if (!is_string($path)) {
        return '';
    }

    $path = trim($path, '/');

    return $path === '' ? '/' : '/' . $path . '/';
}

/**
 * Merge our page-level properties into the Yoast WebPage node.
 *
 * Extra @type values are appended so the node becomes e.g.
 * ["WebPage","CollectionPage"] rather than losing WebPage. Everything else is
 * only set when Yoast has not already set it, so Yoast keeps ownership of
 * title, breadcrumb, image and dates.
 *
 * @param array $node
 * @param array $props
 * @return array
 */
function dyne_schema_merge_page_node($node, $props)
{
    foreach ($props as $key => $value) {
        if ($key === '@type') {
            $types = (array) ($node['@type'] ?? 'WebPage');
            foreach ((array) $value as $extra) {
                if (!in_array($extra, $types, true)) {
                    $types[] = $extra;
                }
            }
            $node['@type'] = count($types) === 1 ? $types[0] : $types;
            continue;
        }

        // description: only fill it in if Yoast left it empty.
        if ($key === 'description' && !empty($node['description'])) {
            continue;
        }

        $node[$key] = $value;
    }

    return $node;
}

/**
 * The one entry point: rewrite the Yoast graph.
 */
add_filter('wpseo_schema_graph', function ($graph, $context = null) {
    if (!is_array($graph)) {
        return $graph;
    }

    // Proof that Yoast is present and this filter ran. The standalone fallback
    // at the bottom checks it. More reliable than sniffing for a class name,
    // and it makes a double graph impossible.
    $GLOBALS['dyne_schema_yoast_ran'] = true;

    $path      = dyne_schema_current_path();
    $pages     = dyne_schema_data('pages');
    $page_data = ($path !== '' && isset($pages[$path])) ? $pages[$path] : [];

    // 1. Patch Yoast's own nodes: publisher everywhere, our properties on the
    //    WebPage node for this URL.
    foreach ($graph as $i => $node) {
        if (!is_array($node)) {
            continue;
        }

        $types = (array) ($node['@type'] ?? []);

        if (in_array('WebSite', $types, true)) {
            $graph[$i]['publisher'] = ['@id' => DYNE_SCHEMA_ORG_ID];
            continue;
        }

        $is_webpage = (bool) array_intersect(
            $types,
            ['WebPage', 'CollectionPage', 'AboutPage', 'ContactPage', 'ItemPage', 'MedicalWebPage', 'SearchResultsPage']
        );

        if (!$is_webpage) {
            continue;
        }

        $graph[$i]['publisher'] = ['@id' => DYNE_SCHEMA_ORG_ID];

        // Match on the path, not the full URL: Yoast's @id carries whatever
        // domain the site currently runs on, which differs on staging.
        $node_path = dyne_schema_norm_path(isset($node['@id']) ? $node['@id'] : '');

        if (!empty($page_data['page']) && $node_path !== '' && $node_path === $path) {
            $graph[$i] = dyne_schema_merge_page_node($graph[$i], $page_data['page']);
        }
    }

    // 2. Append the page's own entity nodes.
    if (!empty($page_data['nodes'])) {
        foreach ($page_data['nodes'] as $node) {
            $graph[] = $node;
        }
    }

    // 3. Append the common company graph on every page. A page may add
    //    properties to #organization through its "org" key — /about-us/ uses
    //    this to attach employee references next to the Person definitions.
    $global = dyne_schema_data('global');
    if (!empty($global['nodes'])) {
        foreach ($global['nodes'] as $node) {
            if (
                !empty($page_data['org'])
                && isset($node['@id'])
                && $node['@id'] === DYNE_SCHEMA_ORG_ID
            ) {
                $node = array_merge($node, $page_data['org']);
            }
            $graph[] = $node;
        }
    }

    return $graph;
}, 20, 2);

/**
 * Fallback for a site without Yoast SEO.
 *
 * Active, but self-disabling: it runs at wp_head priority 20, by which point
 * Yoast has already built its graph and set the flag below. So on a site with
 * Yoast this outputs nothing, and if Yoast is ever deactivated the markup
 * keeps working instead of silently disappearing.
 *
 * It does NOT emit a WebPage or WebSite node — those are Yoast's, and
 * inventing them here would create two competing versions of the same node.
 */
function dyne_schema_standalone_output()
{
    if (!empty($GLOBALS['dyne_schema_yoast_ran'])) {
        return; // the Yoast filter already emitted these nodes
    }

    if (class_exists('WPSEO_Options') || defined('WPSEO_VERSION')) {
        return; // Yoast present; its filter runs before wp_head priority 20
    }

    $path      = dyne_schema_current_path();
    $pages     = dyne_schema_data('pages');
    $page_data = ($path !== '' && isset($pages[$path])) ? $pages[$path] : [];
    $global = dyne_schema_data('global');
    $nodes  = [];

    foreach ($global['nodes'] ?? [] as $node) {
        if (
            !empty($page_data['org'])
            && isset($node['@id'])
            && $node['@id'] === DYNE_SCHEMA_ORG_ID
        ) {
            $node = array_merge($node, $page_data['org']);
        }
        $nodes[] = $node;
    }

    $nodes = array_merge($nodes, $page_data['nodes'] ?? []);

    if (!$nodes) {
        return;
    }

    echo '<script type="application/ld+json">'
        . wp_json_encode(['@context' => 'https://schema.org', '@graph' => $nodes], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        . '</script>' . "\n";
}
add_action('wp_head', 'dyne_schema_standalone_output', 20);

/**
 * llms.txt discoverability — the standard link relation from spec v2.
 *
 * home_url() rather than the hardcoded domain, so staging points at staging's
 * own file. Emitted only when the file is actually in the site root, so a
 * missing file cannot leave a dead link in the head.
 */
add_action('wp_head', function () {
    if (!file_exists(ABSPATH . 'llms.txt')) {
        return;
    }

    printf(
        '<link rel="describedby" href="%s">' . "\n",
        esc_url(home_url('/llms.txt'))
    );
}, 1);
