<?php

declare(strict_types=1);

namespace PHPPdf\Util;

class AbstractStringFilterContainer implements StringFilterContainer
{
    /**
     * @var StringFilter[]
     */
    protected $stringFilters = [];

    /**
     * {@inheritdoc}
     */
    public function setStringFilters(array $filters): void
    {
        $this->stringFilters = [];

        foreach ($filters as $filter) {
            $this->addStringFilter($filter);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addStringFilter(StringFilter $filter): void
    {
        $this->stringFilters[] = $filter;
    }
}
