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

### Воспроизведение аудио
Аудиофайлы скачиваются через nginx. Nginx при этом делает внутренний запрос на разрешение скачивания файла пользователем.
Чтобы аудиофайл воспроизводился:
- Отключить проверку для локальной разработки - в `.env` добавить `AUDIOBOOK_CHECK_TOKEN_TO_ALLOW_USER_DOWNLOAD_AUDIO=false`
- Или добавить в компоненте/на странице генерацию токена для пользователя - см. `AudioFileListenTokenService::class`
