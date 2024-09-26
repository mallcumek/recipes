<?php

namespace App\Module\Admin\Presenters;

use App\Forms;
use Nette;
use Nette\Application\UI\Form;
use App\Model\PostFacade;
use Nette\Utils\Json;

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
            ->limit(5);
        $this->template->context = 'post'; // Nastavte kontext pro příspěvky
        $this->template->context2 = 'recipe'; // Nastavte kontext pro příspěvky

        // Získání absolutní URL aktuální stránky
        $this->template->canonicalUrl = $this->getHttpRequest()->getUrl()->getAbsoluteUrl();
    }
    // Připojení k databázi pro zobrazení příspěvku
    // Metoda renderShow vyžaduje jeden argument – ID jednoho konkrétního článku, který má být zobrazen.
    // Poté tento článek načte z databáze a předá ho do šablony.
    public function renderShow(int $postId): void
    {

        // Ulozim do sablony info o aktualnim renderu
        //$this->template->context = 'recipe';

        $post = $this->database
            ->table('posts')
            ->get($postId);
        // Pokud nemůže být příspěvek nalezen, zavoláním $this->error(...) zobrazíme stránku 404.
        // Pozor na to, že ve vývojářském módu (localhost) tuto chybovou stránku neuvidíte.
        if (!$post) {
            $this->error('Stránka nebyla nalezena');
        }
        // Získání názvu kuchyně podle cuisineID
        $cuisineTitle = $this->facade->getCuisineTitleByCuisineID($post->cuisine_id);
        // Uložení názvu kuchyně do šablony
        $this->template->cuisine_title = $cuisineTitle;

        // Uložení příspěvku do šablony
        $this->template->post = $post;
        // Uložení komentáře do šablony
        $this->template->comments = $post->related('comments')->order('created_at');

        // Fetch ratings for the post
        $ratings = $this->facade->getRatingsForPost($postId);
        $totalRatings = count($ratings);
        $sumRatings = array_sum($ratings);
        $averageRating = $totalRatings > 0 ? $sumRatings / $totalRatings : 0;

        // Pass ratings info to the template
        $this->template->totalRatings = $totalRatings;
        $this->template->averageRating = round($averageRating, 1);


        // Funkce na vytazeni poctu kalorii ze sloupce nutrition_facts.
        // Projití každého příspěvku a extrakce kalorií z textového řetězce v poli nutrition_facts.

        if (preg_match('~Calories:\s*(\d+)~', $post->nutrition_facts, $matches)) {
            // Pokud regex najde odpovídající kalorie, přiřadíme je do pole pod klíčem odpovídajícím ID příspěvku.
            $caloriesData = $matches[1];
        } else {
            // Pokud nejsou kalorie specifikovány, nastavíme hodnotu na 'N/A'.
            $caloriesData = 'N/A';
        }
        // Předání načtených příspěvků a dat o kaloriích do šablony.
        $this->template->caloriesData = $caloriesData;

        // Získání base URL bez závěrečného lomítka
        $baseUrl = rtrim($this->getHttpRequest()->getUrl()->getBaseUrl(), '/');
        // Získání image path bez počátečního lomítka
        $imagePath = ltrim($post->image_path, '/');
        // Sestavení plné URL pro obrázek
        $imageUrl = $baseUrl . '/' . $imagePath;
        // Formátování data na Y-m-d
        $datePublished = (new \DateTime($post->created_at))->format('Y-m-d');

        // Připravíme data pro JSON LD - chceme hvězdičkové hodnocení receptu pro Google, generujeme JSON do head v HTML
        $jsonLdData = [
            '@context' => 'https://schema.org/',
            '@type' => 'Recipe',
            'name' => $post->title,
            'image' => [$imageUrl],
            'author' => [
                '@type' => 'Person',
                'name' => $post->username,
            ],
            'datePublished' => $datePublished,
            'recipeCategory' => $post->category_seotitle,
            'description' => $post->content,
            'recipeCuisine' => $cuisineTitle,
            'prepTime' => 'PT' . $post->prep_time . 'M',
            'cookTime' => 'PT' . $post->cook_time . 'M',
            'nutrition' => [
                '@type' => 'NutritionInformation',
                'calories' => $caloriesData . ' calories',
            ],
            'recipeYield' => $post->servings . ' Servings',
        ];

        // Podmíněné přidání aggregateRating pouze pokud existují hodnocení
        if ($totalRatings > 0) {
            $jsonLdData['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $averageRating,
                'ratingCount' => $totalRatings,
            ];
        } else {

        }

        // Rozdělení ingrediencí do pole na základě nových řádků (chatgpt)
        $ingredientsList = explode("\n", $post->ingredients);

// Očištění každé ingredience (odstranění přebytečných mezer a prázdných řádků)
        $ingredientsList = array_map('trim', $ingredientsList);
        $ingredientsList = array_filter($ingredientsList, function($ingredient) {
            return !empty($ingredient);
        });

// Přidání pole ingrediencí do JSON-LD dat
        $jsonLdData['recipeIngredient'] = $ingredientsList;

        // Kódování pole do JSON formátu
        $jsonLdData = Json::encode($jsonLdData);
        // Předáme JSON LD data do šablony
        $this->template->jsonLdData = $jsonLdData;


    }

    // Továrna na formulář v Presenteru
    protected function createComponentCommentForm(): Form
    {
        //Vytvoření pole pro hodnocení
        $rating = [
            '5' => '5star',
            '4' => '4star',
            '3' => '3star',
            '2' => '2star',
            '1' => '1star',
        ];

        $form = new Form; // means Nette\Application\UI\Form
        $form->addRadioList('ratio', 'Rating:', $rating);
        $form->addHidden('post_id', $this->getParameter('postId'));
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
    private function commentFormSucceeded(\stdClass $data, array $values): void
    {



            // reCAPTCHA verifikace prošla, pokračujte s uložením komentáře
            $selectedValue = $values['ratio'] ?? 0;
            $postId = $this->getParameter('postId');

            $this->database->table('comments')->insert([
                'post_id' => $postId,
                'name' => $data->name,
                'email' => $data->email,
                'content' => $data->content,
                'rating' => $selectedValue,
            ]);

            $this->flashMessage('Děkujeme za hodnocení a komentář!', 'success');
            $this->redirect('this');


    }


}