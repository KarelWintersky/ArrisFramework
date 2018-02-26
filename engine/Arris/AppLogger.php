<?php
/**
 * User: Arris
 *
 * Class Logger
 * Namespace: engine\Arris
 *
 * Date: 26.02.2018, time: 23:44
 */

namespace Engine\Arris;

use Engine\Arris\App;
use Monolog\Logger;
use Monolog\Handler\FilterHandler;
use Monolog\Handler\StreamHandler;

interface AppLoggerInterface {

    /**
     * DEBUG (100): Detailed debug information.
     *
     * @param ...$args
     * @return bool
     */
    public static function debug(...$args);

    /**
     * INFO (200): Interesting events. Examples: User logs in, SQL logs.
     *
     * @param ...$args
     * @return bool
     */
    public static function info(...$args);

    /**
     * NOTICE (250): Normal but significant events.
     *
     * @param ...$args
     * @return bool
     */
    public static function notice(...$args);

    /**
     * WARNING (300): Exceptional occurrences that are not errors. Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     *
     * @param ...$args
     * @return bool
     */
    public static function warning(...$args);

    /**
     * ERROR (400): Runtime errors that do not require immediate action but should typically be logged and monitored.
     *
     * @param ...$args
     * @return bool
     */
    public static function error(...$args);

    /**
     * CRITICAL (500): Critical conditions. Example: Application component unavailable, unexpected exception.
     *
     * @param ...$args
     * @return bool
     */
    public static function critical(...$args);

    /**
     * ALERT (550): Action must be taken immediately. Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     *
     * @param ...$args
     * @return bool
     */
    public static function alert(...$args);

    /**
     * EMERGENCY (600): Emergency: system is unusable.
     *
     * @param ...$args
     * @return bool
     */
    public static function emergency(...$args);
}

/**
 *
 * Class AppLogger
 * @package engine\Arris
 */
class AppLogger implements AppLoggerInterface
{
    /**
     * @var AppLogger $_instance
     */
    private static $_instance;

    /**
     * @var \Monolog\Logger $_log
     */
    private static $_log;

    public static function init()
    {
        $channel = App::get('monolog/channel');
        self::$_log = new Logger($channel);

        switch (App::get('monolog/handler')) {
            case 'file': {
                self::handler_file();

                break;
            }
            case 'mysql': {
                self::handler_mysql();

                break;
            }
        }
    }

    public function __construct()
    {
        self::init();
    }

    private static function handler_file()
    {
        $path = str_replace('$', $_SERVER['DOCUMENT_ROOT'], App::get('monolog/filepath'));
        $name = App::get('monolog/channel') . '.log';

        self::$_log->pushHandler(new StreamHandler($path . $name, Logger::WARNING));
    }


    private static function handler_mysql()
    {
/*        $log_handler = new KWPDOHandler(DB::getConnection(), 'rpgcrf_clubs_logs', [], [], Logger::INFO);
        self::$_log->pushHandler( $log_handler );*/
    }


    /**
     * Check instance of standalone class and creates new one
     */
    public static function checkInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self;
        }
    }


    public static function __callStatic($method, $args)
    {
        self::checkInstance();
        return self::$_log->$method(...$args);
    }


    // interface implementation

    /**
     * DEBUG (100): Detailed debug information.
     *
     * @param ...$args
     * @return bool
     */
    public static function debug(...$args)
    {
        self::checkInstance();
        return self::$_log->debug(...$args);
    }

    /**
     * INFO (200): Interesting events. Examples: User logs in, SQL logs.
     *
     * @param ...$args
     * @return bool
     */
    public static function info(...$args)
    {
        self::checkInstance();
        return self::$_log->info(...$args);
    }

    /**
     * NOTICE (250): Normal but significant events.
     *
     * @param ...$args
     * @return bool
     */
    public static function notice(...$args)
    {
        self::checkInstance();
        return self::$_log->notice(...$args);
    }

    /**
     * WARNING (300): Exceptional occurrences that are not errors. Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     *
     * @param ...$args
     * @return bool
     */
    public static function warning(...$args)
    {
        self::checkInstance();
        return self::$_log->warning(...$args);
    }

    /**
     * ERROR (400): Runtime errors that do not require immediate action but should typically be logged and monitored.
     *
     * @param ...$args
     * @return bool
     */
    public static function error(...$args)
    {
        self::checkInstance();
        return self::$_log->error(...$args);
    }

    /**
     * CRITICAL (500): Critical conditions. Example: Application component unavailable, unexpected exception.
     *
     * @param ...$args
     * @return bool
     */
    public static function critical(...$args)
    {
        self::checkInstance();
        return self::$_log->critical(...$args);
    }

    /**
     * ALERT (550): Action must be taken immediately. Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     *
     * @param ...$args
     * @return bool
     */
    public static function alert(...$args)
    {
        self::checkInstance();
        return self::$_log->alert(...$args);
    }

    /**
     * EMERGENCY (600): Emergency: system is unusable.
     *
     * @param ...$args
     * @return bool
     */
    public static function emergency(...$args)
    {
        self::checkInstance();
        return self::$_log->emergency(...$args);
    }


}