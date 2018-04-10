<?php
/**
 * User: Karel Wintersky
 *
 * Class Config
 * Namespace: Arris\PHPAuth
 *
 * Date: 09.04.2018, time: 4:14
 */

namespace Arris\PHPAuth;

/**
 *
 * Class Config
 * @package Arris\PHPAuth
 */
class Config
{
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;

        $language = $config['language'] ?? 'en_GB';

        $path = __DIR__ . '/languages/' . $language . '.ini';

        if (file_exists($path)) {
            $dictionary = parse_ini_file($path);
        } else {
            //@todo: monolog
            die("<strong>FATAL ERROR:</strong> PHPAuth language file `{$path}` not found or not exists. ");
        }

        $this->config['dictionary'] = $dictionary;

        $this->setForgottenDefaults(); // Danger foreseen is half avoided.
    }

    /**
     * Config::__get()
     *
     * @param mixed $setting
     * @return string
     */
    public function __get($setting)
    {
        return $this->config[$setting];
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->config;
    }

    /**
     * Config::__set()
     *
     * @param mixed $setting
     * @param mixed $value
     */
    public function __set($setting, $value)
    {
        $this->config[$setting] = $value;
    }

    /**
     * Config::override()
     *
     * @param mixed $setting
     * @param mixed $value
     * @return bool
     */
    public function override($setting, $value)
    {
        $this->config[$setting] = $value;

        return true;
    }

    /**
     *
     *
     * @param $name
     * @param $args
     * @return bool
     */
    public function __call($name, $args)
    {
        if ($name == 'get') {
            return $this->config[$args[0]];
        } elseif ($name == 'getAll') {
            return $this->config;
        } else {
            var_dump('Called undefined method: ' . $name);
            return false;
        }
    }

    /**
     * Danger foreseen is half avoided.
     *
     * Set default values.
     * REQUIRED FOR USERS THAT DOES NOT UPDATE THEIR `config` TABLES.
     */
    protected function setForgottenDefaults()
    {
        // verify* values.

        if (!isset($this->config['verify_password_min_length'])) {
            $this->config['verify_password_min_length'] = 3;
        }

        if (!isset($this->config['verify_password_max_length'])) {
            $this->config['verify_password_max_length'] = 150;
        }

        if (!isset($this->config['verify_password_strong_requirements'])) {
            $this->config['verify_password_strong_requirements'] = 1;
        }

        if (!isset($this->config['verify_email_min_length'])) {
            $this->config['verify_email_min_length'] = 5;
        }

        if (!isset($this->config['verify_email_max_length'])) {
            $this->config['verify_email_max_length'] = 100;
        }

        if (!isset($this->config['verify_email_use_banlist'])) {
            $this->config['verify_email_use_banlist'] = 1;
        }

        // emailmessage* values

        if (!isset($this->config['emailmessage_suppress_activation'])) {
            $this->config['emailmessage_suppress_activation'] = 0;
        }

        if (!isset($this->config['emailmessage_suppress_reset'])) {
            $this->config['emailmessage_suppress_reset'] = 0;
        }

        if (!isset($this->config['mail_charset'])) {
            $this->config['mail_charset'] = "UTF-8";
        }
    }


}