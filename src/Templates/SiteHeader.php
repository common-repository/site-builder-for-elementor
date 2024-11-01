<?php
/**
 * Template to display site header
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="profile" href="http://gmpg.org/xfn/11">
        <?php if (!current_theme_supports('title-tag')) : ?>
            <title><?php echo wp_get_document_title(); ?></title>
        <?php endif; ?>
        <?php wp_head(); ?>
    </head>
<body <?php body_class(); ?>>
<?php

// Theme authors may render something before.
do_action('esb_before_render_site_header', $header_tmpl_id);

// Output Elementor builder content without CSS.
echo Elementor\Plugin::$instance->frontend->get_builder_content($header_tmpl_id, false);

// Theme author may render something after.
do_action('esb_after_render_site_header', $header_tmpl_id);
