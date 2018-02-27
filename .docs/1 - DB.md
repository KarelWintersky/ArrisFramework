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

