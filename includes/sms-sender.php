<?php
if (!defined('ABSPATH')) {
    exit;
}

function bsm_log_sms($data) {
    global $wpdb;

    $table = $wpdb->prefix . 'booking_sms_logs';
    $now   = current_time('mysql');

    $defaults = array(
        'booking_id'    => 0,
        'event_type'    => '',
        'recipient'     => '',
        'message'       => '',
        'status'        => 'failed',
        'twilio_sid'    => '',
        'twilio_status' => '',
        'http_code'     => 0,
        'api_response'  => '',
        'error_message' => '',
        'created_at'    => $now,
        'updated_at'    => $now,
    );

    $data = wp_parse_args($data, $defaults);

    $wpdb->insert(
        $table,
        $data,
        array(
            '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s'
        )
    );

    return (int) $wpdb->insert_id;
}

function bsm_update_sms_log($log_id, $data) {
    global $wpdb;

    $table = $wpdb->prefix . 'booking_sms_logs';

    $data['updated_at'] = current_time('mysql');

    $wpdb->update(
        $table,
        $data,
        array('id' => absint($log_id))
    );
}

function bsm_normalize_phone($phone) {
    $phone = trim((string) $phone);

    if ($phone === '') {
        return '';
    }

    // Keep + and digits. Twilio expects E.164 for best reliability.
    $phone = preg_replace('/(?!^\+)[^\d]/', '', $phone);

    return $phone;
}

function bsm_send_twilio_sms($phone, $message, $booking_id, $type) {
    $phone = bsm_normalize_phone($phone);
    $message = trim((string) $message);

    if ($phone === '') {
        $log_id = bsm_log_sms(array(
            'booking_id'    => absint($booking_id),
            'event_type'    => sanitize_key($type),
            'recipient'     => '',
            'message'       => $message,
            'status'        => 'failed',
            'error_message' => 'No phone number was found for this booking.',
        ));

        error_log('BSM SMS ERROR: No phone number for Booking #' . absint($booking_id));
        return false;
    }

    if ($message === '') {
        bsm_log_sms(array(
            'booking_id'    => absint($booking_id),
            'event_type'    => sanitize_key($type),
            'recipient'     => $phone,
            'message'       => '',
            'status'        => 'failed',
            'error_message' => 'SMS message is empty.',
        ));

        return false;
    }

    $settings = bsm_get_settings();

    if (empty($settings['account_sid']) || empty($settings['auth_token']) || empty($settings['from_number'])) {
        bsm_log_sms(array(
            'booking_id'    => absint($booking_id),
            'event_type'    => sanitize_key($type),
            'recipient'     => $phone,
            'message'       => $message,
            'status'        => 'failed',
            'error_message' => 'Twilio settings are incomplete.',
        ));

        error_log('BSM SMS ERROR: Twilio settings are incomplete.');
        return false;
    }

    $twilio_url = 'https://api.twilio.com/2010-04-01/Accounts/' .
        rawurlencode($settings['account_sid']) .
        '/Messages.json';

    $authorization = base64_encode(
        $settings['account_sid'] . ':' . $settings['auth_token']
    );

    $log_id = bsm_log_sms(array(
        'booking_id' => absint($booking_id),
        'event_type' => sanitize_key($type),
        'recipient'  => $phone,
        'message'    => $message,
        'status'     => 'sending',
    ));

    $response = wp_remote_post(
        $twilio_url,
        array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Basic ' . $authorization,
            ),
            'body' => array(
                'To'   => $phone,
                'From' => $settings['from_number'],
                'Body' => $message,
            ),
        )
    );

    if (is_wp_error($response)) {
        $error = $response->get_error_message();

        bsm_update_sms_log($log_id, array(
            'status'        => 'failed',
            'error_message' => $error,
        ));

        error_log(
            'BSM ' . strtoupper($type) . ' SMS ERROR - Booking #' .
            absint($booking_id) . ' - ' . $error
        );

        return false;
    }

    $response_code = (int) wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $decoded       = json_decode($response_body, true);

    $twilio_sid    = is_array($decoded) && !empty($decoded['sid']) ? sanitize_text_field($decoded['sid']) : '';
    $twilio_status = is_array($decoded) && !empty($decoded['status']) ? sanitize_text_field($decoded['status']) : '';
    $error_message = '';

    if (is_array($decoded) && !empty($decoded['message'])) {
        $error_message = sanitize_textarea_field($decoded['message']);
    }

    if ($response_code >= 200 && $response_code < 300) {
        bsm_update_sms_log($log_id, array(
            'status'        => 'success',
            'twilio_sid'    => $twilio_sid,
            'twilio_status' => $twilio_status,
            'http_code'     => $response_code,
            'api_response'  => $response_body,
            'error_message' => '',
        ));

        error_log(
            'BSM ' . strtoupper($type) . ' SMS SENT - Booking #' .
            absint($booking_id) . ' - To: ' . $phone .
            ' - SID: ' . $twilio_sid
        );

        return true;
    }

    bsm_update_sms_log($log_id, array(
        'status'        => 'failed',
        'twilio_sid'    => $twilio_sid,
        'twilio_status' => $twilio_status,
        'http_code'     => $response_code,
        'api_response'  => $response_body,
        'error_message' => $error_message ?: 'Twilio returned a non-success HTTP response.',
    ));

    error_log(
        'BSM ' . strtoupper($type) . ' SMS FAILED - Booking #' .
        absint($booking_id) . ' - HTTP ' . $response_code .
        ' - ' . $response_body
    );

    return false;
}

function bsm_sms_already_sent($booking_id, $type) {
    global $wpdb;

    $table = $wpdb->prefix . 'booking_sms_logs';

    $count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table}
             WHERE booking_id = %d
             AND event_type = %s
             AND status = 'success'",
            absint($booking_id),
            sanitize_key($type)
        )
    );

    return ((int) $count > 0);
}
