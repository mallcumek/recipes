<?php

declare(strict_types=1);

namespace App\Module\Admin\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Contributte\ImageStorage\ImageStoragePresenterTrait;
use Nette\Application\UI\Presenter;
use Nette\Http\FileUpload;
use Nette\Utils\Image;
use Nette\Utils\ImageException;
use Nette\Utils\ImageColor;
use Nette\Utils\ImageType;
use Nette\Utils\Strings;
use App\Model\PostFacade;

/**
 * Presenter for the dashboard view.
 * Ensures the user is logged in before access.
 */
final class DashboardPresenter extends Nette\Application\UI\Presenter
{
    // Incorporates methods to check user login status
    use RequireLoggedUser;
    use ImageStoragePresenterTrait;

    // Konstruktor, který vyžaduje dazabázové spojení
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
        $this->template->context = null; // Výchozí hodnota pro context
        // Získání absolutní URL aktuální stránky
        $this->template->canonicalUrl = $this->getHttpRequest()->getUrl()->getAbsoluteUrl();
    }
    // Formular pro ukladani (editovanych) prispevku
    protected function createComponentPostForm(): Form
    {
        // Zde načteme kategorie  do pole pro vypsání ve formuláři
        $categories = $this->facade->getCategories();
        $categoryOptions = [];
        foreach ($categories as $category) {
            // Předpokládáme, že máte sloupce 'category_seotitle' a 'category_title' v tabulce 'category'
            $categoryOptions[$category->category_seotitle] = $category->category_title;
        }

        // Zde načteme subkategorie  do pole pro vypsání ve formuláři
        $subcategories = $this->facade->getAllSubCategories();
        $subcategoryOptions = [];
        foreach ($subcategories as $subcategory) {
            // Předpokládáme, že máte sloupce 'category_seotitle' a 'category_title' v tabulce 'category'
            $subcategoryOptions[$subcategory->subcategory_seotitle] = $subcategory->subcategory_title;
        }
        // Přidání prázdné možnosti pro subkategorie
        $subcategoryOptions = ['' => 'No subcategory'] + $subcategoryOptions;
        $form = new Form;
        $form->addTextArea('recipe_post', 'Recipe post:')
            ->setHtmlAttribute('rows', '5');
        $form->addText('title', 'Recipe title:')->setRequired();
        $form->addText('title_longer', 'Recipe longer title:');
        $form->addTextArea('meta_description', 'Meta description:')
        ->setHtmlAttribute('rows', '3');
        $form->addSelect('category_seotitle', 'Category select:', $categoryOptions); // Použijeme dynamicky načtené kategorie
        $form->addSelect('subcategory_seotitle', 'Subategory select:', $subcategoryOptions); // Použijeme dynamicky načtené subkategorie
        $form->addSelect('subcategory_seotitle2', 'Subategory 2 select:', $subcategoryOptions); // Použijeme dynamicky načtené subkategorie 2
        $form->addSelect('subcategory_seotitle3', 'Subategory 3 select:', $subcategoryOptions); // Použijeme dynamicky načtené subkategorie 3

        $form->addTextArea('content', 'Description:')->setRequired()
            ->setHtmlAttribute('rows', '5');
        $form->addTextArea('ingredients', 'Ingredients:')->setRequired()
            ->setHtmlAttribute('rows', '5');
        $form->addTextArea('instructions', 'Directions:')->setRequired()
            ->setHtmlAttribute('rows', '5');
        $form->addInteger('prep_time', 'Prep time:')
            ->setHtmlAttribute('class', 'trida');
        $form->addInteger('cook_time', 'Cook time:');
        $form->addInteger('servings', 'Servings:');
        $form->addTextArea('tips_and_tricks', 'Tips and Tricks: ')->setRequired()
            ->setHtmlAttribute('rows', '5');
        $form->addTextArea('chefs_notes', 'Chef\'s Notes')
            ->setHtmlAttribute('rows', '5');
        $form->addTextArea('nutrition_facts', 'Nutrition Facts')
            ->setHtmlAttribute('rows', '5');
        $form->addTextArea('recipe_history', 'Historical & Cultural Overview')
            ->setHtmlAttribute('rows', '5');
        $form->addText('image_alt', 'Image alt:');
        // Přidáváme pole pro nahrávání souborů
        $form->addUpload('image', 'Image:');

        // Přidání skrytého pole pro uživatelské jméno
        $form->addHidden('username');

        $form->addSubmit('send', 'Uložit a publikovat');
        $form->onSuccess[] = [$this, 'postFormSucceeded'];
       // $form->setRenderer($this->formatTemplateClass());

        // Nastavení uživatelského jména do skrytého pole
        $form['username']->setDefaultValue($this->getUser()->getIdentity()->username);
        return $form;
    }

    // Ukladani + editace noveho prispevku. tato metoda získá data z formuláře, vloží je do databáze,
    // vytvoří zprávu pro uživatele o úspěšném uložení příspěvku a přesměruje na stránku s novým příspěvkem, takže hned uvidíme, jak vypadá.
    public function postFormSucceeded(Form $form, array $data): void
    {
        $postId = $this->getParameter('postId');
        if ($postId) {
            $post = $this->database->table('posts')->get($postId);
            if (!$post) {
                $this->error('Post not found');
            }

            // Pokud není nahrán nový soubor a není nastaven $postImage (původní hodnota z databáze),
            // použije se původní hodnota image z databáze.
            if (!$form['image']->isFilled()) {
                $data['image'] = $post->image; // Předpokládám, že pole s názvem původního obrázku je 'image'.
            } // Pokud je nahrán soubor
            else {
                // Získání původního názvu souboru
                $file = $data['image'];
                $originalName = $file->getSanitizedName();
                // Odstranění staré přípony (např. .jpeg)
                $imageNameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);
                // Udelame novy nazev s webp pro ulozeni do mysql, protoze menime format
                $newImageNameWebp = $imageNameWithoutExtension . ".webp";
                $newImageNameWebp = strtolower($newImageNameWebp);
                $data['image'] = $newImageNameWebp;
            }
            $post->update($data);
        } else {
            // Získání původního názvu souboru
            $file = $data['image'];
            $originalName = $file->getSanitizedName();
            // Odstranění staré přípony (např. .jpeg)
            $imageNameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);
            //udelame novy nazev s webp pro ulozeni do mysql, protoze u resizu menime formát obrázku
            $newImageNameWebp = $imageNameWithoutExtension . ".webp";
            $newImageNameWebp = strtolower($newImageNameWebp);
            $originalNameStrtoLower = strtolower($originalName);
            // Ulož název souboru obrázku do pole
            $data['image'] = $newImageNameWebp;
            //Titulek projede funkci webalize na seo titulek - vynecha znaky, diakritiku, male pismo, mezery na pomlcky. blabla
            $seoTitle = Strings::webalize($data['title']);
            $data['seotitle'] = $seoTitle;
            $post = $this->database->table('posts')->insert($data);
        }

        // Získání informací o nahrávaném souboru
        /** @var FileUpload $uploadedFile */
        $uploadedFile = $form['image']->getValue();

        // Pokud se soubor nahrál tak:

        if ($uploadedFile->isOk()) {
            //Volání Metody storeUploadedFile:
            //Volá metodu $this->storeUploadedFile($uploadedFile, $post->id).
            //Předává metodě $uploadedFile, což je instance třídy FileUpload, reprezentující nahrávaný soubor, a $post->id, což je identifikátor příspěvku, ke kterému soubor patří.
            $imagePath = $this->storeUploadedFile($uploadedFile, $post->id);
            //Uložení Vracené Cesty k Obrázku:
            //Návratová hodnota metody storeUploadedFile je přiřazena do proměnné $imagePath.
            //Tato hodnota představuje cestu k uloženému a případně zpracovanému souboru.
            $post->update(['image_path' => $imagePath]);

            // Získání původního názvu souboru * duplikovaný jako v hlavní podmínce výše u zápisu do DB, opravit. ale funguje.

            $originalName = $uploadedFile->getSanitizedName();
            // Odstranění staré přípony (např. .jpeg)
            $originalName = pathinfo($originalName, PATHINFO_FILENAME);
            //udelame novy nazev s webp pro ulozeni do mysql, protoze u resizu menime formát obrázku
            $originalNameWebp = $originalName . ".webp";
            $originalNameWebp = strtolower($originalNameWebp);
            $post->update(['image' => $originalNameWebp]);

            //Aktualizace Databázového Záznamu:
            //Aktualizuje databázový záznam příspěvku ($post) pomocí metody update.
            //Nová hodnota pole image_path je nastavena na hodnotu proměnné $imagePath, což je cesta k uloženému obrázku.
        }
        // Nacteni seotitle a uprava redirectu na pouziti id + seotitle misto pouze ID. Chatgpt
        $seoTitle = $data['seotitle'] ?? $post->seotitle;
        $this->flashMessage('Příspěvek byl úspěšně publikován.', 'success');
        $this->redirect('Post:show', ['postId' => $post->id, 'seotitle' => $seoTitle]);;
        // old redirect $this->redirect('Post:show', $post->id);
    }
    // Pokud je k dispozici parametr postId, znamená to, že budeme upravovat příspěvek.
    // V tom případě ověříme, že požadovaný příspěvek opravdu existuje a pokud ano, aktualizujeme jej v databázi.
    // Pokud parametr postId není k dispozici, pak to znamená, že by měl být nový příspěvek přidán.
    // Kde se však onen parametr postId vezme? Jedná se o parametr, který byl vložen do metody renderEdit

    // Metoda pro uložení nahrávaného souboru na server
    private function storeUploadedFile(Nette\Http\FileUpload $file, int $postId): string
    {
        // Funkce pro smazání obsahu adresáře
        function clearDir($dir): void
        {
            $files = glob($dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        $uploadDir = __DIR__ . '/../../../../www/data';

        // Vytvoření adresáře pro každý příspěvek cleardir maze puvodni soubory
        $postDir = $uploadDir . '/' . $postId;
        if (!is_dir($postDir)) {
            mkdir($postDir, 0777, true);

        } else {
            clearDir($postDir);
        }

        // Získání původního názvu souboru
        $originalName = $file->getSanitizedName();
        // Odstranění staré přípony (např. .jpeg)
        $imageNameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);

        // Pouzito pro nize zakomentovanou verzi ukladani originalniho souboru na disk
        $originalImageNameStrtoLower = strtolower($originalName);

        // Udelame novy nazev malými písmeny s webp pro ulozeni do mysql, protoze u resizu menime formát obrázku. Tohle je největší width,zbytek bude jen pro srcset
        $newImageNameWebp = $imageNameWithoutExtension . ".webp";
        $newImageNameWebp = strtolower($newImageNameWebp);
        // Názvy pro menší obrázky webp do srcset, které následně uložíme jako soubory
        $newImageNameWebp1920 = $imageNameWithoutExtension . "-1920w.webp";
        $newImageNameWebp1920 = strtolower($newImageNameWebp1920);
        $newImageNameWebp1800 = $imageNameWithoutExtension . "-1800w.webp";
        $newImageNameWebp1800 = strtolower($newImageNameWebp1800);
        $newImageNameWebp1600 = $imageNameWithoutExtension . "-1600w.webp";
        $newImageNameWebp1600 = strtolower($newImageNameWebp1600);
        $newImageNameWebp1400 = $imageNameWithoutExtension . "-1400w.webp";
        $newImageNameWebp1400 = strtolower($newImageNameWebp1400);
        $newImageNameWebp1200 = $imageNameWithoutExtension . "-1200w.webp";
        $newImageNameWebp1200 = strtolower($newImageNameWebp1200);
        $newImageNameWebp1000 = $imageNameWithoutExtension . "-1000w.webp";
        $newImageNameWebp1000 = strtolower($newImageNameWebp1000);
        $newImageNameWebp800 = $imageNameWithoutExtension . "-800w.webp";
        $newImageNameWebp800 = strtolower($newImageNameWebp800);
        $newImageNameWebp600 = $imageNameWithoutExtension . "-600w.webp";
        $newImageNameWebp600 = strtolower($newImageNameWebp600);
        $newImageNameWebp400 = $imageNameWithoutExtension . "-400w.webp";
        $newImageNameWebp400 = strtolower($newImageNameWebp400);
        $newImageNameWebp200 = $imageNameWithoutExtension . "-200w.webp";
        $newImageNameWebp200 = strtolower($newImageNameWebp200);

        // Přečtení obsahu souboru z objektu FileUpload
        $fileContent = $file->getContents();
        // Vytvoření instance třídy Image pro manipulaci s obrázkem
        $image = Image::fromString($fileContent);

        /* Verze s ulozenim puvodniho obrazku na disk a nasledne cteni z disku na vytvoreni objektu

                // Přesun souboru do cílového adresáře
                 $file->move($postDir . '/' . $originalImageNameStrtoLower);
                // Vytvoření instance třídy Image pro manipulaci s obrázkem
                  $image = Image::fromFile($postDir . '/' . $originalImageNameStrtoLower);
        */

        //pokud je obrazek vetsi nez 1920px tak ho ulož v puvodni velikosti
        if ($image->getWidth() >= 1920) {
            $image->sharpen();
        }
        // Ulož soubor do složky "$uploadDir = __DIR__ . '/../../../../www/data'" (resized)
        $image->save($postDir . '/' . $newImageNameWebp, 80, Image::WEBP);

        //****************** Pro každou zmenšenou fotku zvášť resize blok **********************

        // Vytvoření kopie původní instance obrázku v 1800w
        $thumb1920 = Image::fromString($image->toString());
        //pokud je obrazek vetsi 1920px tak ho resizni na 1600 a zbytek dopocitej
        if ($thumb1920->getWidth() >= 1920) {
            $thumb1920->resize(1920, null);
            $thumb1920->sharpen();
        }
        $thumb1920->save($postDir . '/' . $newImageNameWebp1920, 80, Image::WEBP);

        // Vytvoření kopie původní instance obrázku v 1800w
        $thumb1800 = Image::fromString($image->toString());
        if ($thumb1800->getWidth() >= 1800) {
            $thumb1800->resize(1800, null);
            $thumb1800->sharpen();
        }
        $thumb1800->save($postDir . '/' . $newImageNameWebp1800, 80, Image::WEBP);

        // Vytvoření kopie původní instance obrázku v 1600w
        $thumb1600 = Image::fromString($image->toString());
        if ($thumb1600->getWidth() >= 1600) {
            $thumb1600->resize(1600, null);
            $thumb1600->sharpen();
        }
        $thumb1600->save($postDir . '/' . $newImageNameWebp1600, 80, Image::WEBP);

        // Vytvoření kopie původní instance obrázku v 1400w
        $thumb1400 = Image::fromString($image->toString());
        if ($thumb1400->getWidth() >= 1400) {
            $thumb1400->resize(1400, null);
            $thumb1400->sharpen();
        }
        $thumb1400->save($postDir . '/' . $newImageNameWebp1400, 80, Image::WEBP);

        // Vytvoření kopie původní instance obrázku v 1200w
        $thumb1200 = Image::fromString($image->toString());
        if ($thumb1200->getWidth() >= 1200) {
            $thumb1200->resize(1200, null);
            $thumb1200->sharpen();
        }
        $thumb1200->save($postDir . '/' . $newImageNameWebp1200, 80, Image::WEBP);

        // Vytvoření kopie původní instance obrázku v 1000w
        $thumb1000 = Image::fromString($image->toString());
        if ($thumb1000->getWidth() >= 1000) {
            $thumb1000->resize(1000, null);
            $thumb1000->sharpen();
        }
        $thumb1000->save($postDir . '/' . $newImageNameWebp1000, 80, Image::WEBP);

        // Vytvoření kopie původní instance obrázku v 800w
        $thumb800 = Image::fromString($image->toString());
        if ($thumb800->getWidth() >= 800) {
            $thumb800->resize(800, null);
            $thumb800->sharpen();
        }
        $thumb800->save($postDir . '/' . $newImageNameWebp800, 80, Image::WEBP);

        // Vytvoření kopie původní instance obrázku v 600w
        $thumb600 = Image::fromString($image->toString());
        if ($thumb600->getWidth() >= 600) {
            $thumb600->resize(600, null);
            $thumb600->sharpen();
        }
        $thumb600->save($postDir . '/' . $newImageNameWebp600, 80, Image::WEBP);

        // Vytvoření kopie původní instance obrázku v 400w
        $thumb400 = Image::fromString($image->toString());
        if ($thumb400->getWidth() >= 400) {
            $thumb400->resize(400, null);
            $thumb400->sharpen();
        }
        $thumb400->save($postDir . '/' . $newImageNameWebp400, 80, Image::WEBP);

        // Vytvoření kopie původní instance obrázku v 200w
        $thumb200 = Image::fromString($image->toString());
        if ($thumb200->getWidth() >= 200) {
            $thumb200->resize(200, null);
            $thumb200->sharpen();
        }
        $thumb200->save($postDir . '/' . $newImageNameWebp200, 80, Image::WEBP);

        //******************End Pro každou zmenšenou fotku zvášť resize blok **********************


        // Uloží do funkce string cesty s názvem souboru pro následné uložení do mysql. Strašně důležitý.
        return '/data/' . $postId . '/' . $newImageNameWebp;
    }

    // Přidáme novou stránku edit do presenteru Dashboard Presenter:
    public function renderEdit(int $postId): void
    {

        $post = $this->database
            ->table('posts')
            ->get($postId);

        if (!$post) {
            $this->error('Post not found');
        }

        $this['postForm']->setDefaults($post->toArray());
    }


}
