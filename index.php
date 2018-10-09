<?php
/**
 * User: Arris
 * Date: 10.04.2018, time: 20:29
 */

define('AF_ROOT', getenv('AF_ROOT') ?? __DIR__);

define('__ROOT__', __DIR__);
define('__CONFIG__', __ROOT__ . '/config/');

require_once 'vendor/autoload.php';

require_once 'engine/core.helpers.php';
require_once 'engine/core.functions.php';

use Pecee\SimpleRouter\SimpleRouter;
use Arris\App;
use Arris\DB;
use Arris\AppLogger as Log;
use Arris\VisitLogger as VLog;
use Arris\WebSunTemplate as Template;
use Arris\Auth;

App::init([
    'config.ini',
    'db.ini',
    'monolog'   =>  'monolog.ini',
    'visitlog'  =>  'visitlog.ini',
    'phpauth'   =>  'phpauth.ini'
], '$/.config/');

SimpleRouter::get('/', function(){
    $template = new Template('index.html', __DIR__ . '/templates');

    $userinfo = Auth::getCurrentUserInfo();

    $template->set('userinfo', $userinfo);

    echo $template->render(), PHP_EOL;
});

SimpleRouter::get('/whoami', function(){
    dump('cookie', $_COOKIE);

    if (Auth::isLogged()) {
        dump('Auth::getCurrentUserInfo()', Auth::getCurrentUserInfo() );
    } else {
        echo 'Not logged';
    }

    dump( Auth::getInstance()->config->cookie_userlogin_new_registered );
});

SimpleRouter::get('/login', function(){
    $auth_result = Auth::login('karel.wintersky@yandex.ru', 'password', 1);

    dump($auth_result);
});

SimpleRouter::get('/logout', function(){
    $status = Auth::logout();

    dump($status);
});

SimpleRouter::get('/register', function(){
    $status = Auth::register('karel.wintersky@yandex.ru', 'password', 'password');
    dump($status);
});

SimpleRouter::get('/test', function(){
    dump('test');
});

SimpleRouter::get('/template', function(){
    $template = new Template('template1.html', __DIR__ . '/templates');
    $template->set('', [
        'value1'    =>  1,
        'value2'    =>  2
    ]);

    echo $template->render(), PHP_EOL;

    echo '---------------------------', PHP_EOL;


    $template = Websun\websun::websun_parse_template_path( [
        'value1'    =>  1,
        'value2'    =>  2
    ], 'template1.html', __DIR__ . '/templates' );

    echo $template , PHP_EOL;
});


SimpleRouter::start();

echo PHP_EOL, '<br/><a href="/">Back...</a>';