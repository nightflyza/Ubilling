Ubilling
========

Ubilling is opensource ISP billing system based on stargazer.

Please visit our official resources:

  * [Project homepage](http://ubilling.net.ua)
  * [Documentation](http://wiki.ubilling.net.ua)
  * [Community forums](http://local.com.ua/forum/forum/144-stargazer-ubilling/)
 
## Краткое руководство по создание [Pull request](https://github.com/nightflyza/Ubilling/pulls)

Для начала Вам необходимо создать ФОРК проекта.

Для этого нажмите верху страницы проекта кнопку **Fork**
![image](https://user-images.githubusercontent.com/11805503/41152954-f56f22ce-6b1d-11e8-88a9-dce178c27529.png)

Это действие сделает копию исходного кода со всеми ветками в ваш акаунт. 
Это нам понадобится позже.

Далее на сервера выполните следующие действия:
```
git clone https://github.com/nightflyza/Ubilling
cd Ubilling
git remote add my-fork https://github.com/pautiina/Ubilling.git
git checkout -b master-some-fix
vi CONTRIBUTING.md
git add CONTRIBUTING.md
git commit -m "I add some line to CONTRIBUTING"
git push —set-upstream my-fork master-some-fix
```
Заходим на страницу вашего форка и видим, что Гитхаб предлагает вам создать пулреквест.
![image](https://user-images.githubusercontent.com/11805503/41153451-5c856bca-6b1f-11e8-8758-acb1cf5d6785.png).

После создания пулреквеста - гитхаб определит, если конфликт с официальным кодом или нет. Если конфликта нет, то просто ждем пока его смержат. Если конфликт есть - закрываем пулреквест и делаем все изминения по новой, только так, что-бы не получились снова конфликты.
Ждем, пока Найт все @nightflyza . Главное, чтобы ваши коммиты не пересекались с его изменениями, иначе тогда придется все по новой делать.
Дальше возвращаемся в ветку master официального проекта и обновляем уже смерженный код
```
git checkout master
git pull
```
Теперь можно удалить ветку с вашего форка ( master-some-fix)
```
git push my-fork :master-some-fix
git branch -D master-some-fix
```

[![Build Status](https://travis-ci.org/nightflyza/Ubilling.svg?branch=master)](https://travis-ci.org/nightflyza/Ubilling)
