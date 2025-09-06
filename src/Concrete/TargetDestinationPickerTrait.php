<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases;

defined('C5_EXECUTE') or die('Access Denied.');

trait TargetDestinationPickerTrait
{
    protected function getTargetDestinationPickerConfig(): array
    {
        return [
            'page',
            'file',
            'external_url',
        ];
    }
}
