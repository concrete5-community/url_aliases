<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases;

use Concrete\Core\File\File;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Page;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Package\UrlAliases\Entity\Target;
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

    public function resolve(Target $target, ?Request $request = null): Result
    {
        try {
            switch ($target->getTargetType()) {
                case Target::TARGETTYPE_PAGE:
                    return $this->resolvePage($target, $request);
                    break;
                case Target::TARGETTYPE_FILE:
                    return $this->resolveFile($target, $request);
                case Target::TARGETTYPE_EXTERNAL_URL:
                    return $this->resolveExternalUrl($target, $request);
                default:
                    throw new RuntimeException(t('Unrecognized target type'));
            }
        } catch (Throwable $x) {
            return Result::failure($x->getMessage());
        }
    }

    private function resolvePage(Target $target, ?Request $request = null): Result
    {
        $pageID = (int) trim($target->getTargetValue());
        if ($pageID <= 0) {
            throw new RuntimeException(t('Invalid parameter: %s', 'pageID'));
        }
        $page = Page::getByID($pageID, 'ACTIVE');
        if (!$page || $page->isError()) {
            throw new RuntimeException(t('Unable to find the page with ID %s', $pageID));
        }

        return Result::success(
            $this->finalizeUrl((string) $this->pageUrlResolver->resolve([$page]), $target, $request),
            t('Page: %s', $page->isGeneratedCollection() ? t($page->getCollectionName()) : $page->getCollectionName())
        );
    }

    private function resolveFile(Target $target, ?Request $request = null): Result
    {
        $fileID = (int) trim($target->getTargetValue());
        if ($fileID <= 0) {
            throw new RuntimeException(t('Invalid parameter: %s', 'fileID'));
        }
        $file = File::getByID($fileID);
        $fileVersion = $file ? $file->getApprovedVersion() : null;
        if (!$fileVersion) {
            throw new RuntimeException(t('Unable to find the file with ID %s', $fileID));
        }

        return Result::success(
            $this->finalizeUrl((string) $fileVersion->getDownloadURL(), $target, $request),
            t('File: %s', $fileVersion->getFileName())
        );
    }

    private function resolveExternalUrl(Target $target, ?Request $request = null): Result
    {
        return Result::success(
            $this->finalizeUrl($target->getTargetValue(), $target, $request),
            t('External URL')
        );
    }

    private function finalizeUrl(string $targetUrl, Target $target, ?Request $request): string
    {
        switch ($target->getTargetType()) {
            case Target::TARGETTYPE_PAGE:
                if (($fragmentIdentifier = $target->getFragmentIdentifier()) !== '') {
                    $targetUrl .= '#' . $fragmentIdentifier;
                }
                break;
        }
        $components = parse_url($targetUrl);
        if ($components === false) {
            throw new RuntimeException(t('Failed to parse the URL "%s"', $targetUrl));
        }
        if ($target->isForwardQuerystringParams() === false || $request === null || $request->query->count() === 0) {
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
