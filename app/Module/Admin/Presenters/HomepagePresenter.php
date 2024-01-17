<?php

declare(strict_types=1);

namespace App\Module\Admin\Presenters;

use Nette;
use Nette\Application\UI\Form;

/**
 * Presenter for the dashboard view.
 * Ensures the user is logged in before access.
 */
final class HomepagePresenter extends Nette\Application\UI\Presenter
{
    // Incorporates methods to check user login status
    //  use RequireLoggedUser;
    // Vypnul jsem v HomepagePresenteru potřebu přihlášení, to dám do administrace

    // Pripojeni k mysql
    public function __construct(
        private Nette\Database\Explorer $database,
    ) {
    }

    // Nyní načteme příspěvky z databáze a pošleme je do šablony, která je následně vykreslí jako HTML kód.
    // Pro tohle je určena takzvaná render metoda:
    public function renderDefault(): void
    {
        $this->template->posts = $this->database
            ->table('posts')
            ->order('created_at DESC')
            ->limit(5);
    }
}
