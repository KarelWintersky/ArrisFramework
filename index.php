<?php
/**
 * User: Arris
 * Date: 18.02.2018, time: 16:10
 */
define('__ROOT__', __DIR__);
define('__CONFIG__', __ROOT__ . '/.config/');

require_once 'vendor/autoload.php';

use Engine\Arris\App;
use Engine\Arris\DB;

App::init([
    'config.ini',
    'db.ini',
    'monolog'   =>  'monolog.ini'
]);

dump(App::$config);

$c1 = DB::getConnection();
$s1 = $c1->query("SELECT 1;");

dump($s1->fetchColumn());

$c2 = DB::getConnection('crontasks');
$s2 = $c2->query("SELECT COUNT(*) FROM `pastvu_photos`");

dump($s2->fetchColumn());

$c3 = DB::getConnection();
$s3 = $c3->query("SELECT COUNT(*) FROM `antign_wordstorage`");

dump($s3->fetchColumn());

dump($c1 === $c3);