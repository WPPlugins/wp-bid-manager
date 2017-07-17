<?php
/*
Plugin Name: WP Bid Manager
Plugin URI: https://www.supplyingcontractors.com
Description: WordPress bid management system.  Create and manage bids. Then get quotes for those bids by sending them via email from the dashboard.
Version: 1.1.9
Author: Supplying Contractors
Author URI: https://www.supplyingcontractors.com
License: GPL2
*/

// No direct access allowed.
if (!defined('ABSPATH')) {
    exit;
}
// Setup constants to be used throught the plugin
define(BM_SITE_URL, get_bloginfo("url"));
define(BM_CDBOARD, BM_SITE_URL . '/wp-admin/admin.php?page=bid_manager_dashboard');
define(BM_CINFO, BM_SITE_URL . '/wp-admin/admin.php?page=company_information');
define(BM_CBID, BM_SITE_URL . '/wp-admin/admin.php?page=new_bid');
define(BM_EMAIL_SETTINGS, BM_SITE_URL . '/wp-admin/admin.php?page=bm_email_settings/');
define(BM_REPORTING, BM_SITE_URL . '/wp-admin/admin.php?page=bm_report');
define(BM_BMSETTINGS, BM_SITE_URL . '/wp-admin/admin.php?page=bm_settings');

define(PLUGIN_ROOT, plugins_url('/', __FILE__)); // Plugin root folder

if (!defined(BM_BIDS)) {

    global $table_prefix;

    define(BM_BIDS, $table_prefix . 'bm_bids');
    define(BM_USER, $table_prefix . 'bm_user');
    define(BM_RESPONDERS, $table_prefix . 'bm_responder');
    define(BM_BIDS_RESPONSES, $table_prefix . 'bm_bids_responses');
    define(BM_EMAILS, $table_prefix . 'bm_responder_emails');
    define(BM_NOTIFICATIONS, $table_prefix . 'bm_notifications');
    define(BM_USERMETA, $table_prefix . 'usermeta');
    define(BM_OPTIONS, $table_prefix . 'options');

}

// Load all the files that are necessary for the plugin
require_once('includes/bm_user.php');
require_once('includes/bm_responder.php');
require_once('includes/reports.php');
require_once('includes/dbtables.php');
require_once('includes/ajax.php');
require_once('includes/shortcodes.php');
require_once('includes/notifications.php');

function bid_manager_menu()
{

    add_menu_page('Bid Manager', 'Bid Manager', 'read', 'bid_manager', 'bm_main', 'dashicons-media-text', 3);
    add_submenu_page('bid_manager', 'Dashboard', 'Dashboard', 'read', 'bid_manager_dashboard', 'bm_dashboard');
    add_submenu_page('bid_manager', 'New Bid', 'New Bid', 'read', 'new_bid', 'bm_new_bid');
    add_submenu_page('bid_manager', 'Company Information', 'Company Information', 'read', 'company_information', 'bm_company_info');
    add_submenu_page('bid_manager', 'Reports', 'Reports', 'read', 'bm_report', 'bm_reports');
    add_submenu_page('bid_manager', 'Emails', 'Email Settings', 'read', 'bm_email_settings', 'bm_user_email_settings');
    add_submenu_page('bid_manager', 'BM Settings', 'BM Settings', 'read', 'bm_settings', 'bm_settings');


}

//  Geo spatial code for finding out radius on addresses for bid requesters and responders

/**
 * Geocode service response
 * @param string $address - e.g. 123 Main St, Denver, CO 80221
 */
function bm_get_lat_and_lng($address)
{

    global $wpdb;
    global $current_user;
    get_currentuserinfo();

    $id = $current_user->ID;

    //  Query for the API key
    $query = "SELECT meta_value FROM " . BM_USERMETA . " WHERE user_id = %d AND meta_key = %s";
    $data = array(
        $id,
        'bm_google_api_key'
    );

    $query = $wpdb->prepare($query, $data);
    $results = $wpdb->get_results($query);

    foreach ($results as $record) {
        $key = $record->meta_value;
    }

    $address = str_replace(" ", "+", urlencode($address));

    // sample URL: https://maps.googleapis.com/maps/api/geocode/json?address=122+Flinders+St,+Darlinghurst,+NSW,+Australia&sensor=false&key=AIzaSyDjtX-Q1FYasO0wcQKqrOktFLghekf9Uns

    $details_url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&sensor=false&key={$key}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $details_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = json_decode(curl_exec($ch), true);

    // If Status Code is ZERO_RESULTS, OVER_QUERY_LIMIT, REQUEST_DENIED or INVALID_REQUEST
    if ($response['status'] != 'OK') {
        return FALSE;
    }

    //print_r($response);
    $geometry = $response['results'][0]['geometry'];

    $latitude = $geometry['location']['lat'];
    $longitude = $geometry['location']['lng'];

    $array = array(
        'lat' => $geometry['location']['lat'],
        'lng' => $geometry['location']['lng'],
        'location_type' => $geometry['location_type'],
    );

    return $array;
}

//  Scramble the file names for bid request/response file uploads and put them on the server
function bm_handle_file_upload($key, $upload_path)
{

    $upload_path = rtrim($upload_path, '/') . '/';

    if (!isset($_FILES[$key])) {
        return;
    }

    $file = $_FILES[$key];

    if (empty($file['name'])) {
        return FALSE;
    }

    // We need the PATH, for moving / saving files
    $base_path = wp_upload_dir();
    $base_url = $base_path['baseurl'] . '/' . $upload_path;
    $base_path = $base_path['basedir'] . '/' . $upload_path;

    $pathinfo = pathinfo($file['name']);
    $ext = $pathinfo['extension'];

    $salt = '1234SomeRandomPatternOfLettersAndNumbers!!!$&#$';

    // Get the name of the file.  But we only care a little, because we want to make it unique / random
    $name = basename($file['name']);

    // Create the random file name
    $name = md5($name . $salt) . '.' . $ext;

    // Assign the PATH to move the file to
    $path = $base_path . $name;
    // Set up the URL to view the file
    $url = $base_url . $name;

    if (move_uploaded_file($file['tmp_name'], $path)) {
        return $url;
    } else {
        // Move failed. Possible duplicate?
        echo "The upload failed.  There is a possibility there could be a duplicate.";
    }

}


// This function ties into the admin_init() to load the necessary javascript and CSS files
function bm_head()
{


    /*
    * Styles
    */
    // Load Styles
    wp_enqueue_style('bm-dashboard-style', PLUGIN_ROOT . '/css/style.css');
    wp_enqueue_style('jquery-ui-style', PLUGIN_ROOT . '/css/jquery-ui.css');

    /*
     * Scripts
     */
    // Load Scripts
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script('bm-datatables', PLUGIN_ROOT . '/javascript/jquery.dataTables.js');
    wp_register_script('bm-common-scripts', PLUGIN_ROOT . '/javascript/bm.common.js', array('jquery'));
    wp_localize_script('bm-common-scripts', 'ajax_object',
        array('ajax_url' => admin_url('admin-ajax.php')));
    wp_enqueue_script('bm-common-scripts');
}

add_action('admin_enqueue_scripts', 'bm_head');

add_action('wp_ajax_my_action', 'bm_hide_notes');
add_action('wp_ajax_nopriv_my_action', 'bm_hide_notes');

// Bid Manager Main screen

function bm_main()
{

    $info = BM_CINFO;
    $settings = BM_BMSETTINGS;
    $email_settings = BM_EMAIL_SETTINGS;
    $new_bid = BM_CBID;
    $bm_dashboard = BM_CDBOARD;


    ?>
    <div class="wrap">
        <table>
            <tr>
                <td>
                    <?php
                    sc_show_notifications();
                    ?>
                </td>
            </tr>
            <tr>
                <td>
                    <h1>WP Bid Manager</h1>

                    <p>Here is a list of things to do in order to get up and running smoothly.</p>
                    <ol>
                        <li>Enter your <a href="<?php echo $info ?>">company information</a>. You must enter your info
                            to use
                            the system. It identifies you and is also necessary when using the quote request option.
                        </li>
                        <li>Manage your <a href="<?php echo $settings ?>">settings</a>. Set your page for the
                            [bm-invite]
                            shortcode to get quote responses and enter your API key for Google Maps.
                        </li>
                        <li>Configure your <a href="<?php echo $email_settings ?>">email settings</a>. This allows you
                            to
                            customize the from, subject line, and the body of the email template for quote requests.
                        </li>
                        <li>Create a <a href="<?php echo $new_bid ?>">new bid</a>. You are able to create a real bid
                            that you
                            would like to manage or send out for a quote.
                        </li>
                        <li>Keep track of all of your bids in <a href="<?php echo $bm_dashboard ?>">the bid manager
                                dashboard</a>.
                        </li>
                    </ol>
                </td>
            </tr>
        </table>
    </div>
    <?php
}

// Bid Manager Dashboard

function bm_dashboard()
{
    bm_user_dashboard();
}


// Company Information

function bm_company_info()
{
    bm_user_info();
}

// New Bid

function bm_new_bid()
{
    bm_bid_form();
}

// Reports

function bm_reports()
{
    bm_report_controller();
}

// Allows the user to customize their emails to the bid responder

/**
 *
 */
function bm_user_email_settings()
{

    global $wpdb;
    global $current_user;
    get_currentuserinfo();

    $id = $current_user->ID;


    if (isset($_POST['bm_admin_email_settings'])) {

        $query = "SELECT meta_value FROM " . BM_USERMETA . " WHERE user_id = %d AND meta_key = 'bm_subject_line' OR meta_key = 'bm_from_line' OR meta_key = 'bm_email_content' OR meta_key = 'email_from_name'";
        $data = array(
            $id
        );

        $query = $wpdb->prepare($query, $data);
        $results = $wpdb->get_results($query);

        $email_subject = sanitize_text_field($_POST['subject_line']);
        $email_from = sanitize_text_field($_POST['email_from']);
        $email_content = $_POST['bm_email_body_content'];
        $email_from_name = sanitize_text_field($_POST['email_from_name']);


        if ($results) {

            //  Add the subject line
            $query = "UPDATE " . BM_USERMETA . " SET meta_value = %s WHERE user_id = %d AND meta_key = %s";
            $data = array(
                $email_subject,
                $id,
                'bm_subject_line'
            );

            $query = $wpdb->prepare($query, $data);
            $wpdb->get_results($query);

            //  Add the from line
            $query = "UPDATE " . BM_USERMETA . " SET meta_value = %s WHERE user_id = %d AND meta_key = %s";
            $data = array(
                $email_from,
                $id,
                'bm_from_line'
            );

            $query = $wpdb->prepare($query, $data);
            $wpdb->get_results($query);

            //  Add the email body
            $query = "UPDATE " . BM_USERMETA . " SET meta_value = %s WHERE user_id = %d AND meta_key = %s";
            $data = array(
                $email_content,
                $id,
                'bm_email_content'
            );

            $query = $wpdb->prepare($query, $data);
            $wpdb->get_results($query);

            //  Add the from name line
            $query = "UPDATE " . BM_USERMETA . " SET meta_value = %s WHERE user_id = %d AND meta_key = %s";
            $data = array(
                $email_from_name,
                $id,
                'email_from_name'
            );

            $query = $wpdb->prepare($query, $data);
            $wpdb->get_results($query);

        } else {

            //  Add the subject line
            $query = "INSERT INTO " . BM_USERMETA . " (umeta_id, user_id, meta_key, meta_value)" .
                "VALUES (%d, %d, %s, %s);";
            $data = array(
                '',
                $id,
                'bm_subject_line',
                $email_subject
            );

            $query = $wpdb->prepare($query, $data);
            $wpdb->get_results($query);

            //  Add the from line
            $query = "INSERT INTO " . BM_USERMETA . " (umeta_id, user_id, meta_key, meta_value)" .
                "VALUES (%d, %d, %s, %s);";
            $data = array(
                '',
                $id,
                'bm_from_line',
                $email_from
            );

            $query = $wpdb->prepare($query, $data);
            $wpdb->get_results($query);

            //  Add the email body
            $query = "INSERT INTO " . BM_USERMETA . " (umeta_id, user_id, meta_key, meta_value)" .
                "VALUES (%d, %d, %s, %s);";
            $data = array(
                '',
                $id,
                'bm_email_content',
                $email_content
            );

            $query = $wpdb->prepare($query, $data);
            $wpdb->get_results($query);

            //  Add the from name
            $query = "INSERT INTO " . BM_USERMETA . " (umeta_id, user_id, meta_key, meta_value)" .
                "VALUES (%d, %d, %s, %s);";
            $data = array(
                '',
                $id,
                'email_from_name',
                $email_from_name
            );

            $query = $wpdb->prepare($query, $data);
            $wpdb->get_results($query);
        }

    }

    // Pull the information from the database if it is there and populate the form for the returning user


    // $query = "SELECT meta_value FROM " . BM_USERMETA . " WHERE user_id = %d AND meta_key = 'bm_subject_line' OR meta_key = 'bm_from_line' OR meta_key = 'bm_email_content' OR meta_key = 'email_from_name'";
    $query = "SELECT meta_value FROM " . BM_USERMETA . "";
    $where = " WHERE user_id = %d AND meta_key = 'bm_email_content'";
    $data = array(
        $id
    );

    $query = $wpdb->prepare($query . $where, $data);
    $results = $wpdb->get_results($query);

    if ($results) {
        foreach ($results as $record) {
            $copy = stripslashes($record->meta_value);
        }
    }

    $query = "SELECT meta_value FROM " . BM_USERMETA . "";
    $where = " WHERE user_id = %d AND meta_key = 'bm_subject_line'";
    $data = array(
        $id
    );

    $query = $wpdb->prepare($query . $where, $data);
    $results = $wpdb->get_results($query);

    if ($results) {
        foreach ($results as $record) {
            $subject = stripslashes($record->meta_value);
        }
    }

    $query = "SELECT meta_value FROM " . BM_USERMETA . "";
    $where = " WHERE user_id = %d AND meta_key = 'email_from_name'";
    $data = array(
        $id
    );

    $query = $wpdb->prepare($query . $where, $data);
    $results = $wpdb->get_results($query);

    if ($results) {
        foreach ($results as $record) {
            $from = stripslashes($record->meta_value);
        }
    }

    $query = "SELECT meta_value FROM " . BM_USERMETA . "";
    $where = " WHERE user_id = %d AND meta_key = 'bm_from_line'";
    $data = array(
        $id
    );

    $query = $wpdb->prepare($query . $where, $data);
    $results = $wpdb->get_results($query);

    if ($results) {
        foreach ($results as $record) {
            $email_from = stripslashes($record->meta_value);
        }
    }

    // $default_email_copy = '<p>Here is where you can customize your email template.';


    // $copy = ($copy ? $copy : $default_email_copy);
    ?>
    <div class="wrap">
        <form id="admin_email_settings" action="" method="post">
            <table id="email_body_editor">
                <tbody>
                <tr>
                    <td>
                        <h1>Email Configuration and Setup</h1>

                        <p>These settings will allow you to customize the email that is sent to the person you want a
                            quote
                            from.</p>

                        <div>
                            <label for="email_from_name">From Name</label>
                            <input id="email_from_name" value="<?php echo $from ?>" name="email_from_name"
                                   placeholder="Ex: Your Company"/>

                            <p>This defaults to the company name if left blank.</p>
                        </div>
                        <div>
                            <label for="subject_line">Subject Line</label>
                            <input id="subject_line" value="<?php echo $subject ?>" name="subject_line"
                                   placeholder="Ex: You are receiving this email from..."/>

                            <p>This defaults to "Invitation for Quote Response" if left blank.</p>
                        </div>
                        <div>
                            <label for="email_from">From Email</label>
                            <input id="email_from" value="<?php echo $email_from ?>" name="email_from"
                                   placeholder="Ex: abc123@gmail.com" type="email" required/>

                            <p>This defaults to "no-reply@wordpress.org" if left blank.</p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p>Email Body</p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?php
                        $args = array(
                            'media_buttons' => FALSE,
                            'textarea_name' => 'bm_email_body_content'
                        );
                        wp_editor(html_entity_decode($copy), 'email_body', $args)
                        ?>
                        <h2>Configuration notes:</h2>

                        <p id="note_wrapper_1" class="bm_note">
                            &ndash; WordPress does not send
                            mail via SMTP by default. For this reason, the email may or may not end up in your spam/junk
                            folder. We absolutely recommend this plugin to configure SMTP so your mail does not go to
                            anybody's spam/junk: <a href="https://wordpress.org/plugins/wp-mail-smtp/" target="_blank">WP
                                Mail SMTP</a></p>

                        <p class="bm_note">&ndash; The text
                            in the box below will appear <strong><em>after</em></strong> your custom message. It is
                            system
                            text and is mandatory. Feel free to use the customize box above to add anything prior to
                            this
                            text. In addition, it is always a good idea to test an email to yourself first, before
                            sending
                            it out.</p>

                        <p class="bm_example"><em>"Please follow the link below to sign in and review the quote request.<br><br><a>Click
                                    here to view and respond</a>."</em></p>
                    </td>
                </tr>
                </tbody>
            </table>
            <input class="button-primary" type="submit" name="bm_admin_email_settings" value="Save &raquo;"/>
        </form>
    </div>
    <?php

}

function bm_settings()
{
    global $wpdb;
    global $current_user;
    get_currentuserinfo();

    $id = $current_user->ID;

    $content = '';

    if ($_GET['page'] == 'bm_settings') {
//        $content .= '<nav class="bm_plugin_settings">
//
//        <li><a href="#one">One</a></li>
//        <li><a href="#two">Two</a></li>
//
//        </nav>';
        $content .= '<div class="wrap">';
        $content .= '<h1>Bid Management Settings</h1>';
        $content .= '<h2>Invitation Details Page</h2>';
        $content .= '<p>Put this shortcode on the page you select below: <strong>[bm-invite]</strong></p>';
        $content .= '<p>If you don\'t put this shortcode on the page you select, you will not have a page for the bid information to be displayed for whoever you want to get a response from.  This <strong>MUST</strong> be done in order to send email requests to people for quote responses.</p>';

        $query = "SELECT meta_value FROM " . BM_USERMETA . " WHERE user_id = %d AND meta_key = 'bm_invite_page'";
        $data = array(
            $id
        );

        $query = $wpdb->prepare($query, $data);
        $results = $wpdb->get_results($query);

        foreach ($results as $record) {
            $link_id = $record->meta_value;
        }

        $defaults = array(
            'depth' => 0,
            'child_of' => 0,
            'selected' => $link_id,
            'echo' => 0,
            'name' => 'page_id',
            'id' => null, // string
            'class' => null, // string
            'show_option_none' => 'Please select a page', // string
            'show_option_no_change' => null, // string
            'option_none_value' => null, // string
        );

        $content .= '
    <form id="invite_scode_page" method="post" action="">

    <p>' . wp_dropdown_pages($defaults) . '</p>
    <h2>Google Maps API Key</h2>';

        $query = "SELECT meta_value FROM " . BM_USERMETA . " WHERE user_id = %d AND meta_key = 'bm_google_api_key'";
        $data = array(
            $id
        );

        $query = $wpdb->prepare($query, $data);
        $results = $wpdb->get_results($query);

        foreach ($results as $record) {
            $key = $record->meta_value;
        }

        $content .= '<p><input id="google_maps_api" class="" type="text" value="' . $key . '" name="google_maps_api" size="50"></p>
                    <p>This will put a Google Map with your bids pinned to it at the bottom of your <a href="' . BM_CINFO . '">company information page</a>.</p>
                    <p>If you need help getting a Google Maps API key, you can <a target="_blank" href="https://developers.google.com/maps/documentation/javascript/get-api-key?hl=en">get started here</a>.</p>
                    <p><input class="button-primary" type="submit" value="Submit &raquo;" name="bm_settings_save" /></p>
                    </form></div>';


        if (isset($_POST['bm_settings_save'])) {

            // Write the permalink ID to the database if page selected
            if ($_POST['page_id']) {

                $query = "INSERT INTO " . BM_USERMETA . " (umeta_id, user_id, meta_key, meta_value)" .
                    "VALUES (%d, %d, %s, %s);";
                $data = array(
                    '',
                    $id,
                    'bm_invite_page',
                    $_POST['page_id']
                );

                $query = $wpdb->prepare($query, $data);
                $wpdb->get_results($query);
            }

            if ($_POST['google_maps_api']) {

                if (empty($_POST['google_maps_api'])) {
                    $_POST['google_maps_api'] = '%20';
                }

                $query = "SELECT meta_value FROM " . BM_USERMETA . " WHERE user_id = %d AND meta_key = 'bm_google_api_key'";
                $data = array(
                    $id
                );

                $query = $wpdb->prepare($query, $data);
                $results = $wpdb->get_results($query);

                if ($results) {

                    $query = "UPDATE " . BM_USERMETA . " SET meta_value = %s WHERE user_id = %d AND meta_key = 'bm_google_api_key'";
                    $data = array(
                        $_POST['google_maps_api'],
                        $id
                    );

                    $query = $wpdb->prepare($query, $data);
                    $wpdb->get_results($query);
                } else {

                    $query = "INSERT INTO " . BM_USERMETA . " (umeta_id, user_id, meta_key, meta_value)" .
                        "VALUES (%d, %d, %s, %s);";
                    $data = array(
                        '',
                        $id,
                        'bm_google_api_key',
                        $_POST['google_maps_api']
                    );

                    $query = $wpdb->prepare($query, $data);
                    $wpdb->get_results($query);
                }
            }

        } else {
            echo $content;
        }

    }

    if (isset($_POST['bm_settings_save'])) {

        $bm_settings = BM_BMSETTINGS;

        $content = '<p class="success">Your settings have been saved.</p>';
        $content .= '<p><a href="' . $bm_settings . '">&laquo Back to Settings</a></p>';

        echo $content;
    }

}


function bm_activate()
{


    // Make the directories
    $upload = wp_upload_dir();
    $upload_dir = $upload['basedir'];
    $upload_dir = $upload_dir . '/bid_requests';  // Bid requests file folder
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0700);
    }


    $upload = wp_upload_dir();
    $upload_dir = $upload['basedir'];
    $upload_dir = $upload_dir . '/bid_responses';  //  Bid responses file folder
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0700);
    }

    //Check to see if we need to write the tables to the DB
    bm_user_check();
    bm_email_check();
    bm_responder_check();
    bm_bids_check();
    bm_responses_check();
    bm_notifications_check();

    // This injects the notification to the bm_notifications table
    bm_notice_injection();  // Turn this on if there is a notification to be run in the notifications.php file

}

register_activation_hook(__FILE__, 'bm_activate');

function bm_plugin_version()
{
    require_once('includes/updates.php');

    /*
     * We initially want to setup a version number.  Either inserting it or updating it.  Then we can do some checks against it.
     */

    $current_version = (float)1.17;  //  Set my version #
    $option = 'bm_plugin_version';

    //  Find if the option exists
    $db_version = (float)get_option($option);

    if (!$db_version) { // If the option does not exist, write it
        bm_update_02162016();
        add_option($option, $current_version);

    }

    if ($db_version < $current_version) {
        bm_update_02162016();
        update_option($option, $current_version);
    }

}

add_action('admin_init', 'bm_plugin_version');


/*
 * The following is where we tie into actions/hooks/filters, etc to harness WordPress native functionality
 */

//  Creates the admin menu on the left hand navigation
add_action('admin_menu', 'bid_manager_menu');

// Tap into the admin_init() so we can enque/register/deregister any styles or scripts
add_action('admin_init', 'bm_head');