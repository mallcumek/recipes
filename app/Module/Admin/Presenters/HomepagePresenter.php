<?php

declare(strict_types=1);

namespace App\Module\Admin\Presenters;

use Nette;
use App\Model\PostFacade;
use Nette\Application\UI\Form;
use Nette\Utils\Json;

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
    public function renderDefault(int $page = 1): void
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

               // Inicializace pole pro uložení dat o kaloriích z jednotlivých příspěvků.
        $caloriesData = [];


        // Funkce na vytazeni poctu kalorii ze sloupce nutrition_facts.
        // Projití každého příspěvku a extrakce kalorií z textového řetězce v poli nutrition_facts.
        foreach ($posts as $post) {
            if (preg_match('~Calories:\s*(\d+)~', $post->nutrition_facts, $matches)) {
                // Pokud regex najde odpovídající kalorie, přiřadíme je do pole pod klíčem odpovídajícím ID příspěvku.
                $caloriesData[$post->id] = $matches[1];
            } else {
                // Pokud nejsou kalorie specifikovány, nastavíme hodnotu na 'N/A'.
                $caloriesData[$post->id] = 'N/A';
            }
        }

        // Předání načtených příspěvků a dat o kaloriích do šablony.
        $this->template->posts = $posts;
        $this->template->caloriesData = $caloriesData;


        //****** Pagination část kódu kde manuálu, akorát používám fasádu********
        //***********************************************************************
        // Zjistíme si celkový počet publikovaných článků
        $articlesCount = $this->facade->getPublishedArticlesCount();

        // Vyrobíme si instanci Paginatoru a nastavíme jej
        $paginator = new Nette\Utils\Paginator;
        $paginator->setItemCount($articlesCount); // celkový počet článků
        $paginator->setItemsPerPage(32); // počet položek na stránce
        $paginator->setPage($page); // číslo aktuální stránky

        // Z databáze si vytáhneme omezenou množinu článků podle výpočtu Paginatoru
        $articles = $this->facade->findPublishedArticles($paginator->getLength(), $paginator->getOffset());

        // kterou předáme do šablony
        $this->template->articles = $articles;
        // a také samotný Paginator pro zobrazení možností stránkování
        $this->template->paginator = $paginator;



        // Načtení hodnocení pro každý článek a předání do šablony   *chatgpt
        $ratingsByPost = [];
        foreach ($posts as $post) {
            $ratings = $this->facade->getRatingsForPost($post->id);
            $ratingsByPost[$post->id] = $ratings;

            // Calculate the sum and average of the ratings
            $sum = 0;
            foreach ($ratings as $rating) {
                $sum += $rating;  // Here, $rating is an integer
            }

            $count = count($ratings);  // Count the number of ratings for this post
            $average = count($ratings) > 0 ? $sum / count($ratings) : 0;
            $roundedAverage = round($average, 1);  // Round to one decimal place

            // Store the average and sum for use in the template
            $ratingsByPost[$post->id]['average'] = $roundedAverage;
            $ratingsByPost[$post->id]['sum'] = $sum;
            $ratingsByPost[$post->id]['count'] = $count;  // Store the count of ratings

        }

        $this->template->ratingsByPost = $ratingsByPost;

    }



}
