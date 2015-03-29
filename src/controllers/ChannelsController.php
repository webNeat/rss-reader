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
use rss\models\Category;
use rss\worker\fetchers\Fetcher;

class ChannelsController extends Controller implements ControllerProviderInterface {
	protected $finder;
	protected $categoriesFinder;
	public function __construct(Application $app, FormatNegotiator $fn, Mapper $mapper, Finder $channelsFinder, Finder $categoriesFinder){
		parent::__construct($app, $fn, $mapper);
		$this->finder = $channelsFinder;
		$this->categoriesFinder = $categoriesFinder;
	}

	public function connect( Application $app ){
		$c = $app['controllers_factory'];
		// REST API (return html or json depending on the request)
		$c->get('/', [ $this, 'index' ]);
		$c->post('/', [ $this, 'add' ]);
		$c->get('/{id}', [ $this, 'show' ]);
		// $c->put('/{id}', [ $this, 'update' ]); 
		// channels are updated by the worker
		$c->delete('/{id}', [ $this, 'delete' ]);
		$c->get('/{id}/items', [ $this, 'items' ]);
		$c->get('/{id}/items/all', [ $this, 'allItems' ]);
		// Additional Routes (for showing forms, only HTML)
		$c->get('/create', [ $this, 'create' ]);
		
		return $c;
	}

	public function index(Request $request){
		$channels = $this->finder->get();
		return $this->choose( $request, 
			$this->render('channels/index.twig', [
				'channels' => $channels
			]), 
			$this->app->json($channels)
		);
	}

	public function create(){
		$categories = $this->categoriesFinder->get();
		return $this->render('channels/create.twig', [
			'categories' => $categories
		]);
	}

	public function add(Request $request){
		$htmlResponse = null;
		$jsonResponse = [
			'done' => false,
			'errors' => null,
			'result' => null
		];
		if($request->get('link')){
			$channel = new Channel;
			$channel->feedLink = $this->app->escape($request->get('link'));
			$errors = $this->app['validator']->validate($channel);
			if(count($errors) > 0){
				$this->addFlash('errors', $errors);
				$htmlResponse = $this->app->redirect('/channels/create');
				$jsonResponse['errors'] = $errors;
			} else {
				$exists = $this->finder
					->where('feed_link','=',$channel->feedLink)
					->get(1);
				if(!is_null($exists)){
					$this->addFlash('errors', 'This channel already exists !');
					$jsonResponse['errors'] = ['This channel already exists !'];
					$htmlResponse = $this->app->redirect('/channels/create');
				} else {
					try {
						$channel->title = 'Pending ...';
						$channel->description = 'Please wait, the details of this channel will be fetched soon';
						$channel->category = $this->categoriesFinder->getById($this->app->escape($request->get('category')));
						$this->mapper->persist($channel);
						exec('bash -c "exec nohup setsid '.__DIR__.'/../../worker fetch-all >> '.__DIR__.'/../../logs/commands.log 2>&1 &"');
						$this->addFlash('success', 'Channel added successfuly !');
						$jsonResponse['done'] = true;
						$jsonResponse['result'] = $channel;
						$htmlResponse = $this->app->redirect('/channels');
					} catch ( \Exception $e ){
						$this->app['monolog']->addWarning($e->getMessage());
						$this->addFlash('errors', 'Error while persisting the channel !');
						$jsonResponse['errors'] = ['Error while persisting the channel !'];
						$htmlResponse = $this->app->redirect('/channels/create');
					}
				}
			}
		} else {
			$this->addFlash('errors', 'Please fill the form first !');
			$jsonResponse['errors'] = ['Some required data was not sent !'];
			$htmlResponse = $this->app->redirect('/channels/create');
		}
		return $this->choose($request, $htmlResponse, $jsonResponse);
	}

	public function show(Request $request, $id){
		if( 'create' == $id )
			return $this->create();
		$htmlResponse = null;
		$jsonResponse = [
			'done' => false,
			'errors' => null,
			'result' => null
		];
		$channel = $this->finder->getById($id);
		if(is_null($channel)){
			$htmlResponse = $this->app->redirect('/not-found');
			$jsonResponse['errors'] = ['Channel not found !'];
		} else {
			$jsonResponse['done'] = true;
			$jsonResponse['result'] = $channel;
			$htmlResponse = $this->app->redirect('/channels/{$id}/items');
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
		$channel = $this->finder->getById($id);
		if(is_null($channel)){
			$this->addFlash('infos', 'The channel does not exist !');
			$jsonResponse['errors'] = ['The channel does not exist !'];
		} else {
			try {
				$this->mapper->remove($channel);
				$this->addFlash('success', 'The channel was removed');
				$jsonResponse['done'] = true;
			} catch (\Exception $e){
				$this->addFlash('errors', 'Error happened while removing the channel !');
				$jsonResponse['errors'] = ['Error happened while removing the channel !'];
			}
		}
		$subRequest = Request::create('/channels', 'GET');
		$htmlResponse = $this->index($subRequest);
		return $this->choose($request, $htmlResponse, $jsonResponse);
	}

	public function items(Request $request, $id){
		$htmlResponse = null;
		$jsonResponse = [
			'done' => false,
			'errors' => null,
			'result' => null
		];
		$channel = $this->finder->setRecursions(2)->getById($id);
		if(is_null($channel)){
			$htmlResponse = $this->app->redirect('/not-found');
			$jsonResponse['errors'] = ['The channel does not exist !'];
		} else {
			$htmlResponse = $this->render('items/index.twig', [
				'channel' => $channel,
				'items' => $channel->newItems()
			]);
			$jsonResponse['done'] = true;
			$jsonResponse['result'] = $channel->newItems();
		}
		return $this->choose($request, $htmlResponse, $jsonResponse);	
	}

	public function allItems(Request $request, $id){
		$htmlResponse = null;
		$jsonResponse = [
			'done' => false,
			'errors' => null,
			'result' => null
		];
		$channel = $this->finder->setRecursions(2)->getById($id);
		if(is_null($channel)){
			$htmlResponse = $this->app->redirect('/not-found');
			$jsonResponse['errors'] = ['The channel does not exist !'];
		} else {
			$htmlResponse = $this->render('items/index.twig', [
				'channel' => $channel,
				'items' => $channel->items
			]);
			$jsonResponse['done'] = true;
			$jsonResponse['result'] = $channel->items;
		}
		return $this->choose($request, $htmlResponse, $jsonResponse);	
	}

}