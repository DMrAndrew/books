## Installing

```sh
cp .env.example .env
```
```sh
docker-compose up -d
```
```sh
docker-compose exec site.loc /bin/bash
```
```sh
composer install
```
```sh
exit
```
```sh
./vendor/bin/sail up -d
```
```sh
./vendor/bin/sail artisan key:generate
```
```sh
./vendor/bin/sail artisan october:migrate
```

### Известные проблемы
Если не удается авторизоваться как админ, на странице `/backend` при попытке авторизоваться выпадает ошибка `Неправильный токен безопасности`
Необходимо пересобрать контейнеры командой - `export WWWUSER=${WWWUSER:-$UID} && export WWWGROUP=${WWWGROUP:-$(id -g)} && docker-compose up -d`

### Запустить Telescope
- включить в .env - ``TELESCOPE_ENABLED=true``
- выполнить ``php artisan vendor:publish --tag telescope-assets --force``
- выполнить ``php artisan vendor:publish --tag telescope-config``

Открывать по адресу ``/backend/vdlp/telescope/dashboard``
