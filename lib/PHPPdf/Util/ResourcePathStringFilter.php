<?php

declare(strict_types=1);

namespace PHPPdf\Util;

/**
 * Replaces %resources% string to path to Resources directory
 */
final class ResourcePathStringFilter implements StringFilter
{
    private $path;

    public function __construct(?string $path = null)
    {
        if ($path === null) {
            $path = str_replace('\\', '/', realpath(__DIR__.'/../Resources'));
        }

        $this->path = $path;
    }

    public function filter($value)
    {
        return str_replace('%resources%', $this->path, $value);
    }
}
