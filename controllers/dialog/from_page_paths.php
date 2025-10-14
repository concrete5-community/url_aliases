<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases\Controller\Dialog;

use Concrete\Controller\Backend\UserInterface;
use Concrete\Core\Entity\Page\PagePath;
use Concrete\Core\Page\Page;
use Concrete\Core\Permission\Checker;
use Concrete\Core\Url\Resolver\PageUrlResolver;
use Concrete\Package\UrlAliases\NormalizePathTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

defined('C5_EXECUTE') or die('Access Denied.');

class FromPagePaths extends UserInterface
{
    use NormalizePathTrait;

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Controller\Controller::$viewPath
     */
    protected $viewPath = '/dialogs/from_page_paths';

    public function view(): ?Response
    {
        if (version_compare(APP_VERSION, '9') < 0) {
            $this->requireAsset('javascript', 'vue');
        }
        $em = $this->app->make(EntityManagerInterface::class);
        $qb = $em->createQueryBuilder();
        $q = $qb
            ->from(PagePath::class, 'ppAlias')
            ->innerJoin(PagePath::class, 'ppCanonical', 'WITH', 'ppAlias.cID = ppCanonical.cID AND ppAlias.ppIsCanonical = 0 AND ppCanonical.ppIsCanonical = 1')
            ->select('ppAlias.ppID')
            ->addSelect('ppAlias.cID')
            ->addSelect('ppAlias.cPath')
            ->orderBy('ppAlias.cID')
            ->addOrderBy('ppAlias.cPath')
            ->addOrderBy('ppAlias.ppID')
            ->getQuery()
        ;
        $additionalPaths = [];
        $pageUrlResolver = $this->app->make(PageUrlResolver::class);
        $curPage = null;
        foreach ($q->getArrayResult() as $row) {
            $cID = (int) $row['cID'];
            if ($curPage === null || $curPage['id'] !== $cID) {
                $curPage = ['id' => $cID];
                $c = Page::getByID($cID);
                if ($c && !$c->isError()) {
                    $curPage['name'] = $c->isGeneratedCollection() ? t($c->getCollectionName()) : $c->getCollectionName();
                    $curPage['url'] = (string) $pageUrlResolver->resolve([$c]);
                }
            }
            if (isset($curPage['url'])) {
                $additionalPaths[] = [
                    'id' => (int) $row['ppID'],
                    'additionalPath' => '/' . $this->normalizePath($row['cPath']),
                    'pageName' => $curPage['name'],
                    'canonicalUrl' => $curPage['url'],
                ];
            }
        }
        $this->set('additionalPaths', $additionalPaths);

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
