<?php
/**
 * User: Karel Wintersky
 *
 * Class Auth
 * Namespace: Arris
 *
 * Date: 09.04.2018, time: 4:08
 */

namespace Arris;

use Arris\PHPAuth\Config as PHPAuthConfig;
use Arris\PHPAuth\Auth as PHPAuth;

class Auth
{
    private static $instance = NULL;
    private static $phpauth = NULL;

    public static function checkInstance()
    {
        return NULL !== self::$instance;
    }

    public static function getInstance():PHPAuth
    {
        if (!self::checkInstance()) {
            self::init();
        }

        return self::$phpauth;
    }

    public static function init()
    {
        self::$instance = new self();
    }

    public function __construct()
    {
        $config = new PHPAuthConfig( App::get('phpauth') , App::get('google_recaptcha', []));

        self::$phpauth = new PHPAuth( DB::getConnection( App::get('phpauth/db_prefix', NULL) ), $config );
    }

    /* === static methods ==== */


    public static function __callStatic($method, $args)
    {
        return self::getInstance()->$method(...$args);
    }

}