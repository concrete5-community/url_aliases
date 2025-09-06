<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases\Controller\SinglePage\Dashboard\System;

use Concrete\Core\Error\UserMessageException;
use Concrete\Core\File\File;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Localization\Localization;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Page\Page;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Core\Validation\CSRF\Token;
use Concrete\Package\UrlAliases\Entity\UrlAlias;
use Concrete\Package\UrlAliases\Entity\UrlAliasRepository;
use Concrete\Package\UrlAliases\NormalizePathTrait;
use Concrete\Package\UrlAliases\TargetResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

defined('C5_EXECUTE') or die('Access Denied.');

class UrlAliases extends DashboardPageController
{
    use NormalizePathTrait;

    /**
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function view(): ?Response
    {
        if (version_compare(APP_VERSION, '9') < 0) {
            $this->requireAsset('javascript', 'vue');
            $this->addHeaderItem('<style>[v-cloak] { display: none !important; }</style>');
        }
        $this->addHeaderItem(
            <<<'EOT'
<style>
.ua-loading-icon {
    width: 1em;
    height: 1em;
    border-width: min(max(0.15em, 1px), 5px);
    border-style: solid;
    border-color: #fff;
    border-bottom-color: #ff3d00;
    border-radius: 50%;
    display: inline-block;
    box-sizing: border-box;
    animation: ua-loading-icon-keyframes 1s linear infinite;
}
@keyframes ua-loading-icon-keyframes {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}
</style>
EOT
        );
        $this->set('token', $this->app->make(Token::class));
        $this->set('currentLocale', $this->app->make(Localization::class)->getLocale());
        $this->set('rootUrl', rtrim((string) $this->app->make(ResolverManagerInterface::class)->resolve(['/']), '/') . '/');
        $this->set('urlAliases', $this->serializeAllAliases());

        return null;
    }

    public function autoRefresh(): JsonResponse
    {
        $token = $this->app->make(Token::class);
        if (!$token->validate('ua-autorefresh')) {
            throw new UserMessageException($token->getErrorMessage());
        }

        return $this->app->make(ResponseFactoryInterface::class)->json($this->serializeAllAliases());
    }

    public function saveUrlAlias(): JsonResponse
    {
        $token = $this->app->make(Token::class);
        $em = $this->app->make(EntityManagerInterface::class);
        if (!$token->validate('ua-urlalias-save')) {
            throw new UserMessageException($token->getErrorMessage());
        }
        $post = $this->request->request;
        if ($post->get('id') === 'new') {
            $urlAlias = new UrlAlias();
        } else {
            $id = $post->getInt('id');
            $urlAlias = $id ? $em->find(UrlAlias::class, $id) : null;
            if ($urlAlias === null) {
                throw new UserMessageException(t('Unable to find the requested alias'));
            }
        }
        [$targetType, $targetValue] = $this->normalizeTarget($post->get('targetType'), $post->get('targetValue'));
        $urlAlias
            ->setPath($this->normalizePath($post->get('path')))
            ->setQuerystring($this->normalizeQuerystring($post->get('querystring')))
            ->setTargetType($targetType)
            ->setTargetValue($targetValue)
            ->setEnabled($post->getBoolean('enabled'))
            ->setAcceptAdditionalQuerystringParams($post->getBoolean('acceptAdditionalQuerystringParams'))
            ->setForwardQuerystringParams($post->getBoolean('forwardQuerystringParams'))
        ;
        if ($urlAlias->getPath() === '') {
            throw new UserMessageException(t('Please specify the path of the alias Url'));
        }
        if ($urlAlias->isEnabled()) {
            $this->checkClashes($urlAlias);
        }
        if ($urlAlias->getID() === null) {
            $em->persist($urlAlias);
        }
        $em->flush();

        return $this->app->make(ResponseFactoryInterface::class)->json(
            $this->serializeAlias($urlAlias, $this->buildSerializationServices())
        );
    }

    public function setUrlAliasEnabled(): JsonResponse
    {
        $token = $this->app->make(Token::class);
        if (!$token->validate('ua-urlalias-setenabled')) {
            throw new UserMessageException($token->getErrorMessage());
        }
        $em = $this->app->make(EntityManagerInterface::class);
        $id = $this->request->request->getInt('id');
        $urlAlias = $id ? $em->find(UrlAlias::class, $id) : null;
        if ($urlAlias === null) {
            throw new UserMessageException(t('Unable to find the requested alias'));
        }
        $enable = $this->request->request->getBoolean('enable');
        if ($enable) {
            $this->checkClashes($urlAlias);
        }
        $urlAlias->setEnabled($enable);
        $em->flush();

        return $this->app->make(ResponseFactoryInterface::class)->json(['enabled' => $enable]);
    }

    public function deleteUrlAlias(): JsonResponse
    {
        $token = $this->app->make(Token::class);
        if (!$token->validate('ua-urlalias-delete')) {
            throw new UserMessageException($token->getErrorMessage());
        }
        $em = $this->app->make(EntityManagerInterface::class);
        $id = $this->request->request->getInt('id');
        $urlAlias = $id ? $em->find(UrlAlias::class, $id) : null;
        if ($urlAlias === null) {
            throw new UserMessageException(t('Unable to find the requested alias'));
        }
        $em->remove($urlAlias);
        $em->flush();

        return $this->app->make(ResponseFactoryInterface::class)->json(true);
    }

    private function serializeAllAliases(): array
    {
        $em = $this->app->make(EntityManagerInterface::class);
        $repo = $em->getRepository(UrlAlias::class);
        $services = $this->buildSerializationServices();
        $result = [];
        foreach ($repo->findAll() as $urlAlias) {
            $result[] = $this->serializeAlias($urlAlias, $services);
        }

        return $result;
    }

    private function serializeAlias(UrlAlias $urlAlias, array $services): array
    {
        $result = [
            'id' => $urlAlias->getID(),
            'createdOn' => ($d = $urlAlias->getCreatedOn()) === null ? null : $d->getTimestamp(),
            'path' => $urlAlias->getPath(),
            'pathAndQuerystring' => $urlAlias->getPathAndQuerystring(),
            'querystring' => $urlAlias->getQuerystring(),
            'acceptAdditionalQuerystringParams' => $urlAlias->isAcceptAdditionalQuerystringParams(),
            'enabled' => $urlAlias->isEnabled(),
            'targetType' => $urlAlias->getTargetType(),
            'targetValue' => $urlAlias->getTargetValue(),
            'forwardQuerystringParams' => $urlAlias->isForwardQuerystringParams(),
            'firstHit' => ($d = $urlAlias->getFirstHit()) === null ? null : $d->getTimestamp(),
            'lastHit' => ($d = $urlAlias->getLastHit()) === null ? null : $d->getTimestamp(),
            'hitCount' => $urlAlias->getHitCount(),
            'targetInfo' => $services['targetResolver']->resolveUrlAlias($urlAlias),
        ];

        return $result;
    }

    private function buildSerializationServices(): array
    {
        return [
            'targetResolver' => $this->app->make(TargetResolver::class),
        ];
    }

    /**
     * @throws \Concrete\Core\Error\UserMessageException
     */
    private function checkClashes(UrlAlias $urlAlias): void
    {
        $repo = $this->app->make(UrlAliasRepository::class);
        for ($cycle = 1; $cycle <= 2; $cycle++) {
            switch ($cycle) {
                case 1:
                    $matched = $repo->findByPathAndQuerysring($urlAlias->getPath(), $urlAlias->getQuerystring(), true, 2);
                    break;
                case 2:
                    if ($urlAlias->getQuerystring() !== '') {
                        continue 2;
                    }
                    $matched = $repo->findBy(['path' => $urlAlias->getPath(), 'enabled' => 1]);
                    break;
            }
            while (($existing = array_pop($matched)) !== null) {
                if ($existing === $urlAlias) {
                    continue;
                }
                throw new UserMessageException(t("You can't enable this alias since at least one other alias would intercept the same requests (for example, the alias with \"%s\")", '/' . $existing->getPathAndQuerystring()));
            }
        }
    }

    private function normalizeQuerystring($raw): string
    {
        return is_string($raw) ? ltrim(trim($raw), '?') : '';
    }

    private function normalizeTarget(?string $targetType, $targetValue): array
    {
        switch ($targetType ?? '') {
            case UrlAlias::TARGETTYPE_PAGE:
                return [UrlAlias::TARGETTYPE_PAGE, (string) $this->normalizeTargetPage($targetValue)];
            case UrlAlias::TARGETTYPE_FILE:
                return [UrlAlias::TARGETTYPE_FILE, (string) $this->normalizeTargetFile($targetValue)];
            case UrlAlias::TARGETTYPE_EXTERNAL_URL:
                return [UrlAlias::TARGETTYPE_EXTERNAL_URL, $this->normalizeTargetExternalUrl($targetValue)];
            default:
                throw new UserMessageException(t('Unrecognized target type'));
        }
    }

    private function normalizeTargetPage($raw): int
    {
        $pageID = is_numeric($raw) ? (int) $raw : 0;
        if ($pageID <= 0) {
            throw new UserMessageException(t('Please specify the target page'));
        }
        $page = Page::getByID($pageID, 'ACTIVE');
        if (!$page || $page->isError()) {
            throw new UserMessageException(t('Unable to find the page with ID %s', $pageID));
        }

        return $pageID;
    }

    private function normalizeTargetFile($raw): int
    {
        $fileID = is_numeric($raw) ? (int) $raw : 0;
        if ($fileID <= 0) {
            throw new UserMessageException(t('Please specify the target file'));
        }
        $file = File::getByID($fileID);
        $fileVersion = $file ? $file->getApprovedVersion() : null;
        if (!$fileVersion) {
            throw new UserMessageException(t('Unable to find the file with ID %s', $fileID));
        }

        return $fileID;
    }

    private function normalizeTargetExternalUrl($raw): string
    {
        $externalUrl = is_string($raw) ? trim($raw) : '';
        if ($externalUrl === '') {
            throw new UserMessageException(t('Please specify the external URL'));
        }

        return $externalUrl;
    }
}
