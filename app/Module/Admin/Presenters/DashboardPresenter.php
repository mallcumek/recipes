<?php

declare(strict_types=1);

namespace App\Module\Admin\Presenters;

use Nette;
use Nette\Application\UI\Form;


/**
 * Presenter for the dashboard view.
 * Ensures the user is logged in before access.
 */
final class DashboardPresenter extends Nette\Application\UI\Presenter
{
	// Incorporates methods to check user login status
	use RequireLoggedUser;
    // Konstruktor, který vyžaduje dazabázové spojení
    public function __construct(
        private Nette\Database\Explorer $database,
    ) {
    }
    // Formular pro ukladani (editovanych) prispevku
    protected function createComponentPostForm(): Form
    {
        $form = new Form;
        $form->addText('title', 'Titulek:')
            ->setRequired();
        $form->addTextArea('content', 'Obsah:')
            ->setRequired();

        $form->addSubmit('send', 'Uložit a publikovat');
        $form->onSuccess[] = $this->postFormSucceeded(...);

        return $form;
    }
    // Ukladani + editace noveho prispevku. tato metoda získá data z formuláře, vloží je do databáze,
    // vytvoří zprávu pro uživatele o úspěšném uložení příspěvku a přesměruje na stránku s novým příspěvkem, takže hned uvidíme, jak vypadá.
    private function postFormSucceeded(array $data): void
    {
        $postId = $this->getParameter('postId');

        if ($postId) {
            $post = $this->database
                ->table('posts')
                ->get($postId);
            $post->update($data);

        } else {
            $post = $this->database
                ->table('posts')
                ->insert($data);
        }

        $this->flashMessage('Příspěvek byl úspěšně publikován.', 'success');
        $this->redirect('Post:show', $post->id);
    }
    // Pokud je k dispozici parametr postId, znamená to, že budeme upravovat příspěvek.
    // V tom případě ověříme, že požadovaný příspěvek opravdu existuje a pokud ano, aktualizujeme jej v databázi.
    // Pokud parametr postId není k dispozici, pak to znamená, že by měl být nový příspěvek přidán.
    // Kde se však onen parametr postId vezme? Jedná se o parametr, který byl vložen do metody renderEdit

    // Přidáme novou stránku edit do presenteru EditPresenter:
    public function renderEdit(int $postId): void
    {
        $post = $this->database
            ->table('posts')
            ->get($postId);

        if (!$post) {
            $this->error('Post not found');
        }

        $this->getComponent('postForm')
            ->setDefaults($post->toArray());
    }


}
