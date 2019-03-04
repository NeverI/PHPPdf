<?php

namespace PHPPdf\Stub\ComplexAttribute;

use PHPPdf\Core\Document;
use PHPPdf\Core\ComplexAttribute\ComplexAttribute;
use PHPPdf\Core\Node\Page;
use PHPPdf\Core\Node\Node;

class ComplexAttributeStub extends ComplexAttribute
{
    private $color;
    private $someParameter;
    
    public function __construct($color, $someParameter = null)
    {
        $this->color = $color;
        $this->someParameter = $someParameter;
    }

    protected function doEnhance($gc, Node $node, Document $document)
    {
    }
}
