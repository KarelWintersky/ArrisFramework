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

interface AuthArrisInterface {

    public static function getInstance():PHPAuth;
    public static function login($email, $password, $remember = false, $captcha_response = NULL);
    public static function register($email, $password, $repeatpassword, $params = Array(), $captcha = NULL, $sendmail = NULL);
    public static function logout();
    public static function isLogged();

}

class Auth implements AuthArrisInterface
{
    private static $instance = NULL;
    private static $phpauth = NULL;

    /**
     * Проверяет существование инстанса этого класса
     *
     * @return bool
     */
    public static function checkInstance()
    {
        return NULL !== self::$instance;
    }

    /**
     * Возвращает инстанс PHPAuth или создает инстанс этого класса
     *
     * @return PHPAuth
     */
    public static function getInstance():PHPAuth
    {
        if (!self::checkInstance()) {
            self::init();
        }

        return self::$phpauth;
    }

    /**
     *
     */
    public static function init()
    {
        self::$instance = new self();
    }

    /**
     * Конструктор класса
     */
    public function __construct()
    {
        $config = new PHPAuthConfig( App::get('phpauth') , App::get('google_recaptcha', []));

        self::$phpauth = new PHPAuth( DB::getConnection( App::get('phpauth/db_prefix', NULL) ), $config );
    }


    /* === Arris\Auth implementations === */

    /**
     * Логинит пользователя и устанавливает необходимые куки.
     *
     * @param $email
     * @param $password
     * @param bool|false $remember
     * @param null $captcha_response
     * @return array
     */
    public static function login($email, $password, $remember = false, $captcha_response = NULL)
    {
        $instance = self::getInstance();

        $auth_result = $instance->login($email, $password, $remember, $captcha_response);

        if (!$auth_result['error']) {
            setcookie($instance->config->cookie_name, $auth_result['hash'], time()+$auth_result['expire'], $instance->config->cookie_path);
            self::unsetcookie(App::get('phpauth_cookies/new_registered_userlogin'));
        }

        return $auth_result;
    }

    /**
     * Регистрирует пользователя и устанавливает куки
     *
     * @param $email
     * @param $password
     * @param $repeatpassword
     * @param array $params
     * @param null $captcha
     * @param null $sendmail
     * @return array
     */
    public static function register($email, $password, $repeatpassword, $params = Array(), $captcha = NULL, $sendmail = NULL)
    {
        $instance = self::getInstance();

        $auth_result = $instance->register($email, $password, $repeatpassword, $params, $captcha, $sendmail);

        if (!$auth_result['error']) {
            setcookie(App::get('phpauth_cookies/new_registered_userlogin'), $email, time()+60*60, $instance->config->cookie_path);
        }
        return $auth_result;
    }

    /**
     *
     * @return mixed
     */
    public static function logout()
    {
        $instance = self::getInstance();

        $return['error'] = true;

        $hash = $instance->getSessionHash();
        $userinfo = $instance->getUser($instance->getSessionUID($hash));

        if ($hash === NULL) {
            return $return;
        }

        $auth_result = $instance->logout($hash);

        if ($auth_result) {
            self::unsetcookie($instance->config->cookie_name);
            setcookie(App::get('phpauth_cookies/last_logger_userlogin'), $userinfo['email'], time()+60*60*24, $instance->config->cookie_path);
            $return['error'] = false;
        }
        return $return;
    }

    /**
     * Проверяет, залогинен ли пользователь
     *
     * @return bool
     */
    public static function isLogged()
    {
        return self::getInstance()->isLogged();
    }


    /**
     * Удаляет куку
     * @param $cookie_name
     * @param string $cookie_path
     */
    private static function unsetcookie($cookie_name, $cookie_path = '/')
    {
        unset($_COOKIE[$cookie_name]);
        \setcookie($cookie_name, null, -1, $cookie_path);
    }


    /**
     * __callStatic method
     *
     * @param $method
     * @param $args
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        return self::getInstance()->$method(...$args);
    }

}