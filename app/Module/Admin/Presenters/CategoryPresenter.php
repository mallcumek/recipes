<?php

declare(strict_types=1);

namespace App\Module\Admin\Presenters;

use Nette;
use App\Model\PostFacade;
use Nette\Application\UI\Form;

final class CategoryPresenter extends Nette\Application\UI\Presenter
{
    // Konstruktor, který vyžaduje dazabázové spojení
    public function __construct(
        private PostFacade $facade,
    )
    {
        $this->facade = $facade;
    }

    public function beforeRender(): void
    {
        // Záchrana od Chatgpt - parent beforeRender udrží zobrazení loginu i po klikání v rámci webu
        // (opakované kliknutí na stejnou stránku nezobrazilo přihlášeného uživatele, až po F5 - obnovení stránky)
        // ((Takže byl pořád přihlášený, ale v šabloně se to nevykreslilo))
        parent::beforeRender();
        if (!$this->getUser()->isLoggedIn()) {
            // Tady můžete buď přesměrovat nebo jen zobrazit zprávu
            $this->flashMessage('You can upload own recipe or cooking photos after Login.');
            // $this->redirect('Sign:in');
        }

        // Nacteni kategorii do sablony
        $this->template->categories = $this->facade
            ->getCategories()
            ->limit(50);
        // Získání absolutní URL aktuální stránky
        $this->template->canonicalUrl = $this->getHttpRequest()->getUrl()->getAbsoluteUrl();
    }

    public function renderShow(?string $category_seotitle): void
    {   // Ulozim do sablony info o aktualnim renderu
        $this->template->context = 'category';
        // Nacteni kategorie do sablony
        $category = $this->facade->getCategoryBySeoTitle($category_seotitle);
        $this->template->category = $category;

        // Nacteni subkategorii do sablony
        $subcategories = $this->facade->getSubCategories($category_seotitle);
        $this->template->subcategories = $subcategories;

        /*/ původní načteni postů do sablony z tutoriálu
        $this->template->posts = $this->facade
            ->getPublicArticlesByCategory($category_seotitle)
            ->limit(50);
        */
        $posts = $this->facade->getPublicArticlesByCategory($category_seotitle)->limit(50);
        $this->template->posts = $posts;

        // Získání unikátních SEO titulů subkategorií z příspěvků
        $seoTitles = [];
        foreach ($posts as $post) {
            if ($post->subcategory_seotitle && !in_array($post->subcategory_seotitle, $seoTitles)) {
                $seoTitles[] = $post->subcategory_seotitle;
            }
        }

        // Získání názvů subkategorií
        $subcategoryTitles = $this->facade->getSubCategoryTitles($seoTitles);
        $this->template->subcategoryTitles = $subcategoryTitles;
    }

    public function renderSubcategory(?string $subcategory_seotitle, ?string $category_seotitle): void
    {   // Ulozim do sablony info o aktualnim renderu, pro podminky v @layout.latte
        $this->template->context = 'subcategory';
        // Nacteni subkategorii do sablony
        $subcategories = $this->facade->getSubCategories($category_seotitle);
        $this->template->subcategories = $subcategories;

        // Nacteni postů podle podkategorie do sablony
        $posts = $this->facade->getPublicArticlesBySubcategory($subcategory_seotitle);
        $this->template->posts = $posts;

        // Nacteni subkategorie do sablony
        $subcategory = $this->facade->getSubCategoryBySubcategorySeoTitle($subcategory_seotitle);
        $this->template->subcategory = $subcategory;

        // Získání unikátních SEO titulů subkategorií z příspěvků
        $seoTitles = [];
        foreach ($posts as $post) {
            if ($post->subcategory_seotitle && !in_array($post->subcategory_seotitle, $seoTitles)) {
                $seoTitles[] = $post->subcategory_seotitle;
            }
        }

        // Získání názvů subkategorií
        $subcategoryTitles = $this->facade->getSubCategoryTitles($seoTitles);
        $this->template->subcategoryTitles = $subcategoryTitles;


        // Zde přidáme získání informací o kategorii
        if ($category_seotitle !== null) {
            $category = $this->facade->getCategoryBySeoTitle($category_seotitle);
            $this->template->category = $category; // Předání kategorie do šablony
        } else {
            // Můžete zde přidat zpracování chyby, pokud je to potřeba
            $this->template->category = null;
        }

    }
}
