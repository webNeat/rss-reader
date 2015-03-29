<?php
namespace rss\models;

use rss\orm\Model;

class Item extends Model {
	// Relationships
	public $channel; // belongs to a channel
	// Own attributes
	public $title;
	public $link;
	public $content;
	public $pubDate;
	public $author;
	public $viewed = false;
}