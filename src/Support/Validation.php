<?php

namespace Impack\WP\Support;

class Validation
{
    public static function canSubmit($action = -1, $capability = 'manage_options')
    {
        if (!isset($_POST['_wpnonce']) || !\wp_verify_nonce($_POST['_wpnonce'], $action)) {
            return false;
        }

        if (!\current_user_can($capability)) {
            return false;
        }

        return true;
    }
}