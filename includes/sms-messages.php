<?php

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| SMS TEMPLATE DEFAULTS
|--------------------------------------------------------------------------
*/

function bsm_get_default_sms_templates()
{
    return array(

        /*
        |--------------------------------------------------------------------------
        | PENDING / NEW BOOKING
        |--------------------------------------------------------------------------
        */

        'pending' => "Your reservation with Go Fetch Services has been received and your approval is pending.

You are receiving this SMS message in case your email confirmation fails to reach you.

Many emails are filtered as spam and certain email services do not allow successful delivery of automatically generated emails.

Booking Details
--------------------
Booking #: {booking_id}
Customer Name: {customer_name}
Email: {email}
Phone: {phone}
Number of Dogs: {visitors}
First Dog: {dog_1}
Second Dog: {dog_2}
Third Dog: {dog_3}
Bathing Option: {bath}
Oatmeal Shampoo: {oatmeal_shampoo}
Nail Trim: {nail_trim}
Pick-up Time: {booking_time}
Booking Date(s): {booking_date}
Comment: {comment}

Thank you for using Go Fetch Services, and if you have any questions, or needs regarding your reservation, please use the contact information below.

Go Fetch Services
8914 Glendale Milford Rd
Loveland, Ohio 45140
513-791-4811
www.gofetchservices.com",


        /*
        |--------------------------------------------------------------------------
        | APPROVED
        |--------------------------------------------------------------------------
        */

        'approved' => "Your reservation with Go Fetch Services has been approved.

Booking Details
--------------------
Booking #: {booking_id}
Customer Name: {customer_name}
Email: {email}
Phone: {phone}
Number of Dogs: {visitors}
First Dog: {dog_1}
Second Dog: {dog_2}
Third Dog: {dog_3}
Bathing Option: {bath}
Oatmeal Shampoo: {oatmeal_shampoo}
Nail Trim: {nail_trim}
Pick-up Time: {booking_time}
Booking Date(s): {booking_date}
Comment: {comment}

Thank you, Go Fetch Team!

Go Fetch Services
513-791-4811 (call or text)",


        /*
        |--------------------------------------------------------------------------
        | PRE-ARRIVAL
        |--------------------------------------------------------------------------
        */

        'pre_arrival' => "Reminder from Go Fetch Services: Your reservation is tomorrow.

Booking Details
--------------------
Booking #: {booking_id}
Customer Name: {customer_name}
Booking Date(s): {booking_date}
Pick-up Time: {booking_time}
First Dog: {dog_1}
Second Dog: {dog_2}
Third Dog: {dog_3}

If you have any questions regarding your reservation, please call or text 513-791-4811.

Go Fetch Services",


        /*
        |--------------------------------------------------------------------------
        | PRE-PICKUP
        |--------------------------------------------------------------------------
        */

        'pre_pickup' => "Reminder from Go Fetch Services: Your scheduled pick-up is coming up soon.

Booking Details
--------------------
Booking #: {booking_id}
Customer Name: {customer_name}
Booking Date: {booking_date}
Pick-up Time: {booking_time}
First Dog: {dog_1}
Second Dog: {dog_2}
Third Dog: {dog_3}

Please be ready for your scheduled pick-up.

If you have any questions, please call or text 513-791-4811.

Go Fetch Services",


        /*
        |--------------------------------------------------------------------------
        | COMPLETED
        |--------------------------------------------------------------------------
        */

        'completed' => "Go Fetch Services: Your reservation has been completed.

Booking #: {booking_id}
Name: {customer_name}
Booking Date: {booking_date}
Pick-up Time: {booking_time}

Thank you for choosing Go Fetch Services!",
    );
}


/*
|--------------------------------------------------------------------------
| GET SAVED SMS TEMPLATES
|--------------------------------------------------------------------------
*/

function bsm_get_sms_templates()
{
    $defaults = bsm_get_default_sms_templates();

    $saved = get_option(
        'bsm_sms_templates',
        array()
    );

    if (!is_array($saved)) {
        $saved = array();
    }

    return wp_parse_args(
        $saved,
        $defaults
    );
}


/*
|--------------------------------------------------------------------------
| REPLACE SMS PLACEHOLDERS
|--------------------------------------------------------------------------
*/

function bsm_replace_sms_placeholders($message, $details)
{
    $values = array(

        '{booking_id}' =>
            isset($details['booking_id'])
                ? $details['booking_id']
                : '',

        '{customer_name}' =>
            isset($details['customer_name'])
                ? $details['customer_name']
                : '',

        '{email}' =>
            isset($details['email'])
                ? $details['email']
                : '',

        '{phone}' =>
            isset($details['phone'])
                ? $details['phone']
                : '',

        '{visitors}' =>
            isset($details['visitors'])
                ? $details['visitors']
                : '',

        '{dog_1}' =>
            isset($details['dog_1'])
                ? $details['dog_1']
                : '',

        '{dog_2}' =>
            isset($details['dog_2'])
                ? $details['dog_2']
                : '',

        '{dog_3}' =>
            isset($details['dog_3'])
                ? $details['dog_3']
                : '',

        '{bath}' =>
            isset($details['bath'])
                ? $details['bath']
                : '',

        '{oatmeal_shampoo}' =>
            isset($details['oatmeal_shampoo'])
                ? $details['oatmeal_shampoo']
                : '',

        '{nail_trim}' =>
            isset($details['nail_trim'])
                ? $details['nail_trim']
                : '',

        '{booking_time}' =>
            isset($details['booking_time'])
                ? $details['booking_time']
                : '',

        '{booking_date}' =>
            isset($details['date_text'])
                ? $details['date_text']
                : '',

        '{comment}' =>
            isset($details['comment'])
                ? $details['comment']
                : '',
    );

    return strtr(
        $message,
        $values
    );
}


/*
|--------------------------------------------------------------------------
| PENDING / NEW BOOKING
|--------------------------------------------------------------------------
*/

function bsm_pending_booking_message($details)
{
    $templates = bsm_get_sms_templates();

    $message = $templates['pending'];

    return bsm_replace_sms_placeholders(
        $message,
        $details
    );
}


/*
|--------------------------------------------------------------------------
| APPROVED BOOKING
|--------------------------------------------------------------------------
*/

function bsm_approved_booking_message($details)
{
    $templates = bsm_get_sms_templates();

    $message = $templates['approved'];

    return bsm_replace_sms_placeholders(
        $message,
        $details
    );
}


/*
|--------------------------------------------------------------------------
| PRE-ARRIVAL REMINDER
|--------------------------------------------------------------------------
*/

function my_wpbc_pre_arrival_booking_message($details)
{
    $templates = bsm_get_sms_templates();

    $message = $templates['pre_arrival'];

    return bsm_replace_sms_placeholders(
        $message,
        $details
    );
}


/*
|--------------------------------------------------------------------------
| PRE-PICKUP REMINDER
|--------------------------------------------------------------------------
*/

function my_wpbc_pre_pickup_booking_message($details)
{
    $templates = bsm_get_sms_templates();

    $message = $templates['pre_pickup'];

    return bsm_replace_sms_placeholders(
        $message,
        $details
    );
}


/*
|--------------------------------------------------------------------------
| COMPLETED BOOKING
|--------------------------------------------------------------------------
*/

function bsm_completed_booking_message($details)
{
    $templates = bsm_get_sms_templates();

    $message = $templates['completed'];

    return bsm_replace_sms_placeholders(
        $message,
        $details
    );
}