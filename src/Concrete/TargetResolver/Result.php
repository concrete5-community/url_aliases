<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases\TargetResolver;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @readonly
 */
final class Result
{
    /**
     * @var string
     */
    public $error;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $displayName;

    private function __construct(string $error, string $url, string $displayName)
    {
        $this->error = $error;
        $this->url = $url;
        $this->displayName = $displayName;
    }

    public static function success(string $url, string $displayName): self
    {
        return new self('', $url, $displayName);
    }

    public static function failure(string $error): self
    {
        return new self($error, '', '');
    }
}
