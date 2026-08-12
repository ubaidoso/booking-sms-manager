<?php
if (!defined('ABSPATH')) {
    exit;
}

function bsm_pending_booking_message($details) {
    $message  = "Your reservation with Go Fetch Services has been received and your approval is pending.\n\n";
    $message .= "You are receiving this SMS message in case your email confirmation fails to reach you.\n\n";
    $message .= "Booking Details\n";
    $message .= "--------------------\n";
    $message .= "Booking #: " . $details['booking_id'] . "\n";
    $message .= "Customer Name: " . $details['customer_name'] . "\n";
    $message .= "Email: " . $details['email'] . "\n";
    $message .= "Phone: " . $details['phone'] . "\n";
    $message .= "Number of Dogs: " . $details['visitors'] . "\n";

    if (!empty($details['dog_1'])) {
        $message .= "First Dog: " . $details['dog_1'] . "\n";
    }
    if (!empty($details['dog_2'])) {
        $message .= "Second Dog: " . $details['dog_2'] . "\n";
    }
    if (!empty($details['dog_3'])) {
        $message .= "Third Dog: " . $details['dog_3'] . "\n";
    }

    $message .= "Bathing Option: " . $details['bath'] . "\n";
    $message .= "Oatmeal Shampoo: " . $details['oatmeal_shampoo'] . "\n";
    $message .= "Nail Trim: " . $details['nail_trim'] . "\n";
    $message .= "Pick-up Time: " . $details['booking_time'] . "\n";
    $message .= "Booking Date(s): " . $details['date_text'] . "\n";

    if (!empty($details['comment'])) {
        $message .= "Comment: " . $details['comment'] . "\n";
    }

    $message .= "\n";
    $message .= "Thank you for using Go Fetch Services. If you have any questions, please use the contact information below.\n\n";
    $message .= "Go Fetch Services\n";
    $message .= "8914 Glendale Milford Rd\n";
    $message .= "Loveland, Ohio 45140\n";
    $message .= "513-791-4811\n";
    $message .= "www.gofetchservices.com";

    return $message;
}

function bsm_approved_booking_message($details) {
    $message  = "Your reservation with Go Fetch Services has been approved.\n\n";
    $message .= "Booking Details\n";
    $message .= "--------------------\n";
    $message .= "Booking #: " . $details['booking_id'] . "\n";
    $message .= "Customer Name: " . $details['customer_name'] . "\n";
    $message .= "Email: " . $details['email'] . "\n";
    $message .= "Phone: " . $details['phone'] . "\n";
    $message .= "Number of Dogs: " . $details['visitors'] . "\n";

    if (!empty($details['dog_1'])) {
        $message .= "First Dog: " . $details['dog_1'] . "\n";
    }
    if (!empty($details['dog_2'])) {
        $message .= "Second Dog: " . $details['dog_2'] . "\n";
    }
    if (!empty($details['dog_3'])) {
        $message .= "Third Dog: " . $details['dog_3'] . "\n";
    }

    $message .= "Bathing Option: " . $details['bath'] . "\n";
    $message .= "Oatmeal Shampoo: " . $details['oatmeal_shampoo'] . "\n";
    $message .= "Nail Trim: " . $details['nail_trim'] . "\n";
    $message .= "Pick-up Time: " . $details['booking_time'] . "\n";
    $message .= "Booking Date(s): " . $details['date_text'] . "\n";

    if (!empty($details['comment'])) {
        $message .= "Comment: " . $details['comment'] . "\n";
    }

    $message .= "\n";
    $message .= "Thank you, Go Fetch Team!\n\n";
    $message .= "Go Fetch Services\n";
    $message .= "513-791-4811 (call or text)";

    return $message;
}
