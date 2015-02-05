<?php
/**
 * @package ReportTools
 * @version 0.3
 * Uses helpers.php file that is included with PDOWrapper.
 * See https://github.com/mts7/PDOWrapper for details.
 */
/*
 * Plugin Name: Report Tools
 * Plugin URI: https://github.com/mts7/report-tools
 * Description: Exports database values as requested
 * Author: Mike Rodarte
 * Version: 0.3
 * Author URI: http://www.mikerodarte.com
*/

require_once 'helpers.php';
include_once  plugin_dir_path( __FILE__ ) . 'options.php';

// get the action, if it is set, to process the JavaScript
$action = isset($_POST['report_tools_action']) ? $_POST['report_tools_action'] : false;

if ($action) {
    // all processing should happen within the ReportTools class
    require_once plugin_dir_path( __FILE__ ) . 'ReportTools.php';
    $tool = new ReportTools();

    // each tool should have its own action in this switch statement
    switch($action) {
        case 'users':
            // get users from ReportTools
            $result = $tool->getUsers();
            if ($result) {
                echo 'Emailed '.$tool->getAdmin().' the CSV file.';
            } else {
                echo 'Error getting users to admin email: '.$tool->getMessage();
            }
            // stop executing the script so the page doesn't load itself as a response
            exit;
            break;
        default:
            //echo 'action is '.$action;
            break;
    }

    // in case a case statement had no exit call, call it here
    exit(1);
}

if (is_admin()) {
    add_action('admin_menu', 'report_tools_admin_menu');

    function report_tools_admin_menu() {
        add_management_page('Report Tools', 'Report Tools', 'administrator', 'report-tools',
            'report_tools_form');
    }

    /**
     * All tools should have their HTML, JavaScript, and CSS in this function for display.
     * @return string HTML form with JavaScript (jQuery)
     */
    function report_tools_form() {
        $out = '<script type="text/javascript">'.NL;
        $out .= 'jQuery(function() {
    jQuery(\'#button_users\').on(\'click\', function() {
        jQuery.ajax({
            url: \'report-tools.php\',
            type: \'POST\',
            data: {
                report_tools_action: \'users\'
            }
        })
        .done(function(data) {
            jQuery(\'#output_users\').html(data);
        });
    });
});'.NL;
        $out .= '</script>'.NL;
        $out .= '<div class="wrap">'.NL;
        $out .= '<h2>Report Tools</h2>'.NL;
        $out .= '<input type="button" id="button_users" value="Email Users to Admin" />'.NL;
        $out .= '<div id="output_users"></div>'.NL;
        $out .= '</div> <!-- end .wrap -->'.NL;

        echo $out;
    }
}
?>