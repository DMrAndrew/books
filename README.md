## Installing

1. cp .env.example .env
2. docker-compose up -d
3. docker-compose exec site.loc /bin/bash
4. composer install
5. exit
6. ./vendor/bin/sail up -d
7. ./vendor/bin/sail artisan key:generate
8. ./vendor/bin/sail artisan october:migrate

### Известные проблемы
