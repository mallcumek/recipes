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
        private Nette\Database\Explorer $database,
    )
    {
    }

    // Formular pro ukladani (editovanych) prispevku
    protected function createComponentPostForm(): Form
    {

        $form = new Form;
        $form->addText('title', 'Titulek:')->setRequired();
        $form->addTextArea('content', 'Obsah:')->setRequired();

        // Přidáváme pole pro nahrávání souborů
        $form->addUpload('image', 'Obrázek:');

        $form->addSubmit('send', 'Uložit a publikovat');
        $form->onSuccess[] = [$this, 'postFormSucceeded'];

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

            $originalNameup = $uploadedFile->getSanitizedName();
            $originalNameStrtoLoweru = strtolower($originalNameup);
            $post->update(['image' => $originalNameStrtoLoweru]);

            //Aktualizace Databázového Záznamu:
            //Aktualizuje databázový záznam příspěvku ($post) pomocí metody update.
            //Nová hodnota pole image_path je nastavena na hodnotu proměnné $imagePath, což je cesta k uloženému obrázku.
        }

        $this->flashMessage('Příspěvek byl úspěšně publikován.', 'success');
        $this->redirect('Post:show', $post->id);
    }
    // Pokud je k dispozici parametr postId, znamená to, že budeme upravovat příspěvek.
    // V tom případě ověříme, že požadovaný příspěvek opravdu existuje a pokud ano, aktualizujeme jej v databázi.
    // Pokud parametr postId není k dispozici, pak to znamená, že by měl být nový příspěvek přidán.
    // Kde se však onen parametr postId vezme? Jedná se o parametr, který byl vložen do metody renderEdit

    // Metoda pro uložení nahrávaného souboru na server
    private function storeUploadedFile(Nette\Http\FileUpload $file, int $postId): string
    {
        $uploadDir = __DIR__ . '/../../../../www/data';

        // Vytvoření adresáře pro každý příspěvek
        $postDir = $uploadDir . '/' . $postId;
        if (!is_dir($postDir)) {
            mkdir($postDir, 0777, true);
        }

        // Získání původního názvu souboru
        $originalName = $file->getSanitizedName();
        // Odstranění staré přípony (např. .jpeg)
        $imageNameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);
        // Udelame novy nazev s webp pro ulozeni do mysql, protoze u resizu menime formát obrázku
        $newImageNameWebp = $imageNameWithoutExtension . ".webp";
        // Prevod stringu na male znaky
        $newImageNameWebp = strtolower($newImageNameWebp);
        $originalImageNameStrtoLower = strtolower($originalName);


        // Přesun souboru do cílového adresáře
        $file->move($postDir . '/' . $originalImageNameStrtoLower);
        // Vytvoření instance třídy Image pro manipulaci s obrázkem
        $image = Image::fromFile($postDir . '/' . $originalImageNameStrtoLower);
        //pokud je obrazek vetsi 1920px tak ho resizni na 1600 a zbytek dopocitej
        if ($image->getWidth() >= 1920) {
            // Resize  image
            $image->resize(1920, null);
            $image->sharpen();
        }
        // Přesuň soubor do složky "$uploadDir = __DIR__ . '/../../../../www/data'" (resized)
        $image->save($postDir . '/' . $newImageNameWebp, 80, Image::WEBP);
        return '/data/' . $postId . '/' . $newImageNameWebp;
    }

    // Přidáme novou stránku edit do presenteru EditPresenter:
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
