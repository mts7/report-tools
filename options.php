<?php
// Code taken and modified from https://kovshenin.com/2012/the-wordpress-settings-api/

// add the link for the settings to the admin menu
add_action('admin_menu', 'rt_admin_menu');

function rt_admin_menu() {
    add_options_page('Report Tools', 'Report Tools', 'manage_options', 'report-tools', 'rt_options_page');
}

// display the options page
function rt_options_page() {
    ?>
    <div class="wrap">
        <h2>Report Tools Settings</h2>
        <form action="options.php" method="POST">
            <?php settings_fields('rt-settings-group'); ?>
            <?php do_settings_sections('report-tools'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
<?php
}

// set up the settings
add_action('admin_init', 'rt_admin_init');

function rt_admin_init() {
    register_setting('rt-settings-group', 'rt-setting');
    add_settings_section('config-params', 'Configuration Parameters', 'rt_config_params_help_text', 'report-tools');
    add_settings_field('admin_email', 'Admin Email', 'rt_admin_email_display', 'report-tools', 'config-params');
}

// display a help or introduction message
function rt_config_params_help_text() {
    echo 'Set the parameters here to change parts of the plugin.';
}

// display the form field for admin email
function rt_admin_email_display() {
    $setting = esc_attr(get_option('rt-setting'));
    echo '<input type="text" name="rt-setting" value="'.$setting.'" />';
}
?>