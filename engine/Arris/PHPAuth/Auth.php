<?php
/**
 * User: Arris
 *
 * Class Auth
 * Namespace: Arris\PHPAuth
 *
 * Date: 09.04.2018, time: 4:33
 */

namespace Arris\PHPAuth;

interface AuthInterface {
    public function login($email, $password, $remember = false, $captcha = NULL);

    public function register($email, $password, $repeatpassword, $params = [], $captcha = NULL, $sendmail = NULL);
}

class Auth implements AuthInterface
{
    public function __construct(\PDO $dbh, Config $config)
    {
        $this->dbh = $dbh;
        $this->config = $config;
        $this->lang = $config->dictionary;

        date_default_timezone_set($this->config->site_timezone);
    }



    public function login($email, $password, $remember = false, $captcha = NULL)
    {
        var_dump(__CLASS__ . ' / ' . __METHOD__);
        var_dump(func_get_args());
    }

    public function register($email, $password, $repeatpassword, $params = [], $captcha = NULL, $sendmail = NULL)
    {
        var_dump(__CLASS__ . ' / ' . __METHOD__);
        var_dump(func_get_args());
    }

    public function isLogged() {
        return (isset($_COOKIE[$this->config->cookie_name]) && $this->checkSession($_COOKIE[$this->config->cookie_name]));
    }

    private function checkSession($cookie_name)
    {
        return true;
    }

}