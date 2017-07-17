<?php
function bm_send_email() {

	global $wpdb;

	//  Send this email to the contractor if the rupplier responds to the bid
	if (isset($_POST['responder_bid_response'])) {

		$bm_bid_id = (int)$_GET['invitation_id'];
		$bm_responder = $_POST['responder_busname'];

		$query = "SELECT bmuser_poc, bmuser_email, job_name FROM " . BM_USER . " LEFT OUTER JOIN " . BM_BIDS . " ON " . BM_USER . ".id=" . BM_BIDS . ".bmuser_id WHERE " . BM_BIDS . ".bid_id = %d";
		$data = array(
			$bm_bid_id
		);

		$query = $wpdb->prepare($query, $data);
		$record = $wpdb->get_row($query);
		$to = stripslashes($record->bmuser_email);
		$bm_job_name = stripslashes($record->job_name);
		$bm_poc = stripslashes($record->bmuser_poc);

		$subject = stripslashes($bm_responder) . ' responded to your bid #' . $bm_bid_id;
		$message  = '<p>Hello ' . stripslashes($bm_poc) . ',</p>';
		$message .= '<p>' . stripslashes($bm_responder) . ' has responded to your bid request for job: <strong>' . stripslashes($bm_job_name) . '</strong></p>';
		$message .= '<p>To login and review, please <a href="' . BM_SITE_URL . '/wp-admin/">click here</a>.</p>';

		$headers   = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: WP Bid Manager <no-reply@wordpress.org>'
		);

		wp_mail($to, $subject, $message, $headers);

	}
}

function bm_update_bid() {

	global $wpdb;

	$bm_bid_id = (int)sanitize_text_field($_POST['bid_id']);
	$bm_responder_busname = sanitize_text_field($_POST['responder_busname']);
	$bm_responder_poc = sanitize_text_field($_POST['responder_poc']);
	$bm_responder_street = sanitize_text_field($_POST['responder_address']);
	$bm_responder_street2 = sanitize_text_field($_POST['responder_address_cont']);
	$bm_responder_city = sanitize_text_field($_POST['responder_city']);
	$bm_responder_state = sanitize_text_field($_POST['responder_state']);
	$bm_responder_zip = sanitize_text_field($_POST['responder_zip']);
	$bm_responder_phone = sanitize_text_field($_POST['responder_phone']);
	$bm_responder_email = sanitize_text_field($_POST['responder_email']);
	$bm_responder_notes = sanitize_text_field($_POST['responder_notes']);
	$bm_bid_accepted = 0;
	$bm_quoted_total = sanitize_text_field($_POST['quoted_total']);

	$bm_quoted_amount = preg_replace("/[^0-9.]/", "", $bm_quoted_total);

	$bm_date_submitted = date('Y-m-d H:i:s');

	$bm_file_path = bm_handle_file_upload('responder_bid_file', 'bid_responses/');

	// Insert the record into the responses table
	$query = "INSERT INTO " . BM_BIDS_RESPONSES . " (bid_id, responder_busname, responder_poc, responder_phone, responder_email, responder_bid_file, responder_notes, date_submitted, bid_accepted, quoted_total)" .
		"VALUES (%d, %s, %s, %s, %s, %s, %s, %s, %d, %s);";
	$data = array(
		$bm_bid_id,
		$bm_responder_busname,
		$bm_responder_poc,
		$bm_responder_phone,
		$bm_responder_email,
		$bm_file_path,
		$bm_responder_notes,
		$bm_date_submitted,
		$bm_bid_accepted,
		$bm_quoted_amount
	);

	$query = $wpdb->prepare($query, $data);
	$wpdb->query($query);

	// Insert the record into the supplier table

	$address = $bm_responder_street . ', ' . $bm_responder_city . ' ' . $bm_responder_state . ' ' . $bm_responder_zip;

	$geocode = bm_get_lat_and_lng($address);
	if ($geocode !== FALSE) {
		// save $geocode[‘lat’] and $geocode[‘lng’] to database
		$bm_lat = $geocode['lat'];
		$bm_lng = $geocode['lng'];
	}

	// Find lat and lng in the bm_responder table and if the same combo exists, don't insert that record.  Otherwise do.
	$query = "SELECT lat, lng FROM " . BM_RESPONDERS . " WHERE lat = %s AND lng = %s";
	$data = array(
		$bm_lat,
		$bm_lng
	);
	$query = $wpdb->prepare($query, $data);
	$wpdb->query($query);
	$results = $wpdb->query($query);

	if (!$results) {

		$query = "INSERT INTO " . BM_RESPONDERS . " (id, responder_busname, responder_poc, responder_phone, responder_email, responder_cc_email, responder_street, responder_street_two, responder_city, responder_state, responder_zip, lat, lng, radius)" .
			"VALUES (%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d);";
		$data = array(
			'',
			$bm_responder_busname,
			$bm_responder_poc,
			$bm_responder_phone,
			$bm_responder_email,
			'',
			$bm_responder_street,
			$bm_responder_street2,
			$bm_responder_city,
			$bm_responder_state,
			$bm_responder_zip,
			$bm_lat,
			$bm_lng,
			''
		);

		$query = $wpdb->prepare($query, $data);
		$wpdb->query($query);
	}
	
	//  Get the number of responses a bid has
	
	$query = "SELECT has_response FROM " . BM_BIDS . " WHERE bid_id = %d";
	$data = array(
		$bm_bid_id
	);

	$query = $wpdb->prepare($query, $data);
	$record = $wpdb->get_row($query);
	
	$i = $record->has_response;
	
	++$i;
	
	//  Now update the bm_bids table letting it know that the bid has a submission

	$query = "UPDATE " . BM_BIDS . " SET has_response = %d WHERE bid_id = %d";
	$data = array(
		$i,
		$bm_bid_id
	);

	$query = $wpdb->prepare($query, $data);
	$wpdb->query($query);
}

function bm_responder_responses() {
	global $wpdb;

	$isResponseSubmitted = isset($_POST['responder_bid_response']);

	if ($isResponseSubmitted) {
		bm_update_bid();
		bm_send_email();

        $content = '<p class="success">You have submitted your response to the requester.</p>';

		return $content;

	}

	$recordId = (int)$_GET['invitation_id'];

	$query = "SELECT * FROM " . BM_BIDS . " LEFT OUTER JOIN " . BM_USER . " ON " . BM_BIDS . ".bmuser_id=" . BM_USER . ".id WHERE bid_id = %d";
	$data = array($recordId);
	$query = $wpdb->prepare($query, $data);
	$results = $wpdb->get_results($query);

	foreach ($results as $record) {
		$bm_bid_id       = $record->bid_id;
		$bm_job_name     = stripslashes($record->job_name);
		$bm_user_busname  = stripslashes($record->bmuser_busname);
		$bm_job_street   = stripslashes($record->job_street);
		$bm_job_city     = stripslashes($record->job_city);
		$bm_job_state    = stripslashes($record->job_state);
		$bm_job_zip      = $record->job_zip;
		$bm_file        = stripslashes($record->bmuser_bid_file);
		$date        = date('F jS, Y', strtotime($record->date_needed));
	}


	$bm_responder_responses  = <<<SUPPLIERBIDRESPONSE

	<h1>Quote Request for:  {$bm_job_name}</h1>

	<table class="form-table">
	<tr>
	<th scope="row">Bid ID#:</th>
	<td> {$bm_bid_id} </td>
	</tr>
	<tr>
	<th scope="row">Company:</th>
	<td> {$bm_user_busname} </td>
	</tr>
	<tr>
	<th scope="row">Job Name:</th>
	<td> {$bm_job_name} </td>
	</tr>
	<tr>
	<th scope="row">Need Quote By:</th>
	<td> {$date} </td>
	</tr>
	<tr>
	<th scope="row">Street:</th>
	<td> {$bm_job_street} </td>
	</tr>
	<tr>
	<th scope="row">City:</th>
	<td> {$bm_job_city} </td>
	</tr>
	<tr>
	<th scope="row">State:</th>
	<td>{$bm_job_state}</td>
	</tr>
	<tr>
	<th scope="row">ZIP:</th>
	<td>{$bm_job_zip}</td>
	</tr>
	<tr>
	<th scope="row">Download Material List:</th>
	<td><a class="button button_blue download" href="{$bm_file}">Material List for {$bm_job_name} </a></td>
	</tr>
	</table>
	
	<form action="" method="post" enctype="multipart/form-data">
	<fieldset>
	<table class="form-table">
	<tr>
	<td colspan="2"><h2>Please fill out the information below to respond to the request.</h2></td>
	</tr>
	<tr>
	<th scope="row"><span class="required">*</span>Business Name:</th>
	<td><input name="responder_busname" id="responder_busname" placeholder="ex: Jon's Supplying" type="text" required></td>
	</tr>
	<tr>
	<th scope="row"><span class="required">*</span>Point of Contact:</th>
	<td><input name="responder_poc" id="responder_poc" placeholder="ex: Jon Doe" type="text" required></td>
	</tr>
	<tr>
	<th scope="row"><span class="required">*</span>Address:</th>
	<td><input name="responder_address" id="responder_address" placeholder="ex: 123 Sesame St." type="text" required></td>
	</tr>
	<tr>
	<th scope="row">Address (cont.):</th>
	<td><input name="responder_address_cont" id="responder_address_cont" placeholder="ex: Unit 10A" type="text"></td>
	</tr>
	<tr>
	<th scope="row"><span class="required">*</span>City:</th>
	<td><input name="responder_city" id="responder_city" placeholder="ex: Denver" type="text" required></td>
	</tr>
	<tr>
	<th scope="row"><span class="required">*</span>State:</th>
	<td><input name="responder_state" id="responder_state" placeholder="ex: Colorado" type="text" required></td>
	</tr>
	<tr>
	<th scope="row"><span class="required">*</span>Zip Code:</th>
	<td><input name="responder_zip" id="responder_zip" placeholder="ex: 80249" type="text" required></td>
	</tr>
	<tr>
	<th scope="row"><span class="required">*</span>Phone:</th>
	<td><input name="responder_phone" id="responder_phone" placeholder="ex: 555.555.5555" type="phone" required></td>
	</tr>
	<tr>
	<th scope="row"><span class="required">*</span>Email:</th>
	<td><input name="responder_email" id="responder_email" placeholder="ex: jdoe@yahoo.com" type="email" required></td>
	</tr>
	<tr>
	<td colspan="2"><h2>Submit your response.</h2></td>
	</tr>
	<tr>
	<th scope="row"><span class="required">*</span>Bid Estimate:</th>
	<td><input name="responder_bid_file" id="responder_bid_file" type="file" required><p>This file is your quote back to the requester.  It is what they will see/read when they view your response.</p></td>
	</tr>
	<tr>
	<th scope="row"><span class="required">*</span>Total Amount Quoted:</th>
	<td><input name="quoted_total" id="quoted_total" type="text" required></td>
	</tr>
	<tr>
	<th scope="row">Special Notes:</th>
	<td><textarea cols="50" rows="10" name="responder_notes" id="responder_notes" placeholder="Type a message to the requester."></textarea></td>
	</tr>
	</table>
	</fieldset>
	<input type="hidden" name="bid_id" value="{$bm_bid_id}">
	<p><input id="submit" class="button button-primary" type="submit" name="responder_bid_response" value="Submit Response &raquo;"></p>
	</form>
	
SUPPLIERBIDRESPONSE;


	return $bm_responder_responses;
}