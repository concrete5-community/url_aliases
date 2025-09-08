<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases\Entity;

use Concrete\Package\UrlAliases\NormalizePathTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Request;

defined('C5_EXECUTE') or die('Access Denied.');

class UrlAliasRepository extends EntityRepository
{
    use NormalizePathTrait;

    /**
     * {@inheritdoc}
     *
     * @see \Doctrine\ORM\EntityRepository::getEntityManager()
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return parent::getEntityManager();
    }

    /**
     * @return \Concrete\Package\UrlAliases\Entity\UrlAlias[]
     */
    public function findByRequest(Request $request, ?bool $enabled = true, ?int $maxResults = null): array
    {
        return $this->findByPathAndQuerysring($this->normalizePath($request->getPathInfo()), $request->getQueryString() ?? '', $enabled, $maxResults);
    }

    /**
     * @param string $path normalized path
     * @param string $querystring without leading question mark
     *
     * @return \Concrete\Package\UrlAliases\Entity\UrlAlias[]
     */
    public function findByPathAndQuerysring(string $path, string $querystring, ?bool $enabled = true, ?int $maxResults = null): array
    {
        $qb = $this->createQueryBuilder('ua')
            ->setParameter('pathIndexed', mb_substr($path, 0, UrlAlias::PATH_INDEXED_MAX_LENGTH))
            ->andWhere('ua.pathIndexed = :pathIndexed')
            ->setParameter('path', $path)
            ->andWhere('ua.path = :path')
        ;
        if ($maxResults !== null) {
            $qb->setMaxResults($maxResults);
        }
        if ($enabled !== null) {
            $qb->andWhere($enabled ? 'ua.enabled = 1' : 'ua.enabled = 0');
        }
        if ($querystring === '') {
            $qb->andWhere("ua.querystring = ''");
        }

        return array_values(
            array_filter(
                $qb->getQuery()->getResult(),
                function (UrlAlias $urlAlias) use ($querystring): bool {
                    return $this->urlAliasOkForQuerystring($urlAlias, $querystring);
                }
            )
        );
    }

    /**
     * @param string $querystring without leading question mark
     *
     * @return \Concrete\Package\UrlAliases\Entity\UrlAlias[]
     */
    private function urlAliasOkForQuerystring(UrlAlias $urlAlias, string $querystring): bool
    {
        $urlAliasQuerystring = $urlAlias->getQuerystring();
        if ($urlAliasQuerystring === '') {
            return $urlAlias->isAcceptAdditionalQuerystringParams() ? true : $querystring === '';
        }
        if ($querystring === '') {
            return false;
        }
        $urlAliasParams = [];
        parse_str($urlAliasQuerystring, $urlAliasParams);
        $params = [];
        parse_str($querystring, $params);
        if ($urlAlias->isAcceptAdditionalQuerystringParams() === false && count($urlAliasParams) !== count($params)) {
            return false;
        }
        foreach ($urlAliasParams as $name => $value) {
            if (!isset($params[$name]) || $params[$name] !== $value) {
                return false;
            }
        }

        return true;
    }
}
