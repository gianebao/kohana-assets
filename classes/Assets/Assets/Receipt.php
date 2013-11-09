<?php defined('SYSPATH') or die('No direct script access.');

class Assets_Assets_Receipt {

    public static $cdn = null;

    protected static $source = null;

    public static function set_source($path)
    {
        return Assets_Receipt::$source = null;
    }

    public static function get_source()
    {
        if (empty(Assets_Receipt::$source))
        {
            Assets_Receipt::$source = DOCROOT . 'assets' . DIRECTORY_SEPARATOR . 'receipt.txt';
        }

        return Assets_Receipt::$source;
    }

    public static function get()
    {
        if (empty(Assets_Receipt::$source))
        {
            Assets_Receipt::$source = DOCROOT . 'assets' . DIRECTORY_SEPARATOR . 'receipt.txt';
        }

        $receipt = file_get_contents(Assets_Receipt::$source);

        if (!preg_match('/^[a-zA-Z0-9]{32}$/', $receipt))
        {
            $receipt = '';
        }

        return $receipt;
    }
}