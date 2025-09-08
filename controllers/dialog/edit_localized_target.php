<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases\Controller\Dialog;

use Concrete\Controller\Backend\UserInterface;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Form\Service\DestinationPicker\DestinationPicker;
use Concrete\Core\Form\Service\Form;
use Concrete\Core\Page\Page;
use Concrete\Core\Permission\Checker;
use Concrete\Package\UrlAliases\Entity\UrlAlias;
use Concrete\Package\UrlAliases\Entity\UrlAlias\LocalizedTarget;
use Concrete\Package\UrlAliases\TargetDestinationPickerTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

defined('C5_EXECUTE') or die('Access Denied.');

class EditLocalizedTarget extends UserInterface
{
    use TargetDestinationPickerTrait;

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Controller\Controller::$viewPath
     */
    protected $viewPath = '/dialogs/edit_localized_target';

    public function view(): ?Response
    {
        $em = $this->app->make(EntityManagerInterface::class);
        $urlAliasID = $this->request->query->getInt('urlAlias');
        $urlAlias = $urlAliasID ? $em->find(UrlAlias::class, $urlAliasID) : null;
        if ($urlAlias === null) {
            throw new UserMessageException(t('Unable to find the requested alias'));
        }
        $id = $this->request->query->get('id');
        if ($id === 'new') {
            $localizedTarget = new LocalizedTarget($urlAlias);
        } else {
            $id = (int) $id;
            $localizedTarget = $id ? $em->find(LocalizedTarget::class, $id) : null;
            if ($localizedTarget === null || $localizedTarget->getUrlAlias() !== $urlAlias) {
                throw new UserMessageException(t('Unable to find the requested Target by browser language'));
            }
        }
        $this->set('form', $this->app->make(Form::class));
        $this->set('destinationPicker', $this->app->make(DestinationPicker::class));
        $this->set('targetDestinationPickerConfig', $this->getTargetDestinationPickerConfig());
        $this->set('localizedTarget', $localizedTarget);

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Controller\Backend\UserInterface::canAccess()
     */
    protected function canAccess(): bool
    {
        $page = Page::getByPath('/dashboard/system/url-aliases');
        if (!$page || $page->isError()) {
            return false;
        }
        $pc = new Checker($page);
        if (!$pc->canViewPage()) {
            return false;
        }

        return true;
    }
}
