<?php
declare(strict_types=1);

namespace App\Module\Admin\Presenters;

use App\Forms;
use Nette;
use Nette\Application\Attributes\Persistent;
use Nette\Application\UI\Form;
use App\Model\PostFacade;

/**
 * Presenter for sign-in and sign-up actions.
 */
final class SignPresenter extends Nette\Application\UI\Presenter
{
    /**
     * Stores the previous page hash to redirect back after successful login.
     */
    #[Persistent]
    public string $backlink = '';

    public function __construct(
        private Forms\SignInFormFactory $signInFactory,
        private Forms\SignUpFormFactory $signUpFactory,
        private PostFacade $facade
    ) {
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

    /**
     * Creates the sign-in form component.
     * On successful submission, the user is redirected to the dashboard or back to the previous page.
     */
    protected function createComponentSignInForm(): Form
    {
        return $this->signInFactory->create(function (): void {
            $this->restoreRequest($this->backlink); // redirects the user to the previous page if any
            $this->redirect('Dashboard:'); // or redirects the user to the dashboard
        });
    }

    /**
     * Creates the sign-up form component.
     * On successful submission, the user is redirected to the dashboard.
     */
    protected function createComponentSignUpForm(): Form
    {
        return $this->signUpFactory->create(function (): void {
            $this->redirect('Dashboard:');
        });
    }

    /**
     * Logs out the currently authenticated user.
     */
    public function actionOut(): void
    {
        $this->getUser()->logout();
    }
}
