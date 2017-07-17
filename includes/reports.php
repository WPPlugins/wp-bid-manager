<?php
//  This function provides a list of options the supplier can run reports for
function bm_reportOptions()
{
    if (is_user_logged_in()) {
        $content = '';

        // $content .= '<h2>Report Generator</h2>';
        $content .= '<div class="wrap">';
        $content .= '<form id="responder_report_controller" action="" method="post" enctype="multipart/form-data">';
        $content .= '<h3>Pick a date range:</h3>';
        $content .= '<div class="report_option">';
        $content .= '<input id="report_start" type="date" class="input-small" name="report_date_start" placeholder="ex: mm/dd/yyyy" />';
        $content .= ' <span class="add-on" style="vertical-align: top;height:20px">to</span> ';
        $content .= '<input id="report_end" type="date" class="input-small" name="report_date_end" placeholder="ex: mm/dd/yyyy" />';
        $content .= '</div>';
        $content .= '<p>&mdash; OR &mdash;</p>';
        $content .= '<div class="report_option">';
        $content .= '<input id="beginning_of_time" type="checkbox" name="beginning_of_time" />';
        $content .= '<label for="beginning_of_time">The beginning of time</label>';
        $content .= '</div>';
        $content .= '<h3>Select your options:</h3>';
        $content .= '<div class="report_option">';
        $content .= '<input id="no_response_given" type="checkbox" name="no_response_given" />';
        $content .= '<label for="no_response_given">Bids with no response</label>';
        $content .= '</div>';
        $content .= '<div class="report_option">';
        $content .= '<input id="con_accepted_bids" type="checkbox" name="con_accepted_bids" />';
        $content .= '<label for="con_accepted_bids">All bids accepted</label>';
        $content .= '</div>';
        $content .= '<div class="report_option">';
        $content .= '<input id="avg_bid_amount" type="checkbox" name="avg_bid_amount" />';
        $content .= '<label for="avg_bid_amount">Average bid amount</label>';
        $content .= '</div>';
        $content .= '<div class="report_option">';
        $content .= '<input id="total_purchased" type="checkbox" name="total_purchased" />';
        $content .= '<label for="total_purchased">Total amount</label>';
        $content .= '</div>';
        $content .= '<div class="report_option">';
        $content .= '<input id="high_low" type="checkbox" name="high_low" />';
        $content .= '<label for="high_low">High and low</label>';
        $content .= '</div>';
        $content .= '<div class="report_option">';
        $content .= '<input id="acceptance_rate" type="checkbox" name="acceptance_rate" />';
        $content .= '<label for="acceptance_rate">Acceptance rate</label>';
        $content .= '</div>';

        $content .= '<input class="button-primary" type="submit" name="run_responder_report" value="Run Report &raquo;">';
        $content .= '</form>';
        $content .= '</div>';

        echo $content;
    }
}

//  Run a report to give the average worth of bids the supplier has bid on.  For logged in user
function bm_report()
{

    global $results;
    global $heading;


    if (isset($_POST['run_responder_report'])) {
        global $wpdb;
        global $current_user;
        get_currentuserinfo();

        $bm_user_id = $current_user->ID;

        // Get the report option start date
        $report_start = sanitize_text_field($_POST['report_date_start']);
        $report_start = date('Y-m-d H:i:s', strtotime($report_start));

        // Get the report option end date
        $report_end = sanitize_text_field($_POST['report_date_end']);
        $report_end = date('Y-m-d H:i:s', strtotime($report_end));


        $content  = '';
        $content .= '<div class="wrap">';
        $heading = '';

        // What to do when the no reponse report is checked
        if (isset($_POST['no_response_given'])) {

            $heading = '<h2>Bids with no responses</h2>';


            if (isset($_POST['beginning_of_time'])) {
                $query = "SELECT job_name, job_street, job_street_two, job_city, job_state, job_zip, accepted_flag, quoted_total FROM " . BM_BIDS . " LEFT OUTER JOIN " . BM_BIDS_RESPONSES . " ON " . BM_BIDS_RESPONSES . ".bid_id=" . BM_BIDS . ".bid_id WHERE " . BM_BIDS . ".bmuser_id = %d AND has_response = 0";

                $data = array(
                    $bm_user_id
                );
                $query = $wpdb->prepare($query, $data);
                $results = $wpdb->get_results($query);

                if ($results) {
                    bm_reportTable();
                } else {

                    $content .= $heading;
                    $content .= '<p>You do not have any results to display.</p>';
                }
            } else {
                $query = "SELECT job_name, job_street, job_street_two, job_city, job_state, job_zip, accepted_flag, quoted_total FROM " . BM_BIDS . " LEFT OUTER JOIN " . BM_BIDS_RESPONSES . " ON " . BM_BIDS_RESPONSES . ".bid_id=" . BM_BIDS . ".bid_id WHERE " . BM_BIDS . ".bmuser_id = %d AND " . BM_BIDS . ".date_submitted BETWEEN %s AND %s AND has_response = 0";

                $data = array(
                    $bm_user_id,
                    $report_start,
                    $report_end
                );
                $query = $wpdb->prepare($query, $data);
                $results = $wpdb->get_results($query);

                if ($results) {
                    bm_reportTable();
                } else {

                    $content .= $heading;
                    $content .= '<p>You do not have any results to display.</p>';
                }
            }
        }

        // What to do if the accepted bids report is checked
        if (isset($_POST['con_accepted_bids'])) {

            $heading = '<h2>Here are the bids you have accepted</h2>';

            if (isset($_POST['beginning_of_time'])) {
                $query = "SELECT job_name, job_street, job_street_two, job_city, job_state, job_zip, accepted_flag, quoted_total FROM " . BM_BIDS . " LEFT OUTER JOIN " . BM_BIDS_RESPONSES . " ON " . BM_BIDS_RESPONSES . ".bid_id=" . BM_BIDS . ".bid_id WHERE " . BM_BIDS . ".bmuser_id = %d AND accepted_flag = 1";

                $data = array(
                    $bm_user_id
                );
                $query = $wpdb->prepare($query, $data);
                $results = $wpdb->get_results($query);

                if ($results) {
                    bm_reportTable();
                } else {

                    $content .= $heading;
                    $content .= '<p>You do not have any results to display.</p>';
                }
            } else {
                $query = "SELECT job_name, job_street, job_street_two, job_city, job_state, job_zip, accepted_flag, quoted_total FROM " . BM_BIDS . " LEFT OUTER JOIN " . BM_BIDS_RESPONSES . " ON " . BM_BIDS_RESPONSES . ".bid_id=" . BM_BIDS . ".bid_id WHERE " . BM_BIDS . ".bmuser_id = %d AND " . BM_BIDS . ".date_submitted BETWEEN %s AND %s AND accepted_flag = 1";

                $data = array(
                    $bm_user_id,
                    $report_start,
                    $report_end
                );
                $query = $wpdb->prepare($query, $data);
                $results = $wpdb->get_results($query);

                if ($results) {
                    bm_reportTable();
                } else {

                    $content .= $heading;
                    $content .= '<p>You do not have any results to display.</p>';
                }

            }
        }

        // What to do if the average bid amount report is checked
        if (isset($_POST['avg_bid_amount'])) {

            $heading = '<h2>Here is the average material request amount</h2>';

            if (isset($_POST['beginning_of_time'])) {
                $query = "SELECT quoted_total FROM " . BM_BIDS . " LEFT OUTER JOIN " . BM_BIDS_RESPONSES . " ON " . BM_BIDS_RESPONSES . ".bid_id=" . BM_BIDS . ".bid_id WHERE " . BM_BIDS . ".bmuser_id = %d AND accepted_flag = 1";

                $data = array(
                    $bm_user_id
                );
                $query = $wpdb->prepare($query, $data);
                $results = $wpdb->get_results($query);

                if ($results) {

                    $quotes = array();
                    foreach ($results as $record) {
                        $quotes[] = $record->quoted_total;
                    }

                    $sum = array_sum($quotes);  //  Totals up the quote totals

                    $total_bids = $wpdb->num_rows;  //  Totals up the number of records for accepted quotes

                    $average = ($sum / $total_bids);

                    $content .= $heading;

                    $content .= '<p>Your average material request: <strong>$' . number_format($average, 2) . '</strong> &ndash; (Avg. amt. per/bid response)</p>';
                } else {

                    $content .= $heading;
                    $content .= '<p>You do not have any results to display.</p>';
                }
            } else {
                $query = "SELECT quoted_total FROM " . BM_BIDS . " LEFT OUTER JOIN " . BM_BIDS_RESPONSES . " ON " . BM_BIDS_RESPONSES . ".bid_id=" . BM_BIDS . ".bid_id WHERE " . BM_BIDS . ".bmuser_id = %d AND " . BM_BIDS . ".date_submitted BETWEEN %s AND %s AND accepted_flag = 1";

                $data = array(
                    $bm_user_id,
                    $report_start,
                    $report_end
                );
                $query = $wpdb->prepare($query, $data);
                $results = $wpdb->get_results($query);

                if ($results) {

                    $quotes = array();
                    foreach ($results as $record) {
                        $quotes[] = $record->quoted_total;
                    }

                    $sum = array_sum($quotes);  //  Totals up the quote totals

                    $total_bids = $wpdb->num_rows;  //  Totals up the number of records for accepted quotes

                    $average = ($sum / $total_bids);

                    $content .= $heading;

                    $content .= '<p>Your average material request: <strong>$' . number_format($average, 2) . '</strong> &ndash; (Avg. amt. per/bid response)</p>';
                } else {

                    $content .= $heading;
                    $content .= '<p>You do not have any results to display.</p>';
                }
            }
        }

        // What to do if the total amount of materials report is checked
        if (isset($_POST['total_purchased'])) {

            $heading = '<h2>Here is the total amount of materials you have purchased</h2>';

            if (isset($_POST['beginning_of_time'])) {
                $query = "SELECT quoted_total FROM " . BM_BIDS . " LEFT OUTER JOIN " . BM_BIDS_RESPONSES . " ON " . BM_BIDS_RESPONSES . ".bid_id=" . BM_BIDS . ".bid_id WHERE " . BM_BIDS . ".bmuser_id = %d AND accepted_flag = 1";

                $data = array(
                    $bm_user_id
                );
                $query = $wpdb->prepare($query, $data);
                $results = $wpdb->get_results($query);

                if ($results) {

                    $total_revenue = array();

                    foreach ($results as $record) {
                        $total_revenue[] = $record->quoted_total;
                    }

                    $total_revenue = array_sum($total_revenue);

                    $content .= $heading;
                    $content .= '<p>Total money spent on materials: <strong>$' . number_format($total_revenue, 2) . '</strong> &ndash; (bids accepted)</p>';
                } else {

                    $content .= $heading;
                    $content .= '<p>You do not have any results to display.</p>';
                }
            } else {
                $query = "SELECT quoted_total FROM " . BM_BIDS . " LEFT OUTER JOIN " . BM_BIDS_RESPONSES . " ON " . BM_BIDS_RESPONSES . ".bid_id=" . BM_BIDS . ".bid_id WHERE " . BM_BIDS . ".bmuser_id = %d AND " . BM_BIDS . ".date_submitted BETWEEN %s AND %s AND accepted_flag = 1";

                $data = array(
                    $bm_user_id,
                    $report_start,
                    $report_end
                );
                $query = $wpdb->prepare($query, $data);
                $results = $wpdb->get_results($query);

                if ($results) {

                    $total_revenue = array();

                    foreach ($results as $record) {
                        $total_revenue[] = $record->quoted_total;
                    }

                    $total_revenue = array_sum($total_revenue);

                    $content .= $heading;
                    $content .= '<p>Total money spent on materials: <strong>$' . number_format($total_revenue, 2) . '</strong> &ndash; (bids accepted)</p>';
                } else {

                    $content .= $heading;
                    $content .= '<p>You do not have any results to display.</p>';
                }
            }

        }

        // What to do if the high and low for materials report is checked
        if (isset($_POST['high_low'])) {

            $heading = '<h2>Here is the high and the low of your purchases</h2>';

            $query = "SELECT job_name, job_street, job_street_two, job_city, job_state, job_zip, accepted_flag, quoted_total FROM " . BM_BIDS . " LEFT OUTER JOIN " . BM_BIDS_RESPONSES . " ON " . BM_BIDS_RESPONSES . ".bid_id=" . BM_BIDS . ".bid_id";

            if (isset($_POST['beginning_of_time'])) {
                $where = " WHERE " . BM_BIDS . ".bmuser_id = %d AND accepted_flag = 1";

                $data = array(
                    $bm_user_id
                );
            } else {
                $where = " WHERE " . BM_BIDS . ".bmuser_id = %d AND " . BM_BIDS . ".date_submitted BETWEEN %s AND %s AND accepted_flag = 1";

                $data = array(
                    $bm_user_id,
                    $report_start,
                    $report_end
                );
            }

            $query = $wpdb->prepare($query . $where, $data);
            $results = $wpdb->get_results($query);

            if ($results) {
                $quotes = array();
                foreach ($results as $record) {
                    $quotes[] = $record->quoted_total;
                }

                $max = max($quotes);
                $min = min($quotes);

                $content .= $heading;
                $content .= '<p>The least costly material list: <strong>$' . number_format($min, 2) . '</strong></p>';
                $content .= '<p>The most costly material list: <strong>$' . number_format($max, 2) . '</strong></p>';
            } else {
                $content .= $heading;
                $content .= '<p>You do not have any results to display.</p>';
            }
        }

        // What to do if the acceptance rate report is checked
        if (isset($_POST['acceptance_rate'])) {

            $heading = '<h2>Here is your acceptance rate of all material lists</h2>';

            if (isset($_POST['beginning_of_time'])) {

                //  Query for the total bid responses
                $query = "SELECT has_response FROM " . BM_BIDS . " WHERE bmuser_id = %d AND has_response > 0";

                $data = array(
                    $bm_user_id
                );
                $query = $wpdb->prepare($query, $data);
                $results = $wpdb->get_results($query);

                if ($results) {

                    $total_bid_responses = $wpdb->num_rows;

                    //  Query for the total bids accepted
                    $query = "SELECT accepted_flag FROM " . BM_BIDS . " WHERE bmuser_id = %d AND accepted_flag = 1";

                    $data = array(
                        $bm_user_id
                    );
                    $query = $wpdb->prepare($query, $data);
                    $results = $wpdb->get_results($query);

                    $total_accepted = $wpdb->num_rows;

                    $average = ($total_accepted / $total_bid_responses);

                    $content .= $heading;
                    $content .= '<p>You have a <strong>' . number_format($average, 2) * 100 . '&percnt;</strong> acceptance rate.</p>';
                } else {

                    $content .= $heading;
                    $content .= '<p>You do not have any results to display.</p>';
                }
            } else {

                //  Query for the total bid responses
                $query = "SELECT has_response FROM " . BM_BIDS . " WHERE bmuser_id = %d AND date_submitted BETWEEN %s AND %s AND has_response > 0";

                $data = array(
                    $bm_user_id,
                    $report_start,
                    $report_end
                );
                $query = $wpdb->prepare($query, $data);
                $results = $wpdb->get_results($query);

                if ($results) {


                    $total_bid_responses = $wpdb->num_rows;

                    //  Query for the total bids accepted
                    $query = "SELECT accepted_flag FROM " . BM_BIDS . " WHERE bmuser_id = %d AND accepted_flag = 1";

                    $data = array(
                        $bm_user_id
                    );
                    $query = $wpdb->prepare($query, $data);
                    $results = $wpdb->get_results($query);

                    $total_accepted = $wpdb->num_rows;

                    $average = ($total_accepted / $total_bid_responses);

                    $content .= $heading;
                    $content .= '<p>You have a <strong>' . number_format($average, 2) * 100 . '&percnt;</strong> acceptance rate.</p>';
                } else {

                    $content .= $heading;
                    $content .= '<p>You do not have any results to display.</p>';
                }

            }


        }

        $content .= '</div>';

        echo $content;
    }
}

function bm_reportErrors()
{

    $today = date("Y-m-d H:i:s");

    $error = '';

    // Have to pick a date range for the report
    if (!isset($_POST['beginning_of_time']) && (empty($_POST['report_date_start']) || empty($_POST['report_date_end']))) {
        $error .= '<p class="error">You must select a date option for the report to run.</p>';
    }

    // Must make at least one selection
    if (!isset($_POST['no_response_given']) && !isset($_POST['con_accepted_bids']) && !isset($_POST['avg_bid_amount']) && !isset($_POST['total_purchased']) && !isset($_POST['high_low']) && !isset($_POST['acceptance_rate'])) {
        $error .= '<p class="error">You must make at least one selection to run the report.</p>';
    }

    // Check that the start date is not set for the future
    if (!empty($_POST['report_date_start'])) {

        $report_start = sanitize_text_field($_POST['report_date_start']);
        $report_start = date('Y-m-d H:i:s', strtotime($report_start));

        if ($report_start > $today) {
            $error .= '<p class="error">You selected a start date that is in the future.  Please select a date that has already past.</p>';
        }
    }

    // Check that the end date is not set for the future
    if (!empty($_POST['report_date_end'])) {

        $report_end = sanitize_text_field($_POST['report_date_end']);
        $report_end = date('Y-m-d H:i:s', strtotime($report_end));

        if ($report_end > $today) {
            $error .= '<p class="error">You selected an end date that is in the future.  Please select a date that is not in the future.</p>';
        }
    }

    // Make sure the user did not check the box for beginning of time as well as set a date range
    if (!empty($_POST['report_date_start']) && !empty($_POST['report_date_end']) && isset($_POST['beginning_of_time'])) {
        $error .= '<p class="error">You cannot set a date range and check the option "The beginning of time".  Please choose one or the other.</p>';
    }


    echo $error;

    if ($error) {
        return TRUE;
    } else {
        return FALSE;
    }

}

function bm_reportTable()
{

    global $results;
    global $heading;
    $i = $sum = 0;

    $content = $heading;

    $content .= '
				<div class="blue_table"><table id="bm_report_table" class="blue_table" border="1" bordercolor="#000" cellpadding="5" width="100%">
				<thead>
				<tr>
				<th>
				#
				</th>
				<th>
				Job Name
				</th>
				<th>
				Street
				</th>
				<th>
				City
				</th>
				<th>
				State
				</th>
				<th>
				Zip Code
				</th>
				<th>
				Accepted
				</th>
				<th>
				Quoted Total
				</th>
				</tr>
				</thead>
				<tbody>';
    foreach ($results as $record) {
        $i++;
        $sum += $record->quoted_total;
        $content .= '<tr>';
        $content .= '<td>' . $i . '</td>';
        $content .= '<td>' . stripslashes($record->job_name) . '</td>';
        $content .= '<td>' . stripslashes($record->job_street) . '</td>';
        $content .= '<td>' . stripslashes($record->job_city) . '</td>';
        $content .= '<td>' . stripslashes($record->job_state) . '</td>';
        $content .= '<td>' . $record->job_zip . '</td>';


        if ($record->accepted_flag == 1) {
            $content .= '<td class="accepted"> Yes </td>';
        } elseif ($record->accepted_flag == 0) {
            $content .= '<td class="not_accepted"> No </td>';
        } else {
            $content .= '';
        }

        $content .= '<td>$' . number_format($record->quoted_total, 2) . '</td>';
        $content .= '</tr>';
    }
    $content .= '
				</tbody></table></div>';

    echo $content;
}


function bm_report_controller()
{

    if (is_user_logged_in()) {
        if (isset($_POST['run_responder_report']) && bm_reportErrors() == FALSE) {
            return bm_report();
        } else {
            return bm_reportOptions();
        }
    }
}