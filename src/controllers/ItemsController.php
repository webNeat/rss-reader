<?php
namespace rss\controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Negotiation\FormatNegotiator;
use Symfony\Component\HttpFoundation\Request;

use rss\orm\Mapper;
use rss\orm\Finder;
use rss\models\Category;
use rss\models\Channel;
use rss\models\Item;

class ItemsController extends Controller implements ControllerProviderInterface {
	protected $finder;
	public function __construct(Application $app, FormatNegotiator $fn, Mapper $mapper, Finder $itemsFinder){
		parent::__construct($app, $fn, $mapper);
		$this->finder = $itemsFinder;
	}

	public function connect( Application $app ){
		$c = $app['controllers_factory'];
		$c->get('/', [ $this, 'index' ]);
		$c->post('/', [ $this, 'add' ]);
		$c->get('/{id}', [ $this, 'show' ]);
		$c->put('/{id}', [ $this, 'update' ]);
		$c->delete('/{id}', [ $this, 'delete' ]);

		$c->get('/{id}/channel', [ $this, 'channel' ]);

		return $c;
	}

	public function index(Request $request){
	
	}

	public function add(Request $request){

	}

	public function show(Request $request, $id){

	}

	public function update(Request $request, $id){

	}

	public function delete(Request $request, $id){

	}

	public function channel(Request $request, $id){

	}

}