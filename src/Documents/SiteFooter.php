<?php namespace ElementorSiteBuilder\Documents;

use Elementor\Utils;
use Elementor\Controls_Manager;
use Elementor\Modules\Library\Documents\Library_Document;

/**
 * SiteFooter
 */
final class SiteFooter extends Library_Document
{
    /**
     * @var string
     */
    const TYPE = 'sitefooter';

    /**
     * Get document properties.
     *
     * Retrieve the document properties.
     *
     * @return array Document properties.
     */
    public static function get_properties()
    {
        return [
            'has_elements' => true,
            'is_editable' => true,
            'edit_capability' => '',
            'show_in_finder' => true,
            'show_on_admin_bar' => true,
            'admin_tab_group' => 'library',
            'show_in_library' => true,
            'register_type' => true,
            'support_kit' => false,
            'support_wp_page_templates' => false
        ];
    }

    /**
     * Get document name.
     *
     * Retrieve the document name.
     *
     * @return string Document name.
     */
    public function get_name()
    {
        return self::TYPE;
    }

    /**
     * @return string Document title.
     */
    public static function get_title()
    {
        return __('Footer', 'site-builder-for-elementor');
    }

    /**
     * @return string
     */
    public function get_css_wrapper_selector()
    {
        return '#esb-site-header';
    }

    /**
     * Override container attributes
     */
    public function get_container_attributes()
    {
        $id = $this->get_main_id();

        $settings = $this->get_frontend_settings();

        $attributes = [
            'data-elementor-type' => self::TYPE,
            'data-elementor-id' => $id,
            'class' => 'elementor elementor-' . $id . ' esb-site-footer',
        ];

        return $attributes;
    }

    /**
     * Override default wrapper.
     */
    public function print_elements_with_wrapper($data = null)
    {
        if (!$data) {
            $data = $this->get_elements_data();
        }

        do_action('before_print_esb_site_footer', $data);

        ?>
    		<footer id="esb-site-footer" <?php echo Utils::render_html_attributes($this->get_container_attributes()); ?>>
    			<div class="elementor-inner">
    				<div class="elementor-section-wrap">
    					<?php $this->print_elements($data); ?>
    				</div>
    			</div>
    		</footer>
    		<?php

        do_action('after_print_esb_site_footer', $data);
    }

    /**
     * Register controls
     */
    protected function _register_controls()
    {
        $this->register_document_controls();

        $this->start_controls_section(
            'display_conditions',
            [
                'label' => __('Display Conditions', 'site-builder-for-elementor'),
                'tab' => Controls_Manager::TAB_SETTINGS,
            ]
        );

        $this->add_control(
            'template_hierarchy',
            [
                'type' => Controls_Manager::RAW_HTML,
                /* translators: 1: external link open tag. 2: link close tag */
                'raw' => sprintf(__('Conditions are built on the WordPress template hierarchy. %1$sLearn more...%2$s', 'site-builder-for-elementor'), '<a target="_blank" href="https://developer.wordpress.org/files/2014/10/Screenshot-2019-01-23-00.20.04.png">', '</a>'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
            ]
        );

        $this->add_control(
            'meta_block_select',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => __('If multiple footers have the same condition, the last updated one will be displayed.', 'site-builder-for-elementor'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
            ]
        );

        $this->add_control(
            'show_on',
            [
                'label' => __('Show On', 'site-builder-for-elementor'),
                'type' => Controls_Manager::SELECT,
                'label_block' => true,
                'default' => 'none',
                'options' => [
                    'none' => __('None', 'site-builder-for-elementor'),
                    'blog' => __('Blog Page', 'site-builder-for-elementor'),
                    'index' => __('Index Page', 'site-builder-for-elementor'),
                    'front' => __('Front Page', 'site-builder-for-elementor'),
                    'search' => __('Search Result', 'site-builder-for-elementor'),
                    'err404' => __('Error 404 Page', 'site-builder-for-elementor'),
                    'archive' => __('Archive Pages', 'site-builder-for-elementor'),
                    'singular' => __('Singular Pages', 'site-builder-for-elementor'),
                    'wc_shop' => __('WooCommerce Shop', 'site-builder-for-elementor'),
                    'privacy' => __('Privacy Policy Page', 'site-builder-for-elementor'),
                    'global' => __('Entire Site (Global)', 'site-builder-for-elementor'),
                    'custom' => __('Archive & Singular Pages', 'site-builder-for-elementor')
                ]
            ]
        );

        $this->add_control(
            'singular_pages',
            [
                'label' => __('Select Singular Pages', 'site-builder-for-elementor'),
                'label_block' => true,
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->getSingularPagesOptions(),
                'condition' => [
                    'show_on' => ['singular', 'custom']
                ]
            ]
        );

        $this->add_control(
            'archive_pages',
            [
                'label' => __('Select Archive Pages', 'site-builder-for-elementor'),
                'label_block' => true,
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->getArchivePagesOptions(),
                'condition' => [
                    'show_on' => ['archive', 'custom']
                ]
            ]
        );

        $this->end_controls_section();
    }

    /**
     * @return array
     */
    private function getSingularPagesOptions()
    {
        global $wp_post_types;

        $options = [
            'post' => __('Single Post', 'site-builder-for-elementor'),
            'page' => __('Single Page', 'site-builder-for-elementor'),
            'attachment' => __('Single Attachment', 'site-builder-for-elementor'),
        ];

        foreach ($wp_post_types as $type => $object) {
            if ($object->public && !$object->_builtin && 'elementor_library' != $type) {
                $options[$type] = __('Single', 'site-builder-for-elementor') . ' ' . $object->labels->singular_name;
            }
        }

        return $options;
    }

    /**
     * @return array
     */
    private function getArchivePagesOptions()
    {
        global $wp_taxonomies, $wp_post_types;

        $options = [
            'author' => __('Author', 'site-builder-for-elementor'),
            'date' => __('Date', 'site-builder-for-elementor'),
            'post_tag' => __('Tag', 'site-builder-for-elementor'),
            'category' => __('Category ', 'site-builder-for-elementor'),
        ];

        foreach ($wp_taxonomies as $type => $object) {
            if ($object->public && !$object->_builtin && 'product_shipping_class' != $type) {
                $options[$type] = $object->labels->name;
            }
        }

        foreach ($wp_post_types as $type => $object) {
            if ($object->public && !$object->_builtin && 'elementor_library' != $type) {
                $options[$type] = $object->labels->name . ' ' . __('Archive', 'site-builder-for-elementor');
            }
        }

        return $options;
    }
}
