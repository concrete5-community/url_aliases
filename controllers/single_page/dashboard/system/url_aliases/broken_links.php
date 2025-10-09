<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases\Controller\SinglePage\Dashboard\System\UrlAliases;

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Database\Query\LikeBuilder;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Localization\Localization;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Package\UrlAliases\Entity\NotFoundLogEntry;
use Concrete\Package\UrlAliases\Entity\NotFoundLogEntryRepository;
use Concrete\Package\UrlAliases\NotFoundLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

defined('C5_EXECUTE') or die('Access Denied.');

class BrokenLinks extends DashboardPageController
{
    public function view(): ?Response
    {
        try {
            $this->app->make(NotFoundLogService::class)->deleteOld();
        } catch (Throwable $_) {
        }
        $config = $this->app->make(Repository::class);
        $logEnabled = (bool) $config->get('url_aliases::options.log404.enabled');
        $this->set('logEnabled', $logEnabled);
        if (!$logEnabled) {
            $this->set('optionsUrl', (string) $this->app->make(ResolverManagerInterface::class)->resolve(['/dashboard/system/url-aliases/options']));
        }
        $repo = $this->app->make(NotFoundLogEntryRepository::class);
        $q = $repo->createQueryBuilder('nf')
            ->select('nf.method')
            ->distinct()
            ->addOrderBy('nf.method')
            ->getQuery()
        ;
        if (method_exists($q, 'getSingleColumnResult')) {
            $availableMethods = $q->getSingleColumnResult();
        } else {
            $availableMethods = array_map(
                static function (array $row): string {
                    return $row['method'];
                },
                $q->getArrayResult(),
            );
        }
        $this->set('availableMethods', $availableMethods);
        if (version_compare(APP_VERSION, '9') < 0) {
            $this->requireAsset('javascript', 'vue');
            $this->addHeaderItem('<style>[v-cloak] { display: none; }</style>');
        }
        $this->set('localization', $this->app->make(Localization::class));

        return null;
    }

    public function getNextPage(): JsonResponse
    {
        if (!$this->token->validate('ua-broken-nextpage')) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $post = $this->request->request;
        $afterID = $post->getInt('after');
        if ($afterID === 0) {
            $after = null;
        } else {
            $after = $this->app->make(EntityManagerInterface::class)->find(NotFoundLogEntry::class, $afterID);
            if ($after === null) {
                throw new UserMessageException(t('Invalid parameter value: %s', 'after'));
            }
        }
        $config = $this->app->make(Repository::class);
        $pageSize = max(1, (int) $config->get('url_aliases::options.log404.pageSize'));
        $sortBy = $post->get('sortBy');
        $sortDirection = $post->getInt('sortDescending') === 0 ? 'ASC' : 'DESC';
        $sortDirectionCmpAfter = $sortDirection === 'DESC' ? '<' : '>';
        $qb = $this->app->make(NotFoundLogEntryRepository::class)->createQueryBuilder('nf')
            ->setMaxResults($pageSize + 1)
        ;
        if ($after !== null) {
            $qb->setParameter('afterID', $after->getID());
        }

        switch (is_string($sortBy) ? $sortBy : '') {
            case 'method':
            case 'firstHit':
            case 'lastHit':
            case 'hitCount':
                $qb->orderBy("nf.{$sortBy}", $sortDirection);
                if ($after !== null) {
                    $getter = 'get' . ucfirst($sortBy);
                    $qb
                        ->setParameter('afterValue', $after->{$getter}())
                        ->andWhere(
                            <<<EOT
                            nf.{$sortBy} {$sortDirectionCmpAfter} :afterValue
                            OR (nf.{$sortBy} = :afterValue AND nf.id {$sortDirectionCmpAfter} :afterID)
                            EOT
                        )
                    ;
                }
                break;
            case 'fullPath':
                $qb
                    ->addOrderBy('nf.pathIndexed', $sortDirection)
                    ->addOrderBy('nf.path', $sortDirection)
                    ->addOrderBy('nf.querystringIndexed', $sortDirection)
                    ->addOrderBy('nf.querystring', $sortDirection)
                ;
                if ($after !== null) {
                    $qb
                        ->setParameter('afterPathIndexed', $after->getPathIndexed())
                        ->setParameter('afterPath', $after->getPath())
                        ->setParameter('afterQuerystringIndexed', $after->getQuerystringIndexed())
                        ->setParameter('afterQuerystring', $after->getQuerystring())
                        ->andWhere(
                            <<<EOT
                            nf.pathIndexed {$sortDirectionCmpAfter} :afterPathIndexed
                            OR (
                                nf.pathIndexed = :afterPathIndexed
                                AND (
                                    nf.path {$sortDirectionCmpAfter} :afterPath
                                    OR (
                                        nf.path = :afterPath
                                        AND (
                                            nf.querystringIndexed {$sortDirectionCmpAfter} :afterQuerystringIndexed
                                            OR (
                                                nf.querystringIndexed = :afterQuerystringIndexed
                                                AND (
                                                    nf.querystring {$sortDirectionCmpAfter} :afterQuerystring
                                                    OR (
                                                        nf.querystring = :afterQuerystring
                                                        AND nf.id {$sortDirectionCmpAfter} :afterID
                                                    )
                                                )
                                            )
                                        )
                                    )
                                )
                            )
                            EOT
                        )
                    ;
                }
                break;
            default:
                throw new UserMessageException(t('Invalid parameter value: %s', 'sortBy'));
        }
        $qb->addOrderBy('nf.id', $sortDirection);
        $method = $post->get('method');
        if (is_string($method)) {
            $qb
                ->setParameter('method', $method)
                ->andWhere('nf.method = :method')
            ;
        }
        $path = $post->get('path');
        if (is_string($path) && $path !== '') {
            $likeBuilder = $this->app->make(LikeBuilder::class);
            $qb
                ->setParameter('path', $likeBuilder->escapeForLike($path))
                ->andWhere('nf.path LIKE :path')
            ;
        }
        $result = [
            'entries' => $qb->getQuery()->execute(),
        ];
        $result['hasMore'] = count($result['entries']) > $pageSize;
        if ($result['hasMore']) {
            array_splice($result['entries'], $pageSize);
        }

        return $this->app->make(ResponseFactoryInterface::class)->json($result);
    }

    public function deleteOne(): JsonResponse
    {
        if (!$this->token->validate('ua-broken-deleteone')) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $id = $this->request->request->getInt('id');
        if ($id > 0) {
            $repo = $this->app->make(NotFoundLogEntryRepository::class);
            $qb = $repo->createQueryBuilder('nf');
            $qb->delete()->where('nf.id = :id')->setParameter('id', $id);
            $qb->getQuery()->execute();
        }

        return $this->app->make(ResponseFactoryInterface::class)->json(true);
    }

    public function deleteAll(): JsonResponse
    {
        if (!$this->token->validate('ua-broken-deleteall')) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $this->app->make(NotFoundLogService::class)->deleteAll();

        return $this->app->make(ResponseFactoryInterface::class)->json(true);
    }
}
