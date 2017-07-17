<?php
// Shortcode for user to display on the page they want the bid request to be visible for the responder
function bm_responder_invitation() {

	global $wpdb;

	$hash = $_GET['hash'];
	$invite = $_GET['invitation_id'];

	$query = "SELECT bid_id, hash FROM " . BM_EMAILS . " WHERE bid_id = %d AND hash = %s";
	$data  = array(
        $invite,
        $hash
    );

	$query   = $wpdb->prepare( $query, $data );
	$results = $wpdb->get_results( $query );

	foreach ($results as $record) {
        $dbBid  = $record->bid_id;
        $dbHash = $record->hash;
    }

	$content  = '';

	if (($invite === $dbBid) && ($hash === $dbHash)) {
        $content .= bm_responder_responses();
    } else {
        $content .= '<p>This page is to display bid information for the invited supplier.  Please check the email you were invited from and click the link that was provided.  By doing so, it will populate the appropriate information so you can respond.</p>';
    }

	echo $content;
}

add_shortcode('bm-invite', 'bm_responder_invitation');