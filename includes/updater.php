<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| BOOKING SMS MANAGER - GITHUB UPDATER
|--------------------------------------------------------------------------
*/

define(
    'BSM_GITHUB_REPO',
    'ubaidoso/booking-sms-manager'
);

define(
    'BSM_PLUGIN_FILE',
    'booking-sms-manager/booking-sms-manager.php'
);


/*
|--------------------------------------------------------------------------
| GET LATEST GITHUB RELEASE
|--------------------------------------------------------------------------
*/

function bsm_get_github_latest_release()
{
    $cache_key = 'bsm_github_latest_release';

    $cached = get_transient($cache_key);

    if ($cached !== false) {
        return $cached;
    }

    $url = 'https://api.github.com/repos/' .
        BSM_GITHUB_REPO .
        '/releases/latest';

    $response = wp_remote_get(
        $url,
        array(
            'timeout' => 15,
            'headers' => array(
                'Accept'     => 'application/vnd.github+json',
                'User-Agent' => 'Booking-SMS-Manager-WordPress',
            ),
        )
    );

    if (is_wp_error($response)) {
        return false;
    }

    $code = wp_remote_retrieve_response_code($response);

    if ($code !== 200) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);

    $release = json_decode($body);

    if (
        !is_object($release) ||
        empty($release->tag_name)
    ) {
        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | CACHE FOR 6 HOURS
    |--------------------------------------------------------------------------
    */

    set_transient(
        $cache_key,
        $release,
        6 * HOUR_IN_SECONDS
    );

    return $release;
}


/*
|--------------------------------------------------------------------------
| CLEAN VERSION NUMBER
|--------------------------------------------------------------------------
*/

function bsm_clean_version($version)
{
    return ltrim(
        trim((string) $version),
        'vV'
    );
}


/*
|--------------------------------------------------------------------------
| WORDPRESS UPDATE CHECK
|--------------------------------------------------------------------------
*/

add_filter(
    'pre_set_site_transient_update_plugins',
    'bsm_check_for_plugin_update'
);


function bsm_check_for_plugin_update($transient)
{
    if (!is_object($transient)) {
        return $transient;
    }

    /*
    |--------------------------------------------------------------------------
    | CURRENT PLUGIN VERSION
    |--------------------------------------------------------------------------
    */

    $plugin_file = BSM_PLUGIN_FILE;

    $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;

    if (!file_exists($plugin_path)) {
        return $transient;
    }

    $plugin_data = get_plugin_data(
        $plugin_path,
        false,
        false
    );

    $current_version = isset(
        $plugin_data['Version']
    )
        ? $plugin_data['Version']
        : '0.0.0';


    /*
    |--------------------------------------------------------------------------
    | GET GITHUB RELEASE
    |--------------------------------------------------------------------------
    */

    $release = bsm_get_github_latest_release();

    if (!$release) {
        return $transient;
    }


    /*
    |--------------------------------------------------------------------------
    | IGNORE DRAFT / PRE-RELEASE
    |--------------------------------------------------------------------------
    */

    if (
        !empty($release->draft) ||
        !empty($release->prerelease)
    ) {
        return $transient;
    }


    /*
    |--------------------------------------------------------------------------
    | GITHUB VERSION
    |--------------------------------------------------------------------------
    */

    $github_version = bsm_clean_version(
        $release->tag_name
    );


    /*
    |--------------------------------------------------------------------------
    | FIND ZIP ASSET
    |--------------------------------------------------------------------------
    */

    $package_url = '';

    if (
        !empty($release->assets) &&
        is_array($release->assets)
    ) {

        foreach ($release->assets as $asset) {

            if (
                !empty($asset->name) &&
                strtolower($asset->name) ===
                'booking-sms-manager.zip'
            ) {

                $package_url =
                    $asset->browser_download_url;

                break;
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | NO ZIP = NO UPDATE
    |--------------------------------------------------------------------------
    */

    if (empty($package_url)) {
        return $transient;
    }


    /*
    |--------------------------------------------------------------------------
    | COMPARE VERSIONS
    |--------------------------------------------------------------------------
    */

    if (
        version_compare(
            $github_version,
            $current_version,
            '>'
        )
    ) {

        $transient->response[$plugin_file] =
            (object) array(

                'id' =>
                    'https://github.com/' .
                    BSM_GITHUB_REPO,

                'slug' =>
                    'booking-sms-manager',

                'plugin' =>
                    $plugin_file,

                'new_version' =>
                    $github_version,

                'url' =>
                    !empty($release->html_url)
                        ? $release->html_url
                        : 'https://github.com/' .
                          BSM_GITHUB_REPO,

                'package' =>
                    $package_url,

                'tested' =>
                    get_bloginfo('version'),

            );
    }

    return $transient;
}


/*
|--------------------------------------------------------------------------
| PLUGIN INFORMATION / VIEW DETAILS
|--------------------------------------------------------------------------
*/

add_filter(
    'plugins_api',
    'bsm_plugin_information',
    20,
    3
);


function bsm_plugin_information(
    $result,
    $action,
    $args
) {

    if (
        $action !== 'plugin_information'
    ) {
        return $result;
    }

    if (
        empty($args->slug) ||
        $args->slug !== 'booking-sms-manager'
    ) {
        return $result;
    }

    $release = bsm_get_github_latest_release();

    if (!$release) {
        return $result;
    }

    $version = bsm_clean_version(
        $release->tag_name
    );

    $package_url = '';

    if (
        !empty($release->assets) &&
        is_array($release->assets)
    ) {

        foreach ($release->assets as $asset) {

            if (
                !empty($asset->name) &&
                strtolower($asset->name) ===
                'booking-sms-manager.zip'
            ) {

                $package_url =
                    $asset->browser_download_url;

                break;
            }
        }
    }

    return (object) array(

        'name' =>
            'Booking SMS Manager',

        'slug' =>
            'booking-sms-manager',

        'version' =>
            $version,

        'author' =>
            'Ubaid',

        'homepage' =>
            'https://github.com/' .
            BSM_GITHUB_REPO,

        'download_link' =>
            $package_url,

        'sections' =>
            array(

                'description' =>
                    'SMS notifications for WP Booking Calendar using Twilio.',

                'changelog' =>
                    !empty($release->body)
                        ? $release->body
                        : 'See the GitHub release notes.',

            ),

    );
}