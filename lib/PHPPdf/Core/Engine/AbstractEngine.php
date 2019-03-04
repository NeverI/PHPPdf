<?php

declare(strict_types=1);

namespace PHPPdf\Core\Engine;

use PHPPdf\Core\UnitConverter;

/**
 * Abstract engine
 */
abstract class AbstractEngine implements Engine
{
    /**
     * @var null|UnitConverter
     */
    protected $unitConverter;

    public function __construct(?UnitConverter $unitConverter = null)
    {
        $this->unitConverter = $unitConverter;
    }

    public function convertUnit($value, $unit = null)
    {
        if ($this->unitConverter !== null) {
            return $this->unitConverter->convertUnit($value, $unit);
        }

        return (float) $value;
    }

    public function convertPercentageValue($percent, $value)
    {
        if ($this->unitConverter !== null) {
            return $this->unitConverter->convertPercentageValue($percent, $value);
        }

        return (float) $value;
    }
}
