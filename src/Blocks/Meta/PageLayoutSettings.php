<?php namespace ElementorSiteBuilder\Blocks\Meta;

use Exception;
use ElementorSiteBuilder\Plugin;

/**
 * PageLayoutSettings
 */
final class PageLayoutSettings
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('init', [$this, '_register'], 10, 0);
        add_action('enqueue_block_editor_assets', [$this, '_enqueueScripts'], 10, 0);
    }

    /**
     * Register with editor
     *
     * @internal Used as a callback.
     */
    public function _register()
    {
        register_post_meta('', 'elementor_site_builder_template_siteheader', [
            'type' => 'string',
            'single' => 1,
            'description' => __('Select a header template to display along with the content on the frontend.', 'site-builder-for-elementor'),
            'show_in_rest' => 1,
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        register_post_meta('', 'elementor_site_builder_template_sitefooter', [
            'type' => 'string',
            'single' => 1,
            'description' => __('Select a footer template to display along with the content on the frontend.', 'site-builder-for-elementor'),
            'show_in_rest' => 1,
            'sanitize_callback' => 'sanitize_text_field',
        ]);
    }

    /**
     * Enqueue scripts
     */
    public function _enqueueScripts()
    {
        wp_enqueue_script('esb-meta-sidebar', ELEMENTOR_SITE_BUILDER_URI . 'assets/js/meta-sidebar.min.js', ['wp-blocks', 'wp-element', 'wp-components'], Plugin::VERSION, true);

        wp_localize_script('esb-meta-sidebar', 'elementorSiteBuilderData', [
            'headerTemplates' => $this->listHeaderTemplates(),
            'footerTemplates' => $this->listFooterTemplates()
        ]);
    }

    /**
     * List all available header templates
     *
     * @return array
     */
    private function listHeaderTemplates()
    {
        $options = [
            [
              'label' => __('Global Header', 'site-builder-for-elementor'),
              'value' => 'inherit'
            ],
            [
              'label' => __('Theme Default', 'site-builder-for-elementor'),
              'value' => 'default'
            ]
        ];

        $headers = get_posts([
            'post_type' => 'elementor_library',
            'post_status' => 'publish',
            'meta_key' => '_elementor_template_type',
            'meta_value' => 'siteheader',
            'ignore_sticky_posts' => true,
            'nopaging' => true,
            'no_found_rows' => true,
            'posts_per_page' => -1,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false
        ]);

        if ($headers) {
            foreach ($headers as $header) {
                $options[] = [
                  'label' => $header->post_title,
                  'value' => $header->post_name
                ];
            }
        }

        return $options;
    }

    /**
     * List all available footer templates
     *
     * @return array
     */
    private function listFooterTemplates()
    {
        $options = [
            [
              'label' => __('Global Footer', 'site-builder-for-elementor'),
              'value' => 'inherit'
            ],
            [
              'label' => __('Theme Default', 'site-builder-for-elementor'),
              'value' => 'default'
            ]
        ];

        $footers = get_posts([
            'post_type' => 'elementor_library',
            'post_status' => 'publish',
            'meta_key' => '_elementor_template_type',
            'meta_value' => 'sitefooter',
            'ignore_sticky_posts' => true,
            'nopaging' => true,
            'no_found_rows' => true,
            'posts_per_page' => -1,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false
        ]);

        if ($footers) {
            foreach ($footers as $footer) {
                $options[] = [
                  'label' => $footer->post_title,
                  'value' => $footer->post_name
                ];
            }
        }

        return $options;
    }
}
