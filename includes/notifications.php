<?php
function sc_show_notifications() {
    global $wpdb;

    $query = "SELECT * FROM " . BM_NOTIFICATIONS . " WHERE dont_show = 1";

    $data = array();
    $query   = $wpdb->prepare( $query, $data );
    $results = $wpdb->get_results( $query );

    if ($results) {
        foreach ($results as $record) {

            $notificationid = $record->id;
            $notification = $record->notification;

            $content = '<p class="bm_notification_' . $notificationid . ' bm_notification"><span style="float: right; padding-left: 50px;"><label for="' . $notificationid . '">Dismiss</label> <input id="' . $notificationid . '" class="hide_notification_checkbox" type="checkbox"></span>' . $notification . '</p>';
            echo $content;
        }
    }
}

function bm_notice_injection() {

    global $wpdb;

    /*
     * First notification
     */

    $the_notification = 'Thank you for downloading the WP Bid Manager.  We are proud to offer you bid management for your WordPress dashboard.  If you have any questions or concerns, please feel free to reach out to us at <a href="mailto:contact@supplyingcontractors.com">contact@supplyingcontractors.com</a>.'; // The notification to inject to the bm_notifications table **No <p> tags allowed

    //  Add the subject line
    $query = "INSERT INTO " . BM_NOTIFICATIONS . " (id, notification, dont_show)" .
        "VALUES (%d, %s, %d);";
    $data  = array(
        '',
        $the_notification,
        1
    );

    $query   = $wpdb->prepare( $query, $data );
    $wpdb->get_results( $query );

}