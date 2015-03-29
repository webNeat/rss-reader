<?php
namespace rss\controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Negotiation\FormatNegotiator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use rss\orm\Mapper;
use rss\orm\Finder;
use rss\models\Category;
use rss\models\Channel;

class CategoriesController extends Controller implements ControllerProviderInterface {
	protected $finder;
	public function __construct(Application $app, FormatNegotiator $fn, Mapper $mapper, Finder $categoriesFinder){
		parent::__construct($app, $fn, $mapper);
		$this->finder = $categoriesFinder;
	}

	public function connect( Application $app ){
		$c = $app['controllers_factory'];
		// REST API (return html or json depending on the request)
		$c->get('/', [ $this, 'index' ]);
		$c->post('/', [ $this, 'add' ]);
		$c->get('/{id}', [ $this, 'show' ]);
		$c->put('/{id}', [ $this, 'update' ]);
		$c->delete('/{id}', [ $this, 'delete' ]);
		$c->get('/{id}/channels', [ $this, 'channels' ]);
		$c->get('/{id}/items', [ $this, 'items' ]);
		// Additional Routes (for showing forms, only HTML)
		$c->get('/create', [ $this, 'create' ]);
		$c->get('/{id}/edit', [ $this, 'edit' ]);

		return $c;
	}

	public function index(Request $request){
		$response = null;
		$categories = $this->finder->get();
		return $this->choose( $request, 
			$this->render('categories/index.twig', [
				'categories' => $categories
			]), 
			$this->app->json($categories)
		);
	}

	public function create(){
		return $this->render('categories/create.twig');
	}

	public function add(Request $request){
		$htmlResponse = null;
		$jsonResponse = [
			'done' => false,
			'errors' => null,
			'result' => null
		];
		if($request->get('name')){
			$category = new Category;
			$category->name = $this->app->escape($request->get('name'));
			$category->description = $this->app->escape($request->get('description'));
			$errors = $this->app['validator']->validate($category);
			if(count($errors) > 0){
				$this->addFlash('errors', $errors);
				$htmlResponse = $this->app->redirect('/categories/create');
				$jsonResponse['errors'] = $errors;
			} else {
				try {
					$this->mapper->persist($category);
					$this->addFlash('success', 'Category added successfuly !');
					$jsonResponse['done'] = true;
					$jsonResponse['result'] = $category;
					$htmlResponse = $this->app->redirect('/categories');
				} catch ( \Exception $e ){
					$this->app['monolog']->addWarning($e->getMessage());
					$this->addFlash('errors', 'Error while persisting the category !');
					$jsonResponse['errors'] = ['Error while persisting the category !'];
					$htmlResponse = $this->app->redirect('/categories/create');
				}
			}
		} else {
			$this->addFlash('errors', 'Please fill the form first !');
			$jsonResponse['errors'] = ['Some required data was not sent !'];
			$htmlResponse = $this->app->redirect('/categories/create');
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
		$category = $this->finder->getById($id);
		if(is_null($category)){
			$htmlResponse = $this->app->redirect('/not-found');
			$jsonResponse['errors'] = ['Category not found !'];
		} else {
			$jsonResponse['done'] = true;
			$jsonResponse['result'] = $category;
			$htmlResponse = $this->app->redirect('/categories/{$id}/channels');
		}
		return $this->choose($request, $htmlResponse, $jsonResponse);
	}

	public function edit($id){
		$category = $this->finder->getById($id);
		if(is_null($category))
			return $this->app->redirect('/not-found');
		return $this->render('categories/edit.twig', [
			'category' => $category
		]);
	}

	public function update(Request $request, $id){
		$htmlResponse = null;
		$jsonResponse = [
			'done' => false,
			'errors' => null,
			'result' => null
		];
		$this->setLayout('layouts/partial.twig');
		$category = $this->finder->getById($id);
		if(is_null($category)){
			$htmlResponse = $this->render('static/not-found.twig');
			$jsonResponse['errors'] = ['Category not found !'];
		} else {
			if($request->get('name')){
				$category->name = $this->app->escape($request->get('name'));
				$category->description = $this->app->escape($request->get('description'));
				$errors = $this->app['validator']->validate($category);
				if(count($errors) > 0){
					$this->addFlash('errors', $errors);
					$jsonResponse['errors'] = $errors;
				} else {
					try {
						$this->mapper->persist($category);
						$this->addFlash('success', 'Category saved successfuly !');
						$jsonResponse['done'] = true;
						$jsonResponse['result'] = $category;
					} catch ( \Exception $e ){
						$this->app['monolog']->addWarning($e->getMessage());
						$this->addFlash('errors', 'Error while persisting the category !');
						$jsonResponse['errors'] = ['Error while persisting the category !'];
					}
				}
			} else {
				$this->addFlash('errors', 'Please fill the form first !');
				$jsonResponse['errors'] = ['Please fill the form first !'];
			}
			$htmlResponse = $this->render('categories/edit.twig', [
				'category' => $category
			]);
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
		$category = $this->finder->getById($id);
		if(is_null($category)){
			$this->addFlash('infos', 'The category does not exist !');
			$jsonResponse['errors'] = ['The category does not exist !'];
		} else {
			try {
				$this->mapper->remove($category);
				$this->addFlash('success', 'The category was removed ');
				$jsonResponse['done'] = true;
			} catch (\Exception $e){
				$this->addFlash('errors', 'Error happened while removing the category !');
				$jsonResponse['errors'] = ['Error happened while removing the category !'];
			}
		}
		$subRequest = Request::create('/categories', 'GET');
		$htmlResponse = $this->index($subRequest);
		return $this->choose($request, $htmlResponse, $jsonResponse);
	}

	public function channels(Request $request, $id){
		$htmlResponse = null;
		$jsonResponse = [
			'done' => false,
			'errors' => null,
			'result' => null
		];
		$category = $this->finder->getById($id);
		if(is_null($category)){
			$htmlResponse = $this->app->redirect('/not-found');
			$jsonResponse['errors'] = ['The category does not exist !'];
		} else {
			$htmlResponse = $this->render('channels/index.twig', [
				'category' => $category,
				'channels' => $category->channels
			]);
			$jsonResponse['done'] = true;
			$jsonResponse['result'] = $category->channels;
		}
		return $this->choose($request, $htmlResponse, $jsonResponse);
	}

	public function items(Request $request, $id){
		$htmlResponse = null;
		$jsonResponse = [
			'done' => false,
			'errors' => null,
			'result' => null
		];
		$category = $this->finder->setRecursions(2)->getById($id);
		if(is_null($category)){
			$htmlResponse = $this->app->redirect('/not-found');
			$jsonResponse['errors'] = ['The category does not exist !'];
		} else {
			$items = [];
			foreach( $category->channels as $c ){
				$items = array_merge($items, $c->items);
			}
			$htmlResponse = $this->render('items/index.twig', [
				'category' => $category,
				'items' => $items
			]);
			$jsonResponse['done'] = true;
			$jsonResponse['result'] = $items;
		}
		return $this->choose($request, $htmlResponse, $jsonResponse);	
	}

}