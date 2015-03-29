<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

use Silex\Application;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Negotiation\FormatNegotiator;
use Symfony\Component\HttpFoundation\Request;

use rss\controllers\CategoriesController;
use rss\controllers\ChannelsController;
use rss\controllers\ItemsController;

require __DIR__ . '/../bootstrap.php';

$app = new Application;

$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new TwigServiceProvider(), [
	'twig.path'	=> [__DIR__ . '/../views']
]);
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../logs/web.log',
));

$formatNegotiator = new FormatNegotiator;

$app->mount( '/categories', new CategoriesController($app, $formatNegotiator, $mapper, $categoriesFinder) );
$app->mount( '/channels', new ChannelsController($app, $formatNegotiator, $mapper, $channelsFinder, $categoriesFinder) );
$app->mount( '/items', new ItemsController($app, $formatNegotiator, $mapper, $itemsFinder) );
$app->get('/', function () use ($app) {
    return $app->redirect('/items');
});
$app->get('/not-found', function() use ($app) {
	return $app['twig']->render('static/not-found.twig');
});
$app->error(function (\Exception $e, $code) {
    return 'We are sorry, but something went terribly wrong : ( ' . $e->getMessage() . ')';
});

$app->before(function (Request $request) {
	// If parameters are sent as JSON, replacing the request data
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
    }
});

$app->run();