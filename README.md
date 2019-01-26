# Nullchaba
Форк оригинального репозитория [говнульча](https://github.com/klkvsk/0chan), от магмы с рядом изменений.

## Отличия от оригинала
* Удален луп и все его файлы (репозиторий знатно похудал)
* Удалена зависимость от анального зонда [sentry.io](https://sentry.io)
* Починен докер-компос файл
* Без пикчи невозможно создать тред
* Отключено создание юзердосок (пользователи все еще могут администрировать доски если у них будут права)
* Запилен делол
* Отключены упоминания

## TODO:
* Подробный мануал по поднятию движка
* Вротфильтр деструктивных параш

## Установка
### Требования
* Docker
* Docker Compose(3.2)
* Node.JS(взлетит и на ласт версии)
* git
* Ubuntu 16.04
* Плюшевая игрушка сниви(шутка)
### Установка на НОВОМ сервере
* Вы только что поставили на сервер Ubuntu 16.04 и авторизовались под рутом
1. `apt-get update && apt-get upgrade`
2. `adduser nullchan` (Вводите пароль и тд)
3. `usermod -a -G sudo nullchan`
4. `su nullchan`
5. `cd` (Переходим в корень нашего пользователя)
* Теперь все команды выполняем от `sudo` (Первый раз запросит пароль который вы ввели когда создавали пользователя)
5. `sudo apt-key adv --keyserver hkp://p80.pool.sks-keyservers.net:80 --recv-keys 58118E89F3A912897C070ADBF76221572C52609D`
6. `sudo apt-add-repository 'deb https://apt.dockerproject.org/repo ubuntu-xenial main'`
7. `sudo apt-get update`
8. `sudo apt-get install -y docker-engine`
9. `sudo service docker status` (Если там горит зелёная хуета то продолжаем)
10. `sudo usermod -aG docker nullchan` (Выдаем права на использование докера нашему пользователю)(Желательно после этого перезагрузить сервер)
11. `curl -sL https://deb.nodesource.com/setup_8.x -o nodesource_setup.sh`
12. `sudo chmod +x nodesource_setup.sh`
13. `sh nodesource_setup.sh`
14. `git clone https://github.com/MagmaDivide/0chan`
15. `cd 0chan`
* ДАННЫЙ МАНУАЛ НЕДОПИСАН ПО ЭТОМУ ЖДИТЕ