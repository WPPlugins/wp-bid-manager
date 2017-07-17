<?php
// Check to see if the bm_bids table exists, if not, write it
function bm_bids_check()
{
    global $wpdb;

    if ($wpdb->get_var("SHOW TABLES LIKE '" . BM_BIDS . "'") != BM_BIDS) {
        $sql = "CREATE TABLE IF NOT EXISTS " . BM_BIDS . " (
        `bid_id` int(9) NOT NULL AUTO_INCREMENT,
          `bmuser_id` int(11) NOT NULL,
          `job_name` varchar(255) NOT NULL,
          `date_needed` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
          `date_submitted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `bmuser_bid_file` varchar(355) NOT NULL,
          `job_street` varchar(255) NOT NULL,
          `job_street_two` varchar(100) NOT NULL,
          `job_city` varchar(100) NOT NULL,
          `job_state` varchar(100) NOT NULL,
          `job_zip` varchar(20) NOT NULL,
          `lat` varchar(15) NOT NULL,
          `lng` varchar(15) NOT NULL,
          `accepted_flag` tinyint(1) NOT NULL DEFAULT '0',
          `has_response` smallint(6) NOT NULL DEFAULT '0',
          PRIMARY KEY (`bid_id`),
          UNIQUE KEY `bmuser_id` (`bid_id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7736 ;
    ";

        $wpdb->query($sql);
    }

}

// Check to see if the bids responses table exists, if not, write it
function bm_responses_check()
{
    global $wpdb;

    if ($wpdb->get_var("SHOW TABLES LIKE '" . BM_BIDS_RESPONSES . "'") != BM_BIDS_RESPONSES) {
        $sql = "CREATE TABLE IF NOT EXISTS " . BM_BIDS_RESPONSES . " (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `bid_id` mediumint(9) NOT NULL,
          `responder_busname` text NOT NULL,
          `responder_poc` text NOT NULL,
          `responder_phone` text NOT NULL,
          `responder_email` text NOT NULL,
          `responder_bid_file` varchar(355) NOT NULL,
          `responder_notes` varchar(1500) NOT NULL,
          `date_submitted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `bid_accepted` tinyint(1) NOT NULL DEFAULT '0',
          `quoted_total` decimal(19, 4) NOT NULL,
          `hidden` tinyint(4) NOT NULL DEFAULT '0',
          PRIMARY KEY(`id`)
        ) ENGINE = InnoDB  DEFAULT CHARSET = latin1 AUTO_INCREMENT = 178;
        ";

        $wpdb->query($sql);

    }
}

// Check to see if the bm user/requester table exists, if not, write it
function bm_user_check()
{
    global $wpdb;

    if ($wpdb->get_var("SHOW TABLES LIKE '" . BM_USER . "'") != BM_USER) {
        $sql = "CREATE TABLE IF NOT EXISTS " . BM_USER . " (
          `id` mediumint(9) NOT NULL AUTO_INCREMENT,
          `bmuser_busname` varchar(255) NOT NULL,
          `bmuser_poc` varchar(100) NOT NULL,
          `bmuser_phone` varchar(25) NOT NULL,
          `bmuser_email` varchar(255) NOT NULL,
          `bmuser_street` varchar(255) NOT NULL,
          `bmuser_street_two` varchar(255) NOT NULL,
          `bmuser_city` varchar(255) NOT NULL,
          `bmuser_state` varchar(100) NOT NULL,
          `bmuser_zip` int(20) NOT NULL,
          `lat` float(10,6) NOT NULL,
          `lng` float(10,6) NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=104 ;
        ";

        $wpdb->query($sql);

    }
}

// Check to see if the responder table exists, if not, write it
function bm_responder_check()
{
    global $wpdb;

    if ($wpdb->get_var("SHOW TABLES LIKE '" . BM_RESPONDERS . "'") != BM_RESPONDERS) {
        $sql = "CREATE TABLE IF NOT EXISTS " . BM_RESPONDERS . " (
          `id` mediumint(9) NOT NULL AUTO_INCREMENT,
          `responder_busname` varchar(255) NOT NULL,
          `responder_poc` varchar(100) NOT NULL,
          `responder_phone` varchar(25) NOT NULL,
          `responder_email` varchar(150) NOT NULL,
          `responder_cc_email` text,
          `responder_street` varchar(255) NOT NULL,
          `responder_street_two` varchar(255) NOT NULL,
          `responder_city` varchar(100) NOT NULL,
          `responder_state` varchar(100) NOT NULL,
          `responder_zip` int(20) NOT NULL,
          `lat` float(10,6) NOT NULL,
          `lng` float(10,6) NOT NULL,
          `radius` int(4) NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=104 ;
        ";

        $wpdb->query($sql);

    }
}

// Check to see if the email table exists, if not, write it
function bm_email_check()
{
    global $wpdb;

    if ($wpdb->get_var("SHOW TABLES LIKE '" . BM_EMAILS . "'") != BM_EMAILS) {
        $sql = "CREATE TABLE IF NOT EXISTS " . BM_EMAILS . " (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `bid_id` mediumint(9) NOT NULL,
          `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `hash` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=12 ;
        ";

        $wpdb->query($sql);

    }
}

// Check to see if the notifications table exists, if not, write it
function bm_notifications_check()
{
    global $wpdb;

    if ($wpdb->get_var("SHOW TABLES LIKE '" . BM_NOTIFICATIONS . "'") != BM_NOTIFICATIONS) {
        $sql = "CREATE TABLE IF NOT EXISTS " . BM_NOTIFICATIONS . " (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `notification` varchar(1000) NOT NULL,
          `dont_show` tinyint(1) NOT NULL DEFAULT '1',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;
        ";

        $wpdb->query($sql);

    }
}