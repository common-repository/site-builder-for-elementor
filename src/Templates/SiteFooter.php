<?php
/**
 * Template to display site footer
 */

// Theme authors may render something before.
do_action('esb_before_render_site_footer', $footer_tmpl_id);

// Output Elementor builder content without CSS.
echo Elementor\Plugin::$instance->frontend->get_builder_content($footer_tmpl_id, false);

// Theme author may render something after.
do_action('esb_after_render_site_footer', $footer_tmpl_id);

wp_footer();

?>
</body>
</html>
