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
        $router->addRoute('/signout', 'Admin:Sign:out');
        $router->addRoute('/admin', 'Admin:Dashboard:default');
        $router->addRoute('/create', 'Admin:Dashboard:create');
        $router->addRoute('/edit', 'Admin:Dashboard:edit');
        // funguje na hezke url u postu
        $router->addRoute('/recipe/<postId>-<seotitle>', 'Admin:Post:show');


        // Tato routa na kategorie zmenila automaticky odkazy v @layout.latte n:href="Category:show $cat->category_seotitle"
        // Pokud u routy <subcategory_seotitle> neni "cesta navíc" např /cat/<subcategory_seotitle>, tak nefunguje admin.dashboard
        // = pise chybu promenny " "category_title" on null" v sablone Category/show.latte. Pritom si má zobrazovat Dashboard/default.latte
        $router->addRoute('/<category_seotitle>/', 'Admin:Category:show');
        $router->addRoute('/<category_seotitle>/<subcategory_seotitle>/', 'Admin:Category:subcategory');

        // Default route that maps to the Admin Dashboard
        $router->addRoute('<presenter>/<action>', 'Admin:Homepage:default');
		// Default route : $router->addRoute('<presenter>/<action>', 'Admin:Dashboard:default');


		return $router;
	}
}
