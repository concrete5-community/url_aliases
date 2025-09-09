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
use Concrete\Package\UrlAliases\Entity\Target;
use Concrete\Package\UrlAliases\Entity\UrlAlias;
use Concrete\Package\UrlAliases\Entity\UrlAlias\LocalizedTarget;
use Concrete\Package\UrlAliases\Entity\UrlAliasRepository;
use Concrete\Package\UrlAliases\NormalizePathTrait;
use Concrete\Package\UrlAliases\TargetResolver;
use Doctrine\ORM\EntityManagerInterface;
use Punic;
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
        $this->set('acceptLanguageDictionaries', $this->getAcceptLanguageDictionaries());
        $this->set('currentAcceptLanguageHeader', $this->request->headers->get('Accept-Language', '') ?? '');
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
            ->setFragmentIdentifier($this->normalizeFragmentIdentifier($post->get('fragmentIdentifier')))
            ->setEnabled($post->getBoolean('enabled'))
            ->setAcceptAdditionalQuerystringParams($post->getBoolean('acceptAdditionalQuerystringParams'))
            ->setForwardQuerystringParams($post->getBoolean('forwardQuerystringParams'))
            ->setForwardPost($post->getBoolean('forwardPost'))
        ;
        if ($urlAlias->getPath() === '') {
            throw new UserMessageException(t('Please specify the path of the alias Url'));
        }
        if ($urlAlias->isEnabled()) {
            $this->checkUrlAliasClashes($urlAlias);
        }
        if ($urlAlias->getID() === null) {
            $em->persist($urlAlias);
        }
        $em->flush();

        return $this->app->make(ResponseFactoryInterface::class)->json(
            $this->serializeUrlAlias($urlAlias, $this->buildSerializationServices())
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
            $this->checkUrlAliasClashes($urlAlias);
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

    public function saveLocalizedTarget(): JsonResponse
    {
        $token = $this->app->make(Token::class);
        $em = $this->app->make(EntityManagerInterface::class);
        if (!$token->validate('ua-localizedtarget-save')) {
            throw new UserMessageException($token->getErrorMessage());
        }
        $post = $this->request->request;

        $urlAliasID = $post->getInt('urlAlias');
        $urlAlias = $urlAliasID ? $em->find(UrlAlias::class, $urlAliasID) : null;
        if ($urlAlias === null) {
            throw new UserMessageException(t('Unable to find the requested alias'));
        }
        $id = $post->get('id');
        if ($id === 'new') {
            $localizedTarget = new LocalizedTarget($urlAlias);
        } else {
            $id = (int) $id;
            $localizedTarget = $id ? $em->find(LocalizedTarget::class, $id) : null;
            if ($localizedTarget === null || $localizedTarget->getUrlAlias() !== $urlAlias) {
                throw new UserMessageException(t('Unable to find the requested Target by browser language'));
            }
        }
        [$targetType, $targetValue] = $this->normalizeTarget($post->get('targetType'), $post->get('targetValue'));
        $localizedTarget
            ->setLanguage(trim($post->get('language', '')))
            ->setScript(trim($post->get('script', '')))
            ->setTerritory(trim($post->get('territory', '')))
            ->setTargetType($targetType)
            ->setTargetValue($targetValue)
            ->setFragmentIdentifier($this->normalizeFragmentIdentifier($post->get('fragmentIdentifier')))
        ;
        if ($localizedTarget->getLanguage() === '') {
            throw new UserMessageException(t('Please specify the language'));
        }
        $this->checkLocalizedTargetClashes($localizedTarget);
        if ($localizedTarget->getID() === null) {
            $urlAlias->getLocalizedTargets()->add($localizedTarget);
            $em->persist($urlAlias);
        }
        $em->flush();

        return $this->app->make(ResponseFactoryInterface::class)->json(
            $this->serializeUrlAlias($urlAlias, $this->buildSerializationServices())
        );
    }

    public function deleteLocalizedTarget(): JsonResponse
    {
        $token = $this->app->make(Token::class);
        if (!$token->validate('ua-localizedtarget-delete')) {
            throw new UserMessageException($token->getErrorMessage());
        }
        $em = $this->app->make(EntityManagerInterface::class);
        $urlAliasID = $this->request->request->getInt('urlAlias');
        $urlAlias = $urlAliasID ? $em->find(UrlAlias::class, $urlAliasID) : null;
        if ($urlAlias === null) {
            throw new UserMessageException(t('Unable to find the requested alias'));
        }
        $id = $this->request->request->getInt('id');
        $localizedTarget = $id ? $em->find(LocalizedTarget::class, $id) : null;
        if ($localizedTarget === null) {
            throw new UserMessageException(t('Unable to find the requested Target by browser language'));
        }
        $urlAlias->getLocalizedTargets()->removeElement($localizedTarget);
        $em->remove($localizedTarget);
        $em->flush();

        return $this->app->make(ResponseFactoryInterface::class)->json(
            $this->serializeUrlAlias($urlAlias, $this->buildSerializationServices())
        );
    }

    private function serializeAllAliases(): array
    {
        $repo = $this->app->make(UrlAliasRepository::class);
        $services = $this->buildSerializationServices();
        $qb = $repo->createQueryBuilder('ua');
        $qb
            ->leftJoin('ua.localizedTargets', 'lt')
            ->addSelect('lt')
        ;
        $result = [];
        foreach ($qb->getQuery()->getResult() as $urlAlias) {
            $result[] = $this->serializeUrlAlias($urlAlias, $services);
        }

        return $result;
    }

    private function serializeUrlAlias(UrlAlias $urlAlias, array $services): array
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
            'fragmentIdentifier' => $urlAlias->getFragmentIdentifier(),
            'forwardQuerystringParams' => $urlAlias->isForwardQuerystringParams(),
            'forwardPost' => $urlAlias->isForwardPost(),
            'firstHit' => ($d = $urlAlias->getFirstHit()) === null ? null : $d->getTimestamp(),
            'lastHit' => ($d = $urlAlias->getLastHit()) === null ? null : $d->getTimestamp(),
            'hitCount' => $urlAlias->getHitCount(),
            'targetInfo' => $services['targetResolver']->resolve($urlAlias),
            'localizedTargets' => array_map(
                function (UrlAlias\LocalizedTarget $localizedTarget) use ($services): array {
                    return $this->serializeLocalizedTarget($localizedTarget, $services);
                },
                $urlAlias->getLocalizedTargets()->toArray(),
            ),
        ];

        return $result;
    }

    private function serializeLocalizedTarget(UrlAlias\LocalizedTarget $localizedTarget, array $services): array
    {
        return [
            'id' => $localizedTarget->getID(),
            'language' => $localizedTarget->getLanguage(),
            'script' => $localizedTarget->getScript(),
            'territory' => $localizedTarget->getTerritory(),
            'targetType' => $localizedTarget->getTargetType(),
            'targetValue' => $localizedTarget->getTargetValue(),
            'fragmentIdentifier' => $localizedTarget->getFragmentIdentifier(),
            'targetInfo' => $services['targetResolver']->resolve($localizedTarget),
        ];
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
    private function checkUrlAliasClashes(UrlAlias $urlAlias): void
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

    /**
     * @throws \Concrete\Core\Error\UserMessageException
     */
    private function checkLocalizedTargetClashes(LocalizedTarget $localizedTarget): void
    {
        foreach ($localizedTarget->getUrlAlias()->getLocalizedTargets() as $lt) {
            if ($localizedTarget === $lt) {
                continue;
            }
            if ($localizedTarget->getLanguage() !== $lt->getLanguage()) {
                continue;
            }
            $scriptClash = $localizedTarget->getScript() === $lt->getScript() || in_array('*', [$localizedTarget->getScript(), $lt->getScript()], true);
            $territoryClash = $localizedTarget->getTerritory() === $lt->getTerritory() || in_array('*', [$localizedTarget->getTerritory(), $lt->getTerritory()], true);
            if ($scriptClash && $territoryClash) {
                throw new UserMessageException(
                    implode("\n", [
                        t('This Target by browser language satisfies the same browser language as the other existing Target by browser language with:'),
                        t('Script: %s', $lt->getScript() === '*' ? tc('Script', 'Any') : ($lt->getScript() === '' ? tc('Script', 'None') : $lt->getScript())),
                        t('Territory: %s', $lt->getTerritory() === '*' ? tc('Territory', 'Any') : ($lt->getTerritory() === '' ? tc('Territory', 'None') : $lt->getTerritory())),
                    ])
                );
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
            case Target::TARGETTYPE_PAGE:
                return [Target::TARGETTYPE_PAGE, (string) $this->normalizeTargetPage($targetValue)];
            case Target::TARGETTYPE_FILE:
                return [Target::TARGETTYPE_FILE, (string) $this->normalizeTargetFile($targetValue)];
            case Target::TARGETTYPE_EXTERNAL_URL:
                return [Target::TARGETTYPE_EXTERNAL_URL, $this->normalizeTargetExternalUrl($targetValue)];
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

    /**
     * @param string|mixed $raw
     */
    private function normalizeFragmentIdentifier($raw): string
    {
        if (!is_string($raw) || ($raw = trim($raw)) === '' || $raw === '#') {
            return '';
        }
        if ($raw[0] === '#') {
            $raw = substr($raw, 1);
        }
        if (!preg_match('/^[A-Za-z][A-Za-z0-9\-_:.]*$/', $raw)) {
            throw new UserMessageException(t('The name of an anchor must start with a letter, and may be followed by any number of letters, digits, hyphens, underscores, colons, and periods.'));
        }

        return $raw;
    }

    private function getAcceptLanguageDictionaries(): array
    {
        $result = [
            'LANGUAGES' => [],
            'SCRIPTS' => [],
            'CONTINENTS' => [],
        ];
        foreach (Punic\Language::getAll(true, true) as $code => $name) {
            $result['LANGUAGES'][] = ['code' => $code, 'name' => $name];
        }
        foreach (Punic\Script::getAllScripts() as $code => $name) {
            $result['SCRIPTS'][] = ['code' => $code, 'name' => $name];
        }
        foreach (Punic\Territory::getContinentsAndCountries() as $data) {
            $continent = [
                'name' => $data['name'],
                'territories' => [],
            ];
            foreach ($data['children'] as $code => $info) {
                $continent['territories'][] = ['code' => $code, 'name' => $info['name']];
            }
            $result['CONTINENTS'][] = $continent;
        }

        return $result;
    }
}
