<?php namespace ElementorSiteBuilder;

/**
 * Plugin Name: Site Builder for Elementor
 * Plugin URI: https://wordpress.org/plugins/site-builder-for-elementor
 * Description: Header builder, footer builder and site elements for <a target="_blank" href="https://wordpress.org/plugins/elementor/">Elementor Page Builder</a>.
 * Author: SarahCoding
 * Author URI: https://sarahcoding.com
 * Version: 1.0.0
 * Text Domain: site-builder-for-elementor
 * Requires PHP: 5.6
 * Requires at least: 5.2
 */

use Exception;

/**
 * Plugin container.
 */
final class Plugin
{
    /**
     * Version
     *
     * @var  string
     */
    const VERSION = '1.0.0';

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('plugins_loaded', [$this, '_install'], 10, 0);
        add_action('activate_site-builder-for-elementor/site-builder-for-elementor.php', [$this, '_activate']);
    }

    /**
     * Do activation
     *
     * @internal  Used as a callback.
     *
     * @see  https://developer.wordpress.org/reference/functions/register_activation_hook/
     *
     * @param  bool  $network  Whether to activate this plugin on network or a single site.
     */
    public function _activate($network)
    {
        try {
            $this->preActivate();
        } catch (Exception $e) {
            if (defined('DOING_AJAX') && DOING_AJAX) {
                header('Content-Type: application/json; charset=' . get_option('blog_charset'));
                status_header(500);
                exit(json_encode([
                    'success' => false,
                    'name'    => __('Plugin Activation Error', 'site-builder-for-elementor'),
                    'message' => $e->getMessage()
                ]));
            } else {
                exit($e->getMessage());
            }
        }
    }

    /**
     * Do installation
     *
     * @internal  Used as a callback.
     *
     * @see  https://developer.wordpress.org/reference/hooks/plugins_loaded/
     */
    public function _install()
    {
        // Define useful constants.
        define('ELEMENTOR_SITE_BUILDER_DIR', __DIR__ . '/');
        define('ELEMENTOR_SITE_BUILDER_URI', plugins_url('/', __FILE__));

        // Make sure translation is available.
        load_plugin_textdomain('site-builder-for-elementor', false, basename(__DIR__) . '/languages');

        // Register autoloading.
        $this->registerAutoloading();

        // Load modules.
        new Documents\Manager();
        new Blocks\Meta\PageLayoutSettings();
    }

    /**
     * Register autoloading
     */
    private function registerAutoloading()
    {
        spl_autoload_register(function ($class) {
            if (0 !== strpos($class, __NAMESPACE__ . '\\')) {
                return; // Not in my job description :)
            }

            $path = str_replace(__NAMESPACE__, __DIR__ . '/src', $class);
            $file = str_replace('\\', '/', $path) . '.php';

            if (file_exists($file)) {
                require $file;
            } else {
                /* translators: %s: loading class. */
                throw new Exception(sprintf(__('Autoloading failed. Class "%s" not found.', 'site-builder-for-elementor'), $class));
            }
        }, true, false);
    }

    /**
     * Pre-activation check
     *
     * @throws  Exception
     */
    private function preActivate()
    {
        if (version_compare(PHP_VERSION, '5.6', '<')) {
            throw new Exception(__('This plugin requires PHP version 5.6 at least!', 'site-builder-for-elementor'));
        }

        if (version_compare($GLOBALS['wp_version'], '5.2', '<')) {
            throw new Exception(__('This plugin requires WordPress version 5.2 at least!', 'site-builder-for-elementor'));
        }

        if (!defined('WP_CONTENT_DIR') || !is_writable(WP_CONTENT_DIR)) {
            throw new Exception(__('WordPress content directory is inaccessible.', 'site-builder-for-elementor'));
        }
    }
}

// Initialize plugin.
return new Plugin();
