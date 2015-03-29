<?php
namespace rss\controllers;

use Silex\Application;
use Negotiation\FormatNegotiator;
use Symfony\Component\HttpFoundation\Request;
use rss\orm\Mapper;
use rss\exceptions\UnsupportedAcceptFormat;

class Controller {
	protected $app;
	protected $mapper;
	protected $formatNegotiator;
	protected $messages;

	public function __construct(Application $app, FormatNegotiator $fn, Mapper $mapper){
		$this->app = $app;
		$this->mapper = $mapper;
		$this->formatNegotiator = $fn;
		$this->messages = [
			'errors' => [],
			'success' => [],
			'infos' => []
		];
	}

	protected function requestedFormat(Request $request){
		$format = $this->formatNegotiator->getBest($request->headers->get("Accept"));
		if(! in_array($format->getValue(), ['text/html', 'application/json', '*/*']) )
			throw new UnsupportedAcceptFormat($format->getValue());
		return $format->getValue();
	}

	protected function fillMessages(){
		if($this->app['session']->has('errors')){
			$this->messages['errors'] = $this->app['session']->get('errors');
			$this->app['session']->remove('errors');
		}
		if($this->app['session']->has('infos')){
			$this->messages['infos'] = $this->app['session']->get('infos');
			$this->app['session']->remove('infos');
		}
		if($this->app['session']->has('success')){
			$this->messages['success'] = $this->app['session']->get('success');
			$this->app['session']->remove('success');
		}
	}

	protected function render($template, $args = []){
		$this->fillMessages();

		$args = array_merge($args, [
			'alerts' => $this->messages,
			'layout' => $this->app['session']->get('layout', 'layouts/main.twig')
		]);
		$this->app['session']->remove('layout');
		return $this->app['twig']->render($template, $args);
	}

	protected function addFlash($tag, $msg){
		if(! $this->app['session']->has($tag))
			$this->app['session']->set($tag, []);
		if(! is_array($msg))
			$msg = [ $msg ];
		$this->app['session']->set($tag, array_merge(
			$this->app['session']->get($tag), $msg
		));
	}

	protected function setLayout($layout){
		$this->app['session']->set('layout', $layout);
	}

	protected function choose(Request $request, $html, $json){
		switch($this->requestedFormat($request)){
			case 'text/html':
			case '*/*':
				return $html;
			break;
			case 'application/json':
				return $this->app->json($json);
			break;
		}
	}
}