<?php

declare(strict_types=1);

namespace App\Router;

use Nette;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
	use Nette\StaticClass;

	/**
	 * Creates the main application router with defined routes.
	 */
	public static function createRouter(): RouteList
	{
		$router = new RouteList;
        $router->addRoute('/login', 'Admin:Sign:in');
        $router->addRoute('/registration', 'Admin:Sign:up');
        // funguje na hezke url u postu
        $router->addRoute('/recipe/<postId>-<seotitle>', 'Admin:Post:show');
        // Tato routa na kategore zmenila automaticky odkazy v @layout.latte n:href="Category:show $cat->category_seotitle"
        $router->addRoute('/<category_seotitle>/', 'Admin:Category:show');
		// Default route that maps to the Admin Dashboard
        $router->addRoute('<presenter>/<action>', 'Admin:Homepage:default');
		// Default route : $router->addRoute('<presenter>/<action>', 'Admin:Dashboard:default');


		return $router;
	}
}
