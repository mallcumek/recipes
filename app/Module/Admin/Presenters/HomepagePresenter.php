<?php

declare(strict_types=1);

namespace App\Module\Admin\Presenters;

use Nette;
use App\Model\PostFacade;
use Nette\Application\UI\Form;

/**
 * Presenter for the dashboard view.
 * Ensures the user is logged in before access.
 */
final class HomepagePresenter extends Nette\Application\UI\Presenter
{
    // Incorporates methods to check user login status
    // use RequireLoggedUser;
    // Vypnul jsem v HomepagePresenteru potřebu přihlášení, to dám do administrace

    // Pripojeni k mysql přes fasádu PostFacade
    public function __construct(
        private PostFacade $facade,
    )
    {
    }
    // V sekci use máme App\Model\PostFacade, tak si můžeme zápis v PHP kódu zkrátit na PostFacade.
    // O tento objekt požádáme v konstruktoru, zapíšeme jej do vlastnosti $facade a použijeme v metodě renderDefault.

    public function beforeRender(): void
    {
        // Nacteni kategorii do sablony
        $this->template->categories = $this->facade
            ->getCategories()
            ->limit(50);
        $this->template->context = null; // Výchozí hodnota pro context

    }
    // Nyní načteme příspěvky z databáze a pošleme je do šablony, která je následně vykreslí jako HTML kód.
    // Pro tohle je určena takzvaná render metoda:
    public function renderDefault(): void
    {
        $this->template->posts = $this->facade
            ->getPublicArticles()
            ->limit(50);
    }
}
