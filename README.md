#CodeIgniter Database

Use the Database Library separately from CodeIgniter 3.

## Installation

###With Composer
```json
"require": {
    "nguyenanhung/codeigniter-database-standalone": "^1.0"
}
```

or with command line : `composer require nguyenanhung/codeigniter-database-standalone`

###Without Composer

You can also download it from Github, but no autoloader is provided so you'll need to register it with your own PSR-0 compatible autoloader.

#Usage
```php
<?php
use nguyenanhung\CodeIgniterDB as CI;
$db_data = array(
	'dsn'	=> '',
	'hostname' => 'localhost',
	'username' => 'root',
	'password' => 'my_password',
	'database' => 'my_database',
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => TRUE,
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
);
$oDb =& CI\DB($db_data);
```
For more information visit <a href="http://www.codeigniter.com/userguide3/database/index.html">CodeIgniter user guide</a>.

##Custom option

I've added the possibility to give a mysql ressource to reuse a already opened connection.
Thus to not multiply connections and to use this in parallel with legacy code and proceed to a migration step by step.
**Works only with the mysql driver !**

```php
<?php
use nguyenanhung\CodeIgniterDB as CI;

$db_data = array(
	'dsn'	=> '',
	'hostname' => 'localhost',
	'username' => 'root',
	'password' => 'my_password',
	'database' => 'my_database',
	'dbdriver' => 'mysql',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => TRUE,
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
);

$rDb = mysql_connect($db_data['hostname'], $db_data['root'], $db_data['password']);

$oDb =& CI\DB($db_data, null, $rDb);
```