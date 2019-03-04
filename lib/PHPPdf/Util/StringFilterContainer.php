<?php

declare(strict_types=1);

namespace PHPPdf\Util;

interface StringFilterContainer
{
    /**
     * @param StringFilter[] $filters
     */
    public function setStringFilters(array $filters): void;

    public function addStringFilter(StringFilter $filter): void;
}
