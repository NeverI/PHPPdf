<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Formatter;

use PHPPdf\Core\Formatter\BaseFormatter;
use PHPPdf\Core\Node as Nodes;
use PHPPdf\Core\Document;

/**
 * Calculates real dimension of compose node
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ContainerDimensionFormatter extends BaseFormatter
{
    public function format(Nodes\Node $node, Document $document)
    {
        $minX = $maxX = $minY = $maxY = null;
        foreach ($node->getChildren() as $child) {
            $firstPoint = $child->getFirstPoint();
            $diagonalPoint = $child->getDiagonalPoint();

            $childMinX = $firstPoint->getX() - (float) $child->getMarginLeft();
            $childMaxX = $diagonalPoint->getX() + (float) $child->getMarginRight();
            $childMinY = $diagonalPoint->getY() - (float) $child->getMarginBottom();
            $childMaxY = $firstPoint->getY() + (float) $child->getMarginTop();

            $maxX = $this->changeValueIfIsLess($maxX, $childMaxX);
            $maxY = $this->changeValueIfIsLess($maxY, $childMaxY);

            $minX = $this->changeValueIfIsGreater($minX, $childMinX);
            $minY = $this->changeValueIfIsGreater($minY, $childMinY);
        }

        $paddingVertical = (float) $node->getPaddingTop() + (float) $node->getPaddingBottom();
        $paddingHorizontal = (float) $node->getPaddingLeft() + (float) $node->getPaddingRight();

        $realHeight = $paddingVertical + ($maxY - $minY);
        $realWidth = $paddingHorizontal + ($maxX - $minX);

        if ($realHeight > $node->getHeight()) {
            $node->setHeight($realHeight);
        }

        if ($node->isInline() || $realWidth > $node->getWidth()) {
            $node->setWidth($realWidth);
        }
    }

    private function changeValueIfIsLess($value, $valueToSet)
    {
        if ($value === null || $value < $valueToSet) {
            return $valueToSet;
        }
        
        return $value;
    }

    private function changeValueIfIsGreater($value, $valueToSet)
    {
        if ($value === null || $value > $valueToSet) {
            return $valueToSet;
        }
        return $value;
    }
}
