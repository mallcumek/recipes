<?php

namespace App\Model;

use Nette;

final class PostFacade
{
    public function __construct(
        private Nette\Database\Explorer $database,
    )
    {
    }

    // V adresáři app/Model/ vytvoříme naši modelovou třídu PostFacade, která se nám bude starat o články:
    public function getPublicArticles()
    {
        return $this->database
            ->table('posts')
            ->where('created_at < ', new \DateTime)
            ->order('created_at DESC');
    }
    // Ve třídě si pomocí konstruktoru necháme předat databázový Explorer. Využijeme tak síly DI containeru.

    // Třída PostFacade si v konstruktoru řekne o předání Nette\Database\Explorer a jelikož je tato třída v DI containeru zaregistrovaná, kontejner tuto instanci vytvoří a předá ji.
    // DI za nás takto vytvoří instanci PostFacade a předá ji v konstruktoru třídě HomePresenter, který si o něj požádal. Taková matrjoška. :)
    // Všichni si jen říkají co chtějí a nezajímají se o to, kde se co a jak vytváří. O vytvoření se stará DI container.

    //
    public function findPublishedArticles(int $limit, int $offset): Nette\Database\ResultSet
    {
        return $this->database->query('
			SELECT * FROM posts
			WHERE created_at < ?
			ORDER BY created_at DESC
			LIMIT ?
			OFFSET ?',
            new \DateTime, $limit, $offset,
        );
    }

    /**
     * Vrací celkový počet publikovaných článků
     */
    public function getPublishedArticlesCount(): int
    {
        return $this->database->fetchField('SELECT COUNT(*) FROM posts WHERE created_at < ?', new \DateTime);
    }




    public function getCategories()
    {
        return $this->database
            ->table('category')
            ->order('category_title ASC'); // ASC pro vzestupné řazení, DESC pro sestupné
    }

    public function getAllSubCategories()
    {
        return $this->database
            ->table('subcategory')
            ->order('subcategory_title ASC'); // ASC pro vzestupné řazení, DESC pro sestupné
    }

    public function getCategoryBySeoTitle(string $seoTitle)
    {
        return $this->database
            ->table('category')
            ->where('category_seotitle', $seoTitle)
            ->fetch();
    }

    public function getSubCategoryBySubcategorySeoTitle(string $subcategory_seotitle)
    {
        return $this->database
            ->table('subcategory')
            ->where('subcategory_seotitle', $subcategory_seotitle)
            ->fetch();
    }


    public function getPublicArticlesByCategory(string $categorySeoTitle)
    {
        return $this->database
            ->table('posts')
            ->where('category_seotitle', $categorySeoTitle)
            ->where('created_at < ', new \DateTime)
            ->order('created_at DESC');
    }
    /* Puvodni kod, pred pridanim nacitani vice subkategorii

    public function getPublicArticlesBySubcategory(string $subcategorySeoTitle)
    {
        return $this->database
            ->table('posts')
            ->where('subcategory_seotitle', $subcategorySeoTitle)
            ->where('created_at < ', new \DateTime)
            ->order('created_at DESC');
    }*/

    /* V tomto řešení jsou obě podmínky pro subcategory_seotitle a subcategory_seotitle2 kombinovány do jednoho řetězce s použitím operátoru OR.
       Zároveň je nutné použít zástupné symboly ? pro vložení proměnných do dotazu, což je standardní praxe pro předcházení SQL injekcím.
       Toto by mělo řešit problém s typem argumentu a umožnit filtrování článků podle obou subkategorií.*/
    public function getPublicArticlesBySubcategory(string $subcategorySeoTitle)
    {
        return $this->database
            ->table('posts')
            ->where('created_at < ?', new \DateTime())
            ->where('subcategory_seotitle = ? OR subcategory_seotitle2 = ? OR subcategory_seotitle3 = ?', $subcategorySeoTitle, $subcategorySeoTitle, $subcategorySeoTitle)
            ->order('created_at DESC');
    }


    public function getSubCategories(string $seoTitle)
    {
        return $this->database
            ->table('subcategory')
            ->where('category_seotitle', $seoTitle)
            ->order('subcategory_title ASC'); // ASC pro vzestupné řazení, DESC pro sestupné
    }

    // ChatGPT : Tato metoda vezme pole SEO titulů subkategorií a vrátí páry seoTitle => title.
    // Funkce fetchPairs je užitečná, pokud chcete výsledek přímo jako asociativní pole.
    public function getSubCategoryTitles(array $seoTitles)
    {
        // Přidání kontrolního filtru, aby se zabránilo zahrnutí prázdných nebo neplatných seoTitles //? nechápu uplně
        $seoTitles = array_filter($seoTitles);
        if (empty($seoTitles)) {
            return [];
        }
        return $this->database
            ->table('subcategory')
            ->where('subcategory_seotitle', $seoTitles)
            ->fetchPairs('subcategory_seotitle', 'subcategory_title');
    }

    //funkce pro načtení databáze hodnocení postů z komentářů
    public function getRatingsForPost($postId): array
    {
        $comments = $this->database->table('comments')
            ->select('rating')  // Ensure only the 'rating' column is being selected
            ->where('post_id', $postId)
            ->fetchAll();  // Fetch all records as an array of ActiveRow objects

        // Extract ratings from the ActiveRow objects
        $ratings = array_map(function($comment) {
            return $comment->rating;
        }, $comments);

        return $ratings;
    }



}