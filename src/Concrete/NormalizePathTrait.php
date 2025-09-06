<?php

declare(strict_types=1);

namespace Concrete\Package\UrlAliases;

defined('C5_EXECUTE') or die('Access Denied.');

trait NormalizePathTrait
{
    /**
     * @param string|mixed $raw
     */
    protected function normalizePath($raw): string
    {
        if (!is_string($raw) || ($raw = trim($raw)) === '') {
            return '';
        }
        $rawChunks = preg_split('{/}', trim($raw), -1, PREG_SPLIT_NO_EMPTY);
        if ($rawChunks === []) {
            return '';
        }
        $decodedChunks = array_map('rawurldecode', $rawChunks);
        $lowerCaseDecodedChunks = array_map('mb_strtolower', $decodedChunks);
        $encodedChunks = array_map('rawurlencode', $lowerCaseDecodedChunks);

        return implode('/', $encodedChunks);
    }
}
