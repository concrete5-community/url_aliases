<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases;

use Concrete\Core\File\File;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Page;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Package\UrlAliases\Entity\UrlAlias;
use Concrete\Package\UrlAliases\TargetResolver\Result;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

defined('C5_EXECUTE') or die('Access Denied.');

final class TargetResolver
{
    /**
     * @var \Concrete\Core\Url\Resolver\UrlResolverInterface
     */
    private $pageUrlResolver;

    public function __construct(ResponseFactoryInterface $responseFactory, ResolverManagerInterface $urlResolver)
    {
        $this->responseFactory = $responseFactory;
        $this->pageUrlResolver = $urlResolver->getResolver('concrete.page');
    }

    public function resolveUrlAlias(UrlAlias $urlAlias, ?Request $request = null): Result
    {
        try {
            switch ($urlAlias->getTargetType()) {
                case UrlAlias::TARGETTYPE_PAGE:
                    return $this->resolvePage($urlAlias, $request);
                    break;
                case UrlAlias::TARGETTYPE_FILE:
                    return $this->resolveFile($urlAlias, $request);
                case UrlAlias::TARGETTYPE_EXTERNAL_URL:
                    return $this->resolveExternalUrl($urlAlias, $request);
                default:
                    throw new RuntimeException(t('Unrecognized target type'));
            }
        } catch (Throwable $x) {
            return Result::failure($x->getMessage());
        }
    }

    private function resolvePage(UrlAlias $urlAlias, ?Request $request = null): Result
    {
        $pageID = (int) trim($urlAlias->getTargetValue());
        if ($pageID <= 0) {
            throw new RuntimeException(t('Invalid parameter: %s', 'pageID'));
        }
        $page = Page::getByID($pageID, 'ACTIVE');
        if (!$page || $page->isError()) {
            throw new RuntimeException(t('Unable to find the page with ID %s', $pageID));
        }

        return Result::success(
            $this->finalizeUrl((string) $this->pageUrlResolver->resolve([$page]), $urlAlias, $request),
            t('Page: %s', $page->isGeneratedCollection() ? t($page->getCollectionName()) : $page->getCollectionName())
        );
    }

    private function resolveFile(UrlAlias $urlAlias, ?Request $request = null): Result
    {
        $fileID = (int) trim($urlAlias->getTargetValue());
        if ($fileID <= 0) {
            throw new RuntimeException(t('Invalid parameter: %s', 'fileID'));
        }
        $file = File::getByID($fileID);
        $fileVersion = $file ? $file->getApprovedVersion() : null;
        if (!$fileVersion) {
            throw new RuntimeException(t('Unable to find the file with ID %s', $fileID));
        }

        return Result::success(
            $this->finalizeUrl((string) $fileVersion->getDownloadURL(), $urlAlias, $request),
            t('File: %s', $fileVersion->getFileName())
        );
    }

    private function resolveExternalUrl(UrlAlias $urlAlias, ?Request $request = null): Result
    {
        return Result::success(
            $this->finalizeUrl($urlAlias->getTargetValue(), $urlAlias, $request),
            t('External URL')
        );
    }

    private function finalizeUrl(string $targetUrl, UrlAlias $urlAlias, ?Request $request): string
    {
        $components = parse_url($targetUrl);
        if ($components === false) {
            throw new RuntimeException(t('Failed to parse the URL "%s"', $targetUrl));
        }
        if ($urlAlias->isForwardQuerystringParams() === false || $request === null || $request->query->count() === 0) {
            return $targetUrl;
        }
        if (empty($components['query'] ?? '')) {
            $components['query'] = $request->getQueryString();
        } else {
            $params = [];
            parse_str($components['query'], $params);
            $params += $request->query->all();
            $components['query'] = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }
        $result = '';
        if (isset($components['scheme'])) {
            $result .= $components['scheme'] . ':';
            if (!in_array(strtolower($components['scheme']), ['mailto', 'news', 'urn', 'tel', 'data'], true)) {
                $result .= '//';
            }
        }
        if (isset($components['user'])) {
            $result .= $components['user'];
            if (isset($components['pass'])) {
                $result .= ':' . $components['pass'];
            }
            $result .= '@';
        }
        $result .= $components['host'] ?? '';
        if (isset($components['port'])) {
            $result .= ':' . $components['port'];
        }
        $result .= $components['path'] ?? '';
        if (isset($components['query'])) {
            $result .= '?' . $components['query'];
        }
        if (isset($components['fragment'])) {
            $result .= '#' . $components['fragment'];
        }

        return $result;
    }
}
