Скрипт для отображения состояния коммутаторов из Zabbix на одной странице.
Версия 0.2b - совместима с Zabbix 2.0 (не совместима с 1.8)

УСТАНОВКА
1.	Скопировать файлы на веб-сервер (не обязательно на сервер zabbix).
2.	Создать пользователя в zabbix, разрешить ему API access.
3.	Отредактировать index.php, вписать туда имя пользователя и пароль,
	которые будет запрашивать скрипт для входа, а также пользователя
	и пароль Zabbix API.
4.	Отредактировать ports_ajax.php, установить пользователя и пароль
	для доступа к API еще раз.
5.	Опционально: если вы хотите включить просмотр карты сети,
	отредактируйте $map_URL в index.php и настройки в get_map.php.

Вопросы? vladislav.ross@gmail.com