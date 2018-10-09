<?php
/**
 * User: Karel Wintersky
 * Date: 26.08.2018, time: 14:25 Version 1.5/LIBDb
 * Date: 20.09.2018, time: 16:34 Version 2.0/ArrisFramework
 *
 */

/**
 * Interface DBConnectionInterface
 */
interface DBConnectionInterface
{
    /**
     * Predicted (early) initialization
     *
     * @param null $suffix
     * @param $config
     */
    public static function init($suffix, $config);

    /**
     * Get connection config
     *
     * @param null $suffix
     * @return mixed|null
     */
    public static function getConfig($suffix = NULL): array;

    /**
     * Set connection config
     *
     * @param $config
     * @param null $suffix
     */
    public static function setConfig($config, $suffix = NULL);

    /**
     * Alias: get PDO connection
     *
     * @param null $suffix
     * @return PDO
     */
    public static function getConnection($suffix = NULL): \PDO;

    /**
     * Set current connection key for internal calls === setContext() ?
     *
     * @param $suffix
     */
    public static function setConnection($suffix);

    /**
     * Get class instance == connection instance
     *
     * @param null $suffix
     * @return PDO
     */
    public static function getInstance($suffix = NULL):\PDO;

    /**
     * Get tables prefix for given connection
     *
     * @param null $suffix
     * @return null|string
     */
    public static function getTablePrefix( $suffix = NULL );

    /**
     *
     * @param $query
     * @param null $suffix
     * @return bool|\PDOStatement
     */
    public static function query($query, $suffix = NULL);

    /**
     * Get count(*) for given table
     *
     * @param $table
     * @param null $suffix
     * @return mixed|null
     */
    public static function getRowCount($table, $suffix = NULL);

    /**
     * Аналог rowcound, только дает возможность выбрать поле выборки и условие
     *
     * @param $table
     * @param string $field
     * @param string $condition
     * @param null $suffix
     * @return mixed|null
     */
    public static function getRowCountConditional($table, $field = '*', $condition = '', $suffix = NULL);

    /**
     * get Last Insert ID
     *
     * @param null $suffix
     */
    public static function getLastInsertId($suffix = NULL);

    /**
     * Build INSERT-query by dataset for given table
     *
     * @param $tablename
     * @param $dataset
     * @return string
     */
    public static function makeInsertQuery($tablename, $dataset);

    /**
     * Build UPDATE query by dataset for given table
     *
     * @param $tablename
     * @param $dataset
     * @param string $where_condition
     * @return bool|string
     */
    public static function makeUpdateQuery($tablename, $dataset, $where_condition = '');

}



/**
 * Class DB
 */
class DB implements DBConnectionInterface
{
    const VERSION = '2.0/ArrisFramework';

    private static $_current_connection = null;

    /**
     * \PDO instances
     * @var array
     */
    private static $_instances = [];

    /**
     * DB Configs
     * @var array
     */
    private static $_configs = [];

    /**
     *
     * DB constructor.
     * @param $suffix
     */
    public function __construct($suffix)
    {
        $config_key = self::getKey($suffix);

        $config
            = is_null(self::getConfig($suffix))
            ? Config::get($config_key)
            : self::getConfig($suffix);

        $dbhost = $config['hostname'];
        $dbname = $config['database'];
        $dbuser = $config['username'];
        $dbpass = $config['password'];
        $dbport = $config['port'];

        $db_charset = $config['charset'] ?? 'utf8';
        $db_charset_collate = $config['charset_collate'] ?? 'utf8_unicode_ci';

        $dsl = "mysql:host={$dbhost};port={$dbport};dbname={$dbname}";
        try {
            if ($config === NULL)
                throw new \Exception("Config section `[{$config_key}]` not declared in config workspace.\r\n", 2);

            $dbh = new \PDO($dsl, $dbuser, $dbpass);

            $dbh->exec("SET NAMES {$db_charset} COLLATE {$db_charset_collate}");
            $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $dbh->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

            self::$_instances[$config_key] = $dbh;

            $connection_state = TRUE;

        } catch (\PDOException $pdo_e) {
            $message = "Unable to connect `{$dsl}`, PDO CONNECTION ERROR: " . $pdo_e->getMessage() . "\r\n" . PHP_EOL;

            $connection_state = [
                'error' => $message,
                'state' => FALSE
            ];

        } catch (\Exception $e) {
            $connection_state = [
                'error' => $e->getMessage(),
                'state' => FALSE
            ];
            self::$_configs[$config_key] = NULL;
        }

        if ($connection_state !== TRUE) {
            die($connection_state['error']);
        }

        self::$_configs[$config_key] = $config;

    }

    /**
     * Predicted (early) initialization
     *
     * @param null $suffix
     * @param $config
     */
    public static function init($suffix, $config)
    {
        $config_key = self::getKey($suffix);
        self::setConfig($config, $suffix);
        self::$_instances[$config_key] = (new self($suffix))->getInstance($suffix);
    }

    /**
     * Get connection config
     *
     * @param null $suffix
     * @return mixed|null
     */
    public static function getConfig($suffix = NULL): array
    {
        $config_key = self::getKey($suffix);
        return array_key_exists($config_key, self::$_configs) ? self::$_configs[$config_key] : NULL;
    }

    /**
     * Set connection config
     *
     * @param $config
     * @param null $suffix
     */
    public static function setConfig($config, $suffix = NULL)
    {
        $config_key = self::getKey($suffix);
        self::$_configs[$config_key] = $config;
    }

    /**
     * Alias: get PDO connection
     *
     * @param null $suffix
     * @return PDO
     */
    public static function getConnection($suffix = NULL): \PDO
    {
        return self::getInstance($suffix);
    }

    /**
     * Set current connection key for internal calls === setContext() ?
     *
     * @param $suffix
     */
    public static function setConnection($suffix)
    {
        self::$_current_connection = self::getKey($suffix);
    }

    /**
     * Get class instance == connection instance
     *
     * @param null $suffix
     * @return PDO
     */
    public static function getInstance($suffix = NULL):\PDO
    {
        $key = self::getKey($suffix);
        if (self::checkInstance($suffix)) {
            return self::$_instances[ $key ];
        }

        new self($suffix);
        return self::$_instances[ $key ];

        /* old :
        $key = self::getKey($suffix);
        if (!self::checkInstance($suffix)) {
            self::$_instances[$key] = new self($suffix);
        }
        return self::$_instances[$key];
        */
    }

    /**
     * Get tables prefix for given connection
     *
     * @param null $suffix
     * @return null|string
     */
    public static function getTablePrefix( $suffix = NULL )
    {
        if (!self::checkInstance($suffix)) return NULL;

        $config_key = self::getKey($suffix);

        return
            array_key_exists('table_prefix', self::$_configs[$config_key] )
                ? self::$_configs[$config_key]['table_prefix']
                : '';
    }


    /**
     *
     * @param $query
     * @param null $suffix
     * @return bool|\PDOStatement
     */
    public static function query($query, $suffix = NULL)
    {
        $state = FALSE;

        try {
            $state = DB::getConnection($suffix)->query($query);
        } catch (\PDOException $e) {

        }

        return $state;
    }

    /**
     * Get count(*) for given table
     *
     * @param $table
     * @param null $suffix
     * @return mixed|null
     */
    public static function getRowCount($table, $suffix = NULL)
    {
        if ($table == '') return null;
        $sth = self::getConnection($suffix)->query("SELECT COUNT(*) AS cnt FROM {$table}");

        return ($sth) ? $sth->fetchColumn() : null;
    }

    /**
     * Conditional getRowCount()
     *
     * @param $table
     * @param string $field
     * @param string $condition
     * @param null $suffix
     * @return mixed|null
     */
    public static function getRowCountConditional($table, $field = '*', $condition = '', $suffix = NULL)
    {
        if ($table === '') return null;

        $where = ($condition !== '') ? " WHERE {$condition} " : '';
        $field = ($field !== '*') ? "`{$field}`" : "*";

        $query = "SELECT COUNT({$field}) AS rowcount FROM {$table} {$where}";

        // $suff = self::$_current_connection ?? $suffix;
        $sth = self::getConnection($suffix)->query($query);

        return ($sth) ? $sth->fetchColumn() : null;
    }

    /**
     * get Last Insert ID
     *
     * @param null $suffix
     */
    public static function getLastInsertId($suffix = NULL)
    {
        self::getConnection($suffix)->lastInsertId();
    }

    /**
     * Build INSERT-query by dataset for given table
     *
     * @param $tablename
     * @param $dataset
     * @return string
     */
    public static function makeInsertQuery($tablename, $dataset)
    {
        $query = '';
        $r = [];

        if (empty($dataset)) {
            $query = "INSERT INTO {$tablename} () VALUES (); ";
        } else {
            $query = "INSERT INTO `{$tablename}` SET ";

            foreach ($dataset as $index=>$value) {
                $r[] = "\r\n `{$index}` = :{$index}";
            }

            $query .= implode(', ', $r) . ' ;';
        }

        return $query;
    }

    /**
     * Build UPDATE query by dataset for given table
     *
     * @param $tablename
     * @param $dataset
     * @param string $where_condition
     * @return bool|string
     */
    public static function makeUpdateQuery($tablename, $dataset, $where_condition = '')
    {
        $query = '';
        $r = [];

        if (empty($dataset)) {
            return FALSE;
        } else {
            $query = "UPDATE `{$tablename}` SET";

            foreach ($dataset as $index=>$value) {
                $r[] = "\r\n`{$index}` = :{$index}";
            }

            $query .= implode(', ', $r);

            $query .= " \r\n" . $where_condition . " ;";
        }

        return $query;
    }

    /**
     * Converts connection suffix to internal connection key
     *
     * @param null $suffix
     * @return string
     */
    private static function getKey($suffix = NULL)
    {
        return 'database' . ($suffix ? ":{$suffix}" : '');
    }

    /**
     * Check existance of connection in instances array
     *
     * @param null $suffix
     * @return bool
     */
    private static function checkInstance($suffix = NULL) {

        $key = self::getKey($suffix);
        return ( array_key_exists($key, self::$_instances) && self::$_instances[$key] !== NULL  );
    }

}
