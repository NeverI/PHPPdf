<?php

declare(strict_types=1);

namespace PHPPdf\Util;

/**
 * Modifies and filters string variable
 */
interface StringFilter
{
    public function filter(string $value): string;
}
