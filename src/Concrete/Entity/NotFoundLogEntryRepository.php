<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases\Entity;

use Concrete\Package\UrlAliases\NormalizePathTrait;
use Concrete\Package\UrlAliases\NotFoundLogService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Request;

defined('C5_EXECUTE') or die('Access Denied.');

final class NotFoundLogEntryRepository extends EntityRepository
{
    use NormalizePathTrait;

    /**
     * @var \Concrete\Package\UrlAliases\NotFoundLogService|null
     */
    private $service = null;

    /**
     * {@inheritdoc}
     *
     * @see \Doctrine\ORM\EntityRepository::getEntityManager()
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return parent::getEntityManager();
    }

    public function findByRequest(Request $request): ?NotFoundLogEntry
    {
        $service = $this->getService();

        $method = $service->normalizeRequestMethod($request);
        $path = $service->normalizeRequestPath($request);
        $querystring = $service->normalizeQuerystring($request);

        $qb = $this->createQueryBuilder('nf')
            ->setParameter('method', $method)
            ->andWhere('nf.method = :method')
            ->setParameter('pathIndexed', mb_substr($path, 0, NotFoundLogEntry::PATH_INDEXED_MAX_LENGTH))
            ->andWhere('nf.pathIndexed = :pathIndexed')
            ->setParameter('path', $path)
            ->andWhere('nf.path = :path')
            ->setParameter('querystringIndexed', mb_substr($querystring, 0, NotFoundLogEntry::QUERYSTRING_INDEXED_MAX_LENGTH))
            ->andWhere('nf.querystringIndexed = :querystringIndexed')
            ->setParameter('querystring', $querystring)
            ->andWhere('nf.querystring = :querystring')
            ->setMaxResults(1)
        ;

        $found = $qb->getQuery()->getResult();

        return $found[0] ?? null;
    }

    private function getService(): NotFoundLogService
    {
        if ($this->service === null) {
            $this->service = app(NotFoundLogService::class);
        }

        return $this->service;
    }
}
