<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases\Entity;

defined('C5_EXECUTE') or die('Access Denied.');

interface Target
{
    public const TARGETTYPE_PAGE = 'page';

    public const TARGETTYPE_FILE = 'file';

    public const TARGETTYPE_EXTERNAL_URL = 'external_url';

    /**
     * Get the target type (see the TARGETTYPE constants).
     */
    public function getTargetType(): string;

    /**
     * Get the target value.
     */
    public function getTargetValue(): string;

    /**
     * Forward querystring parameters?
     */
    public function isForwardQuerystringParams(): bool;
}
