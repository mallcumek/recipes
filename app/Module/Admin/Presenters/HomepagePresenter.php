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
        // Získání absolutní URL aktuální stránky
        $this->template->canonicalUrl = $this->getHttpRequest()->getUrl()->getAbsoluteUrl();
    }
    // Nyní načteme příspěvky z databáze a pošleme je do šablony, která je následně vykreslí jako HTML kód.
    // Pro tohle je určena takzvaná render metoda:
    public function renderDefault(): void
    {
         /* puvodni kod z manualu na vypis clanku na homepage

           $this->template->posts = $this->facade
          ->getPublicArticles()
          ->limit(50);
*/
        // Chatgpt kod spojeny s PostFacade a funkcí getSubCategoryTitles pro výpis názvu subkategorií
        $posts = $this->facade->getPublicArticles()->limit(50);
        $this->template->posts = $posts;

        // Získání unikátních SEO titulů subkategorií z příspěvků
        $seoTitles = [];
        foreach ($posts as $post) {
            if (!in_array($post->subcategory_seotitle, $seoTitles)) {
                $seoTitles[] = $post->subcategory_seotitle;
            }
        }

        // Získání názvů subkategorií a předání do šablony
        $subcategoryTitles = $this->facade->getSubCategoryTitles($seoTitles);
        $this->template->subcategoryTitles = $subcategoryTitles;
    }
}
