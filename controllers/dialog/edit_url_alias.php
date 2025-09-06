<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases\Controller\Dialog;

use Concrete\Controller\Backend\UserInterface;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Form\Service\DestinationPicker\DestinationPicker;
use Concrete\Core\Form\Service\Form;
use Concrete\Core\Page\Page;
use Concrete\Core\Permission\Checker;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Package\UrlAliases\Entity\UrlAlias;
use Concrete\Package\UrlAliases\TargetDestinationPickerTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

defined('C5_EXECUTE') or die('Access Denied.');

class EditUrlAlias extends UserInterface
{
    use TargetDestinationPickerTrait;

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Controller\Controller::$viewPath
     */
    protected $viewPath = '/dialogs/edit_url_alias';

    public function view(): ?Response
    {
        $id = $this->request->query->get('id');
        if ($id === 'new') {
            $urlAlias = new UrlAlias();
        } else {
            $id = (int) $id;
            $urlAlias = $id ? $this->app->make(EntityManagerInterface::class)->find(UrlAlias::class, $id) : null;
            if ($urlAlias === null) {
                throw new UserMessageException(t('Unable to find the requested alias'));
            }
        }
        $this->set('form', $this->app->make(Form::class));
        $this->set('destinationPicker', $this->app->make(DestinationPicker::class));
        $this->set('targetDestinationPickerConfig', $this->getTargetDestinationPickerConfig());
        $this->set('rootUrl', rtrim((string) $this->app->make(ResolverManagerInterface::class)->resolve(['/']), '/') . '/');
        $this->set('urlAlias', $urlAlias);
        $this->set('asNew', $this->request->query->getBoolean('asNew'));

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
