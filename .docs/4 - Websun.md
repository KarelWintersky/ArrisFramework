# WebSun

Используется библиотека WebSun (Websun template parser by Mikhail Serov, 1234ru@gmail.com).

Пока нет официального композер-пакета, я обернул её в неймспейс `Arris\Websun` и подключаю
через
```
"psr-4": {
            "Arris\\"           : "engine/Arris",
            "Arris\\Websun\\"   : "engine/Arris/Websun"
        }
```
в `composer.json`

Используется она так:

```
use Arris\Websun as websun;

...

return websun\websun::websun_parse_template_path( $this->template_data, $this->template_file, $this->template_path );

```

то есть, не напрямую, а через WebSunTemplate класс.