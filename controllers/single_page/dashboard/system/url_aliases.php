<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases\Controller\SinglePage\Dashboard\System;

use Concrete\Core\Page\Controller\DashboardPageController;
use Symfony\Component\HttpFoundation\Response;

defined('C5_EXECUTE') or die('Access Denied.');

class UrlAliases extends DashboardPageController
{
    public function view(): Response
    {
        if (method_exists($this, 'buildRedirectToFirstAccessibleChildPage')) {
            return $this->buildRedirectToFirstAccessibleChildPage();
        }

        return $this->buildRedirect('/dashboard/system/url-aliases/aliases');
    }
}
