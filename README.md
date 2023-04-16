# email-notification-app (ena)
## Порядок запуска
* docker compose -f provisioning/docker-compose.local.yaml build
* docker compose -f provisioning/docker-compose.local.yaml up -d
* Убеждаемся, что контейнеры на месте: docker ps. Если нет, то ггвп
* Заходим в контейнер: docker exec -it ena-php bash
* Проверяем консьюмеры: supervisorctl status. Если не запущены или упали, то ггвп
* Создаем таблицы: php migrations.php
* Заполняем таблицы тестовыми данными: php fixtures.php
* Валидируем емейлы: php validate_emails.php (в очередь уйдет сто тысяч сообщений)
* Отправляем нотификации: php notification_sender.php