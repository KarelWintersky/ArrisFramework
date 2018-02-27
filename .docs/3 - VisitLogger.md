# VisitLogger

Используется:
```
\Engine\Arris\VisitLogger::log();
```

Логгирует айпишник посетителя в БД, файл или с помощью Monolog (последний вариант еще не реализован).

Использует конфиг, загруженный в секцию `visitlog` вида:

```
; file | database | (monolog)
handler     = 'file'

; логгировать ли уникальные посещения?
log_unuque  = true

; логгировать ли все посещения
log_all     = true

; имя "канала" (counter_alias, 8 латинских символов)
log_channel = 'abstract'

; логи в файлах. Работает только логгирование ВСЕХ посещений, при этом айпишник данные сохраняются в CSV-виде
; $ означает текущий каталог
[handler:file]
file_log_path = '$/'
file_log_name = 'visit_all.log'

; логи в БД. Работает логгирование разных типов (уников и всех)
[handler:database]
table_log_unuque = 'logs_visit_unuque'
table_log_all    = 'logs_visit_all'

[handler:monolog]
; не реализовано
```
