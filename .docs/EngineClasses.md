# App

По идее - основной класс приложения. Но на данный момент основная функция класса - предоставлять статический интерфейс к
глобальному конфигу.

Приложение инициализируется так:

```
App::init($config, $config_dir);
```
`$config` - массив с перечислением ini-файлов или строчка с одним ini-файлом.
`$config_dir` - по умолчанию, __CONFIG__, определенный в точке входа (index.php) как `define('__CONFIG__', __ROOT__ . '/.config/');`

Пример инициализации:
```
App::init([
    'config.ini',
    'db.ini',
    'monolog'   =>  'monolog.ini'
]);
```
Важный момент: здесь `config.ini` и `db.ini` будут загружены в общую секцию глобального конфига, а ключи из `monolog.ini` в секцию 'monolog':
```
"monolog" => array:4 [
    "channel" => "rpgclubsrf"
    "handler" => "file"
    "handler:file" => array:2 [
      "filepath" => "$/storage/"
      "filename" => "rpgclubsrf.log"
    ]
    "handler:mysql" => array:1 [
      "script" => "monolog.php"
    ]
  ]

```
