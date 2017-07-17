<?php
function bm_hide_notes() {
    global $wpdb;

    if ($_POST['inputid']) {
        $query = "UPDATE " . BM_NOTIFICATIONS . " SET dont_show = 0 WHERE id = %d";
        $data  = array(
            $_POST['inputid']
        );

        $query   = $wpdb->prepare( $query, $data );
        $wpdb->get_results( $query );
    }
    wp_die();
}

add_action( 'wp_ajax_my_action', 'bm_hide_notes' );
