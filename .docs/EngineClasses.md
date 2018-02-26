# App

По идее - основной класс приложения. Но на данный момент основная функция класса - предоставлять статический интерфейс к
глобальному конфигу.

Приложение инициализируется так:

```
App::init($config, $config_dir);
```

- `$config` - массив с перечислением ini-файлов или строчка с одним ini-файлом.
- `$config_dir` - по умолчанию, __CONFIG__, определенный в точке входа (index.php) как `define('__CONFIG__', __ROOT__ . '/.config/');`

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

# DB

Синглтон-класс, реализующий мультиподключения к БД.

Использование (рекомендуемое):
```
$s1 = DB::getConnection()->query("SELECT 1;");
$s2 = DB::getConnection('crontasks')->query("SELECT 1;");
```

или
```
$c1 = DB::getConnection();
$c2 = DB::getConnection();
dump($c1 === $c2); // true
```

Аргументом является префикс секции в конфиге. Особенность текущей реализации: ini-файл с конфигурацией должен загружаться в корень глобального конфига,
то есть `App::init(['db' => 'db.ini'])` делать нельзя. Кроме того, недопустим префикс секции с именем `NULL`.

Формат конфига
```
[database:development]
driver   = 'mysql'
hostname = 'localhost'
username = 'root'
password = 'password'
database = 'firstdatabase'
port     = 3306
table_prefix = 'test_'


[seconddatabase:database:development]
driver   = 'mysql'
hostname = 'localhost'
username = 'mylsquser'
password = 'password'
database = 'seconddatabase'
port     = 3306
table_prefix = 'test_'
```

В данном случае первое подключение будет доступно без префикса, а второе - по префиксу `seconddatabase`:

```
$c1 = DB::getConnection();
$c2 = DB::getConnection('seconddatabase');
```



