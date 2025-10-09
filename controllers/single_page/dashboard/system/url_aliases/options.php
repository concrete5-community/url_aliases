<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases\Controller\SinglePage\Dashboard\System\UrlAliases;

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Package\UrlAliases\NotFoundLogService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

defined('C5_EXECUTE') or die('Access Denied.');

class Options extends DashboardPageController
{
    public function view(): ?Response
    {
        if (version_compare(APP_VERSION, '9') < 0) {
            $this->requireAsset('javascript', 'vue');
        }
        $config = $this->app->make(Repository::class);

        $this->set('log404Enabled', (bool) $config->get('url_aliases::options.log404.enabled'));
        $excludePathRXListCustom = preg_split('/[\r\n]+/', (string) $config->get('url_aliases::options.log404.excludePathRX.custom'), -1, PREG_SPLIT_NO_EMPTY);
        $this->set('log404ExcludePathRXCustom', $excludePathRXListCustom === [] ? '' : (implode("\n", $excludePathRXListCustom) . "\n"));
        $this->set('log404ExcludePathRXUseDefault', (bool) $config->get('url_aliases::options.log404.excludePathRX.useDefault'));
        $logQueryString = $config->get('url_aliases::options.log404.logQueryString');
        if (is_string($logQueryString)) {
            $logQueryString = preg_split('/[\r\n]+/', $logQueryString, -1, PREG_SPLIT_NO_EMPTY);
            if ($logQueryString === []) {
                $logQueryString = false;
            } else {
                $logQueryString = implode("\n", $logQueryString) . "\n";
            }
        } else {
            $logQueryString = (bool) $logQueryString;
        }
        $this->set('log404LogQueryString', $logQueryString);
        $this->set('log404entryMaxAge', (int) $config->get('url_aliases::options.log404.entryMaxAge'));
        $rootUrl = rtrim((string) $this->app->make(ResolverManagerInterface::class)->resolve(['/']), '/');
        if (ends_with($rootUrl, '/' . DISPATCHER_FILENAME)) {
            $rootUrl = substr($rootUrl, 0, -strlen(DISPATCHER_FILENAME));
        } else {
            $rootUrl .= '/';
        }
        $this->set('rootUrl', $rootUrl);

        return null;
    }

    public function testExlcudePathRXCustom(): JsonResponse
    {
        if (!$this->token->validate('ua-options-testexlcudepathrxcustom')) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $customRules = $this->request->request->get('customRules');
        if (!is_string($customRules)) {
            throw new UserMessageException(t('Invalid parameter value: %s', 'customRules'));
        }
        self::normalizeLog404ExcludePathRXCustom($customRules, $this->error, false);
        if ($this->error->has()) {
            throw new UserMessageException($this->error->toText());
        }

        return $this->app->make(ResponseFactoryInterface::class)->json(true);
    }

    public function testExlcudePathRX(): JsonResponse
    {
        if (!$this->token->validate('ua-options-testexlcudepathrx')) {
            throw new UserMessageException($this->token->getErrorMessage());
        }
        $path = $this->request->request->get('path');
        if (!is_string($path)) {
            throw new UserMessageException(t('Invalid parameter value: %s', 'path'));
        }
        foreach (['?', '#'] as $char) {
            if (str_contains($path, $char)) {
                throw new UserMessageException(t("The path can't contain the character '%s'", $char));
            }
        }
        $customRules = self::normalizeLog404ExcludePathRXCustom($this->request->request->get('customRules'), $this->error, false);
        if ($this->error->has()) {
            throw new UserMessageException($this->error->toText());
        }
        $service = $this->app->make(NotFoundLogService::class);
        $result = [
            'normalizedPath' => $service->normalizePath($path),
        ];
        $result['pathSatisfiesDefaultRules'] = $service->normalizedPathSatisfiesDefaultExcludePathRX($result['normalizedPath']);
        $result['pathSatisfiesCustomRules'] = false;
        foreach ($customRules as $customRule) {
            if (preg_match($customRule, $result['normalizedPath'])) {
                $result['pathSatisfiesCustomRules'] = true;
                break;
            }
        }

        return $this->app->make(ResponseFactoryInterface::class)->json($result);
    }

    public function save(): ?Response
    {
        if ($this->request->getMethod() !== 'POST') {
            return $this->buildRedirect('/dashboard/system/url-aliases/options');
        }
        if (!$this->token->validate('ua-options-save')) {
            $this->error->add($this->token->getErrorMessage());
        } else {
            $sets = [];
            $sets['log404.enabled'] = $this->request->request->getBoolean('log404Enabled');
            if ($sets['log404.enabled']) {
                $sets += [
                    'log404.excludePathRX.custom' => $this->parseLog404ExcludePathRXCustom(),
                    'log404.excludePathRX.useDefault' => $this->request->request->getBoolean('log404ExcludePathRXUseDefault'),
                    'log404.logQueryString' => $this->parseLog404LogQueryString(),
                    'log404.entryMaxAge' => $this->parseLog404EntryMaxAge(),
                ];
            }
        }
        if ($this->error->has()) {
            return $this->view();
        }

        $config = $this->app->make(Repository::class);
        foreach ($sets as $key => $value) {
            $config->save('url_aliases::options.' . $key, $value);
        }
        $this->flash('success', t('Options have been saved.'));

        return $this->buildRedirect([$this->getPageObject()]);
    }

    private function parseLog404ExcludePathRXCustom(): string
    {
        $raw = $this->request->request->get('log404ExcludePathRXCustom');

        return implode("\n", self::normalizeLog404ExcludePathRXCustom($raw, $this->error, true));
    }

    /**
     * @param string|mixed $raw
     *
     * @return string[]
     */
    private static function normalizeLog404ExcludePathRXCustom($raw, ErrorList $errors, bool $htmlErrors): array
    {
        if (!is_string($raw)) {
            return [];
        }
        $rxList = [];
        $whyNot = '';
        set_error_handler(
            static function ($errno, $errstr) use (&$whyNot): void {
                if ($whyNot === '' && is_string($errstr)) {
                    $whyNot = preg_replace('/^preg_match\(\):?\s*/', '', trim($errstr));
                }
            },
            -1,
        );
        try {
            foreach (preg_split('/^\s+|\s*[\r\n]+\s*|\s+$/', $raw, -1, PREG_SPLIT_NO_EMPTY) as $rx) {
                if (in_array($rx, $rxList, true)) {
                    continue;
                }
                $rxList[] = $rx;
                $whyNot = '';
                if (preg_match($rx, 'foo') === false) {
                    $message = t('The following regular expression is not valid: %s', $htmlErrors ? '<code>' . h($rx) . '</code>' : $rx);
                    if ($whyNot !== '') {
                        if ($htmlErrors) {
                            $message .= '<br />' . t('Reason: %s', nl2br(h($whyNot)));
                        } else {
                            $message .= "\n" . t('Reason: %s', $whyNot);
                        }
                    }
                    $errors->addError($message, $htmlErrors);
                }
            }
        } finally {
            restore_error_handler();
        }

        return $rxList;
    }

    /**
     * @return bool|string
     */
    private function parseLog404LogQueryString()
    {
        $which = $this->request->request->get('log404LogQueryString');
        if ($which === 'none') {
            return false;
        }
        if ($which === 'all') {
            return true;
        }
        if ($which !== 'some') {
            $this->error->add(t('Please specify if you want to log the querystring parameters'));

            return '';
        }
        $raw = $this->request->request->get('log404LogQueryStringList');
        if (!is_string($raw)) {
            return false;
        }
        $parameterNames = preg_split('/[\r\n]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);

        return $parameterNames === [] ? false : implode("\n", array_unique($parameterNames));
    }

    private function parseLog404EntryMaxAge(): int
    {
        $which = $this->request->request->get('log404entryMaxAge');
        if ($which === 'none') {
            return 0;
        }
        if ($which !== 'set') {
            $this->error->add(t('Please specify if you want to automatically delete old entries'));

            return 0;
        }

        return 0
            + 86400 * max(0, $this->request->request->getInt('log404entryMaxAge-days'))
            + 3600 * max(0, $this->request->request->getInt('log404entryMaxAge-hours'))
            + 60 * max(0, $this->request->request->getInt('log404entryMaxAge-mintes'))
            + 1 * max(0, $this->request->request->getInt('log404entryMaxAge-seconds'));
    }
}
