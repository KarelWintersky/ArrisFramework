# WebSunTemplate

Класс для работы с внутренней системой шаблонов, надстройкой над WebSub

```
$template = new Template('login.html', '/srv/webhosts/ArrisFramework/Arris/templates');

$template->set('href', [
    'form_action'       =>  '/auth_callback_login',
    'frontpage'         =>  '/frontpage'
]);

echo $template->render();
```

Методы:

- `__constructor` - создает инстанс шаблона
- `set($path, $value)` - устанавливает переменные
- `setRender()` - меняет тип рендера - websun или json_encode
- `render()` или `content()` - рендерит шаблон

