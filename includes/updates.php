<?php


// Very first update
function bm_update_02162016() {

    global $wpdb;
    global $table_prefix;

    /*
     * Up until version 1.1.3 we did not include the wp prefix for the tables.
     * Therefore, we have to update all old table names to the new structure
     */

    // Change the bm_notifications table to whatever the users prefix is
    $query = "RENAME TABLE `bm_notifications` TO `" . $table_prefix . "bm_notifications`";
    $data = array();

    $query = $wpdb->prepare($query, $data);
    $wpdb->get_results($query);

    // Change the bm_bids table to whatever the users prefix is
    $query = "RENAME TABLE `bm_bids` TO `" . $table_prefix . "bm_bids`";
    $data = array();

    $query = $wpdb->prepare($query, $data);
    $wpdb->get_results($query);

    // Change the bm_bids_responses table to whatever the users prefix is
    $query = "RENAME TABLE `bm_bids_responses` TO `" . $table_prefix . "bm_bids_responses`";
    $data = array();

    $query = $wpdb->prepare($query, $data);
    $wpdb->get_results($query);

    // Change the bm_responder table to whatever the users prefix is
    $query = "RENAME TABLE `bm_responder` TO `" . $table_prefix . "bm_responder`";
    $data = array();

    $query = $wpdb->prepare($query, $data);
    $wpdb->get_results($query);

    // Change the bm_user table to whatever the users prefix is
    $query = "RENAME TABLE `bm_user` TO `" . $table_prefix . "bm_user`";
    $data = array();

    $query = $wpdb->prepare($query, $data);
    $wpdb->get_results($query);

    // Change the bm_responder_emails table to whatever the users prefix is
    $query = "RENAME TABLE `bm_responder_emails` TO `" . $table_prefix . "bm_responder_emails`";
    $data = array();

    $query = $wpdb->prepare($query, $data);
    $wpdb->get_results($query);

}