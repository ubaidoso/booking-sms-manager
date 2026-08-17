<?php

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| SMS #1
| NEW BOOKING / PENDING
|--------------------------------------------------------------------------
*/

function bsm_pending_booking_message($details)
{
    $message = '';


    $message .=
        "Your reservation with Go Fetch Services has been received and your approval is pending.\n\n";


    $message .=
        "You are receiving this SMS message in case your email confirmation fails to reach you.\n\n";


    $message .=
        "Many emails are filtered as spam and certain email services do not allow successful delivery of automatically generated emails.\n\n";


    /*
    |--------------------------------------------------------------------------
    | BOOKING DETAILS
    |--------------------------------------------------------------------------
    */

    $message .=
        "Booking Details\n";

    $message .=
        "--------------------\n";

    $message .=
        "Booking #: " .
        $details['booking_id'] .
        "\n";

    $message .=
        "Customer Name: " .
        $details['customer_name'] .
        "\n";

    $message .=
        "Email: " .
        $details['email'] .
        "\n";

    $message .=
        "Phone: " .
        $details['phone'] .
        "\n";

    $message .=
        "Number of Dogs: " .
        $details['visitors'] .
        "\n";


    /*
    |--------------------------------------------------------------------------
    | DOGS
    |--------------------------------------------------------------------------
    */

    if (!empty($details['dog_1'])) {

        $message .=
            "First Dog: " .
            $details['dog_1'] .
            "\n";
    }


    if (!empty($details['dog_2'])) {

        $message .=
            "Second Dog: " .
            $details['dog_2'] .
            "\n";
    }


    if (!empty($details['dog_3'])) {

        $message .=
            "Third Dog: " .
            $details['dog_3'] .
            "\n";
    }


    /*
    |--------------------------------------------------------------------------
    | OPTIONS
    |--------------------------------------------------------------------------
    */

    $message .=
        "Bathing Option: " .
        $details['bath'] .
        "\n";

    $message .=
        "Oatmeal Shampoo: " .
        $details['oatmeal_shampoo'] .
        "\n";

    $message .=
        "Nail Trim: " .
        $details['nail_trim'] .
        "\n";

    $message .=
        "Pick-up Time: " .
        $details['booking_time'] .
        "\n";


    /*
    |--------------------------------------------------------------------------
    | DATE
    |--------------------------------------------------------------------------
    */

    $message .=
        "Booking Date(s): " .
        $details['date_text'] .
        "\n";


    /*
    |--------------------------------------------------------------------------
    | COMMENT
    |--------------------------------------------------------------------------
    */

    if (!empty($details['comment'])) {

        $message .=
            "Comment: " .
            $details['comment'] .
            "\n";
    }


    $message .= "\n";


    $message .=
        "Thank you for using Go Fetch Services, and if you have any questions, or needs regarding your reservation, please use the contact information below.\n\n";


    /*
    |--------------------------------------------------------------------------
    | COMPANY INFORMATION
    |--------------------------------------------------------------------------
    */

    $message .=
        "Go Fetch Services\n";

    $message .=
        "8914 Glendale Milford Rd\n";

    $message .=
        "Loveland, Ohio 45140\n";

    $message .=
        "513-791-4811\n";

    $message .=
        "www.gofetchservices.com";


    return $message;
}


/*
|--------------------------------------------------------------------------
| SMS #2
| BOOKING APPROVED
|--------------------------------------------------------------------------
*/

function bsm_approved_booking_message($details)
{
    $message = '';


    $message .=
        "Your reservation with Go Fetch Services has been approved.\n\n";


    /*
    |--------------------------------------------------------------------------
    | BOOKING DETAILS
    |--------------------------------------------------------------------------
    */

    $message .=
        "Booking Details\n";

    $message .=
        "--------------------\n";

    $message .=
        "Booking #: " .
        $details['booking_id'] .
        "\n";

    $message .=
        "Customer Name: " .
        $details['customer_name'] .
        "\n";

    $message .=
        "Email: " .
        $details['email'] .
        "\n";

    $message .=
        "Phone: " .
        $details['phone'] .
        "\n";

    $message .=
        "Number of Dogs: " .
        $details['visitors'] .
        "\n";


    /*
    |--------------------------------------------------------------------------
    | DOGS
    |--------------------------------------------------------------------------
    */

    if (!empty($details['dog_1'])) {

        $message .=
            "First Dog: " .
            $details['dog_1'] .
            "\n";
    }


    if (!empty($details['dog_2'])) {

        $message .=
            "Second Dog: " .
            $details['dog_2'] .
            "\n";
    }


    if (!empty($details['dog_3'])) {

        $message .=
            "Third Dog: " .
            $details['dog_3'] .
            "\n";
    }


    /*
    |--------------------------------------------------------------------------
    | OPTIONS
    |--------------------------------------------------------------------------
    */

    $message .=
        "Bathing Option: " .
        $details['bath'] .
        "\n";

    $message .=
        "Oatmeal Shampoo: " .
        $details['oatmeal_shampoo'] .
        "\n";

    $message .=
        "Nail Trim: " .
        $details['nail_trim'] .
        "\n";

    $message .=
        "Pick-up Time: " .
        $details['booking_time'] .
        "\n";


    /*
    |--------------------------------------------------------------------------
    | DATE
    |--------------------------------------------------------------------------
    */

    $message .=
        "Booking Date(s): " .
        $details['date_text'] .
        "\n";


    /*
    |--------------------------------------------------------------------------
    | COMMENT
    |--------------------------------------------------------------------------
    */

    if (!empty($details['comment'])) {

        $message .=
            "Comment: " .
            $details['comment'] .
            "\n";
    }


    $message .= "\n";


    $message .=
        "Thank you, Go Fetch Team!\n\n";


    $message .=
        "Go Fetch Services\n";

    $message .=
        "513-791-4811 (call or text)";


    return $message;
}


/*
|--------------------------------------------------------------------------
| SMS #3
| PRE-ARRIVAL REMINDER
|--------------------------------------------------------------------------
|
| Sent 24 hours before the booking date.
|
*/

function my_wpbc_pre_arrival_booking_message($details)
{
    $message = '';


    $message .=
        "Reminder from Go Fetch Services: Your reservation is tomorrow.\n\n";


    /*
    |--------------------------------------------------------------------------
    | BOOKING DETAILS
    |--------------------------------------------------------------------------
    */

    $message .=
        "Booking Details\n";

    $message .=
        "--------------------\n";

    $message .=
        "Booking #: " .
        $details['booking_id'] .
        "\n";

    $message .=
        "Customer Name: " .
        $details['customer_name'] .
        "\n";

    $message .=
        "Booking Date(s): " .
        $details['date_text'] .
        "\n";

    $message .=
        "Pick-up Time: " .
        $details['booking_time'] .
        "\n";


    /*
    |--------------------------------------------------------------------------
    | DOGS
    |--------------------------------------------------------------------------
    */

    if (!empty($details['dog_1'])) {

        $message .=
            "First Dog: " .
            $details['dog_1'] .
            "\n";
    }


    if (!empty($details['dog_2'])) {

        $message .=
            "Second Dog: " .
            $details['dog_2'] .
            "\n";
    }


    if (!empty($details['dog_3'])) {

        $message .=
            "Third Dog: " .
            $details['dog_3'] .
            "\n";
    }


    $message .= "\n";


    $message .=
        "If you have any questions regarding your reservation, please call or text 513-791-4811.\n\n";


    $message .=
        "Go Fetch Services";


    return $message;
}


/*
|--------------------------------------------------------------------------
| SMS #4
| PRE-PICKUP REMINDER
|--------------------------------------------------------------------------
|
| Sent 2 hours before pickup time.
|
*/

function my_wpbc_pre_pickup_booking_message($details)
{
    $message = '';


    $message .=
        "Reminder from Go Fetch Services: Your scheduled pick-up is coming up soon.\n\n";


    /*
    |--------------------------------------------------------------------------
    | BOOKING DETAILS
    |--------------------------------------------------------------------------
    */

    $message .=
        "Booking Details\n";

    $message .=
        "--------------------\n";

    $message .=
        "Booking #: " .
        $details['booking_id'] .
        "\n";

    $message .=
        "Customer Name: " .
        $details['customer_name'] .
        "\n";

    $message .=
        "Booking Date: " .
        $details['date_text'] .
        "\n";

    $message .=
        "Pick-up Time: " .
        $details['booking_time'] .
        "\n";


    /*
    |--------------------------------------------------------------------------
    | DOGS
    |--------------------------------------------------------------------------
    */

    if (!empty($details['dog_1'])) {

        $message .=
            "First Dog: " .
            $details['dog_1'] .
            "\n";
    }


    if (!empty($details['dog_2'])) {

        $message .=
            "Second Dog: " .
            $details['dog_2'] .
            "\n";
    }


    if (!empty($details['dog_3'])) {

        $message .=
            "Third Dog: " .
            $details['dog_3'] .
            "\n";
    }


    $message .= "\n";


    $message .=
        "Please be ready for your scheduled pick-up.\n\n";


    $message .=
        "If you have any questions, please call or text 513-791-4811.\n\n";


    $message .=
        "Go Fetch Services";


    return $message;
}