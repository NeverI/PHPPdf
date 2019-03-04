<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Formatter;

use PHPPdf\Core\Formatter\Formatter;
use PHPPdf\Core\Node as Nodes;
use PHPPdf\Core\Formatter\Chain;

/**
 * Sets chain to children nodes
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ContainerFormatter extends BaseFormatter
{
    public function format(Nodes\Node $node, \PHPPdf\Core\Document $document)
    {
        foreach ($node->getChildren() as $child) {
            $child->format($document);
        }
    }
}
