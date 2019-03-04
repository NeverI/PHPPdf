<?php

declare(strict_types=1);

namespace PHPPdf;

use PHPPdf\Exception\BadMethodCallException;

/**
 * Current version of this library
 */
final class Version
{
    public const VERSION = '1.3.0-DEV';

    private function __construct()
    {
        throw new BadMethodCallException(sprintf('Object of "%s" class can not be created.', __CLASS__));
    }
}
