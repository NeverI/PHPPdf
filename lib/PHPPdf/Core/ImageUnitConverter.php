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
 * Base unit of this converter is pixel
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ImageUnitConverter extends AbstractUnitConverter
{
    /**
     * @var float
     */
    private $pixelPerUnits;

    /**
     * @var int
     */
    private $dpi;

    public function __construct(int $dpi = 96)
    {
        if ($dpi < 1) {
            throw new InvalidArgumentException(sprintf('Dpi must be positive integer, "%s" given.', $dpi));
        }

        $this->dpi = $dpi;
        $this->pixelPerUnits = (float) ($this->dpi / self::UNITS_PER_INCH);
    }

    public function convertUnit($value, $unit = null)
    {
        if (is_int($value)) {
            return (float) $value;
        }

        if (is_numeric($value) && is_string($value) && $unit === null) {
            $unit = self::UNIT_PDF;
        } else {
            $unit = $unit ?: strtolower(substr($value, -2, 2));
        }

        $value = $this->doConvertUnit($value, $unit);

        if (is_numeric($value)) {
            return (float) $value;
        } else {
            return $value;
        }
    }

    protected function convertInUnit($value)
    {
        return (float) $value * $this->dpi;
    }

    protected function convertPtUnit($value)
    {
        return (float) $value * $this->dpi / 72;
    }

    protected function convertPxUnit($value)
    {
        return (int) $value;
    }
}
