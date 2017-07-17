<?php
function bm_user_emails() {

	global $wpdb;
	global $current_user;
	get_currentuserinfo();

	$bm_user_id = $current_user->ID;

	$bm_bid_id = ( $_GET['bid_id'] ? (int) $_GET['bid_id'] : (int) $_GET['bid_accepted'] );
	$responseId = (int) $_GET['response_id'];

	//  Send this email to the responder if the requester accepts the bid
	if ( sanitize_text_field( $_GET['accept'] ) == "true" ) {


		$query = "SELECT job_name, job_street, job_street_two, job_city, job_state, job_zip, responder_poc, bmuser_busname, bmuser_poc, bmuser_phone, bmuser_email, bmuser_street, bmuser_street_two, bmuser_city, bmuser_state, bmuser_zip, responder_poc, responder_email FROM " . BM_BIDS_RESPONSES . " LEFT OUTER JOIN " . BM_BIDS . " ON " . BM_BIDS . ".bid_id=" . BM_BIDS_RESPONSES . ".bid_id LEFT OUTER JOIN " . BM_USER . " ON " . BM_USER . ".id=%d WHERE " . BM_BIDS_RESPONSES . ".id = %d AND " . BM_BIDS_RESPONSES . ".bid_id = %d";
		$data  = array(
			$bm_user_id,
			$responseId,
			$bm_bid_id
		);

		$query   = $wpdb->prepare( $query, $data );
		$results = $wpdb->get_results( $query );

		foreach ( $results as $record ) {
			$conEmail   = stripslashes( $record->bmuser_email );
			$bm_responder_email   = stripslashes( $record->responder_email );

			$bm_user_busname = stripslashes( $record->bmuser_busname );
			$bm_user_poc     = stripslashes( $record->bmuser_poc );
			$bm_user_phone   = $record->bmuser_phone;
			$bm_user_street  = $record->bmuser_street;
			$bm_user_street2 = ( $record->bmuser_street_two ? $record->bmuser_street_two : '' );
			$bm_user_city    = $record->bmuser_city;
			$bm_user_state   = $record->bmuser_state;
			$bm_user_zip     = $record->bmuser_zip;
			$bm_job_name    = stripslashes( $record->job_name );
			$bm_job_street  = stripslashes( $record->job_street );
			$bm_job_street2 = ( $record->job_street_two ? $record->job_street_two : '' );
			$bm_job_city    = $record->job_city;
			$bm_job_state   = $record->job_state;
			$bm_job_zip     = $record->job_zip;
			$bm_responder_poc     = $record->responder_poc;
		}

		$to = $bm_responder_email; // . $supCCEmail;

		$subject = $bm_user_busname . ' Accepts Your Bid for - ' . $bm_bid_id; // The subject of the email

		$message = '<p>Hello ' . $bm_responder_poc . '!</p>'; // begins the message
		$message .= '<p>' . $bm_user_busname . ' has accepted your bid for bid #' . $bm_bid_id . '</p>';
		$message .= '<p>From here, you can reach out to ' . $bm_user_poc . ' at ' . $bm_user_busname . ' and setup payment options to complete the transaction.</p>';
		$message .= '<p style="font-size: 18px; font-weight: bold;">Contact Information:</p>';
		$message .= '<p>Point of Contact: ' . $bm_user_poc . '</p>';
		$message .= '<p>Street: ' . $bm_user_street . '</p>';
		if ( $bm_user_street2 ) {
			$message .= '<p>Street Cont.: ' . $bm_user_street2 . '</p>';
		}
		$message .= '<p>City: ' . $bm_user_city . '</p>';
		$message .= '<p>State: ' . $bm_user_state . '</p>';
		$message .= '<p>ZIP Code: ' . $bm_user_zip . '</p>';
		$message .= '<p>Phone: ' . $bm_user_phone . '</p>';
		$message .= '<p><a href="' . $conEmail . '">Email ' . $bm_user_poc . '</a></p>';
		$message .= '<p style="font-size: 18px; font-weight: bold;">Job Details:</p>';
		$message .= '<p>Job Name: ' . $bm_job_name . '</p>';
		$message .= '<p>Street: ' . $bm_job_street . '</p>';
		if ( $bm_job_street2 ) {
			$message .= '<p>Street Cont.: ' . $bm_job_street2 . '</p>';
		}
		$message .= '<p>City: ' . $bm_job_city . '</p>';
		$message .= '<p>State: ' . $bm_job_state . '</p>';
		$message .= '<p>ZIP: ' . $bm_job_zip . '</p>'; // ends the message

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: WP Bid Manager <no-reply@wordpress.org>'
		);

		wp_mail( $to, $subject, $message, $headers );
	}


	//  Send this email to the responder if the requester retracts the bid
	if ( isset($_POST['retract_bid']) && sanitize_text_field( $_POST['retractbid'] ) == 'RETRACT' ) {

		$query = "SELECT bmuser_email, responder_email FROM " . BM_BIDS_RESPONSES . " LEFT OUTER JOIN " . BM_USER . " ON " . BM_USER . ".id=%d  WHERE " . BM_BIDS_RESPONSES . ".id = %d AND " . BM_BIDS_RESPONSES . ".bid_id = %d";
		$data  = array(
			$bm_user_id,
			$responseId,
			$bm_bid_id
		);

		$query   = $wpdb->prepare( $query, $data );
		$results = $wpdb->get_results( $query );

		foreach ( $results as $record ) {
			$bm_responder_email = stripslashes( $record->responder_email );
		}

		$to = $bm_responder_email;

		$subject = 'The Requester Retracted Bid - ' . $bm_bid_id;

		$retractMessage = $_POST['retract_message'];
		$retractMessage = stripslashes($retractMessage);

		$message = '<p>Hello,</p>';
		$message .= '<p>The requester retracted bid #' . $bm_bid_id . '</p>';
		$message .= '<p>Reason for Retracting:</p>';
		$message .= html_entity_decode($retractMessage);

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: WP Bid Manager <no-reply@wordpress.org>'
		);

		wp_mail( $to, $subject, $message, $headers );
	}
}

function bm_retract_bid() {
	global $wpdb;

	$bm_bid_id = $_GET['bid_accepted'];
	$responseId = $_GET['response_id'];

	if ( sanitize_text_field( $_POST['retractbid'] ) !== "RETRACT" ) {
		echo '<p class="error">You must enter the word RETRACT, in all caps, in the field to retract this bid.</p>';
	}

	//  Update the bids table to reflect the bid is not accepted
	$query = "UPDATE " . BM_BIDS . " SET accepted_flag = 0 WHERE bid_id = %d";
	$data  = array(
		$bm_bid_id
	);

	$query   = $wpdb->prepare( $query, $data );
	$wpdb->query( $query );

	//  Update the bids responses table to reflect the bid is not accepted
	$query = "UPDATE " . BM_BIDS_RESPONSES . " SET bid_accepted = 0 WHERE bid_id = %d AND id = %d";
	$data  = array(
		$bm_bid_id,
		$responseId
	);

	$query   = $wpdb->prepare( $query, $data );
	$wpdb->query( $query );

	bm_user_emails();


}


function bm_create_bid() {
	global $wpdb;
	global $current_user;
	get_currentuserinfo();

	$bm_user_id = $current_user->ID;

	$bm_job_name      = sanitize_text_field( $_POST['job_name'] );
	$dateNeeded   = sanitize_text_field( $_POST['date_needed'] );
	$bm_job_street    = sanitize_text_field( $_POST['job_street'] );
	$bm_job_streetTwo = sanitize_text_field( $_POST['job_street_two'] );
	$bm_job_city      = sanitize_text_field( $_POST['job_city'] );
	$bm_job_state     = sanitize_text_field( $_POST['job_state'] );
	$bm_job_zip       = sanitize_text_field( $_POST['job_zip'] );

	$address = $bm_job_street . ', ' . $bm_job_city . ' ' . $bm_job_state . ' ' . $bm_job_zip;

	$geocode = bm_get_lat_and_lng( $address );
	if ( $geocode !== FALSE ) {
		// save $geocode[�lat�] and $geocode[�lng�] to database
		$bm_lat = $geocode['lat'];
		$bm_lng = $geocode['lng'];
	}

	// Converts a date field to MYSQL standard: ex: "5/19/2015" => "2015-5-19 23:15:05"
	$dateNeeded = date( 'Y-m-d H:i:s', strtotime( $dateNeeded ) );

	$bm_file_path = bm_handle_file_upload( 'bmuser_bid_file', 'bid_requests/' );

	$query = "INSERT INTO " . BM_BIDS . " (bmuser_id, job_name, date_needed, bmuser_bid_file, job_street, job_street_two, job_city, job_state, job_zip, lat, lng)" .
		"VALUES (%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);";
	$data  = array(
		$bm_user_id,
		$bm_job_name,
		$dateNeeded,
		$bm_file_path,
		$bm_job_street,
		$bm_job_streetTwo,
		$bm_job_city,
		$bm_job_state,
		$bm_job_zip,
		$bm_lat,
		$bm_lng
	);

	$query   = $wpdb->prepare( $query, $data );
	$wpdb->query( $query );
}

function bm_hide_bid() {

	global $wpdb;

	$bm_bid_id = (int) $_GET['bid_id'];
	// echo $bm_bid_id;
	$responseId = (int) $_GET['response_id'];
	// echo $responseId;

	$query = "UPDATE " . BM_BIDS_RESPONSES . " SET hidden = 1 WHERE id = %d AND bid_id = %d";
	$data  = array(
		$responseId,
		$bm_bid_id
	);

	$query   = $wpdb->prepare( $query, $data );
	$wpdb->query( $query );

	if ($_GET['hide'] == 'true') {

		$link = 'admin.php';
		$params = array( 'page' => 'bid_manager_dashboard', 'bid_response' => $bm_bid_id );
		$link = add_query_arg( $params, $link );
		$link = esc_url($link, '', 'db');

		$content  = '<p class="success">You have successfully hidden this bid.</p>';
		$content .= '<p><a href="' . $link . '">&laquo Back to Bid</a></p>';

		echo $content;
	}
}

function bm_unhide_bid() {

	global $wpdb;

	$bm_bid_id = (int) $_GET['bid_id'];
	$responseId = (int) $_GET['response_id'];

	$query = "UPDATE " . BM_BIDS_RESPONSES . " SET hidden = 0 WHERE bid_id = %d AND id = %d";
	$data  = array(
		$bm_bid_id,
		$responseId
	);

	$query   = $wpdb->prepare( $query, $data );
	$wpdb->query( $query );

	if ($_GET['unhide'] == 'true') {

		$link = 'admin.php';
		$params = array( 'page' => 'bid_manager_dashboard', 'bid_response' => $bm_bid_id );
		$link = add_query_arg( $params, $link );
		$link = esc_url($link, '', 'db');

		$content  = '<p class="success">You have un-hidden this bid.</p>';
		$content .= '<p><a href="' . $link . '">&laquo Back to Bid</a></p>';

		echo $content;
	}

}

function bm_view_hidden() {

	global $wpdb;
	global $current_user;
	get_currentuserinfo();

	$bm_bid_id = (int) $_GET['bid_id'];

	$bm_user_id = $current_user->ID;

	$query = "SELECT " . BM_BIDS_RESPONSES . ".id, " . BM_BIDS_RESPONSES . ".bid_id, " . BM_BIDS_RESPONSES . ".responder_busname, " . BM_BIDS_RESPONSES . ".responder_poc, " . BM_BIDS_RESPONSES . ".responder_phone, " . BM_BIDS_RESPONSES . ".responder_email, " . BM_BIDS_RESPONSES . ".quoted_total FROM " . BM_BIDS_RESPONSES . " LEFT OUTER JOIN " . BM_USER . " ON " . BM_USER . ".id=%d WHERE bid_id = %d AND hidden = 1";
	$data  = array(
		$bm_user_id,
		$bm_bid_id
	);

	$query   = $wpdb->prepare( $query, $data );
	$results = $wpdb->get_results( $query );

	$hiddenBids = '';

	if ( $results ) {
		$hiddenBids .= <<<HIDDENBIDS
		
		<div class="responder_response blue_table">
		<h2>Bids you have hidden from your results</h2>
		<table class="form-table blue_table" border="1" bordercolor="#000">
		<thead>
		<tr>
		<th>
		Name
		</th>
		<th>
		Point of Contact
		</th>
		<th>
		Phone
		</th>
		<th>
		Email
		</th>
		<th>
		Quote Amount
		</th>
		<th>
		Show
		</th>
		</tr>
		</thead>
		<tbody>
HIDDENBIDS;

		foreach ( $results as $record ) {

			$link = 'admin.php';
			$params = array( 'page' => 'bid_manager_dashboard', 'bid_id' => $record->bid_id, 'response_id' => $record->id, 'unhide' => 'true' );
			$link = add_query_arg( $params, $link );
			$link = esc_url($link, '', 'db');

			$row = '<tr>';
			$row .= '<td>' . stripslashes( $record->responder_busname ) . '</td>';
			$row .= '<td>' . stripslashes( $record->responder_poc ) . '</td>';
			$row .= '<td>' . $record->responder_phone . '</td>';
			$row .= '<td><a href="mailto:' . stripslashes( $record->responder_email ) . '">' . stripslashes( $record->responder_email ) . '</a></td>';
			$row .= '<td>$' . number_format( $record->quoted_total, 2 ) . '</td>';
			$row .= '<td><a class="button" href="' . $link . '">Un-hide &raquo;</a></td>';
			$row .= '</tr>';

			$hiddenBids .= $row;
		}

		$hiddenBids .= <<<HIDDENBIDS
		</tbody>
		</table>
		</div>
HIDDENBIDS;

		echo $hiddenBids;

	} else {
		$dboard = BM_CDBOARD;
		$hiddenBids .= <<<HIDDENBIDS
			<p>You do not have any hidden bids to show.</p>
			<p><a class="button" href="{$dboard}">&laquo; Back to Dashboard</a></p>
HIDDENBIDS;

		echo $hiddenBids;
	}
}

function bm_create_user_record() {
	global $wpdb;
	global $current_user;
	get_currentuserinfo();

	$bm_user_id = $current_user->ID;

	$content = '';

	$bm_user_business     = sanitize_text_field( $_POST['comp_info_0'] );
	$bm_user_poc     = sanitize_text_field( $_POST['comp_info_1'] );
	$bm_user_phone   = sanitize_text_field( $_POST['comp_info_2'] );
	$bm_user_email   = sanitize_text_field( $_POST['comp_info_3'] );
	$bm_user_street  = sanitize_text_field( $_POST['comp_info_4'] );
	$bm_user_street2 = sanitize_text_field( $_POST['comp_info_5'] );
	$bm_user_city    = sanitize_text_field( $_POST['comp_info_6'] );
	$bm_user_state   = sanitize_text_field( $_POST['comp_info_7'] );
	$bm_user_zip     = sanitize_text_field( $_POST['comp_info_8'] );

	$address = $bm_user_street . ', ' . $bm_user_city . ' ' . $bm_user_state . ' ' . $bm_user_zip;

	$geocode = bm_get_lat_and_lng( $address );
	if ( $geocode !== FALSE ) {
		// save $geocode[�lat�] and $geocode[�lng�] to database
		$bm_lat = $geocode['lat'];
		$bm_lng = $geocode['lng'];
	}

	$query = "INSERT INTO " . BM_USER . " (id, bmuser_busname, bmuser_poc, bmuser_phone, bmuser_email, bmuser_street, bmuser_street_two, bmuser_city, bmuser_state, bmuser_zip, lat, lng)" .
		"VALUES (%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);";
	$data  = array(
		$bm_user_id,
		$bm_user_business,
		$bm_user_poc,
		$bm_user_phone,
		$bm_user_email,
		$bm_user_street,
		$bm_user_street2,
		$bm_user_city,
		$bm_user_state,
		$bm_user_zip,
		$bm_lat,
		$bm_lng
	);

	$query   = $wpdb->prepare( $query, $data );
	$wpdb->query( $query );

	$success = '<p class="success">Success! You have successfully added your company information.</p>';

	$content .= $success;
	echo $content;

}

function bm_user_bid_review( $error = '' ) {
	global $wpdb;
	global $current_user;
	get_currentuserinfo();

	$bm_user_id = $current_user->ID;

	if ( (int) $_GET['bmuser_bid'] ) {
		$recordId = (int) $_GET['bmuser_bid'];
	} elseif ( (int) $_GET['bmuser_bid_history'] ) {
		$recordId = (int) $_GET['bmuser_bid_history'];
	} elseif ( (int) $_GET['bid_response'] ) {
		$recordId = (int) $_GET['bid_response'];
	}

	$query   = "SELECT * FROM " . BM_BIDS . " WHERE bid_id = %d AND bmuser_id = %d";
	$data    = array( $recordId, $bm_user_id );
	$query   = $wpdb->prepare( $query, $data );
	$results = $wpdb->get_results( $query );

	foreach ( $results as $record ) {
		$bm_bid_id          = $record->bid_id;
		$bm_job_name        = stripslashes( $record->job_name );
		$requiredBy     = date('F jS, Y', strtotime($record->date_needed));
		$bm_job_street      = stripslashes( $record->job_street );
		$bm_job_street2     = ( $record->job_street_two ? $record->job_street_two : '' );
		$bm_job_city        = stripslashes( $record->job_city );
		$bm_job_state       = stripslashes( $record->job_state );
		$bm_job_zip         = $record->job_zip;
		$bm_user_file = stripslashes( $record->bmuser_bid_file );
	}

	$bm_user_bid_review = <<<CONTRACTORBIDREVIEW

	<div id="bid_response_bid_info" class="original_bid_info">
	<h1>Bid Request for: {$bm_job_name}</h1>

	<table class="form-table">
	<tr>
	<th scope="row">Bid ID#:</th>
	<td>{$bm_bid_id}</td>
	</tr>
	<tr>
	<th scope="row">Job Name:</th>
	<td>{$bm_job_name}</td>
	</tr>
	<tr>
	<th scope="row">Need Quote By:</th>
	<td>{$requiredBy}</td>
	</tr>
	<tr>
	<th scope="row">Address:</th>
	<td>{$bm_job_street}</td>
	</tr>
CONTRACTORBIDREVIEW;

	if ( $bm_job_street2 ) {
		$bm_user_bid_review .= <<<CONTRACTORBIDREVIEW
	<tr>
	<th scope="row">Address Cont.:</th>
	<td>{$bm_job_street2}</td>
	</tr>
CONTRACTORBIDREVIEW;
	}

	$bm_user_bid_review .= <<<CONTRACTORBIDREVIEW
	<tr>
	<th scope="row">City:</th>
	<td>{$bm_job_city}</td>
	</tr>
	<tr>
	<th scope="row">State:</th>
	<td>{$bm_job_state}</td>
	</tr>
	<tr>
	<th scope="row">ZIP:</th>
	<td>{$bm_job_zip}</td>
	</table>
	</div>
	<div id="bid_responder_responses">
    <p><a class="button-primary" href="{$bm_user_file}">Download original material list for: {$bm_job_name} &raquo;</a></p>
	<h2>Bid Responses</h2>
CONTRACTORBIDREVIEW;


	$query   = "SELECT * FROM " . BM_BIDS_RESPONSES . " WHERE bid_id = %d AND hidden = 0";
	$data    = array(
		$recordId
	);

	$query   = $wpdb->prepare( $query, $data );
	$results = $wpdb->get_results( $query );

	if ( $results ) {
		$bm_user_bid_review .= <<<CONTRACTORBIDREVIEW
	
		<div class="responder_response blue_table">
		<table id="bm_responses_list" class="form-table blue_table" border="1" bordercolor="#000">
		<thead>
		<tr>
		<th>
		Name
		</th>
		<th>
		Point of Contact
		</th>
		<th>
		Phone
		</th>
		<th>
		Email
		</th>
		<th>
		Quote Amount
		</th>
		<th>
		Responses
		</th>
		<th>
		Don't Show
		</th>
		<th>
		Accept Bid
		</th>
		</tr>
		</thead>
		<tbody>
CONTRACTORBIDREVIEW;
		foreach ( $results as $record ) {
			$row = '<tr>';
			$row .= '<td>' . stripslashes( $record->responder_busname ) . '</td>';
			$row .= '<td>' . stripslashes( $record->responder_poc ) . '</td>';
			$row .= '<td>' . $record->responder_phone . '</td>';
			$row .= '<td><a href="mailto:' . stripslashes( $record->responder_email ) . '">' . stripslashes( $record->responder_email ) . '</a></td>';
			$row .= '<td>$' . number_format( $record->quoted_total, 2 ) . '</td>';

			if ( ! empty( $record->responder_bid_file ) ) {

				$row .= '<td><a class="button button_blue" href="' . $record->responder_bid_file . '">View &raquo;</a>';

			} else {
				$row .= '<td>Not Available</td>';
			}

			$link = 'admin.php';
			$params = array( 'page' => 'bid_manager_dashboard', 'bid_id' => $record->bid_id, 'response_id' => $record->id, 'hide' => 'true' );
			$link = add_query_arg( $params, $link );
			$link = esc_url($link, '', 'db');

			$row .= '<td><a class="button button_red" href="' . $link . '">Hide &raquo;</a></td>';

			$link = 'admin.php';
			$params = array( 'page' => 'bid_manager_dashboard', 'bid_id' => $record->bid_id, 'response_id' => $record->id, 'accept' => 'true' );
			$link = add_query_arg( $params, $link );
			$link = esc_url($link, '', 'db');

			$row .= '<td><a class="button" href="' . $link . '">Accept &raquo;</a></td>';
			// $row . '<td>' . $record->bid_accepted . '</td>';
			$row .= '</tr>';

			$bm_user_bid_review .= $row;
		}

		$bm_user_bid_review .= <<<CONTRACTORBIDREVIEW
		</tbody>
		</table>
		</div>
CONTRACTORBIDREVIEW;

		$query   = "SELECT hidden FROM " . BM_BIDS_RESPONSES . " LEFT OUTER JOIN " . BM_BIDS . " ON " . BM_BIDS . ".bid_id=" . BM_BIDS_RESPONSES . ".bid_id WHERE " . BM_BIDS . ".bid_id = %d AND bmuser_id = %d AND hidden = 1";
		$data    = array( $recordId, $bm_user_id );
		$query   = $wpdb->prepare( $query, $data );
		$results = $wpdb->get_results( $query );

		if ( $results ) {

			$link = 'admin.php';
			$params = array( 'page' => 'bid_manager_dashboard', 'bid_id' => $record->bid_id, 'view_hidden' => 'true' );
			$link = add_query_arg( $params, $link );
			$link = esc_url($link, '', 'db');

			$bm_user_bid_review .= <<<CONTRACTORBIDREVIEW
			<p><a class="button" href="' . $link . '">View Hidden Bids &raquo;</a></p>
			</div>
			</div>
CONTRACTORBIDREVIEW;

			echo $bm_user_bid_review;
			exit;
		} else {
			$bm_user_bid_review .= <<<CONTRACTORBIDREVIEW
		</div>
CONTRACTORBIDREVIEW;

			echo $bm_user_bid_review;
		} // Ends hidden table display
	} else {
		$bm_user_bid_review .= <<<CONTRACTORBIDREVIEW
		<p>There are no reviews to this bid.</p>
CONTRACTORBIDREVIEW;

		$query   = "SELECT hidden FROM " . BM_BIDS_RESPONSES . " LEFT OUTER JOIN " . BM_BIDS . " ON " . BM_BIDS . ".bid_id=" . BM_BIDS_RESPONSES . ".bid_id WHERE " . BM_BIDS . ".bid_id = %d AND bmuser_id = %d AND hidden = 1";
		$data    = array( $recordId, $bm_user_id );
		$query   = $wpdb->prepare( $query, $data );
		$results = $wpdb->get_results( $query );

		if ( $results ) {

			$link = 'admin.php';
			$params = array( 'page' => 'bid_manager_dashboard', 'bid_id' => $record->bid_id, 'view_hidden' => 'true' );
			$link = add_query_arg( $params, $link );
			$link = esc_url($link, '', 'db');

			$bm_user_bid_review .= <<<CONTRACTORBIDREVIEW
			<p><a class="button" href="{$link}">View Hidden Bids &raquo;</a></p>
CONTRACTORBIDREVIEW;

		}

		echo $bm_user_bid_review;
	}// Ends check for records
}

function bm_user_bid_active() {
	global $wpdb;
	global $current_user;
	get_currentuserinfo();

	$bm_user_id = $current_user->ID;

	$activeRecord = sanitize_text_field( $_GET['bmuser_bid_active'] );

	$query   = "SELECT * FROM " . BM_BIDS . " WHERE bid_id = %d AND bmuser_id = %d";
	$data    = array(
		$activeRecord,
		$bm_user_id
	);
	$query   = $wpdb->prepare( $query, $data );
	$results = $wpdb->get_results( $query );

	foreach ( $results as $record ) {
		$bm_bid_id          = $record->bid_id;
		$bm_job_name        = stripslashes( $record->job_name );
		$requiredBy     = $record->date_needed;
		$bm_job_street      = stripslashes( $record->job_street );
		$bm_job_city        = stripslashes( $record->job_city );
		$bm_job_street2     = ( $record->job_street_two ? $record->job_street_two : '' );
		$bm_job_state       = stripslashes( $record->job_state );
		$bm_job_zip         = $record->job_zip;
		$bm_user_file = stripslashes( $record->bmuser_bid_file );
	}

	$requiredBy = date( 'F dS, Y', strtotime( $requiredBy ) );

	$bm_user_bid_active = <<<CONTRACTORBIDACTIVE
	
	<h1>Bid Request for: {$bm_job_name}</h1>
	
	<table class="form-table">
	<tr>
	<th scope="row">Bid ID#:</th>
	<td>{$bm_bid_id}</td>
	</tr>
	<tr>
	<th scope="row">Job Name:</th>
	<td>{$bm_job_name}</td>
	</tr>
	<tr>
	<th scope="row">Need Quote By:</th>
	<td>{$requiredBy}</td>
	</tr>
	<tr>
	<th scope="row">Street:</th>
	<td>{$bm_job_street}</td>
	</tr>
CONTRACTORBIDACTIVE;

	if ( $bm_job_street2 ) {
		$bm_user_bid_active .= <<<CONTRACTORBIDACTIVE
	<tr>
	<th scope="row">Address Cont.:</th>
	<td>{$bm_job_street2}</td>
	</tr>
CONTRACTORBIDACTIVE;
	}

	$bm_user_bid_active .= <<<CONTRACTORBIDACTIVE
	<tr>
	<th scope="row">City:</th>
	<td>{$bm_job_city}</td>
	</tr>
	<tr>
	<th scope="row">State:</th>
	<td>{$bm_job_state}</td>
	</tr>
	<tr>
	<th scope="row">ZIP:</th>
	<td>{$bm_job_zip}</td>
	</tr>
	</table>
	<p><a class="button button_blue download" href="{$bm_user_file}">Download original bid request for:  {$bm_job_name}</a></p>
CONTRACTORBIDACTIVE;

	$bm_user_bid_active .= bm_responder_invite($bm_bid_id);

	$bm_email_settings = BM_EMAIL_SETTINGS;

	$content = <<<SUPINVITE
	<form action="" method="post">
	<h2>Invitation Email</h2>
	<p><button id="addScnt" class="button-secondary">Add another email</button></p>
	<div class="sc_email_wrap">
	<div class="sc_field_wrap">
	<input id="sup_invite_email" class="sup_invite_email" type="text" name="sup_invite_email" size="50" placeholder="ex. jondoe@jondoe.com" required>
	</div>
	</div>
	<p>Send out an invitation for this bid to be reviewed or quoted on.  Remember, it's always a good idea to test it to yourself first.  And be sure to set your "<a href="{$bm_email_settings}">email settings</>".</p>
	<input class="button-primary" type="submit" name="sup_invite_submit" value="Invite &raquo;">
	</form>
SUPINVITE;

	echo $bm_user_bid_active . $content;

}

function bm_user_bid_past() {


	global $wpdb;
	global $current_user;
	get_currentuserinfo();

	$today = date( 'Y-m-d H:i:s' );
	$bm_user_id    = $current_user->ID;

	$pastRecord = sanitize_text_field( $_GET['bmuser_bid_past'] );

	$query   = "SELECT * FROM " . BM_BIDS . " WHERE bid_id = %d AND date_needed < %s AND bmuser_id = %d";
	$data    = array(
		$pastRecord,
		$today,
		$bm_user_id
	);
	$query   = $wpdb->prepare( $query, $data );
	$results = $wpdb->get_results( $query );

	foreach ( $results as $record ) {
		$bm_bid_id          = $record->bid_id;
		$bm_job_name        = stripslashes( $record->job_name );
		$bm_job_street      = stripslashes( $record->job_street );
		$bm_job_city        = stripslashes( $record->job_city );
		$bm_job_state       = stripslashes( $record->job_state );
		$bm_job_zip         = $record->job_zip;
		$bm_user_file = stripslashes( $record->bmuser_bid_file );
		$date           = date( 'F jS, Y', strtotime( $record->date_needed ) );
	}

	$bm_user_bid_past = <<<CONTRACTORBIDPAST
	
	<h1>Bid Request for: {$bm_job_name}</h1>
	
	<table class="form-table">
	<tr>
	<th scope="row">Bid ID#:</th>
	<td>{$bm_bid_id}</td>
	</tr>
	<tr>
	<th scope="row">Job Name:</th>
	<td>{$bm_job_name}</td>
	</tr>
	<tr>
	<th scope="row">Need Quote By:</th>
	<td>{$date}</td>
	</tr>
	<tr>
	<th scope="row">Street:</th>
	<td>{$bm_job_street}</td>
	</tr>
	<tr>
	<th scope="row">City:</th>
	<td>{$bm_job_city}</td>
	</tr>
	<tr>
	<th scope="row">State:</th>
	<td>{$bm_job_state}</td>
	</tr>
	<tr>
	<th scope="row">ZIP:</th>
	<td>{$bm_job_zip}</td>
	</tr>
	</table>
	<p><a class="button button_blue download" target="_blank" href="{$bm_user_file}">Download original material list for:  {$bm_job_name}</a></p>
CONTRACTORBIDPAST;

	echo $bm_user_bid_past;
}

function bm_update_user_record() {
	global $wpdb;
	global $current_user;
	get_currentuserinfo();

	$bm_user_id = $current_user->ID;

	$content = '';

	$bm_user_business     = sanitize_text_field( $_POST['comp_info_0'] );
	$bm_user_poc     = sanitize_text_field( $_POST['comp_info_1'] );
	$bm_user_phone   = sanitize_text_field( $_POST['comp_info_2'] );
	$bm_user_email   = sanitize_text_field( $_POST['comp_info_3'] );
	$bm_user_street  = sanitize_text_field( $_POST['comp_info_4'] );
	$bm_user_street2 = sanitize_text_field( $_POST['comp_info_5'] );
	$bm_user_city    = sanitize_text_field( $_POST['comp_info_6'] );
	$bm_user_state   = sanitize_text_field( $_POST['comp_info_7'] );
	$bm_user_zip     = sanitize_text_field( $_POST['comp_info_8'] );

	$address = $bm_user_street . ', ' . $bm_user_city . ' ' . $bm_user_state . ' ' . $bm_user_zip;

	$geocode = bm_get_lat_and_lng( $address );
	if ( $geocode !== FALSE ) {
		// save $geocode[�lat�] and $geocode[�lng�] to database
		$bm_lat = $geocode['lat'];
		$bm_lng = $geocode['lng'];
	}

	$query   = "UPDATE " . BM_USER . " SET bmuser_busname = '%s', bmuser_poc = '%s', bmuser_phone = '%s', bmuser_email = '%s', bmuser_street = '%s', bmuser_street_two = '%s', bmuser_city = '%s', bmuser_state = '%s', bmuser_zip = '%s', lat = '%s', lng = '%s' WHERE id = {$bm_user_id}";
	$data    = array(
		$bm_user_business,
		$bm_user_poc,
		$bm_user_phone,
		$bm_user_email,
		$bm_user_street,
		$bm_user_street2,
		$bm_user_city,
		$bm_user_state,
		$bm_user_zip,
		$bm_lat,
		$bm_lng
	);
	$query   = $wpdb->prepare( $query, $data );
	$wpdb->get_results( $query );

	$success = '<p class="success">Success! Your company information has been updated.</p>';

	$content .= $success;
	echo $content;

}

function bm_validate_form() {

	$bidJobName      = sanitize_text_field( $_POST['job_name'] );
	$bidNeededBy     = sanitize_text_field( $_POST['date_needed'] );
	$bidMaterialList = $_FILES['bmuser_bid_file'];
	$bidJobStreet    = sanitize_text_field( $_POST['job_street'] );
	$bidJobStreetTwo = sanitize_text_field( $_POST['job_street_two'] );
	$bidJobCity      = sanitize_text_field( $_POST['job_city'] );
	$bidJobState     = sanitize_text_field( $_POST['job_state'] );
	$bidJobZip       = sanitize_text_field( $_POST['job_zip'] );

	$address = $bidJobStreet . ', ' . $bidJobCity . ' ' . $bidJobState . ' ' . $bidJobZip;

	$geocode = bm_get_lat_and_lng( $address );

	$errorMaterialList = '';

	// var_dump(number_format($geocode['lat'], 7));
	if ( $geocode !== FALSE ) {
		// save $geocode[�lat�] and $geocode[�lng�] to database
		$bm_lat = $geocode['lat'];
		$bm_lng = $geocode['lng'];
	}

	if ( empty( $bidJobName ) ) {
		$errorBidJobName = '<div class="error"><p>You must provide a job name.</p></div>';
	}

	if ( empty( $bidNeededBy ) ) {
		$errorNeededBy = '<div class="error"><p>You must provide a date you need the quote by.</p></div>';
	}

	if ( empty( $bidMaterialList['name'] ) ) {
		$errorMaterialList .= '<div class="error"><p>You must provide a material list to the contractor.</p></div>';
	}

	$allowedFileType = array(
		'text/plain', // .txt
		'text/csv', // .csv
		'application/csv', // .csv alternative
		'text/comma-separated-values', // .csv alternative
		'application/zip', // .zip
		'application/msword', // .doc
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
		'application/vnd.ms-excel', // .xls
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
		'application/pdf', // .pdf
		'application/acrobat', // .pdf alternative
		'text/pdf', // .pdf alternative
		'text/x-pdf', // .pdf alternative
		'application/x-pdf' // .pdf alternative
	);

	if ( ! in_array( $_FILES["bmuser_bid_file"]["type"], $allowedFileType ) ) {
		$errorMaterialList .= '<div class="error"><p>Your file type is not supported.</p></div>';
	}

	if ( empty( $bidJobStreet ) ) {
		$errorJobStreet = '<div class="error"><p>You must provide the job street.</p></div>';
	}

	if ( empty( $bidJobCity ) ) {
		$errorJobCity = '<div class="error"><p>You must provide the job city.</p></div>';
	}

	if ( empty( $bidJobState ) ) {
		$errorJobState = '<div class="error"><p>You must provide the job state.</p></div>';
	}

	if ( empty( $bidJobZip ) ) {
		$errorbidJobZip = '<div class="error"><p>You must provide the job zip code.</p></div>';
	}

	if ( $bm_lat == NULL || $bm_lat == 0.00000000 ) {
		$errorbidGeo = '<div class="error"><p>You must provide a correct address.  Please check the address and try again.</p></div>';
		echo $errorbidGeo;
	}

	if ( ! $errorBidJobName && ! $errorNeededBy && ! $errorMaterialList && ! $errorJobStreet && ! $errorJobCity && ! $errorJobState && ! $errorbidJobZip && ! $errorbidGeo ) {
		return TRUE;
	} else {
		return array(
			$errorBidJobName,
			$errorNeededBy,
			$errorMaterialList,
			$errorJobStreet,
			$errorJobCity,
			$errorJobState,
			$errorbidJobZip,
			$errorbidGeo
		);
	}
}

function bm_bid_form() {

	global $wpdb;
	global $current_user;
	get_currentuserinfo();

	$bm_user_id = $current_user->ID;

	$bidJobName      = stripslashes( sanitize_text_field( $_POST['job_name'] ) );
	$bidNeededBy     = sanitize_text_field( $_POST['date_needed'] );
	$bidJobStreet    = stripslashes( sanitize_text_field( $_POST['job_street'] ) );
	$bidJobStreetTwo = stripslashes( sanitize_text_field( $_POST['job_street_two'] ) );
	$bidJobCity      = stripslashes( sanitize_text_field( $_POST['job_city'] ) );
	$bidJobState     = stripslashes( sanitize_text_field( $_POST['job_state'] ) );
	$bidJobZip       = sanitize_text_field( $_POST['job_zip'] );

	$nameReturn      = ( isset( $bidJobName ) ? stripslashes( $bidJobName ) : '' );
	$neededReturn    = ( isset( $bidNeededBy ) ? $bidNeededBy : '' );
	$streetReturn    = ( isset( $bidJobStreet ) ? stripslashes( $bidJobStreet ) : '' );
	$streetTwoReturn = ( isset( $bidJobStreetTwo ) ? stripslashes( $bidJobStreetTwo ) : '' );
	$cityReturn      = ( isset( $bidJobCity ) ? stripslashes( $bidJobCity ) : '' );
	$stateReturn     = ( isset( $bidJobState ) ? $bidJobState : '' );
	$zipReturn       = ( isset( $bidJobZip ) ? $bidJobZip : '' );

	if ( is_user_logged_in() ) {
		if ( isset( $_POST['new_bid'] ) ) {
			$validate = bm_validate_form();
			if ( TRUE === $validate ) {
				bm_create_bid();
			} else {
				list( $errorBidJobName, $errorNeededBy, $errorMaterialList, $errorJobStreet, $errorJobCity, $errorJobState, $errorbidJobZip ) = $validate;
			}
		}


		$bm_bid_form = <<<BIDFORM

		<div class="wrap">

		<h1>New Bid Request Form</h1>

		<form action="" method="post" enctype="multipart/form-data">
		<fieldset>
		<table class="form-table">
		<tr>
		<th scope="row"><span class="required">*</span>Job Name:</th>
		<td><input name="job_name" id="job_name" placeholder="ex: Smith Residence" type="text" value="{$nameReturn}" required>

BIDFORM;

		if ( $errorBidJobName ) {
			echo $errorBidJobName;
		}

		$bm_bid_form .= <<<BIDFORM

		</td>
		</tr>
		<tr>
		<th scope="row"><span class="required">*</span>Project Start Date:</th>
		<td><input name="date_needed" id="date_needed" placeholder="ex: mm/dd/yyyy" type="date" value="{$neededReturn}" required>

BIDFORM;

		if ( $errorNeededBy ) {
			echo $errorNeededBy;
		}

		$bm_bid_form .= <<<BIDFORM

		</td>
		</tr>
		<tr>
		<th scope="row"><span class="required">*</span>Material List:</th>
		<td><input name="bmuser_bid_file" id="bmuser_bid_file" type="file" required>

BIDFORM;

		if ( $errorMaterialList ) {
			echo $errorMaterialList;
		}

		$bm_bid_form .= <<<BIDFORM
		<span>Accepted formats:  <strong>.TXT, .DOC, .DOCX, .XLS, .CSV, .XLSX, .PDF, .ZIP</strong></span>
		</td>
		</tr>
		<tr>
		<td><h3>Job Address:</h3></td>
		</tr>
		<tr>
		<th scope="row"><span class="required">*</span>Address:</th>
		<td><input name="job_street" id="job_street" placeholder="ex: 123 My Street" value="{$streetReturn}" required>

BIDFORM;

		if ( $errorJobStreet ) {
			echo $errorJobStreet;
		}

		$bm_bid_form .= <<<BIDFORM

		</td>
		</tr>
		<tr>
		<th scope="row">Address Cont.:</th>
		<td><input name="job_street_two" id="job_street_two" placeholder="ex: Unit #10 A" value="{$streetTwoReturn}"></td>
		</tr>
		<tr>
		<th scope="row"><span class="required">*</span>City:</th>
		<td><input name="job_city" id="job_city" placeholder="ex: Denver" value="{$cityReturn}" required>

BIDFORM;

		if ( $errorJobCity ) {
			echo $errorJobCity;
		}

		$bm_bid_form .= <<<BIDFORM

		</td>
		</tr>
		<tr>
		<th scope="row"><span class="required">*</span>State:</th>
		<td><input name="job_state" id="job_state" placeholder="ex: Colorado" value="{$stateReturn}" required>

BIDFORM;

		if ( $errorJobState ) {
			echo $errorJobState;
		}

		$bm_bid_form .= <<<BIDFORM

		</td>
		</tr>
		<tr>
		<th scope="row"><span class="required">*</span>ZIP:</th>
		<td><input name="job_zip" id="job_zip" placeholder="ex: 80019" value="{$zipReturn}" required>

BIDFORM;

		if ( $errorbidJobZip ) {
			echo $errorbidJobZip;
		}

		$bm_bid_form .= <<<BIDFORM

		</td>
		</tr>
		</table>
		</fieldset>
		<p><input id="submit" class="button button-primary" type="submit" name="new_bid" value="Submit Bid &raquo;"></p>

BIDFORM;

		$bm_bid_form .= '</form>';

		$query   = "SELECT id FROM " . BM_USER . " WHERE id = %d";
		$data    = array( $bm_user_id );
		$query   = $wpdb->prepare( $query, $data );
		$results = $wpdb->get_results( $query );

		if ( $results ) {
			$content = $bm_bid_form;
			$content .= '</div>';
		} else {
			$content = '<div class="wrap">';
			$content .= '<p>You have not entered your company information.  You must enter your information before you can place a bid.  Bids rely on your contact information and to show the suppliers who the request is coming from.  Please click below to enter your information and get started.</p>';
			$content .= '<p><a class="button" href="' . BM_CINFO . '">Company Info &raquo;</a></p>';
			$content .= '</div>';
		}
	} else {
		$content = bm_protected_content();
	}



	if (isset($_POST['new_bid']) && $validate === TRUE) {

		$bm_new_bid = BM_CBID;

		$content  = '<p class="success">Your bid has been saved.</p>';
		$content .= '<p><a href="' . $bm_new_bid . '">&laquo Back to New Bid</a>';
		echo $content;
	} else {
		echo $content;
	}
}

function bm_user_bid_accepted() {

	global $wpdb;
	global $current_user;
	get_currentuserinfo();

	$bm_user_id = $current_user->ID;

	$recordId = (int) $_GET['bid_accepted'];
	$responseId = (int) $_GET['response_id'];

	$query   = "SELECT * FROM " . BM_BIDS . " WHERE bid_id = %d AND bmuser_id = %d AND accepted_flag = 1";
	$data    = array( $recordId, $bm_user_id );
	$query   = $wpdb->prepare( $query, $data );
	$results = $wpdb->get_results( $query );

	foreach ( $results as $record ) {
		$bm_bid_id          = $record->bid_id;
		$bm_job_name        = stripslashes( $record->job_name );
		$requiredBy     = $record->date_needed;
		$bm_job_street      = stripslashes( $record->job_street );
		$bm_job_city        = stripslashes( $record->job_city );
		$bm_job_state       = stripslashes( $record->job_state );
		$bm_job_zip         = $record->job_zip;
		$bm_user_file = stripslashes( $record->bmuser_bid_file );
		$bm_bid_accepted    = $record->accepted_flag;
	}

	$bm_user_accepted = <<<BIDACCEPTED
	
	<div id="bid_response_bid_info" class="original_bid_info">
	<h1>Bid Request for: {$bm_job_name}</h1>
	
	<table class="form-table">
	<tr>
	<th scope="row">Bid ID#:</th>
	<td>{$bm_bid_id}</td>
	</tr>
	<tr>
	<th scope="row">Job Name:</th>
	<td>{$bm_job_name}</td>
	</tr>
	<tr>
	<th scope="row">Need Quote By:</th>
	<td>{$requiredBy}</td>
	</tr>
	<tr>
	<th scope="row">Street:</th>
	<td>{$bm_job_street}</td>
	</tr>
	<tr>
	<th scope="row">City:</th>
	<td>{$bm_job_city}</td>
	</tr>
	<tr>
	<th scope="row">State:</th>
	<td>{$bm_job_state}</td>
	</tr>
	<tr>
	<th scope="row">ZIP:</th>
	<td>{$bm_job_zip}</td>
	</table>
	</div>
	<div id="bid_responder_responses">
    <p><a class="button button_blue download" target="_blank" href="{$bm_user_file}">Download original material list for: {$bm_job_name} &raquo;</a></p>
BIDACCEPTED;

	if ( $bm_bid_accepted == 0 ) {
		$bm_user_accepted .= <<<BIDACCEPTED
		<p><a class="button" href="?bid_id={$bm_bid_id}&response_id={$responseId}&accept=true">Accept Quote &raquo;</a></p>
BIDACCEPTED;
	} elseif ( $bm_bid_accepted == 1 ) {

		?>

			<table>
				<tr>
					<td>
						<?php echo $bm_user_accepted ?>
					</td>
				</tr>
				<tr>
					<td>
						<p class="warning">You have already accepted this quote. Would you like to retract your acceptance? This will put the bid back in the active que to be bid on by other suppliers.</p>
					</td>
				</tr>
				<tr>
					<td>
						<form action="" method="post" enctype="multipart/form-data">
							<p><span class="required">*</span>Type the word "RETRACT" (all caps) in this box.</p>
							<input type="text" name="retractbid" placeholder="Type:  RETRACT" required>
							<p><span class="required">*</span>Below, type the reason for retracting the bid.  This will be sent to the responder.</p>
							<?php

							$args = array(
								'media_buttons' => FALSE,
								'textarea_name' => 'retract_message'

							);

							wp_editor( '', 'retract_message_text', $args);

							?>
							<input class="button-primary" type="submit" name="retract_bid" value="Retract My Bid &raquo;">
						</form>
					</td>
				</tr>
			</table>
		<?php
	}

	if ( $_POST['retract_bid'] ) {
		bm_retract_bid();
	}
}

function bm_user_dashboard() {
	global $wpdb;
	global $current_user;
	get_currentuserinfo();

	$bm_user_id = $current_user->ID;

	$today = date( 'Y-m-d H:i:s' );

	strtotime( $today );
	$content  = '';
	$content .= '<div class="wrap">';

	if ( $_GET['bid_accepted'] ) {
		return bm_user_bid_accepted();
	} elseif ( $_GET['bmuser_bid_past'] ) {
		return bm_user_bid_past();
	} elseif ( $_GET['bid_response'] ) {
		return bm_user_bid_review();
	} elseif ( $_GET['bmuser_bid_active'] ) {
		return bm_user_bid_active();
	} elseif ( $_GET['hide'] ) {
		return bm_hide_bid();
	} elseif ( sanitize_text_field( $_GET['view_hidden'] ) == "true" ) {
		return bm_view_hidden();
	} elseif ( sanitize_text_field( $_GET['unhide'] ) == "true" ) {
		return bm_unhide_bid();
	}


	//  Check to see if the quote was accepted by the requester.  If it was, save it into two tables (bm_bids and bm_bids_reponses)
	if ( sanitize_text_field( $_GET['accept'] ) == 'true' ) {

		$bm_bid_id       = (int) $_GET['bid_id'];
		$responseId  = (int) $_GET['response_id'];

		//  Run the update on the bm_bids table
		$query = "UPDATE " . BM_BIDS . " SET accepted_flag = 1 WHERE bid_id = %d AND bmuser_id = %d";
		$data  = array(
			$bm_bid_id,
			$bm_user_id
		);

		$query   = $wpdb->prepare( $query, $data );
		$wpdb->get_results( $query );

		//  Run the update on the bm_bids_responses table
		$query = "UPDATE " . BM_BIDS_RESPONSES . " SET bid_accepted = 1 WHERE bid_id = %d AND id = %d";
		$data  = array(
			$bm_bid_id,
			$responseId
		);

		$query   = $wpdb->prepare( $query, $data );
		$wpdb->get_results( $query );
		bm_user_emails();
	}

	$query = "SELECT * FROM " . BM_BIDS . " WHERE date_needed > %s AND bmuser_id = %d AND accepted_flag = 0";

	$data    = array(
		$today,
		$bm_user_id
	);
	$query   = $wpdb->prepare( $query, $data );
	$results = $wpdb->get_results( $query );

	if ( sanitize_text_field( $_GET['message'] ) == "bid_saved" ) {
		$content .= '<p class="success">Bid saved successfully! &ndash; Click the <em>"View Bid &raquo;"</em> action to invite suppliers to respond.</p>';
	}

	// Passing the expiration test above, make sure they are logged in and on the correct sub-level
	if ( is_user_logged_in() ) {
		if ( $results ) {
			$content .= '<h1>Bid Manager Dashboard</h1>';

			$content .= '<div class="active_bids blue_table">';
			$content .= '<h2>Active Bid Requests</h2>';

			$content .= '<table id="conActiveBids" class="blue_table" border="1" bordercolor="#000" cellpadding="5" width="100%">';
			$content .= '<thead>';
			$content .= '<tr>';
			$content .= '<th>';
			$content .= 'Bid Id';
			$content .= '</th>';
			$content .= '<th>';
			$content .= 'Job';
			$content .= '</th>';
			$content .= '<th>';
			$content .= 'Street';
			$content .= '</th>';
			$content .= '<th>';
			$content .= 'City';
			$content .= '</th>';
			$content .= '<th>';
			$content .= 'State';
			$content .= '</th>';
			$content .= '<th>';
			$content .= 'Zip Code';
			$content .= '</th>';
			$content .= '<th>';
			$content .= 'Bid Required By';
			$content .= '</th>';
			$content .= '<th>';
			$content .= 'Take Action';
			$content .= '</th>';
			$content .= '</tr>';
			$content .= '</thead>';
			$content .= '<tbody>';

			foreach ( $results as $record ) {
				$content .= '<tr>';
				$content .= '<td>' . $record->bid_id . '</td>';
				$content .= '<td>' . stripslashes( $record->job_name ) . '</td>';
				$content .= '<td>' . $record->job_street . '</td>';
				$content .= '<td>' . $record->job_city . '</td>';
				$content .= '<td>' . $record->job_state . '</td>';
				$content .= '<td>' . $record->job_zip . '</td>';
				$content .= '<td>' . date( 'F jS, Y', strtotime( $record->date_needed ) ) . '</td>';
				$content .= '<td><a class="button-primary" href="' . BM_CDBOARD . '&amp;bmuser_bid_active=' . $record->bid_id . '">View Bid &raquo;</a></td>';
				$content .= '</tr>';
			}

			$content .= '</tbody>';
			$content .= '</table>';

			$content .= '</div>';

		} else {
			$content .= '<p>There are no active bids.</p><p><a class="button" href="' . BM_CBID . '">Create A Bid &raquo</a></p>';
		}
	} else {
		$content .= bm_protected_content();
	}

	$query   = "SELECT * FROM " . BM_BIDS . " WHERE bmuser_id = %d AND date_needed > %s AND accepted_flag = 0 AND has_response > 0";
	$data    = array(
		$bm_user_id,
		$today
	);
	$query   = $wpdb->prepare( $query, $data );
	$results = $wpdb->get_results( $query );

	if ( $results ) {
		$content .= '<div class="responder_responses blue_table">';

		$content .= '<h2>Bids With Responses (not accepted)</h2>';

		$content .= '<table id="conSupResponse" class="blue_table" border="1" bordercolor="#000" cellpadding="5" width="100%">';
		$content .= '<thead>';
		$content .= '<tr>';
		$content .= '<th>';
		$content .= 'Bid Id';
		$content .= '</th>';
		$content .= '<th>';
		$content .= 'Job Name';
		$content .= '</th>';
		$content .= '<th>';
		$content .= 'Street';
		$content .= '</th>';
		$content .= '<th>';
		$content .= 'City';
		$content .= '</th>';
		$content .= '<th>';
		$content .= 'State';
		$content .= '</th>';
		$content .= '<th>';
		$content .= 'Zip Code';
		$content .= '</th>';
		$content .= '<th>';
		$content .= 'Bid Required By';
		$content .= '</th>';
		$content .= '<th>';
		$content .= '# of Bids';
		$content .= '</th>';
		$content .= '<th>';
		$content .= 'Take Action';
		$content .= '</th>';
		$content .= '</tr>';
		$content .= '</thead>';
		$content .= '<tbody>';

		foreach ( $results as $record ) {
			// $arr_params = array( 'bid_response' => $record->bid_id );
			$content .= '<tr>';
			$content .= '<td>' . $record->bid_id . '</td>';
			$content .= '<td>' . stripslashes( $record->job_name ) . '</td>';
			$content .= '<td>' . $record->job_street . '</td>';
			$content .= '<td>' . $record->job_city . '</td>';
			$content .= '<td>' . $record->job_state . '</td>';
			$content .= '<td>' . $record->job_zip . '</td>';
			$content .= '<td>' . date( 'F jS, Y', strtotime( $record->date_needed ) ) . '</td>';
			$content .= '<td>' . $record->has_response . '</td>';
			$content .= '<td><a class="button-primary" href="' . BM_CDBOARD . '&amp;bid_response=' . $record->bid_id . '">View Bid &raquo;</a></td>';
			$content .= '</tr>';
		}

		$content .= '</tbody>';
		$content .= '</table>';

		$content .= '</div>';

	} else {
		$content .= '';
	}

	$query   = "SELECT bid_id, job_name, job_street, job_city, job_state, job_zip, date_needed FROM " . BM_BIDS . " WHERE date_needed < %s AND bmuser_id = %d AND accepted_flag = 0";
	$data    = array(
		$today,
		$bm_user_id
	);
	$query   = $wpdb->prepare( $query, $data );
	$results = $wpdb->get_results( $query );

	if ( $results ) {
		$content .= '<div class="archived_bids blue_table">';

		$content .= '<h2>Past Bids Submitted</h2>';

		$content .= '<table id="conPastBids" class="blue_table" border="1" bordercolor="#000" cellpadding="5" width="100%">';
		$content .= '<thead>';
		$content .= '<tr>';
		$content .= '<th>';
		$content .= 'Bid Id';
		$content .= '</th>';
		$content .= '<th>';
		$content .= 'Job';
		$content .= '</th>';
		$content .= '<th>';
		$content .= 'Street';
		$content .= '</th>';
		$content .= '<th>';
		$content .= 'City';
		$content .= '</th>';
		$content .= '<th>';
		$content .= 'State';
		$content .= '</th>';
		$content .= '<th>';
		$content .= 'Zip Code';
		$content .= '</th>';
		$content .= '<th>';
		$content .= 'Bid Required By';
		$content .= '</th>';
		$content .= '<th>';
		$content .= 'Take Action';
		$content .= '</th>';
		$content .= '</tr>';
		$content .= '</thead>';
		$content .= '<tbody>';

		foreach ( $results as $record ) {

			$link = 'admin.php';
			$params = array( 'bmuser_bid_past' => $record->bid_id );
			$link = add_query_arg( $params );
			$link = esc_url($link, '', 'db');

			// $arr_params = array( 'bmuser_bid_past' => $record->bid_id );
			$content .= '<tr>';
			$content .= '<td>' . $record->bid_id . '</td>';
			$content .= '<td>' . stripslashes( $record->job_name ) . '</td>';
			$content .= '<td>' . $record->job_street . '</td>';
			$content .= '<td>' . $record->job_city . '</td>';
			$content .= '<td>' . $record->job_state . '</td>';
			$content .= '<td>' . $record->job_zip . '</td>';
			$content .= '<td>' . date( 'F jS, Y', strtotime( $record->date_needed ) ) . '</td>';
			$content .= '<td><a class="button-primary" href="' . $link . '">View Bid &raquo;</a></td>';
			$content .= '</tr>';
		}

		$content .= '</tbody>';
		$content .= '</table>';

		$content .= '</div>';

	} else {
		$content .= '';
	}

	$query   = "SELECT * FROM " . BM_BIDS_RESPONSES . " LEFT OUTER JOIN " . BM_BIDS . " ON " . BM_BIDS . ".bid_id=" . BM_BIDS_RESPONSES . ".bid_id WHERE bid_accepted = 1 AND bmuser_id = %d";
	$data    = array(
		$bm_user_id
	);
	$query   = $wpdb->prepare( $query, $data );
	$results = $wpdb->get_results( $query );


	if ( $results ) {
		$content .= '<div class="contractors_bids_accepted blue_table">';

		$content .= '<h2>Bids You Have Accepted</h2>';

		$content .= '<table id="conAccepted" class="blue_table" border="1" bordercolor="#000" cellpadding="5" width="100%">';
		$content .= '<thead>';
		$content .= '<tr>';
		$content .= '<th>';
		$content .= 'Bid Id';
		$content .= '</th>';
		$content .= '<th>';
		$content .= 'Name';
		$content .= '</th>';
		$content .= '<th>';
		$content .= 'Job';
		$content .= '</th>';
		$content .= '<th>';
		$content .= 'Street';
		$content .= '</th>';
		$content .= '<th>';
		$content .= 'City';
		$content .= '</th>';
		$content .= '<th>';
		$content .= 'State';
		$content .= '</th>';
		$content .= '<th>';
		$content .= 'Zip Code';
		$content .= '</th>';
		$content .= '<th>';
		$content .= 'Bid Required By';
		$content .= '</th>';
		$content .= '<th>';
		$content .= 'Quote Total';
		$content .= '</th>';
		$content .= '<th>';
		$content .= 'Take Action';
		$content .= '</th>';
		$content .= '</tr>';
		$content .= '</thead>';
		$content .= '<tbody>';

		foreach ( $results as $record ) {

			$link = 'admin.php';
			$params = array( 'page' => 'bid_manager_dashboard', 'bid_accepted' => $record->bid_id, 'response_id' => $record->id );
			$link = add_query_arg( $params, $link );
			$link = esc_url($link, '', 'db');

			// $arr_params = array( 'bid_accepted' => $record->bid_id, 'responder_id' => $record->responder_id );
			$content .= '<tr>';
			$content .= '<td>' . $record->bid_id . '</td>';
			$content .= '<td>' . stripslashes( $record->responder_busname ) . '</td>';
			$content .= '<td>' . stripslashes( $record->job_name ) . '</td>';
			$content .= '<td>' . $record->job_street . '</td>';
			$content .= '<td>' . $record->job_city . '</td>';
			$content .= '<td>' . $record->job_state . '</td>';
			$content .= '<td>' . $record->job_zip . '</td>';
			$content .= '<td>' . date( 'F jS, Y', strtotime( $record->date_needed ) ) . '</td>';
			$content .= '<td>$ ' . number_format( $record->quoted_total, 2 ) . '</td>';
			$content .= '<td><a class="button-primary" href="' . $link . '">View Bid &raquo;</a></td>';
			$content .= '</tr>';
		}

		$content .= '</tbody>';
		$content .= '</table>';

		$content .= '</div>';
		$content .= '</div>';

	} else {
		$content .= '';
	}

	echo $content;
}

function bm_user_gmap() {
	global $wpdb;
	global $current_user;
	get_currentuserinfo();

	$bm_user_id = $current_user->ID;

	$query   = "SELECT bmuser_busname, lat, lng FROM " . BM_USER . " WHERE id = %d";
	$data    = array(
		$bm_user_id
	);
	$query   = $wpdb->prepare( $query, $data );
	$results = $wpdb->get_results( $query );

	foreach ( $results as $record ) {
		$bm_user_name = $record->bmuser_busname;
		$bm_lat     = $record->lat;
		$bm_lng     = $record->lng;
	}

	$map   = "'googleMap'";
	$title = $bm_user_name;
	$load  = "'load'";

	$blueBidsMarker = PLUGIN_ROOT . '/images/map_marker_blue.png';
	// $yellowBidsMarker = PLUGIN_ROOT . '/images/map_marker_yellow.png';
	$greenBidsMarker = PLUGIN_ROOT . '/images/map_marker_green.png';
	// $redBidsMarker = PLUGIN_ROOT . '/images/map_marker_red.png';

	$content = '';

	//  Query for the API key
	$query = "SELECT meta_value FROM " . BM_USERMETA . " WHERE user_id = %d AND meta_key = %s";
	$data  = array(
		$bm_user_id,
		'bm_google_api_key'
	);

	$query   = $wpdb->prepare( $query, $data );
	$results = $wpdb->get_results( $query );

	if ($results) {

		foreach ($results as $record) {
			$key = $record->meta_value;
		}

		if (!empty($key) && $key != " ") {
			$content .= '<h2>Company location with your current active bids (does not show accepted or past due bids)</h2>';

			$content .= '<table class="wp-dash-table" border="1" bordercolor="#000" cellpadding="5" style="margin-bottom: 15px;">';
			$content .= '<thead>';
			$content .= '<tr>';
			$content .= '<th>';
			$content .= 'Your Company';
			$content .= '</th>';
			$content .= '<th>';
			$content .= 'Active Bids';
			$content .= '</th>';
			$content .= '</tr>';
			$content .= '</thead>';
			$content .= '<tbody>';
			$content .= '<tr>';
			$content .= '<td align="center">';
			$content .= '<img src="' . $greenBidsMarker . '">';
			$content .= '</td>';
			$content .= '<td align="center">';
			$content .= '<img src="' . $blueBidsMarker . '">';
			$content .= '</td>';
			$content .= '</tr>';
			$content .= '</tbody>';
			$content .= '</table>';

			$content .= <<<CONMAP
	<script
			src="https://maps.googleapis.com/maps/api/js?key={$key}">
		</script>
	<script>
	  function initialize() {
	  var myLatlng = new google.maps.LatLng({$bm_lat},{$bm_lng});
	  var mapOptions = {
	    zoom: 7,
	    center: myLatlng,
	    mapTypeId: google.maps.MapTypeId.HYBRID
	  }
	  var map = new google.maps.Map(document.getElementById({$map}), mapOptions);

	  var marker = new google.maps.Marker({
	    position: myLatlng,
	  	icon: "{$greenBidsMarker}",
	    map: map,
	    title: "{$title}"
	  });
CONMAP;


			$today = date( 'Y-m-d H:i:s' );

			strtotime( $today );

			$query   = "SELECT bmuser_id, job_name, lat, lng FROM " . BM_BIDS . " WHERE bmuser_id = %d AND date_needed > %s AND accepted_flag = 0";
			$data    = array( $bm_user_id, $today );
			$query   = $wpdb->prepare( $query, $data );
			$results = $wpdb->get_results( $query );

			foreach ( $results as $record ) {
				$conId    = $record->bmuser_id;
				$bm_job_name  = $record->job_name;
				$bm_lat      = $record->lat;
				$bm_lng      = $record->lng;
				$content .= <<<CONMAP

		  var marker = new google.maps.Marker({
		  	  icon: "{$blueBidsMarker}",
		      position: new google.maps.LatLng({$bm_lat},{$bm_lng }),
		      map: map,
		      title: "{$bm_job_name}"
		  });

		  var contractorBid{$conId} = new google.maps.Circle({
			  center: new google.maps.LatLng({$bm_lat},{$bm_lng}),
			  map: map,
			  strokeColor: "#fff600",
		  });
CONMAP;
			}


			$content .= <<<CONMAP
	}

		google.maps.event.addDomListener(window, {$load}, initialize);
	</script>
	<div id="googleMap" style="width:100%; height: 550px;"></div>
CONMAP;
		};


		$query   = "SELECT id FROM " . BM_USER . " WHERE id = %d";
		$data    = array(
			$bm_user_id
		);
		$query   = $wpdb->prepare( $query, $data );
		$results = $wpdb->get_results( $query );

		if ( $results ) {
			return $content;
		} else {
			$message = '<p>Please enter your <a href="' . CCINFO . '">business information</a> to show the Google Map.</p>';

			return $message;
		}

	}
}

function bm_user_info() {
	global $wpdb;
	global $current_user;
	get_currentuserinfo();

	$bm_user_id = $current_user->ID;

	$content = '';


// Check to see if the company information form has been submitted and update the record

	$isContractorInfoSubmitted = isset( $_POST['bmuser_company_info'] );

	$query   = "SELECT * FROM " . BM_USER . "";
	$data    = array();
	$query   = $wpdb->prepare( $query, $data );
	$results = $wpdb->get_results( $query );

	if ( $results ) {
		foreach ( $results as $record ) {
			if ( $isContractorInfoSubmitted && ( $bm_user_id == $record->id ) ) {
				$entryExists = TRUE;
			}
		}
	}

	if ($isContractorInfoSubmitted) {
		if ( ! $entryExists ) {
			bm_create_user_record();
		} else if ( $entryExists ) {
			bm_update_user_record();
		}
	}

	$query   = "SELECT * FROM " . BM_USER . " WHERE id = {$bm_user_id}";
	$data    = array();
	$query   = $wpdb->prepare( $query, $data );
	$results = $wpdb->get_results( $query );

	$i = 0; // Sets dynamic number for input IDs

	if ( is_user_logged_in() ) {
		$content .= '<div class="wrap">';
		$content .= '<h1>Company Information</h1>';

		$content .= '<form action="" method="post" enctype="multipart/form-data">' . PHP_EOL;
		$content .= '<fieldset>' . PHP_EOL;
		$content .= '<table class="form-table">';

	if ($results) {
		foreach ($results as $record) {
			// It is VERY important to not change the order of the array below.  Doing so will change the indexes of the array and will have negative implications on saving the data
			$user_info = array(
				'Business Name'    => $record->bmuser_busname,
				'Point of Contact' => $record->bmuser_poc,
				'Phone'			   => $record->bmuser_phone,
				'Email' 		   => $record->bmuser_email,
				'Street'           => $record->bmuser_street,
				'Street2'          => $record->bmuser_street_two,
				'City'             => $record->bmuser_city,
				'State'            => $record->bmuser_state,
				'Zip'              => $record->bmuser_zip
			);

			foreach ($user_info as $k => $v) {
				$content .= '<tr><td>' . $k . ': </td><td><input id="comp_info_' . $i . '" name="comp_info_' . $i . '" value="' . stripslashes($v) . '" type="text"></td></tr>';
				++$i;
			}
		}
	}	else {

		/*
		 * Please read this comment before touching the code below.
		 */

			// It is VERY important to not change the order of the array below.  Doing so will change the indexes of the array and will have negative implications on saving the data
			$user_info = array(
				'Business Name'    => $record->bmuser_busname,
				'Point of Contact' => $record->bmuser_poc,
				'Phone'			   => $record->bmuser_phone,
				'Email' 		   => $record->bmuser_email,
				'Street'           => $record->bmuser_street,
				'Street2'          => $record->bmuser_street_two,
				'City'             => $record->bmuser_city,
				'State'            => $record->bmuser_state,
				'Zip'              => $record->bmuser_zip
			);

			foreach ($user_info as $k => $v) {
				$content .= '<tr><td>' . $k . ': </td><td><input id="comp_info_' . $i . '" name="comp_info_' . $i . '" value="" type="text" placeholder="' . stripslashes($v) . '"></td></tr>';
				++$i;
			}
	}

		$content .= '</table>';
		$content .= '</fieldset>' . PHP_EOL;
		$content .= '<p><input id="submit" class="button button-primary" type="submit" name="bmuser_company_info" value="Update Company Info &raquo;"></p>' . PHP_EOL;
		$content .= '</form>' . PHP_EOL;

		$content .= bm_user_gmap();
		$content .= '</div>';

	} else {
		$content .= bm_protected_content();
	}

	echo $content;
}


function bm_responder_invite($bm_bid_id) {

	/* Setup email */
	if ($_POST['sup_invite_submit'] && !empty($_POST['sup_invite_email'])) {

		global $wpdb;
		global $current_user;
		get_currentuserinfo();

		$bm_user_id = $current_user->ID;

		$currentBid = $_GET['bmuser_bid_active'];
		$submissionDate = date( 'Y-m-d H:i:s');

		$string = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 24);
		$hash = md5($string);

		//  Store the info to the sc_responder_emails table to make a unique link for the responder

		$query = "INSERT INTO " . BM_EMAILS . " (id, bid_id, date, hash)" .
			"VALUES (%d, %d, %s, %s);";
		$data  = array(
			'',
			$currentBid,
			$submissionDate,
			$hash
		);

		$query   = $wpdb->prepare( $query, $data );
		$wpdb->get_results( $query );

		//  End data storage to sc_responder_emails table

		// Setup the invite email to the responder

		$query = "SELECT bmuser_busname FROM " . BM_USER . " WHERE id = %d";
		$data  = array(
			$bm_user_id
		);

		$query   = $wpdb->prepare( $query, $data );
		$results = $wpdb->get_results( $query );

		foreach ( $results as $record ) {
			$bm_user_busname = (empty($record->bmuser_busname)) ? 'WP Bid Manager' : $record->bmuser_busname;
		}

		$bm_user_busname = stripcslashes($bm_user_busname);

		$bm_responderEmail = sanitize_text_field($_POST["sup_invite_email"]);

		$to = $bm_responderEmail; // The email the mail will be going to for certain

		// Optional emails

		if (!empty($_POST["sup_invite_email_2"])) {
			$to .= ', ' . $_POST["sup_invite_email_2"];
		}

		if (!empty($_POST["sup_invite_email_3"])) {
			$to .= ', ' . $_POST["sup_invite_email_3"];
		}

		if (!empty($_POST["sup_invite_email_4"])) {
			$to .= ', ' . $_POST["sup_invite_email_4"];
		}

		if (!empty($_POST["sup_invite_email_5"])) {
			$to .= ', ' . $_POST["sup_invite_email_5"];
		}

		if (!empty($_POST["sup_invite_email_6"])) {
			$to .= ', ' . $_POST["sup_invite_email_6"];
		}

		if (!empty($_POST["sup_invite_email_7"])) {
			$to .= ', ' . $_POST["sup_invite_email_7"];
		}

		if (!empty($_POST["sup_invite_email_8"])) {
			$to .= ', ' . $_POST["sup_invite_email_8"];
		}

		if (!empty($_POST["sup_invite_email_9"])) {
			$to .= ', ' . $_POST["sup_invite_email_9"];
		}

		if (!empty($_POST["sup_invite_email_10"])) {
			$to .= ', ' . $_POST["sup_invite_email_10"];
		}

		if (!empty($_POST["sup_invite_email_11"])) {
			$to .= ', ' . $_POST["sup_invite_email_11"];
		}


		//  This query finds the email subject, from line, and body text from the " . BM_USERMETA . " table
		$query = "SELECT meta_value FROM " . BM_USERMETA . "";
		$where = " WHERE user_id = %d AND meta_key = 'bm_email_content'";
		$data  = array(
            $bm_user_id
		);

		$query   = $wpdb->prepare( $query . $where, $data );
		$results = $wpdb->get_results( $query );

		if ($results) {
			foreach ($results as $record) {
				$copy = stripslashes($record->meta_value);
			}
		}

		$query = "SELECT meta_value FROM " . BM_USERMETA . "";
		$where = " WHERE user_id = %d AND meta_key = 'bm_subject_line'";
		$data  = array(
            $bm_user_id
		);

		$query   = $wpdb->prepare( $query . $where, $data );
		$results = $wpdb->get_results( $query );

		if ($results) {
			foreach ($results as $record) {
				$subject = stripslashes($record->meta_value);
			}
		}

		$query = "SELECT meta_value FROM " . BM_USERMETA . "";
		$where = " WHERE user_id = %d AND meta_key = 'email_from_name'";
		$data  = array(
            $bm_user_id
		);

		$query   = $wpdb->prepare( $query . $where, $data );
		$results = $wpdb->get_results( $query );

		if ($results) {
			foreach ($results as $record) {
				$from = stripslashes($record->meta_value);
			}
		}

		$query = "SELECT meta_value FROM " . BM_USERMETA . "";
		$where = " WHERE user_id = %d AND meta_key = 'bm_from_line'";
		$data  = array(
            $bm_user_id
		);

		$query   = $wpdb->prepare( $query . $where, $data );
		$results = $wpdb->get_results( $query );

		if ($results) {
			foreach ($results as $record) {
				$email_from = stripslashes($record->meta_value);
			}
		}


		// These ternary statements setup return values to use for if the variable is set or not set
		$subject = ($subject ? $subject : 'Invitation for Quote Response'); // The subject of the email
		$from = ($from ? 'From: ' . $from . ' <' . $email_from . '>' : 'From: ' . $bm_user_busname . ' <no-reply@wordpress.org>');


		// This query finds the permalink ID that the user set for the [bm-invite] page
		$query = "SELECT meta_value FROM " . BM_USERMETA . " WHERE user_id = %d AND meta_key = 'bm_invite_page'";
		$data  = array(
			$bm_user_id
		);

		$query   = $wpdb->prepare( $query, $data );
		$results = $wpdb->get_results( $query );

		foreach ($results as $record) {
			$link_id = $record->meta_value;
		}

		//  Based on the ID retrieved above, we build the link and parameters to execute when a user lands on that page to review a quote request
		$link = get_permalink($link_id);
		$params = array( 'invitation_id' => $bm_bid_id, 'hash' => $hash );
		$link = add_query_arg( $params, $link );
		$link = esc_url($link, '', 'db');

		//  Start message body
		$message = <<<MESSAGE
		<p style="font-family: Arial,Helvetica Neue,Helvetica,sans-serif; font-size: 14px; color: #444; margin: 0 0 15px 0; padding: 0;">Please follow the link below to sign in and review the quote request.</p>
		<p style="font-family: Arial,Helvetica Neue,Helvetica,sans-serif; font-size: 14px; color: #444; margin: 0 0 15px 0; padding: 0;"><a href="{$link}">Click here to view and respond</a>.</p>
MESSAGE;

		$message .= <<<MESSAGE
		<p style="height: 1px; width: 100%; display: block; border-top: 1px solid #444;"></p>

		<p style="font-family: Arial,Helvetica Neue,Helvetica,sans-serif; font-size: 14px; color: #444; margin: 0 0 15px 0; padding: 0; text-align: center;"><a href="https://www.supplyingcontractors.com/" style="color: #444; text-decoration: underline;">Supplying Contractors</a></p>
		<p style="font-family: Arial,Helvetica Neue,Helvetica,sans-serif; font-size: 12px; color: #444; margin: 0 0 15px 0; padding: 0; text-align: center;"><a href="mailto:contact@supplyingcontractors.com" style="color: #444; text-decoration: underline;">Email Us</a></p>
		<p style="font-family: Arial,Helvetica Neue,Helvetica,sans-serif; font-size: 12px; color: #444; margin: 0 0 15px 0; padding: 0; text-align: center;"><a href="tel:18558288481" style="color: #444; text-decoration: underline;">1-855-828-8481</a></p>
MESSAGE;
		//  End message body

		if (! empty($copy)) {
			$message = '<p style="font-family: Arial,Helvetica Neue,Helvetica,sans-serif; font-size: 14px; color: #444; margin: 0 0 15px 0; padding: 0;">' . $copy . '</p>' . $message;
		}

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			$from
			// 'Cc: jon@supplyingcontractors.com'
		);

		wp_mail($to, $subject, $message, $headers);

		//  End email setup

		$content = '';
		$content .= '<p class="success">Success!  Your invitation has been sent to ' . $to . '</p>';

		$link = BM_CDBOARD;
		$params = array( 'bmuser_bid_active' => $currentBid );
		$link = add_query_arg( $params, $link );
		$link = esc_url($link, '', 'db');

		$content .= '<a class="button" href="' . $link . '">Send Another Invite &raquo;</a>';

		echo $content;

	}
}