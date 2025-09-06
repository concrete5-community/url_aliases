<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases;

use Concrete\Core\Http\Middleware\DelegateInterface;
use Concrete\Core\Http\Middleware\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

defined('C5_EXECUTE') or die('Access Denied.');

final class Middleware implements MiddlewareInterface
{
    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Http\Middleware\MiddlewareInterface::process()
     */
    public function process(Request $request, DelegateInterface $frame)
    {
        $response = $frame->next($request);
        try {
            if ($this->isNotFoundResponse($response)) {
                $response = app(RequestResolver::class)->resolve($request, true) ?? $response;
            }
        } catch (Throwable $x) {
            try {
                app(LoggerInterface::class)->addError($x->getMessage(), ['exception' => $x]);
            } catch (Throwable $_) {
            }
        }

        return $response;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Response|mixed $response
     */
    private function isNotFoundResponse($response): bool
    {
        return $response instanceof Response && $response->getStatusCode() === Response::HTTP_NOT_FOUND;
    }
}
