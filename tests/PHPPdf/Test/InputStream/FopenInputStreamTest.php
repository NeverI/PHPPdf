<?php

namespace PHPPdf\Test\InputStream;

use PHPPdf\InputStream\FopenInputStream;

class FopenInputStreamTest extends InputStreamTest
{
    protected function setUp(): void
    {
        $this->stream = new FopenInputStream(TEST_RESOURCES_DIR.'/test.txt', 'rb');
    }
}
