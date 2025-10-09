<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases;

use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Validation\CSRF\Token;
use Concrete\Package\UrlAliases\Entity\Target;
use Concrete\Package\UrlAliases\Entity\UrlAlias;
use Concrete\Package\UrlAliases\Entity\UrlAlias\LocalizedTarget;
use Concrete\Package\UrlAliases\Entity\UrlAliasRepository;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

defined('C5_EXECUTE') or die('Access Denied.');

final class RequestResolver
{
    public const TESTFIELD_TOKEN = 'ua-testing_url_aliases_token';

    public const TESTFIELD_OVERRIDEACCEPTLANGUAGE = 'ua-testing_url_aliases_acceptlanguage';

    private const COMMON_RESPONSE_HEADERS = [
        'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
    ];

    /**
     * @var \Concrete\Package\UrlAliases\Entity\UrlAliasRepository
     */
    private $repo;

    /**
     * @var \Concrete\Core\Http\ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var \Concrete\Package\UrlAliases\TargetResolver
     */
    private $targetResolver;

    /**
     * @var \Concrete\Core\Validation\CSRF\Token
     */
    private $token;

    public function __construct(UrlAliasRepository $repo, ResponseFactoryInterface $responseFactory, TargetResolver $targetResolver, Token $token)
    {
        $this->repo = $repo;
        $this->responseFactory = $responseFactory;
        $this->targetResolver = $targetResolver;
        $this->token = $token;
    }

    /**
     * @throws \Throwable
     */
    public function resolve(Request $request, bool $hitIt = false): ?Response
    {
        if (!$this->isTesting($request)) {
            return $this->buildResponse($request, $hitIt, false);
        }
        try {
            $response = $this->buildResponse($request, false, true);
        } catch (Throwable $x) {
            $response = $this->buildTestingResponse($x->getMessage(), true);
        }
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        return $response;
    }

    private function buildResponse(Request $request, bool $hitIt, bool $isTesting): ?Response
    {
        $urlAliases = $this->repo->findByRequest($request, true, 2);
        switch (count($urlAliases)) {
            case 0:
                return $isTesting ? $this->buildTestingResponse(t('No alias matches the requested URL'), true) : null;
            case 1:
                $urlAlias = $urlAliases[0];
                break;
            default:
                if (!$isTesting) {
                    return null;
                }
                $message = t('More than one alias matches the requested URL.');
                $message .= "\n" . t('For examples, these two aliases match:');
                $message .= "\n- /" . $urlAliases[0]->getPathAndQuerystring();
                $message .= "\n- /" . $urlAliases[1]->getPathAndQuerystring();

                return $this->buildTestingResponse($message, true);
        }
        $target = $this->findUrlAliasTarget($urlAlias, $request, $isTesting);
        $resolved = $this->targetResolver->resolve($target, $request);
        if ($resolved->error !== '') {
            if ($isTesting) {
                return $this->buildTestingResponse($resolved->error, true);
            }
            throw new RuntimeException($resolved->error);
        }
        if ($isTesting) {
            return $this->buildTestingResponse(t('Users will be redirected to: %s', $resolved->url));
        }
        if ($target->isForwardPost() && $request->getMethod() === 'POST') {
            $response = $this->buildForwardPostResponse($resolved->url, $request);
        } else {
            $response = $this->buildRedirectResponse($resolved->url);
        }
        if ($hitIt) {
            $urlAlias->hit();
            $this->repo->getEntityManager()->flush();
        }

        return $response;
    }

    private function isTesting(Request $request): bool
    {
        if ($request->getMethod() !== 'POST') {
            return false;
        }
        $token = $request->request->get(self::TESTFIELD_TOKEN);
        if (!$token) {
            return false;
        }

        return $this->token->validate(self::TESTFIELD_TOKEN, $token);
    }

    private function buildTestingResponse(string $message, bool $error = false): Response
    {
        $charset = APP_CHARSET;
        $head = <<<EOT
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset={$charset}">
        <script>
        if (window.parent && window.parent !== window) {
            const from = window.parent.document.documentElement;
            const to = window.document.documentElement;
            if (from.lang) {
                to.lang = from.lang;
            }
            const theme = from.getAttribute('data-bs-theme');
            if (theme) {
                to.setAttribute('data-bs-theme', theme);
            }
            from.querySelectorAll('style, link[rel="stylesheet"]').forEach((el) => {
                document.write(el.outerHTML);
            });
        }
        </script>
        </head>
        EOT;
        $html = '<!DOCTYPE html><html>' . $head . '<body style="margin: 0; padding: 0"><div class="ccm-ui" style="margin: 0">';
        $html .= '<div class="alert ' . ($error ? 'alert-danger' : 'alert-success') . '" style="white-space: pre-wrap">' . h($message) . '</div>';
        $html .= '</div></body></html>';

        return $this->responseFactory->create($html, Response::HTTP_OK, ['Content-Type' => 'text/html; charset=' . $charset]);
    }

    private function findUrlAliasTarget(UrlAlias $urlAlias, Request $request, bool $isTesting): Target
    {
        $localizedTargets = $urlAlias->getLocalizedTargets()->toArray();
        if ($localizedTargets === []) {
            return $urlAlias;
        }
        if ($isTesting && $request->request->has(self::TESTFIELD_OVERRIDEACCEPTLANGUAGE)) {
            $locales = [(string) $request->request->get(self::TESTFIELD_OVERRIDEACCEPTLANGUAGE)];
        } else {
            $locales = $request->getLanguages();
        }
        foreach ($locales as $locale) {
            $localizedTarget = $this->findLocalizedTargetForLocale($localizedTargets, $locale);
            if ($localizedTarget !== null) {
                return $localizedTarget;
            }
        }

        return $urlAlias;
    }

    /**
     * @param \Concrete\Package\UrlAliases\Entity\UrlAlias\LocalizedTarget[] $localizedTargets
     */
    private function findLocalizedTargetForLocale(array $localizedTargets, string $locale): ?LocalizedTarget
    {
        [$language, $script, $territory] = $this->inspectLocale($locale);
        if ($language === '') {
            return null;
        }
        $localizedTargets = array_filter(
            $localizedTargets,
            static function (LocalizedTarget $localizedTarget) use ($language, $script, $territory): bool {
                if ($localizedTarget->getLanguage() !== $language) {
                    return false;
                }
                if (!in_array($localizedTarget->getScript(), ['*', $script], true)) {
                    return false;
                }
                if (!in_array($localizedTarget->getTerritory(), ['*', $territory], true)) {
                    return false;
                }

                return true;
            },
        );

        return array_shift($localizedTargets);
    }

    private function inspectLocale(string $locale): array
    {
        $territory = $script = $language = '';
        $rx = <<<'EOT'
        /^
        # language
        (?<language>[a-zA-Z]{2,3})
        # Next, optionally, the territory or the script
        (?:-(?<seg1>[A-Za-z]{2,4}|\d{3}))?
        # Next, optionally, the territory or the script
        (?:-(?<seg2>[A-Za-z]{2,4}|\d{3}))?
        (?:\W|$)
        /x
        EOT;
        $match = null;
        if (preg_match($rx, str_replace('_', '-', $locale), $match)) {
            $language = strtolower($match['language']);
            foreach ([$match['seg1'] ?? '', $match['seg2'] ?? ''] as $seg) {
                switch (strlen($seg)) {
                    case 0:
                        break;
                    case 2:
                    case 3:
                        if ($territory !== '') {
                            break 2;
                        }
                        $territory = strtoupper($seg);
                        break;
                    case 4:
                        if ($script !== '') {
                            break 2;
                        }
                        $script = ucfirst(strtolower($seg));
                        break;
                    default:
                        break 2;
                }
            }
        }

        return [$language, $script, $territory];
    }

    private function buildRedirectResponse(string $targetUrl): Response
    {
        return $this->responseFactory->redirect($targetUrl, Response::HTTP_TEMPORARY_REDIRECT, self::COMMON_RESPONSE_HEADERS);
    }

    private function buildForwardPostResponse(string $targetUrl, Request $request): Response
    {
        $charset = APP_CHARSET;
        $hTargetUrl = htmlspecialchars($targetUrl, ENT_QUOTES, APP_CHARSET);
        $html = <<<EOT
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="{$charset}" />
        </head>
        <body onload="document.forms[0].submit()">
            <form method="POST" action="{$hTargetUrl}">

        EOT;
        $renderFields = null;
        $renderFields = static function (array $data, string $namePrefix = '') use (&$renderFields, &$html): void {
            foreach ($data as $key => $value) {
                $key = (string) $key;
                $name = $namePrefix === '' ? $key : "{$namePrefix}[{$key}]";
                if (is_array($value)) {
                    $renderFields($value, $name);
                } else {
                    $escapedName = htmlspecialchars($name, ENT_QUOTES, APP_CHARSET);
                    $escapedValue = htmlspecialchars((string) $value, ENT_QUOTES, APP_CHARSET);
                    $html .= <<<EOT
                            <input type="hidden" name="{$escapedName}" value="{$escapedValue}" />

                    EOT;
                }
            }
        };
        $renderFields($request->request->all());
        $html .= <<<'EOT'
            </form>
        </body>
        </html>

        EOT;

        return new Response($html, Response::HTTP_OK, self::COMMON_RESPONSE_HEADERS);
    }
}
