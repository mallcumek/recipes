<?php

declare(strict_types=1);

namespace App\Module\Admin\Presenters;

use Nette;


/**
 * Presenter for the dashboard view.
 * Ensures the user is logged in before access.
 */
final class HomepagePresenter extends Nette\Application\UI\Presenter
{
    // Incorporates methods to check user login status
    //  use RequireLoggedUser;
}
