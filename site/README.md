# МаноБанк

Банковские калькуляторы на Laravel с шаблонами Blade и базой данных SQLite:
ипотека, автокредит, потребительский кредит, пенсионные накопления и вклад повседневный.
Формулы, ограничения и интерфейсы определены согласованным техническим заданием.
Файлы приложения находятся в `site/`. Команды ниже выполняются из корня проекта,
то есть из папки, содержащей `site`, `tools` и `docs`.

## Инструменты

- PHP 8.5 (локально установлена версия 8.5.13) и Composer.
- Node.js и npm для сборки ресурсов через Vite.
- Dompdf для формирования PDF.
- PhpSpreadsheet для экспорта XLSX.
- PHPUnit для автоматических проверок.

Сценарий `tools/project.ps1` запускает команды в папке приложения, подключает
локальные расширения GD и GMP и направляет временные файлы в `temp/runtime/`.
Общая конфигурация PHP на компьютере не изменяется.

## Восстановление зависимостей

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\tools\project.ps1 composer install
powershell -NoProfile -ExecutionPolicy Bypass -File .\tools\project.ps1 npm ci
```

При первом развёртывании нужно скопировать `site/.env.example` в `site/.env`,
создать пустой файл `site/database/database.sqlite`, затем выполнить:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\tools\project.ps1 php artisan key:generate
powershell -NoProfile -ExecutionPolicy Bypass -File .\tools\project.ps1 php artisan migrate
powershell -NoProfile -ExecutionPolicy Bypass -File .\tools\project.ps1 php artisan db:seed
powershell -NoProfile -ExecutionPolicy Bypass -File .\tools\project.ps1 npm run build
```

Настройки среды, ключ приложения, рабочая база, зависимости и результаты сборки
не включаются в Git. Точные версии зависимостей записаны в `composer.lock`
и `package-lock.json`.

## Запуск и проверка

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\tools\project.ps1 composer serve
```

Локальный адрес: http://127.0.0.1:8000.

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\tools\project.ps1 composer test
powershell -NoProfile -ExecutionPolicy Bypass -File .\tools\project.ps1 npm run build
```

Предусмотрены отдельный административный вход, внутренние черновики калькуляторов,
просмотр снимков расчётов и экспорт XLSX. Во вложении письма формируется полный PDF.
До включения и настройки SMTP отправка возвращает ошибку, не имитируя доставку.
Для реальной отправки через Mail.ru нужно отдельно настроить SMTP и проверить получение.
Пароли и другие секреты хранятся в локальном `site/.env` и не публикуются.

Подробные команды запуска, задания пароля, резервного копирования и проверки
восстановления: [руководство по эксплуатации](../docs/Эксплуатация_МаноБанк.md).
Общие страницы, каталог, финансовые формулы, снимки, отчёты, почта, администрация,
статистика и сопровождение имеют отдельных владельцев в `app/Modules`.

Laravel распространяется по лицензии [MIT](https://opensource.org/licenses/MIT).
Лицензии установленных библиотек сохраняются в их исходных пакетах.
