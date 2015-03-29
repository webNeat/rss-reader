<?php
namespace rss\controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Negotiation\FormatNegotiator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use rss\orm\Mapper;
use rss\orm\Finder;
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
		// REST API (return html or json depending on the request)
		$c->get('/', [ $this, 'index' ]);
		$c->get('/all', [ $this, 'all' ]);
		// $c->post('/', [ $this, 'add' ]);
		// items are added by the worker
		$c->put('/{id}', [ $this, 'update' ]); 
		$c->delete('/{id}', [ $this, 'delete' ]);
		
		return $c;
	}

	public function index(Request $request){
		$items = $this->finder
			->where('viewed', '=', false)
			->get();
		$this->app['monolog']->addDebug(var_export($items, true));
		return $this->choose( $request, 
			$this->render('items/index.twig', [
				'items' => $items
			]), 
			$this->app->json($items)
		);
	}

	public function all(Request $request){
		$items = $this->finder->get();
		return $this->choose( $request, 
			$this->render('items/index.twig', [
				'items' => $items
			]), 
			$this->app->json($items)
		);
	}

	public function update(Request $request, $id){
		$htmlResponse = null;
		$jsonResponse = [
			'done' => false,
			'errors' => null,
			'result' => null
		];
		$item = $this->finder->getById($id);
		if(is_null($item)){
			$htmlResponse = $this->render('static/not-found.twig');
			$jsonResponse['errors'] = ['Category not found !'];
		} else {
			if($request->get('viewed')){
				$item->viewed = $request->get('viewed');
				try {
					$this->mapper->persist($item);
					$jsonResponse['done'] = true;
					$jsonResponse['result'] = $item;
				} catch ( \Exception $e ){
					$this->app['monolog']->addWarning($e->getMessage());
					$this->addFlash('errors', 'Error while persisting the item !');
					$jsonResponse['errors'] = ['Error while persisting the item !'];
				}
			} else {
				$jsonResponse['errors'] = ['Please fill the form first !'];
			}
			$htmlResponse = 'Item Updated !';
		}
		return $this->choose($request, $htmlResponse, $jsonResponse); 
	}

	public function delete(Request $request, $id){
		$htmlResponse = null;
		$jsonResponse = [
			'done' => false,
			'errors' => null,
			'result' => null
		];
		$this->setLayout('layouts/partial.twig');
		$item = $this->finder->getById($id);
		if(is_null($item)){
			$this->addFlash('infos', 'The item does not exist !');
			$jsonResponse['errors'] = ['The item does not exist !'];
		} else {
			try {
				$this->mapper->remove($item);
				$this->addFlash('success', 'The item was removed');
				$jsonResponse['done'] = true;
			} catch (\Exception $e){
				$this->addFlash('errors', 'Error happened while removing the item !');
				$jsonResponse['errors'] = ['Error happened while removing the item !'];
			}
		}
		$url = $this->app->escape($request->get('url'));
		$subRequest = Request::create($url, 'GET');
		$htmlResponse = $this->app->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);
		return $this->choose($request, $htmlResponse, $jsonResponse);
	}

}