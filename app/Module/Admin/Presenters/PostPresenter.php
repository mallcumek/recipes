<?php

namespace App\Module\Admin\Presenters;

use App\Forms;
use Nette;
use Nette\Application\UI\Form;
use App\Model\PostFacade;

final class PostPresenter extends Nette\Application\UI\Presenter
{
    // private PostFacade $facade pomohlo, aby beforeRender fungoval.
    public function __construct(
        private Nette\Database\Explorer $database, private PostFacade $facade
    )
    {
    }
    public function beforeRender(): void
    {
        // Nacteni kategorii do sablony
        $this->template->categories = $this->facade
            ->getCategories()
            ->limit(5);
        $this->template->context = 'post'; // Nastavte kontext pro příspěvky
    }
    // Připojení k databázi pro zobrazení příspěvku
    // Metoda renderShow vyžaduje jeden argument – ID jednoho konkrétního článku, který má být zobrazen.
    // Poté tento článek načte z databáze a předá ho do šablony.
    public function renderShow(int $postId): void
    {
        $post = $this->database
            ->table('posts')
            ->get($postId);
        // Pokud nemůže být příspěvek nalezen, zavoláním $this->error(...) zobrazíme stránku 404.
        // Pozor na to, že ve vývojářském módu (localhost) tuto chybovou stránku neuvidíte.
        if (!$post) {
            $this->error('Stránka nebyla nalezena');
        }
        // Uložení příspěvku do šablony
        $this->template->post = $post;
        // Uložení komentáře do šablony
        $this->template->comments = $post->related('comments')->order('created_at');
    }
        // Továrna na formulář v Presenteru
    protected function createComponentCommentForm(): Form
    {
        $form = new Form; // means Nette\Application\UI\Form

        $form->addText('name', 'Name:')
            ->setRequired();

        $form->addEmail('email', 'E-mail:');

        $form->addTextArea('content', 'Message:')
            ->setRequired();

        $form->addSubmit('send', 'Send');
        $form->onSuccess[] = $this->commentFormSucceeded(...);

        return $form;
    }

    //Tato nová metoda má jeden argument, což je instance formuláře, který byl odeslán – vytvořen továrnou.
    // Odeslané hodnoty získáme ve $data. A následně uložíme data do databázové tabulky comments.
    private function commentFormSucceeded(\stdClass $data): void
    {
        $postId = $this->getParameter('postId');

        $this->database->table('comments')->insert([
            'post_id' => $postId,
            'name' => $data->name,
            'email' => $data->email,
            'content' => $data->content,
        ]);

        $this->flashMessage('Děkuji za komentář', 'success');
        $this->redirect('this');
    }




}