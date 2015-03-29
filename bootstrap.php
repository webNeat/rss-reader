<?php
use rss\orm\Connection;
use rss\orm\Mapper;
use rss\orm\Finder;
use rss\orm\Model;
use rss\orm\Schema;

require __DIR__.'/vendor/autoload.php';
require __DIR__.'/config.php';

$connection = new Connection( $config['db_driver'], $config['db_host'], 
	$config['db_name'], $config['db_user'], $config['db_pass']);
$schema = new Schema($connection);
$mapper = new Mapper($connection, $schema);
$categoriesFinder = new Finder($connection, $schema, 'rss\models\Category');
$channelsFinder = new Finder($connection, $schema, 'rss\models\Channel');
$itemsFinder = new Finder($connection, $schema, 'rss\models\Item');