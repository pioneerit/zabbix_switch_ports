zabbix_switch_ports
===================

Repo for build visible map of switch ports.
Create for modify original from topic: http://habrahabr.ru/post/154723/

Original README: [readme_rus.txt](https://raw.github.com/pioneerit/zabbix_switch_ports/master/readme_rus.txt "readme_rus.txt")

TODO:
------------------------
* Commit uptades
* Update installation process

Install:
------------------------
-  Git clone to the root folder of Zabbix PHP Frontend as `zabbix_ports`
-  Copy, or link `ports.php` and `switches.php` files to the root folder of Zabbix PHP Frontend
-  Edit `zabbix_ports/config.inc.php`. Example in `config.inc.php.sample`
-  Edit include/menu.inc.php:
	After:

		array(
			'url' => 'maps.php',
			'label' => _('Maps'),
			'sub_pages' => array('map.php')
		),

	Add:

		array(
			'url' => 'ports.php',
			'label' => _('Switch Ports'),
			'sub_pages' => array('ports.php')
		),
