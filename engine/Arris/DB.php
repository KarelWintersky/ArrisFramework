<?php
/**
 * User: Arris
 *
 * Class DB
 * Namespace: engine\Arris
 *
 * Date: 26.02.2018, time: 7:05
 */

namespace Arris;

/**
 *
 * Class DB
 * @package engine\Arris
 */
class DB
{
    private static $_instances = [
    ];

    private static $_configs = [
    ];

    private static $_connect_states = [
    ];

    private static $_pdo_instances = [
    ];

    /**
     * Проверяет сущестование инстанса синглтона в массиве $_instances
     * @param null $prefix
     * @return bool
     */
    private static function checkInstance($prefix = NULL) {

        $key = ($prefix === NULL) ? 'NULL' : $prefix;

        return ( array_key_exists($key, self::$_instances) && self::$_instances[$key] !== NULL  );
    }

    public static function getInstance($prefix = NULL):DB {

        $key = ($prefix === NULL) ? 'NULL' : $prefix;

        if (!self::checkInstance($prefix)) self::$_instances[$key] = new self($prefix);

        return self::$_instances[$key];
    }

    /**
     * Возвращает PDO::Connection
     *
     * @param null $prefix
     * @return \PDO
     */
    public static function getConnection($prefix = NULL):\PDO {

        $key = ($prefix === NULL) ? 'NULL' : $prefix;

        if (!self::checkInstance($prefix)) self::$_instances[$key] = new self($prefix); // EQ self::getInstance($prefix);

        return self::$_pdo_instances[$key];
    }

    public static function init($prefix = NULL) {

        $key = ($prefix === NULL) ? 'NULL' : $prefix;

        self::$_instances[$key] = new self($prefix);
    }


    public function __construct($prefix = NULL)
    {
        $key = ($prefix === NULL) ? 'NULL' : $prefix;

        $config_key
            = $prefix !== NULL
            ?  $prefix . ":database:" . App::get("connection/suffix")
            :             "database:" . App::get("connection/suffix");

        $config = App::get($config_key);

        $dbhost = $config['hostname'];
        $dbname = $config['database'];
        $dbuser = $config['username'];
        $dbpass = $config['password'];
        $dbport = $config['port'];

        $dsl = "mysql:host=$dbhost;port=$dbport;dbname=$dbname";

        try {
            if ($config === NULL)
                throw new \Exception("DB Config section `{$config_key}` not declared or config file not loaded.\r\n" , 2);

            $dbh = new \PDO($dsl, $dbuser, $dbpass);

            $dbh->exec("SET NAMES utf8 COLLATE utf8_unicode_ci");
            $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $dbh->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

            self::$_pdo_instances[ $key ] = $dbh;

            self::$_connect_states[ $key ] = TRUE;

        } catch (\PDOException $pdo_e) {
            $message = "Unable to connect `{$dsl}`, PDO CONNECTION ERROR: " . $pdo_e->getMessage() . "\r\n" . PHP_EOL;
            self::$_connect_states[ $key ] = [
                'error' =>  $message,
                'state' =>  FALSE
            ];
        } catch (\Exception $e) {

            self::$_connect_states[ $key ] = [
                'error' =>  $e->getMessage(),
                'state' =>  FALSE
            ];
        }

        if (self::$_connect_states[$key] !== TRUE) {
            die(self::$_connect_states[$key]['error']);
        }

        self::$_configs[$key] = $config;

        return true;
    }

    public static function getTablePrefix( $prefix = NULL ) {

        $key = ($prefix === NULL) ? 'NULL' : $prefix;

        if (!self::checkInstance($prefix)) return NULL;

        return array_key_exists('table_prefix', self::$_configs[$key]) ? self::$_configs[$key]['table_prefix'] : '';
    }

}