<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases;

use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Validation\CSRF\Token;
use Concrete\Package\UrlAliases\Entity\UrlAliasRepository;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

defined('C5_EXECUTE') or die('Access Denied.');

final class RequestResolver
{
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
        $isTesting = $this->isTesting($request);
        if ($isTesting) {
            try {
                $response = $this->buildResponse($request, false, $isTesting);
            } catch (Throwable $x) {
                $response = $this->buildTestingResponse($x->getMessage(), true);
            }
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

            return $response;
        }

        return $this->buildResponse($request, $hitIt, $isTesting);
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
        $resolved = $this->targetResolver->resolveUrlAlias($urlAlias, $request);
        if ($resolved->error !== '') {
            if ($isTesting) {
                return $this->buildTestingResponse($resolved->error, true);
            }
            throw new RuntimeException($resolved->error);
        }
        if ($isTesting) {
            return $this->buildTestingResponse(t('Users will be redirected to: %s', $resolved->url));
        }
        $response = $this->responseFactory->redirect($resolved->url, Response::HTTP_FOUND);
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
        $token = $request->request->get('ua-testing_url_aliases_token');
        if (!$token) {
            return false;
        }

        return $this->token->validate('ua-testing_url_aliases_token', $token);
    }

    private function buildTestingResponse(string $message, bool $error = false): Response
    {
        $charset = APP_CHARSET;
        $head = <<<EOT
<head>
<meta http-equiv="Content-Type" content="text/html; charset={$charset}">
<script>
function closeDialog()
{
    window.frameElement.closest('dialog').close();
}

if (window.parent && window.parent !== window) {

    window.addEventListener('keydown', (e) => {
        if (e?.key === 'Escape') {
            closeDialog();
        }
    });

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
EOT
        ;
        $html = '<!DOCTYPE html><html>' . $head . '<body style="margin: 0; padding: 1rem"><div class="ccm-ui">';
        $html .= '<div class="alert ' . ($error ? 'alert-danger' : 'alert-success') . '" style="white-space: pre-wrap">' . h($message) . '</div>';
        $html .= '<br /><br/><div class="text-center"><button type="button" class="btn btn-primary" onclick="closeDialog()">' . t('Close') . '</div>';
        $html .= '</div></body></html>';

        return $this->responseFactory->create($html, Response::HTTP_OK, ['Content-Type' => 'text/html; charset=' . $charset]);
    }
}
