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
        // Nacteni kategorii do sablony
        $this->template->categories = $this->facade
            ->getCategories()
            ->limit(50);

    }

    public function renderShow(?string $category_seotitle): void
    {
        // Nacteni kategorie do sablony
        $category = $this->facade->getCategoryBySeoTitle($category_seotitle);
        $this->template->category = $category;

        // Nacteni postů do sablony
        $this->template->posts = $this->facade
            ->getPublicArticlesByCategory($category_seotitle)
            ->limit(50);
    }
}
