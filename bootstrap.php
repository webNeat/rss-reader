<?php
use rss\orm\Connection;
use rss\orm\Mapper;
use rss\orm\Finder;
use rss\orm\Model;
use rss\models\Item;

require __DIR__.'/vendor/autoload.php';
require __DIR__.'/config.php';

$connection = new Connection( $config['db_driver'], $config['db_host'], 
	$config['db_name'], $config['db_user'], $config['db_pass']);
$mapper = new Mapper($connection);
$itemsFinder = new Finder($connection, 'rss\models\Item');