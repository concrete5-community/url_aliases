<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases;

use Concrete\Core\Config\Repository\Repository;
use Concrete\Package\UrlAliases\Entity\NotFoundLogEntry;
use Concrete\Package\UrlAliases\Entity\NotFoundLogEntryRepository;
use Symfony\Component\HttpFoundation\Request;

defined('C5_EXECUTE') or die('Access Denied.');

final class NotFoundLogService
{
    use NormalizePathTrait { normalizePath as public; }

    /**
     * @var \Concrete\Core\Config\Repository\Repository
     */
    private $config;

    /**
     * @var \Concrete\Package\UrlAliases\Entity\NotFoundLogEntryRepository
     */
    private $notFoundLogEntryRepository;

    public function __construct(Repository $config, NotFoundLogEntryRepository $notFoundLogEntryRepository)
    {
        $this->config = $config;
        $this->notFoundLogEntryRepository = $notFoundLogEntryRepository;
    }

    public function deleteAll(): void
    {
        $em = $this->notFoundLogEntryRepository->getEntityManager();
        $metadata = $em->getClassMetadata(NotFoundLogEntry::class);
        $tableName = $metadata->getTableName();
        $cn = $em->getConnection();
        $sql = $cn->getDatabasePlatform()->getTruncateTableSQL($tableName);
        if (method_exists($cn, 'executeStatement')) {
            $cn->executeStatement($sql);
        } else {
            $cn->executeUpdate($sql);
        }
    }

    public function deleteOld(): int
    {
        $maxAge = (int) $this->config->get('url_aliases::options.log404.entryMaxAge');
        if ($maxAge <= 0) {
            return 0;
        }
        $em = $this->notFoundLogEntryRepository->getEntityManager();
        $metadata = $em->getClassMetadata(NotFoundLogEntry::class);
        $tableName = $metadata->getTableName();
        $columnName = $metadata->getColumnName('lastHit');
        $cn = $em->getConnection();
        $plat = $cn->getDatabasePlatform();
        $sql = 'DELETE FROM ' . $plat->quoteSingleIdentifier($tableName) . ' WHERE ' . $plat->quoteSingleIdentifier($columnName) . ' <= ' . $plat->getDateSubSecondsExpression($plat->getNowExpression(), $maxAge);

        return (int) (method_exists($cn, 'executeStatement') ? $cn->executeStatement($sql) : $cn->executeUpdate($sql));
    }

    public function log(Request $request): void
    {
        if (!$this->shouldLog($request)) {
            return;
        }
        $em = $this->notFoundLogEntryRepository->getEntityManager();
        $entity = $this->notFoundLogEntryRepository->findByRequest($request);
        if ($entity === null) {
            $entity = new NotFoundLogEntry($request, $this);
            $em->persist($entity);
        } else {
            $entity->hit();
        }

        $em->flush();
    }

    public function normalizeRequestMethod(Request $request): string
    {
        $method = $request->getMethod();
        $method = trim($method);
        $method = mb_substr($method, 0, 255);

        return rtrim($method);
    }

    public function normalizeRequestPath(Request $request): string
    {
        return $this->normalizePath($request->getBaseUrl() . $request->getPathInfo());
    }

    public function normalizeQuerystring(Request $request): string
    {
        $logQueryString = $this->config->get('url_aliases::options.log404.logQueryString');
        if ($logQueryString === false) {
            return '';
        }
        $params = $request->query->all();
        if ($params === []) {
            return '';
        }
        if (is_string($logQueryString)) {
            $allowList = preg_split('/[\r\n]+/', $logQueryString, -1, PREG_SPLIT_NO_EMPTY);
            if ($allowList !== []) {
                $keysToRemove = array_diff(array_keys($params), $allowList);
                foreach ($keysToRemove as $keyToRemove) {
                    unset($params[$keyToRemove]);
                }
            }
        }

        ksort($params);

        return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    private function shouldLog(Request $request): bool
    {
        if (!$this->config->get('url_aliases::options.log404.enabled')) {
            return false;
        }
        $rxList = preg_split('/[\r\n]+/', (string) $this->config->get('url_aliases::options.log404.excludePathRX.custom'), -1, PREG_SPLIT_NO_EMPTY);
        $useDefault = (bool) $this->config->get('url_aliases::options.log404.excludePathRX.useDefault');
        if ($rxList !== [] || $useDefault) {
            $path = $this->normalizeRequestPath($request);
            if ($useDefault) {
                if ($this->normalizedPathSatisfiesDefaultExcludePathRX($path)) {
                    return false;
                }
            }
            foreach ($rxList as $rx) {
                if (preg_match($rx, $path)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function normalizedPathSatisfiesDefaultExcludePathRX(string $normalizedPath): bool
    {
        if (!preg_match('{(?:^|/)index\.php(?:$|/|\?)}', $normalizedPath) && preg_match('{.+\.php(?:$|/)}', $normalizedPath)) {
            return true;
        }

        return false;
    }
}
