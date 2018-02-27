# AppLogger

Обёртка над Monolog.

```
use Engine\Arris\AppLogger as Log;
Log::alert('Warning');
```

Использует настройки, добавленные в секцию `monolog` из конфигурационного файла `monolog.ini` следующего вида:
```
channel = 'application'
handler = 'file'

[handler:file]
filepath = '$/'
filename = 'application.log'

[handler:mysql]
script = 'monolog.php'
```

В данный момент не поддерживается раскладка разных ошибок по разным файлам.