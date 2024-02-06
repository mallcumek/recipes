<?php
namespace App\Model;

use Nette;

final class PostFacade
{
    public function __construct(
        private Nette\Database\Explorer $database,
    ) {
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
    public function getCategories()
    {
        return $this->database
            ->table('category')
            ->order('category_title ASC'); // ASC pro vzestupné řazení, DESC pro sestupné
    }

    public function getCategoryBySeoTitle(string $seoTitle)
    {
        return $this->database
            ->table('category')
            ->where('category_seotitle', $seoTitle)
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

}