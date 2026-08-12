<?php
if (!defined('ABSPATH')) {
    exit;
}

/*
 * NEW BOOKING / PENDING
 */
add_action('wpbc_track_new_booking', 'bsm_send_pending_booking_sms', 10, 1);

function bsm_send_pending_booking_sms($params) {
    $booking_id = 0;

    if (is_array($params) && isset($params['booking_id'])) {
        $booking_id = absint($params['booking_id']);
    }

    if (!$booking_id && is_numeric($params)) {
        $booking_id = absint($params);
    }

    if (!$booking_id) {
        error_log('BSM PENDING SMS ERROR: Booking ID not found.');
        return;
    }

    if (bsm_sms_already_sent($booking_id, 'pending')) {
        error_log('BSM PENDING SMS: Already sent for Booking #' . $booking_id);
        return;
    }

    $details = bsm_get_booking_details($booking_id);

    if (!$details) {
        error_log('BSM PENDING SMS: Booking #' . $booking_id . ' not found.');
        return;
    }

    $message = bsm_pending_booking_message($details);

    bsm_send_twilio_sms(
        $details['phone'],
        $message,
        $booking_id,
        'pending'
    );
}

/*
 * BOOKING APPROVED
 */
add_action('wpbc_booking_action__approved', 'bsm_send_approved_booking_sms', 10, 2);

function bsm_send_approved_booking_sms($booking_id, $approved = 0) {
    $booking_id = absint($booking_id);

    if (!$booking_id) {
        error_log('BSM APPROVED SMS ERROR: Invalid Booking ID.');
        return;
    }

    if (bsm_sms_already_sent($booking_id, 'approved')) {
        error_log('BSM APPROVED SMS: Already sent for Booking #' . $booking_id);
        return;
    }

    $details = bsm_get_booking_details($booking_id);

    if (!$details) {
        error_log('BSM APPROVED SMS: Booking #' . $booking_id . ' not found.');
        return;
    }

    $message = bsm_approved_booking_message($details);

    bsm_send_twilio_sms(
        $details['phone'],
        $message,
        $booking_id,
        'approved'
    );
}
