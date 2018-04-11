<?php
/**
 * User: Karel Wintersky
 *
 * Class Auth
 * Namespace: Arris\PHPAuth
 *
 * Date: 09.04.2018, time: 4:33
 *
 * https://jeka.by/ask/124/mysql-ip-address/
 */

/**
lang\[\"(.*)\"\];
to
__lang\(\"$1\"\);
 *
 */

namespace Arris\PHPAuth;

use PHPMailer\PHPMailer\PHPMailer;
use ReCaptcha\ReCaptcha;


/**
 * Class Auth
 *
 * @package Arris\PHPAuth
 */
class Auth
{
    const TOKEN_LENGTH = 20;
    const HASH_LENGTH = 40;

    /**
     * @var \PDO $dbh
     */
    private $dbh;
    public $config;

    public $lang;
    public $recaptcha;


    /**
     * @param \PDO $dbh
     * @param Config $config
     */
    public function __construct(\PDO $dbh, Config $config)
    {
        $this->dbh = $dbh;
        $this->config = $config;
        $this->lang = $config->dictionary;
        $this->recaptcha = $config->recaptcha;

        date_default_timezone_set($this->config->site_timezone);
    }

    /**
     * Logs a user in
     *
     * @param string $email
     * @param string $password
     * @param bool|false $remember
     * @param string $captcha_response
     * @return array $return
     */
    public function login($email, $password, $remember = false, $captcha_response = NULL)
    {
        $return['error'] = true;
        $block_status = $this->isBlocked();

        if ($block_status == "verify") {
            if ($this->checkCaptcha($captcha_response) == false) {
                $return['message'] = $this->lang["captcha_verify_failed"]; //@todo: rename to 'captcha_verify_failed'
                return $return;
            }
        }
        if ($block_status == "block") {
            $return['message'] = $this->lang["user_blocked"];
            return $return;
        }

        $validateEmail = $this->validateEmail($email);
        $validatePassword = $this->validatePassword($password);

        if ($validateEmail['error'] == 1) {
            $this->addAttempt();
            $return['message'] = $this->lang["user_validate_email_incorrect"]; // was email password invalid
            return $return;
        } elseif ($validatePassword['error'] == 1) {
            $this->addAttempt();
            $return['message'] = $this->lang["user_validate_password_incorrect"]; // was email password invalid
            return $return;
        } elseif ($remember != 0 && $remember != 1) {
            $this->addAttempt();
            $return['message'] = $this->lang["user_validate_remember_me_invalid"]; // was remember_me_invalid
            return $return;
        }

        $uid = $this->getUID(strtolower($email));

        if (!$uid) {
            $this->addAttempt();

            $return['message'] = $this->lang["user_validate_user_not_found"]; // was email_password_incorrect
            return $return;
        }

        $user = $this->getUser($uid, true);

        if (!password_verify($password, $user['password'])) {
            $this->addAttempt();

            $return['message'] = $this->lang["user_login_incorrect_password"]; // was email_password_incorrect
            return $return;
        }

        if ($user['isactive'] != 1) {
            $this->addAttempt();

            $return['message'] = $this->lang["user_login_account_inactive"]; // was account_inactive
            return $return;
        }

        $sessiondata = $this->addSession($user['uid'], $remember);

        if ($sessiondata == false) {
            $return['message'] = $this->lang["system_error"] . " #01";
            return $return;
        }

        $return['error'] = false;
        $return['message'] = $this->lang["logged_in"];

        $return['hash'] = $sessiondata['hash'];
        $return['expire'] = $sessiondata['expiretime'];

        $return['uid'] = $uid;

        return $return;

    }

    /***
     * Creates a new user, adds them to database
     * @param string $email
     * @param string $password
     * @param string $repeatpassword
     * @param array $additional_fields
     * @param string $captcha = NULL
     * @param bool $use_email_activation = NULL
     * @return array $return
     */
    public function register($email, $password, $repeatpassword, $additional_fields = Array(), $captcha = NULL, $use_email_activation = NULL)
    {
        $return['error'] = true;
        $block_status = $this->isBlocked();
        if ($block_status == "verify") {
            if ($this->checkCaptcha($captcha) == false) {
                $return['message'] = $this->lang["user_verify_failed"];
                return $return;
            }
        }

        if ($block_status == "block") {
            $return['message'] = $this->lang["user_blocked"];
            return $return;
        }

        if ($password !== $repeatpassword) {
            $return['message'] = $this->lang["password_nomatch"];
            return $return;
        }

        // Validate email
        $validateEmail = $this->validateEmail($email);
        if ($validateEmail['error'] == 1) {
            $return['message'] = $validateEmail['message'];
            return $return;
        }

        // Validate password
        $validatePassword = $this->validatePassword($password);
        if ($validatePassword['error'] == 1) {
            $return['message'] = $validatePassword['message'];
            return $return;
        }

        if ($this->isEmailTaken($email)) {
            $this->addAttempt();

            $return['message'] = $this->lang["email_taken"];
            return $return;
        }

        $addUser = $this->addUser($email, $password, $additional_fields, $use_email_activation);

        if ($addUser['error'] != 0) {
            $return['message'] = $addUser['message'];
            return $return;
        }

        $return['error'] = false;
        $return['message'] = ($use_email_activation == true ? $this->lang["register_success"] : $this->lang['register_success_emailmessage_suppressed']);

        return $return;

    }


    /***
     * Logs out the session, identified by hash
     * @param string $hash
     * @return boolean
     */
    public function logout($hash)
    {
        if (strlen($hash) != self::HASH_LENGTH) {
            return false;
        }

        return $this->deleteSession($hash);
    }

    /***
     * Activates a user's account
     * @param string $key
     * @return array $return
     */
    public function activate($key)
    {
        $return['error'] = true;

        $block_status = $this->isBlocked();
        if ($block_status == "block") {
            $return['message'] = $this->lang["user_blocked"];
            return $return;
        }

        if (strlen($key) !== self::TOKEN_LENGTH) {
            $this->addAttempt();

            $return['message'] = $this->lang["activationkey_invalid"];
            return $return;
        }

        $getRequest = $this->getRequest($key, "activation");

        if ($getRequest['error'] == 1) {
            $return['message'] = $getRequest['message'];
            return $return;
        }

        if ($this->getUser($getRequest['uid'], true)['isactive'] == 1) {
            $this->addAttempt();
            $this->deleteRequest($getRequest['id']);

            $return['message'] = $this->lang["system_error"] . " #02";
            return $return;
        }

        $query = $this->dbh->prepare("UPDATE {$this->config->table_users} SET isactive = :isactive WHERE id = :id");
        $query_params = [
            'isactive' => 1,
            'id' => $getRequest['uid']
        ];
        $query->execute($query_params);

        $this->deleteRequest($getRequest['id']);

        $return['error'] = false;
        $return['message'] = $this->lang["account_activated"];

        return $return;
    }

    /**
     * Creates a reset key for an email address and sends email
     * @param $email
     * @param null $sendmail
     * @return mixed
     */
    public function requestReset($email, $sendmail = NULL)
    {
        $return['error'] = true;
        $block_status = $this->isBlocked();
        if ($block_status == "block") {
            $return['message'] = $this->lang["user_blocked"];
            return $return;
        }

        $validateEmail = $this->validateEmail($email);

        if ($validateEmail['error'] == 1) {
            $return['message'] = $this->lang["user_validate_email_incorrect"];
            return $return;
        }

        $query = $this->dbh->prepare("SELECT id FROM {$this->config->table_users} WHERE email = :email");
        $query_params = [
            'email' => $email
        ];
        $query->execute($query_params);

        if ($query->rowCount() == 0) {
            $this->addAttempt();

            $return['message'] = $this->lang["email_incorrect"];
            return $return;
        }

        $addRequest = $this->addRequest($query->fetch(\PDO::FETCH_ASSOC)['id'], $email, "reset", $sendmail);
        if ($addRequest['error'] == 1) {
            $this->addAttempt();

            $return['message'] = $addRequest['message'];
            return $return;
        }

        $return['error'] = false;
        $return['message'] = ($sendmail == true ? $this->lang["reset_requested"] : $this->lang['reset_requested_emailmessage_suppressed']);

        return $return;
    }

    /**
     * Function to check if a session is valid
     * @param string $hash
     * @return boolean
     */
    public function checkSession($hash)
    {
        $ip = $this->getIp();

        $block_status = $this->isBlocked();
        if ($block_status == "block") {
            $return['message'] = $this->lang["user_blocked"];
            return false;
        }
        if (strlen($hash) != self::HASH_LENGTH) {
            return false;
        }

        $query = $this->dbh->prepare("SELECT id, uid, expiredate, INET_NTOA(ip) as ip, agent, cookie_crc FROM {$this->config->table_sessions} WHERE hash = :hash");
        $query_params = [
            'hash' => $hash
        ];
        $query->execute($query_params);

        if ($query->rowCount() == 0) {
            return false;
        }

        $row = $query->fetch(\PDO::FETCH_ASSOC);

        $sid = $row['id'];
        $uid = $row['uid'];
        $expiredate = strtotime($row['expiredate']);
        $currentdate = strtotime(date("Y-m-d H:i:s"));
        $db_ip = $row['ip'];
        $db_agent = $row['agent'];
        $db_cookie = $row['cookie_crc'];

        if ($currentdate > $expiredate) {
            $this->deleteExistingSessions($uid);
            return false;
        }

        if ($ip != $db_ip) {
            return false;
        }

        if ($db_cookie == sha1($hash . $this->config->site_key)) {
            return true;
        }

        return false;
    }

    /**
     * Retrieves the UID associated with a given session hash
     * @param string $hash
     * @return int $uid
     */
    public function getSessionUID($hash)
    {
        $query = $this->dbh->prepare("SELECT uid FROM {$this->config->table_sessions} WHERE hash = :hash");
        $query_params = [
            'hash' => $hash
        ];
        $query->execute($query_params);

        if ($query->rowCount() == 0) {
            return false;
        }

        return $query->fetch(\PDO::FETCH_ASSOC)['uid'];
    }

    /**
     * Allows a user to delete their account
     * @param int $uid
     * @param string $password
     * @param string $captcha = NULL
     * @return array $return
     */
    public function deleteUser($uid, $password, $captcha = NULL)
    {
        $return['error'] = true;

        $block_status = $this->isBlocked();
        if ($block_status == "verify") {
            if ($this->checkCaptcha($captcha) == false) {
                $return['message'] = $this->lang["user_verify_failed"];
                return $return;
            }
        }
        if ($block_status == "block") {
            $return['message'] = $this->lang["user_blocked"];
            return $return;
        }

        $validatePassword = $this->validatePassword($password);

        if ($validatePassword['error'] == 1) {
            $this->addAttempt();

            $return['message'] = $validatePassword['message'];
            return $return;
        }

        $user = $this->getUser($uid, true);

        if (!password_verify($password, $user['password'])) {
            $this->addAttempt();

            $return['message'] = $this->lang["password_incorrect"];
            return $return;
        }

        $query = $this->dbh->prepare("DELETE FROM {$this->config->table_users} WHERE id = ?");

        if (!$query->execute(array($uid))) {
            $return['message'] = $this->lang["system_error"] . " #05";
            return $return;
        }

        $query = $this->dbh->prepare("DELETE FROM {$this->config->table_sessions} WHERE uid = ?");

        if (!$query->execute(array($uid))) {
            $return['message'] = $this->lang["system_error"] . " #06";
            return $return;
        }

        $query = $this->dbh->prepare("DELETE FROM {$this->config->table_requests} WHERE uid = ?");

        if (!$query->execute(array($uid))) {
            $return['message'] = $this->lang["system_error"] . " #07";
            return $return;
        }

        $return['error'] = false;
        $return['message'] = $this->lang["account_deleted"];

        return $return;
    }

    /**
     * Allows a user to reset their password after requesting a reset key.
     * @param string $key
     * @param string $password
     * @param string $repeatpassword
     * @param string $captcha = NULL
     * @return array $return
     */
    public function resetPass($key, $password, $repeatpassword, $captcha = NULL)
    {
        $return['error'] = true;

        $block_status = $this->isBlocked();
        if ($block_status == "verify") {
            if ($this->checkCaptcha($captcha) == false) {
                $return['message'] = $this->lang["user_verify_failed"];
                return $return;
            }
        }
        if ($block_status == "block") {
            $return['message'] = $this->lang["user_blocked"];
            return $return;
        }

        if (strlen($key) != self::TOKEN_LENGTH) {
            $return['message'] = $this->lang["resetkey_invalid"];
            return $return;
        }

        $validatePassword = $this->validatePassword($password);

        if ($validatePassword['error'] == 1) {
            $return['message'] = $validatePassword['message'];
            return $return;
        }

        if ($password !== $repeatpassword) {
            // Passwords don't match
            $return['message'] = $this->lang["newpassword_nomatch"];
            return $return;
        }

        $data = $this->getRequest($key, "reset");

        if ($data['error'] == 1) {
            $return['message'] = $data['message'];
            return $return;
        }

        $user = $this->getUser($data['uid'], true);

        if (!$user) {
            $this->addAttempt();
            $this->deleteRequest($data['id']);

            $return['message'] = $this->lang["system_error"] . " #11";
            return $return;
        }

        if (password_verify($password, $user['password'])) {
            $this->addAttempt();

            $return['message'] = $this->lang["newpassword_match"];
            return $return;
        }

        $password = $this->getHash($password);

        $query = $this->dbh->prepare("UPDATE {$this->config->table_users} SET password = :password WHERE id = :id");
        $query_params = [
            'password' => $password,
            'id' => $data['uid']
        ];
        $query->execute($query_params);

        if ($query->rowCount() == 0) {
            $return['message'] = $this->lang["system_error"] . " #12";
            return $return;
        }

        $this->deleteRequest($data['id']);

        $return['error'] = false;
        $return['message'] = $this->lang["password_reset"];

        return $return;
    }

    /**
     * Recreates activation email for a given email and sends
     * @param $email
     * @param null $sendmail
     * @return mixed
     */
    public function resendActivation($email, $sendmail = NULL)
    {
        $return['error'] = true;
        $block_status = $this->isBlocked();
        if ($block_status == "block") {
            $return['message'] = $this->lang["user_blocked"];
            return $return;
        }

        if ($sendmail == NULL) {
            $return['message'] = $this->lang['function_disabled'];
            return $return;
        }

        $validateEmail = $this->validateEmail($email);

        if ($validateEmail['error'] == 1) {
            $return['message'] = $validateEmail['message'];
            return $return;
        }

        $query = $this->dbh->prepare("SELECT id FROM {$this->config->table_users} WHERE email = ?");
        $query->execute(array($email));

        if ($query->rowCount() == 0) {
            $this->addAttempt();

            $return['message'] = $this->lang["email_incorrect"];
            return $return;
        }

        $row = $query->fetch(\PDO::FETCH_ASSOC);

        if ($this->getUser($row['id'], true)['isactive'] == 1) {
            $this->addAttempt();

            $return['message'] = $this->lang["already_activated"];
            return $return;
        }

        $addRequest = $this->addRequest($row['id'], $email, "activation", $sendmail);

        if ($addRequest['error'] == 1) {
            $this->addAttempt();

            $return['message'] = $addRequest['message'];
            return $return;
        }

        $return['error'] = false;
        $return['message'] = $this->lang["activation_sent"];
        return $return;
    }

    /**
     * Changes a user's password
     * @param int $uid
     * @param string $currpass
     * @param string $newpass
     * @param string $repeatnewpass
     * @param string $captcha = NULL
     * @return array $return
     */
    public function changePassword($uid, $currpass, $newpass, $repeatnewpass, $captcha = NULL)
    {
        $return['error'] = true;

        $block_status = $this->isBlocked();
        if ($block_status == "verify") {
            if ($this->checkCaptcha($captcha) == false) {
                $return['message'] = $this->lang["user_verify_failed"];
                return $return;
            }
        }
        if ($block_status == "block") {
            $return['message'] = $this->lang["user_blocked"];
            return $return;
        }

        $validatePassword = $this->validatePassword($currpass);

        if ($validatePassword['error'] == 1) {
            $this->addAttempt();

            $return['message'] = $validatePassword['message'];
            return $return;
        }

        $validatePassword = $this->validatePassword($newpass);

        if ($validatePassword['error'] == 1) {
            $return['message'] = $validatePassword['message'];
            return $return;
        } elseif ($newpass !== $repeatnewpass) {
            $return['message'] = $this->lang["newpassword_nomatch"];
            return $return;
        }

        /*
		$zxcvbn = new Zxcvbn();

		if($zxcvbn->passwordStrength($newpass)['score'] < intval($this->config->password_min_score)) {
			$return['message'] = $this->lang['password_weak'];
			return $return;
		}
        */

        $user = $this->getUser($uid, true);

        if (!$user) {
            $this->addAttempt();

            $return['message'] = $this->lang["system_error"] . " #13";
            return $return;
        }

        if (!password_verify($currpass, $user['password'])) {
            $this->addAttempt();

            $return['message'] = $this->lang["password_incorrect"];
            return $return;
        }

        $newpass = $this->getHash($newpass);

        $query = $this->dbh->prepare("UPDATE {$this->config->table_users} SET password = ? WHERE id = ?");
        $query->execute(array($newpass, $uid));

        $return['error'] = false;
        $return['message'] = $this->lang["password_changed"];
        return $return;
    }

    /**
     * Changes a user's email
     * @param int $uid
     * @param string $email
     * @param string $password
     * @param string $captcha = NULL
     * @return array $return
     */
    public function changeEmail($uid, $email, $password, $captcha = NULL)
    {
        $return['error'] = true;

        $block_status = $this->isBlocked();
        if ($block_status == "verify") {
            if ($this->checkReCaptcha($captcha) == false) {
                $return['message'] = $this->lang["captcha_verify_failed"];
                return $return;
            }
        }
        if ($block_status == "block") {
            $return['message'] = $this->lang["user_blocked"];
            return $return;
        }

        $validateEmail = $this->validateEmail($email);

        if ($validateEmail['error'] == 1) {
            $return['message'] = $validateEmail['message'];
            return $return;
        }

        $validatePassword = $this->validatePassword($password);

        if ($validatePassword['error'] == 1) {
            $return['message'] = $this->lang["password_notvalid"];
            return $return;
        }

        $user = $this->getUser($uid, true);

        if (!$user) {
            $this->addAttempt();

            $return['message'] = $this->lang["system_error"] . " #14";
            return $return;
        }

        if (!password_verify($password, $user['password'])) {
            $this->addAttempt();

            $return['message'] = $this->lang["password_incorrect"];
            return $return;
        }

        if ($email == $user['email']) {
            $this->addAttempt();

            $return['message'] = $this->lang["newemail_match"];
            return $return;
        }

        $query = $this->dbh->prepare("UPDATE {$this->config->table_users} SET email = ? WHERE id = ?");
        $query->execute(array($email, $uid));

        if ($query->rowCount() == 0) {
            $return['message'] = $this->lang["system_error"] . " #15";
            return $return;
        }

        $return['error'] = false;
        $return['message'] = $this->lang["email_changed"];
        return $return;
    }

    /**
     * Returns is user logged in
     * @return boolean
     */
    public function isLogged()
    {
        return (isset($_COOKIE[$this->config->cookie_name]) && $this->checkSession($_COOKIE[$this->config->cookie_name]));
    }

    /**
     * Returns current session hash
     *
     * @return string
     */
    public function getSessionHash()
    {
        /*return isset($_COOKIE[$this->config->cookie_name]) ? $_COOKIE[$this->config->cookie_name] : null;*/

        return $_COOKIE[$this->config->cookie_name] ?? null;
    }

    /**
     * Compare user's password with given password
     * @param int $userid
     * @param string $password_for_check
     * @return bool
     */
    public function comparePasswords($userid, $password_for_check)
    {
        $query = $this->dbh->prepare("SELECT password FROM {$this->config->table_users} WHERE id = ?");
        $query->execute(array($userid));

        if ($query->rowCount() == 0) {
            return false;
        }

        $data = $query->fetch(\PDO::FETCH_ASSOC);

        if (!$data) {
            return false;
        }

        return password_verify($password_for_check, $data['password']);
    }


    /* ============================================================================================================ */
    /* ============================================================================================================ */
    /* ============================================================================================================ */


    /**
     * Informs if a user is locked out
     * @return string
     */
    public function isBlocked()
    {
        $ip = $this->getIp();

        $this->deleteAttempts($ip, false);

        $query = $this->dbh->prepare("SELECT count(*) FROM {$this->config->table_attempts} WHERE ip = INET_ATON(:ip)");
        $query->execute(['ip' => $ip]);
        $attempts = $query->fetchColumn();

        if ($attempts < intval($this->config->attempts_before_verify)) {
            return "allow";
        }

        if ($attempts < intval($this->config->attempts_before_ban)) {
            return "verify";
        }

        return "block";
    }

    /**
     * Get user IP
     *
     * @return mixed
     */
    public function getIp()
    {
        if (getenv('HTTP_CLIENT_IP')) {
            $ipAddress = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ipAddress = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_X_FORWARDED')) {
            $ipAddress = getenv('HTTP_X_FORWARDED');
        } elseif (getenv('HTTP_FORWARDED_FOR')) {
            $ipAddress = getenv('HTTP_FORWARDED_FOR');
        } elseif (getenv('HTTP_FORWARDED')) {
            $ipAddress = getenv('HTTP_FORWARDED');
        } elseif (getenv('REMOTE_ADDR')) {
            $ipAddress = getenv('REMOTE_ADDR');
        } else {
            $ipAddress = '127.0.0.1';
        }

        return $ipAddress;
    }

    /**
     * Deletes (all) attempts for a given IP from database
     * @param string $ip
     * @param bool|false $all
     * @return bool
     */
    public function deleteAttempts($ip, $all = false)
    {
        if ($all == true) {
            $query = $this->dbh->prepare("DELETE FROM {$this->config->table_attempts} WHERE ip = INET_ATON(:ip)");
            return $query->execute(['ip' => $ip]);
        }

        $currentdate = time();      // было $currentdate = strtotime(date("Y-m-d H:i:s"));
        $queryDel = $this->dbh->prepare("DELETE FROM {$this->config->table_attempts} WHERE id = :id");

        // получаем все возможные "попытки входа"
        $query = $this->dbh->prepare("SELECT id, expiredate FROM {$this->config->table_attempts} WHERE ip = INET_ATON(:ip)");
        $query->execute(['ip' => $ip]);

        // $row['expiredate'] contains DATETIME and have type STRING

        $row = $query->fetch(\PDO::FETCH_ASSOC);

        dump($row);

        dump(gettype($row['expiredate']));

        while ($row) {
            $expiredate = strtotime($row['expiredate']);
            /*if ($currentdate > $expiredate) {
                $queryDel->execute(['id' => $row['id']]);
            }*/
            $row = $query->fetch(\PDO::FETCH_ASSOC);
            dump($row);
        }
        return true;
    }


    /**
     * Verifies a captcha code
     * @todo: учитывать конфиг. Если в конфиге капча отключена- всегда возвращается TRUE
     *
     * @param $captcha
     * @return bool
     */
    private function checkCaptcha($captcha)
    {
        return true;
    }


    /**
     * Проверяет код Google Recaptcha
     * Если использование капчи в конфиге отключено - всегда возвращается TRUE
     *
     * @param $captcha_response
     * @return bool
     */
    private function checkReCaptcha($captcha_response)
    {
        if ($this->recaptcha['enable'] == FALSE) return true;

        if ($this->recaptcha['enable']) {

            if (empty($this->recaptcha['secret_key'])) throw new \RuntimeException('No secret provided');
            if (!is_string($this->recaptcha['secret_key'])) throw new \RuntimeException('The provided secret must be a string');

            $recaptcha = new ReCaptcha($this->recaptcha['secret_key']);
            $checkout = $recaptcha->verify($captcha_response, $this->getIp());

            if (!$checkout->isSuccess()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifies that an email is valid
     *
     * @param string $email
     * @return array $return
     */
    private function validateEmail($email)
    {
        $return['error'] = true;

        if (strlen($email) < (int)$this->config->verify_email_min_length) {
            $return['message'] = $this->lang["email_short"];
            return $return;
        } elseif (strlen($email) > (int)$this->config->verify_email_max_length) {
            $return['message'] = $this->lang["email_long"];
            return $return;
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $return['message'] = $this->lang["email_invalid"];
            return $return;
        }

        if ((int)$this->config->verify_email_use_banlist) {
            $bannedEmails = json_decode(file_get_contents(__DIR__ . "/files/domains.json"));

            if (in_array(strtolower(explode('@', $email)[1]), $bannedEmails)) {
                $return['message'] = $this->lang["email_banned"];
                return $return;
            }
        }

        $return['error'] = false;
        return $return;
    }

    /**
     * Verifies that a password is valid and respects security requirements
     *
     * @param string $password
     * @return array $return
     */
    private function validatePassword($password)
    {
        $return['error'] = true;

        if (strlen($password) < (int)$this->config->verify_password_min_length) {
            $return['message'] = $this->lang["password_short"];
            return $return;
        } elseif (strlen($password) > (int)$this->config->verify_password_max_length) {
            $return['message'] = $this->lang["password_long"];
            return $return;
        } elseif ((int)$this->config->verify_password_strong_requirements) {
            if (!preg_match('@[A-Z]@', $password) || !preg_match('@[a-z]@', $password) || !preg_match('@[0-9]@', $password)) {
                $return['message'] = $this->lang["password_invalid"];
                return $return;
            }
        }

        $return['error'] = false;
        return $return;
    }

    /**
     * Adds an attempt to database
     * @return boolean
     */
    private function addAttempt()
    {
        $ip = $this->getIp();

        $attempt_expiredate = date("Y-m-d H:i:s", strtotime($this->config->attack_mitigation_time));

        $query = $this->dbh->prepare("INSERT INTO {$this->config->table_attempts} (ip, expiredate) VALUES (INET_ATON(:ip), :expiredate)");
        return $query->execute([
            'ip' => $ip,
            'expiredate' => $attempt_expiredate
        ]);
    }

    /***
     * Gets UID for a given email address and returns an array
     * @param string $email
     * @return array $uid
     */
    private function getUID($email)
    {
        $query = $this->dbh->prepare("SELECT id FROM {$this->config->table_users} WHERE email = :email");
        $query->execute(['email' => $email]);

        if ($query->rowCount() == 0) {
            return false;
        }

        return $query->fetchColumn();  // return $query->fetch(\PDO::FETCH_ASSOC)['id'];
    }

    /**
     * Gets public user data for a given UID and returns an array, password will be returned if
     * param $withpassword is TRUE
     * @param $uid
     * @param bool|false $withpassword
     * @return bool|mixed
     */
    public function getUser($uid, $withpassword = false)
    {
        $query = $this->dbh->prepare("SELECT * FROM {$this->config->table_users} WHERE id = :id");
        $query->execute(['id' => $uid]);

        if ($query->rowCount() == 0) {
            return false;
        }

        $data = $query->fetch(\PDO::FETCH_ASSOC);

        if (!$data) {
            return false;
        }

        $data['uid'] = $uid;

        if (!$withpassword)
            unset($data['password']);
        return $data;
    }


    /***
     * Creates a session for a specified user id
     * @param int $uid
     * @param boolean $remember
     * @return array $data
     */
    private function addSession($uid, $remember)
    {
        $ip = $this->getIp();
        $user = $this->getUser($uid, true);

        if (!$user) {
            return false;
        }

        $data['hash'] = sha1($this->config->site_key . microtime());
        $agent = $_SERVER['HTTP_USER_AGENT'];

        $this->deleteExistingSessions($uid);

        if ($remember == true) {
            $data['expire'] = date("Y-m-d H:i:s", strtotime($this->config->cookie_remember));
            $data['expiretime'] = strtotime($data['expire']);
        } else {
            $data['expire'] = date("Y-m-d H:i:s", strtotime($this->config->cookie_forget));
            $data['expiretime'] = 0;
        }

        $data['cookie_crc'] = sha1($data['hash'] . $this->config->site_key);

        $query = $this->dbh->prepare("
INSERT INTO {$this->config->table_sessions}
(uid, hash, expiredate, ip, agent, cookie_crc)
VALUES (:uid, :hash, :expiredate, INET_ATON(:ip), :agent, :cookie_crc)
");

        $query_params = [
            'uid' => $uid,
            'hash' => $data['hash'],
            'expiredate' => $data['expire'],
            'ip' => $ip,
            'agent' => $agent,
            'cookie_crc' => $data['cookie_crc']
        ];

        if (!$query->execute($query_params)) {
            return false;
        }

        $data['expire'] = strtotime($data['expire']);
        return $data;
    }

    /***
     * Removes all existing sessions for a given UID
     * @param int $uid
     * @return boolean
     */
    private function deleteExistingSessions($uid)
    {
        $query = $this->dbh->prepare("DELETE FROM {$this->config->table_sessions} WHERE uid = :uid");
        $query->execute(['uid' => $uid]);

        return $query->rowCount() == 1;
    }

    /**
     * Checks if an email is already in use
     * @param string $email
     * @return boolean
     */
    private function isEmailTaken($email)
    {
        $query = $this->dbh->prepare("SELECT count(*) FROM {$this->config->table_users} WHERE email = :email");
        $query->execute(['email' => $email]);

        if ($query->fetchColumn() == 0) {
            return false;
        }

        return true;
    }

    /**
     * Adds a new user to database
     *
     * @param string $email -- email
     * @param string $password -- password
     * @param array $additional_fields -- additional params
     * @param boolean $use_email_activation -- activate email confirm or not
     * @return int $uid
     */
    private function addUser($email, $password, $additional_fields = array(), &$use_email_activation)
    {
        $return['error'] = true;

        $query = $this->dbh->prepare("INSERT INTO {$this->config->table_users} VALUES ()");

        if (!$query->execute()) {
            $return['message'] = $this->lang["system_error"] . " #03";
            return $return;
        }

        $uid = $this->dbh->lastInsertId();

        $email = htmlentities(strtolower($email));

        if ($use_email_activation) {
            $addRequest = $this->addRequest($uid, $email, "activation", $use_email_activation);

            if ($addRequest['error'] == 1) {
                $query = $this->dbh->prepare("DELETE FROM {$this->config->table_users} WHERE id = :id");
                $query_params = [
                    'id' => $uid
                ];
                $query->execute($query_params);

                $return['message'] = $addRequest['message'];
                return $return;
            }

            $isactive = 0;
        } else {
            $isactive = 1;
        }

        $password = $this->getHash($password);

        if (is_array($additional_fields) && count($additional_fields) > 0) {
            $customParamsQueryArray = Array();

            foreach ($additional_fields as $paramKey => $paramValue) {
                $customParamsQueryArray[] = array('value' => $paramKey . ' = ?');
            }

            $setParams = ', ' . implode(', ', array_map(function ($entry) {
                    return $entry['value'];
                }, $customParamsQueryArray));

        } else {
            $setParams = '';
        }

        $query = $this->dbh->prepare("UPDATE {$this->config->table_users} SET email = ?, password = ?, isactive = ? {$setParams} WHERE id = ?");

        $bindParams = array_values(array_merge(array($email, $password, $isactive), $additional_fields, array($uid)));

        if (!$query->execute($bindParams)) {
            $query = $this->dbh->prepare("DELETE FROM {$this->config->table_users} WHERE id = ?");
            $query->execute(array($uid));

            $return['message'] = $this->lang["system_error"] . " #04";
            return $return;
        }

        $return['error'] = false;
        return $return;
    }


    /**
     * Creates an activation entry and sends email to user
     * @param int $uid
     * @param string $email
     * @param string $type
     * @param boolean $sendmail = NULL
     * @return boolean
     */
    private function addRequest($uid, $email, $type, &$sendmail)
    {
        $return['error'] = true;

        if ($type != "activation" && $type != "reset") {
            $return['message'] = $this->lang["system_error"] . " #08";
            return $return;
        }

        // if not set manually, check config data
        if ($sendmail === NULL) {
            $sendmail = true;
            if ($type == "reset" && $this->config->emailmessage_suppress_reset === true) {
                $sendmail = false;
                $return['error'] = false;
                return $return;
            }
            if ($type == "activation" && $this->config->emailmessage_suppress_activation === true) {
                $sendmail = false;
                $return['error'] = false;
                return $return;
            }
        }

        $query = $this->dbh->prepare("SELECT id, expire FROM {$this->config->table_requests} WHERE uid = :uid AND type = :type");
        $query->execute(['uid' => $uid, 'type' => $type]);

        if ($query->rowCount() > 0) {
            $row = $query->fetch(\PDO::FETCH_ASSOC);

            $expiredate = strtotime($row['expire']);
            $currentdate = strtotime(date("Y-m-d H:i:s"));

            if ($currentdate < $expiredate) {
                $return['message'] = $this->lang["reset_exists"];
                return $return;
            }

            $this->deleteRequest($row['id']);
        }

        if ($type == "activation" && $this->getUser($uid, true)['isactive'] == 1) {
            $return['message'] = $this->lang["already_activated"];
            return $return;
        }

        $key = $this->getRandomKey(self::TOKEN_LENGTH);
        $expire = date("Y-m-d H:i:s", strtotime($this->config->request_key_expiration));

        $query = $this->dbh->prepare("INSERT INTO {$this->config->table_requests} (uid, token, expire, type) VALUES (:uid, :token, :expire, :type)");

        $query_params = [
            'uid' => $uid,
            'token' => $key,
            'expire' => $expire,
            'type' => $type
        ];

        if (!$query->execute($query_params)) {
            $return['message'] = $this->lang["system_error"] . " #09";
            return $return;
        }

        $request_id = $this->dbh->lastInsertId();

        if ($sendmail === true) {
            $sendmail_status = $this->do_SendMail($email, $type, $key);

            if (!$sendmail_status) {
                $this->deleteRequest($request_id);

                $return['message'] = $this->lang["system_error"] . " #10";
                return $return;
            }
        }

        $return['error'] = false;
        return $return;
    }

    /**
     * Deletes request from database
     * @param int $id
     * @return boolean
     */
    private function deleteRequest($id)
    {
        $query = $this->dbh->prepare("DELETE FROM {$this->config->table_requests} WHERE id = :id");
        return $query->execute(['id' => $id]);
    }

    /**
     * Returns a random string of a specified length
     * @param int $length
     * @return string $key
     */
    private function getRandomKey($length = self::TOKEN_LENGTH)
    {
        $chars = "A1B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6Q7R8S9T0U1V2W3X4Y5Z6a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6";
        $key = "";

        for ($i = 0; $i < $length; $i++) {
            $key .= $chars{mt_rand(0, strlen($chars) - 1)};
        }

        return $key;

    }

    /***
     * Hashes provided password with Bcrypt
     * @param string $password
     * @return string
     */
    private function getHash($password)
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $this->config->bcrypt_cost]);
    }

    /***
     * Removes a session based on hash
     * @param string $hash
     * @return boolean
     */
    private function deleteSession($hash)
    {
        $query = $this->dbh->prepare("DELETE FROM {$this->config->table_sessions} WHERE hash = :hash");
        $query->execute(['hash' => $hash]);
        return $query->rowCount() == 1;
    }

    /**
     * Returns request data if key is valid
     * @param string $key
     * @param string $type
     * @return array $return
     */
    public function getRequest($key, $type)
    {
        $return['error'] = true;

        $query = $this->dbh->prepare("SELECT id, uid, expire FROM {$this->config->table_requests} WHERE token = ? AND type = ?");
        $query->execute(array($key, $type));

        if ($query->rowCount() === 0) {
            $this->addAttempt();

            $return['message'] = $this->lang[$type . "key_incorrect"];
            return $return;
        }

        $row = $query->fetch();

        $expiredate = strtotime($row['expire']);
        $currentdate = strtotime(date("Y-m-d H:i:s"));

        if ($currentdate > $expiredate) {
            $this->addAttempt();

            $this->deleteRequest($row['id']);

            $return['message'] = $this->lang[$type . "key_expired"];
            return $return;
        }

        $return['error'] = false;
        $return['id'] = $row['id'];
        $return['uid'] = $row['uid'];

        return $return;
    }

    /* Custom KW methods */
    /**
     * Update userinfo for user with given id = $uid
     * @param int $uid
     * @param array $params
     * @return array $return[error/message]
     */
    public function updateUser($uid, $params)
    {
        $setParams = '';
        if (is_array($params) && count($params) > 0) {
            $customParamsQueryArray = Array();

            foreach ($params as $paramKey => $paramValue) {
                $customParamsQueryArray[] = array('value' => $paramKey . ' = ?');
            }

            $setParams = implode(', ', array_map(function ($entry) {
                return $entry['value'];
            }, $customParamsQueryArray));
        }
        $query = $this->dbh->prepare("UPDATE {$this->config->table_users} SET {$setParams} WHERE id = ?");
        $bindParams = array_values(array_merge($params, array($uid)));

        if (!$query->execute($bindParams)) {
            $return['message'] = $this->lang["system_error"] . " #04";
            return $return;
        }
        $return['error'] = false;
        $return['message'] = 'Ok.';
        return $return;
    }

    /**
     * Возвращает информацию о текущем пользователе (массив)
     * get CURRENT userinfo: array or false
     */
    public function getCurrentUserInfo()
    {
        return $this->getUser($this->getSessionUID($this->getSessionHash()));
    }

    /**
     * Возвращает идентификатор текущего пользователя или false, если мы не залогинены.
     * @return int
     */
    public function getCurrentUID()
    {
        return $this->getSessionUID($this->getSessionHash());
    }


    private function do_SendMail($email, $type, $key)
    {
        // Check configuration for SMTP parameters
        $mail = new PHPMailer;
        if ($this->config->smtp) {
            $mail->isSMTP();
            $mail->Host = $this->config->smtp_host;
            $mail->SMTPAuth = $this->config->smtp_auth;
            if (!is_null($this->config->smtp_auth)) {
                $mail->Username = $this->config->smtp_username;
                $mail->Password = $this->config->smtp_password;
            }
            $mail->Port = $this->config->smtp_port;

            if (!is_null($this->config->smtp_security)) {
                $mail->SMTPSecure = $this->config->smtp_security;
            }
        }

        $mail->From = $this->config->site_email;
        $mail->FromName = $this->config->site_name;
        $mail->addAddress($email);
        $mail->isHTML(true);

        if ($type == 'activation') {
            $mail->Subject = sprintf($this->lang['email_activation_subject'], $this->config->site_name);
            $mail->Body = sprintf($this->lang['email_activation_body'], $this->config->site_url, $this->config->site_activation_page, $key);
            $mail->AltBody = sprintf($this->lang['email_activation_altbody'], $this->config->site_url, $this->config->site_activation_page, $key);
        } elseif ($type == 'reset') {
            $mail->Subject = sprintf($this->lang['email_reset_subject'], $this->config->site_name);
            $mail->Body = sprintf($this->lang['email_reset_body'], $this->config->site_url, $this->config->site_password_reset_page, $key);
            $mail->AltBody = sprintf($this->lang['email_reset_altbody'], $this->config->site_url, $this->config->site_password_reset_page, $key);
        } else {
            return false;
        }

        return $mail->send();

    }

    /**
     * Translates key-message to defined language
     *
     * @param $key
     * @return mixed
     */
    private function __lang($key)
    {
        return array_key_exists($key, $this->lang) ? $this->lang[$key] : $key;
    }


}