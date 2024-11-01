<?php namespace ElementorSiteBuilder\Documents;

use Elementor\Controls_Manager;
use Elementor\Plugin as Elementor;
use Elementor\Core\Documents_Manager;

/**
 * Manager
 */
final class Manager
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('get_header', [$this, '_renderSiteHeader'], 11);
        add_action('get_footer', [$this, '_renderSiteFooter'], 11);
        add_action('template_include', [$this, '_editTemplate'], 11);
        add_action('elementor/documents/register', [$this, '_registerDocuments']);
        add_action('elementor/editor/after_save', [$this, '_updateTemplateLocation'], 11, 2);
    }

    /**
     * @internal Used as a callback
     */
    public function _registerDocuments(Documents_Manager $manager)
    {
        $manager->register_document_type(SiteHeader::TYPE, SiteHeader::class);
        $manager->register_document_type(SiteFooter::TYPE, SiteFooter::class);
    }

    /**
     * @internal Used as a callback
     */
    public function _editTemplate($template)
    {
        if (is_singular()) {
            $document = Elementor::$instance->documents->get_doc_for_frontend(get_the_ID());
            if ($document) {
                if ($document instanceof SiteHeader || $document instanceof SiteFooter) {
                    return ELEMENTOR_SITE_BUILDER_DIR . 'src/Templates/BlankPage.php';
                }
            }
        }

        return $template;
    }

    /**
     * @internal Used as a callback
     */
    public function _renderSiteHeader($name)
    {
        $header_tmpl_id = $this->hasAssignedTemplate(SiteHeader::TYPE);

        if ($header_tmpl_id) {
            require ELEMENTOR_SITE_BUILDER_DIR . 'src/Templates/SiteHeader.php';
            $templates = [];
            if ($name) {
                $templates[] = "header-{$name}.php";
            }
            $templates[] = 'header.php';
            remove_all_actions('wp_head');
            ob_start();
            locate_template($templates, true);
            ob_get_clean();
        }
    }

    /**
     * @internal Used as a callback
     */
    public function _renderSiteFooter($name)
    {
        $footer_tmpl_id = $this->hasAssignedTemplate(SiteFooter::TYPE);

        if ($footer_tmpl_id) {
            require ELEMENTOR_SITE_BUILDER_DIR . 'src/Templates/SiteFooter.php';
            $templates = [];
            if ($name) {
                $templates[] = "footer-{$name}.php";
            }
            $templates[] = 'footer.php';
            remove_all_actions('wp_footer');
            ob_start();
            locate_template($templates, true);
            ob_get_clean();
        }
    }

    /**
     * @return int
     */
    private function getCurrentPageId()
    {
        global $wp_query;

        if (!is_main_query()) {
            return 0;
        }

        if (is_home() && !is_front_page()) {
            return (int)get_option('page_for_posts');
        } elseif (!is_home() && is_front_page()) {
            return (int)get_option('page_on_front');
        } elseif (function_exists('is_shop') && is_shop()) {
            return wc_get_page_id('shop');
        } elseif (is_privacy_policy()) {
            return (int)get_option('wp_page_for_privacy_policy');
        } elseif (!empty($wp_query->post->ID)) {
            return (int)$wp_query->post->ID;
        } else {
            return 0;
        }
    }

    /**
     * Check if queried location has an assigned template
     *
     * @param string $type Template type.
     *
     * @return int|bool Template id or false if there's no assigned template.
     */
    private function hasAssignedTemplate($type)
    {
        global $wp_query;

        $tpl = false;

        if (is_front_page() && is_home()) {
            $tpl = $this->getAssignedTemplate($type, 'index');
        } elseif (is_front_page() && !is_home()) {
            $tpl = $this->getAssignedTemplate($type, 'front');
        } elseif (!is_front_page() && is_home()) {
            $tpl = $this->getAssignedTemplate($type, 'blog');
        } elseif (function_exists('is_shop') && is_shop()) {
            $tpl = $this->getAssignedTemplate($type, 'wc_shop');
        } elseif (is_search()) {
            $tpl = $this->getAssignedTemplate($type, 'search');
        } elseif (is_404()) {
            $tpl = $this->getAssignedTemplate($type, 'err404');
        } elseif (is_privacy_policy()) {
            $tpl = $this->getAssignedTemplate($type, 'privacy');
        } elseif (is_singular()) {
            if (!empty($wp_query->post->post_type)) {
                $tpl = $this->getAssignedTemplate($type, 'singular_' . $wp_query->post->post_type);
            }
            if (!$tpl) {
                $tpl = $this->getAssignedTemplate($type, 'singular');
            }
        } elseif (is_archive()) {
            if (is_author()) {
                $tpl = $this->getAssignedTemplate($type, 'archive_author');
            } elseif (is_date()) {
                $tpl = $this->getAssignedTemplate($type, 'archive_date');
            } elseif (is_tax()) {
                $tpl = $this->getAssignedTemplate($type, 'archive_' . $wp_query->queried_object->taxonomy);
            } elseif (is_post_type_archive()) {
                $tpl = $this->getAssignedTemplate($type, 'archive_' . $wp_query->posts[0]->post_type);
            }
            if (!$tpl) {
                $tpl = $this->getAssignedTemplate($type, 'archive');
            }
        }

        $_tpl = get_post_meta($this->getCurrentPageId(), 'elementor_site_builder_template_' . $type, true);

        if ($_tpl && 'inherit' !== $_tpl) {
            if ('default' === $_tpl) {
                return false;
            } else {
                $tpl = get_page_by_path($_tpl, OBJECT, 'elementor_library');
            }
        }

        if (!$tpl) {
            $tpl = $this->getAssignedTemplate($type, 'global');
        }

        return $tpl ? $tpl->ID : false;
    }

    /**
     * Get assigned template by location and page type
     */
    private function getAssignedTemplate($tpl_type, $page_type)
    {
        global $wp_query;

        $tsl = get_option('stylesheet');
        $tpl = get_option($tsl . '_mod_esb_tpl_' . $tpl_type . '_' . $page_type);

        if ($tpl) {
            return get_page_by_path($tpl, OBJECT, 'elementor_library');
        }

        return false;
    }

    /**
     * @internal Used as a callback
     */
    public function _updateTemplateLocation($post_id, $data)
    {
        global $wpdb;

        $tpl = get_post($post_id);
        $type = get_post_meta($post_id, '_elementor_template_type', true);
        $settings = get_post_meta($post_id, '_elementor_page_settings', true);
        $key_prefix = get_option('stylesheet') . '_mod_esb_tpl_' . $type . '_';

        if (in_array($type, [SiteHeader::TYPE, SiteFooter::TYPE])) {
            $wpdb->query("DELETE FROM " . $wpdb->options . " WHERE option_name LIKE '" . $wpdb->esc_like($key_prefix) . "%' AND option_value='" . $tpl->post_name . "'");
            switch ($settings['show_on']) {
                case 'global':
                    add_option($key_prefix . 'global', $tpl->post_name);
                    break;
                case 'index':
                    add_option($key_prefix . 'index', $tpl->post_name);
                    break;
                case 'blog':
                    add_option($key_prefix . 'blog', $tpl->post_name);
                    break;
                case 'front':
                    add_option($key_prefix . 'front', $tpl->post_name);
                    break;
                case 'search':
                    add_option($key_prefix . 'search', $tpl->post_name);
                    break;
                case 'err404':
                    add_option($key_prefix . 'err404', $tpl->post_name);
                    break;
                case 'wc_shop':
                    add_option($key_prefix . 'wc_shop', $tpl->post_name);
                    break;
                case 'privacy':
                    add_option($key_prefix . 'privacy', $tpl->post_name);
                    break;
                case 'singular':
                    if (!empty($settings['singular_pages'])) {
                        foreach ($settings['singular_pages'] as $page_type) {
                            add_option($key_prefix . 'singular_' . $page_type, $tpl->post_name);
                        }
                    } else {
                        add_option($key_prefix . 'singular', $tpl->post_name);
                    }
                    break;
                case 'archive':
                    if (!empty($settings['archive_pages'])) {
                        foreach ($settings['archive_pages'] as $page_type) {
                            add_option($key_prefix . 'archive_' . $page_type, $tpl->post_name);
                        }
                    } else {
                        add_option($key_prefix . 'archive', $tpl->post_name);
                    }
                    break;
                case 'custom':
                    if (!empty($settings['singular_pages'])) {
                        foreach ($settings['singular_pages'] as $page_type) {
                            add_option($key_prefix . 'singular_' . $page_type, $tpl->post_name);
                        }
                    }
                    if (!empty($settings['archive_pages'])) {
                        foreach ($settings['archive_pages'] as $page_type) {
                            add_option($key_prefix . 'archive_' . $page_type, $tpl->post_name);
                        }
                    }
                    break;
                default:
                    break;
            }
        }
    }
}
