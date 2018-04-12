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

    /**
     *
     *
     * @param \PDO $db_connection
     * @param array $config -- [ array of params, loaded from ini file or DB ]
     * @param array $recaptcha_config - array of recaptcha params [ enable = 0|1 , site_key = '...' , secret_key = '...']
     * */
    public function __construct($db_connection, $config, $recaptcha_config)
    {
        $this->config = $config;

        $language = $config['language'] ?? 'en_GB';

        $path = __DIR__ . '/languages/' . $language . '.ini';

        if (file_exists($path)) {
            $dictionary = parse_ini_file($path);
        } else {
            //@todo: monolog
            // die("<strong>FATAL ERROR:</strong> PHPAuth language file `{$path}` not found or not exists. ");

            $dictionary = $this->setForgottenDictionary();
        }

        $this->config['dictionary'] = $dictionary;

        $this->config['recaptcha'] = $recaptcha_config;

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
        return $this->config[$setting] ?? NULL;
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

    protected function setForgottenDictionary()
    {
        return [
            'user_blocked' => 'You are currently locked out of the system.',
            'user_verify_failed' => 'Captcha Code was invalid.',

            'email_password_invalid' => 'Email address / password are invalid.',
            'email_password_incorrect' => 'Email address / password are incorrect.',

            'remember_me_invalid' => 'The remember me field is invalid.',

            'password_short' => 'Password is too short.',
            'password_weak' => 'Password is too weak.',
            'password_nomatch' => 'Passwords do not match.',
            'password_changed' => 'Password changed successfully.',
            'password_incorrect' => 'Current password is incorrect.',
            'password_notvalid' => 'Password is invalid.',

            'newpassword_short' => 'New password is too short.',
            'newpassword_long' => 'New password is too long.',
            'newpassword_invalid' => 'New password must contain at least one uppercase and lowercase character, and at least one digit.',
            'newpassword_nomatch' => 'New passwords do not match.',
            'newpassword_match' => 'New password is the same as the old password.',

            'email_short' => 'Email address is too short.',
            'email_long' => 'Email address is too long.',
            'email_invalid' => 'Email address is invalid.',
            'email_incorrect' => 'Email address is incorrect.',
            'email_banned' => 'This email address is not allowed.',
            'email_changed' => 'Email address changed successfully.',

            'newemail_match' => 'New email matches previous email.',

            'account_inactive' => 'Account has not yet been activated.',
            'account_activated' => 'Account activated.',

            'logged_in' => 'You are now logged in.',
            'logged_out' => 'You are now logged out.',

            'system_error' => 'A system error has been encountered. Please try again.',

            'register_success' => 'Account created. Activation email sent to email.',
            'register_success_emailmessage_suppressed' => 'Account created.',

            'email_taken' => 'The email address is already in use.',

            'resetkey_invalid' => 'Reset key is invalid.',
            'resetkey_incorrect' => 'Reset key is incorrect.',
            'resetkey_expired' => 'Reset key has expired.',

            'password_reset' => 'Password reset successfully.',

            'activationkey_invalid' => 'Activation key is invalid.',
            'activationkey_incorrect' => 'Activation key is incorrect.',
            'activationkey_expired' => 'Activation key has expired.',

            'reset_requested' => 'Password reset request sent to email address.',
            'reset_requested_emailmessage_suppressed' => 'Password reset request is created.',
            'reset_exists' => 'A reset request already exists.',

            'already_activated' => 'Account is already activated.',
            'activation_sent' => 'Activation email has been sent.',
            'activation_exists' => 'An activation email has already been sent.',

            'email_activation_subject' => '%s - Activate account',
            'email_activation_body' => 'Hello,<br/><br/> To be able to log in to your account you first need to activate your account by clicking on the following link : <strong><a href=>"%1$s/%2$s">%1$s/%2$s</a></strong><br/><br/> You then need to use the following activation key: <strong>%3$s</strong><br/><br/> If you did not sign up on %1$s recently then this message was sent in error, please ignore it.',
            'email_activation_altbody' => 'Hello, \n\nTo be able to log in to your account you first need to activate your account by visiting the following link :\n%1$s/%2$s\n\nYou then need to use the following activation key: %3$s\n\nIf you did not sign up on %1$s recently then this message was sent in error, please ignore it.',

            'email_reset_subject' => '%s - Password reset request',
            'email_reset_body' => 'Hello,<br/><br/>To reset your password click the following link :<br/><br/><strong><a href=>"%1$s/%2$s">%1$s/%2$s</a></strong><br/><br/>You then need to use the following password reset key: <strong>%3$s</strong><br/><br/>If you did not request a password reset key on %1$s recently then this message was sent in error, please ignore it.',
            'email_reset_altbody' => 'Hello, \n\nTo reset your password please visiting the following link :\n%1$s/%2$s\n\nYou then need to use the following password reset key: %3$s\n\nIf you did not request a password reset key on %1$s recently then this message was sent in error, please ignore it.',

            'account_deleted' => 'Account deleted successfully.',

            'function_disabled' => 'This function has been disabled.',

            'user_validate_email_incorrect' => 'Некорректный формат электронной почты.',
            'user_validate_password_incorrect' => 'Пароль слишком короткий, слишком длинный или не соответствует заявленным требованиям строгости',
            'user_validate_remember_me_invalid' => 'Недопустимое значение поля &ldquo;запомнить пользователя&rdquo;.',
            'user_validate_user_not_found' => 'Пользователь с таким E-Mail&quot;ом не найден.',

            'user_login_incorrect_password' => 'Неправильный пароль.',
            'user_login_account_inactive' => 'Аккаунт еще не активирован.',

            'user_register_email_taken' => 'Этот E-Mail уже используется!',
            'user_register_success' => 'Учётная запись создана. На вашу почту отправлены инструкции по активации.',

            'captcha_verify_failed' => 'Captcha Code was invalid.',

        ];
    }


}