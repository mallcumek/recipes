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

        // Pridanim routy /<category_seotitle>/ prestal fungovat admin.dashboard viz. níže. Opraveno pridanim routy /admin

		$router = new RouteList;
        $router->addRoute('/login', 'Admin:Sign:in');
        $router->addRoute('/registration', 'Admin:Sign:up');
        $router->addRoute('/admin', 'Admin:Dashboard:default');
        // funguje na hezke url u postu
        $router->addRoute('/recipe/<postId>-<seotitle>', 'Admin:Post:show');

        // Tato routa na kategore zmenila automaticky odkazy v @layout.latte n:href="Category:show $cat->category_seotitle"
        // pokud tam neni "cesta navíc" např /recipes/, tak nefunguje admin.dashboard
        // = pise chybu promenny " "category_title" on null" v sablone Category/show.latte. Pritom si má zobrazovat Dashboard/default.latte
        $router->addRoute('/<category_seotitle>/', 'Admin:Category:show');
        $router->addRoute('/cat/<subcategory_seotitle>/', 'Admin:Category:subcategory');
		// Default route that maps to the Admin Dashboard
        $router->addRoute('<presenter>/<action>', 'Admin:Homepage:default');
		// Default route : $router->addRoute('<presenter>/<action>', 'Admin:Dashboard:default');


		return $router;
	}
}
