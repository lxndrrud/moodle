После поднятия контейнеров docker-compose up -d  нужно запустить установку мудла:
docker exec -it moodle php ./admin/cli/install.php

Выборы в установке показаны на скринах, только web address  нужно ставить http://localhost:8000/moodle
База данных db
Ввести пароль и логин от базы
Ввести новый пароль для админа
