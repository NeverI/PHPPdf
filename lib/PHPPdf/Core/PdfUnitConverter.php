<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core;

use PHPPdf\Exception\InvalidArgumentException;

/**
 * Unit converter
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class PdfUnitConverter extends AbstractUnitConverter
{
    /**
     * @var int
     */
    private $dpi;

    /**
     * @var float
     */
    private $unitsPerPixel;

    public function __construct(int $dpi = 96)
    {
        if ($dpi < 1) {
            throw new InvalidArgumentException(sprintf('Dpi must be positive integer, "%s" given.', $dpi));
        }

        $this->dpi = $dpi;
        $this->unitsPerPixel = (float) (self::UNITS_PER_INCH / $this->dpi);
    }

    public function convertUnit($value, $unit = null)
    {
        if (is_numeric($value) && $unit === null) {
            return (float) $value;
        }

        $unit = $unit ?: strtolower(substr($value, -2, 2));

        return $this->doConvertUnit($value, $unit);
    }

    protected function convertPxUnit($value)
    {
        return (float) $value * $this->unitsPerPixel;
    }

    protected function convertInUnit($value)
    {
        return ((float) $value) * self::UNITS_PER_INCH;
    }

    protected function convertPtUnit($value)
    {
        return (float) $value;
    }

    protected function convertMmUnit($value)
    {
        return $this->convertInUnit($value) / self::MM_PER_INCH;
    }
}
