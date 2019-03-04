<?php

namespace PHPPdf\Test\Core\Node;

use PHPPdf\Core\Engine\ZF\Engine;

use PHPPdf\Core\DrawingTaskHeap;

use PHPPdf\Core\Node\Node;
use PHPPdf\Core\DrawingTask;
use PHPPdf\Core\Document;
use PHPPdf\Core\Node\Page;
use PHPPdf\Core\Node\PageContext;
use PHPPdf\Core\Node\DynamicPage;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Boundary;

class PageTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    /**
     * @var Page
     */
    private $page;

    /**
     * @var Document
     */
    private $document;

    protected function setUp(): void
    {
        $this->page = new Page();
        $this->document = new Document(new Engine());
    }

    /**
     * @test
     */
    public function drawing()
    {
        $tasks = new DrawingTaskHeap();
        $this->page->collectOrderedDrawingTasks($this->document, $tasks);

        foreach ($tasks as $task) {
            $task->invoke();
        }
    }

    /**
     * @test
     * @expectedException PHPPdf\Core\Exception\DrawingException
     */
    public function failureDrawing()
    {
        $child = $this->getMock('\PHPPdf\Core\Node\Node', ['doDraw']);
        $child->expects($this->any())
            ->method('doDraw')
            ->will($this->throwException(new \Exception('exception')));

        $this->page->add($child);

        $tasks = new DrawingTaskHeap();
        $this->page->collectOrderedDrawingTasks($this->document, $tasks);

        foreach ($tasks as $task) {
            $task->invoke();
        }
    }

    /**
     * @test
     */
    public function attributes()
    {
        $this->assertEquals(595, $this->page->getWidth());

        $this->page->setPageSize('100:200');
        $this->assertEquals(100, $this->page->getWidth());
        $this->assertEquals(200, $this->page->getHeight());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function invalidPageSize()
    {
        $this->page->setPageSize('100');
    }

    /**
     * @test
     */
    public function getStartingPoint()
    {
        list($x, $y) = $this->page->getStartDrawingPoint();
        $this->assertEquals($this->page->getHeight(), $y);
        $this->assertEquals(0, $x);
    }

    /**
     * @test
     */
    public function boundary()
    {
        $boundary = $this->page->getBoundary();

        $this->assertEquals([0, $this->page->getHeight()], $boundary->getFirstPoint()->toArray());
        $this->assertEquals([$this->page->getWidth(), 0], $boundary->getDiagonalPoint()->toArray());
    }

    /**
     * @test
     */
    public function innerMargins()
    {
        $height = $this->page->getHeight();
        $width = $this->page->getWidth();

        $firstPoint = $this->page->getFirstPoint();
        $diagonalPoint = $this->page->getDiagonalPoint();

        $verticalMargin = 40;
        $horizontalMargin = 20;

        $originalVerticalMargin = 33;
        $originalHorizontalMargin = 22;

        $unitConverter = $this->getMock('PHPPdf\Core\UnitConverter');
        $this->page->setUnitConverter($unitConverter);

        foreach ([0, 2] as $i) {
            $unitConverter->expects($this->at($i))
                ->method('convertUnit')
                ->with($originalVerticalMargin)
                ->will($this->returnValue($verticalMargin));
        }

        foreach ([1, 3] as $i) {
            $unitConverter->expects($this->at($i))
                ->method('convertUnit')
                ->with($originalHorizontalMargin)
                ->will($this->returnValue($horizontalMargin));
        }

        $this->page->setMargin($originalVerticalMargin, $originalHorizontalMargin);

        $this->assertEquals($height - 2 * $verticalMargin, $this->page->getHeight());
        $this->assertEquals($width - 2 * $horizontalMargin, $this->page->getWidth());

        $this->assertEquals($firstPoint->translate(20, 40), $this->page->getFirstPoint());
        $this->assertEquals($diagonalPoint->translate(-20, -40), $this->page->getDiagonalPoint());
    }

    /**
     * @test
     */
    public function addingFooter()
    {
        $boundary = new Boundary();

        $footerHeight = 25;
        $mock = $this->createFooterOrHeaderMock($boundary, $footerHeight);

        $this->page->setMargin(20);
        $pageBoundary = clone $this->page->getBoundary();

        $this->page->setFooter($mock);

        $this->assertEquals($pageBoundary[3]->translate(0, -$footerHeight), $boundary[0]);
        $this->assertEquals($pageBoundary[2]->translate(0, -$footerHeight), $boundary[1]);
        $this->assertEquals($pageBoundary[2], $boundary[2]);
        $this->assertEquals($pageBoundary[3], $boundary[3]);

        $this->assertTrue($this->page->getPlaceholder('footer') === $mock);
    }

    private function createFooterOrHeaderMock(Boundary $boundary, $height = null)
    {
        $mock = $this->getMock('PHPPdf\Core\Node\Container', ['getBoundary', 'getHeight', 'setStaticSize']);
        $mock->expects($this->atLeastOnce())
            ->method('getBoundary')
            ->will($this->returnValue($boundary));

        $mock->expects($this->once())
            ->method('setStaticSize')
            ->with($this->equalTo(true))
            ->will($this->returnValue($mock));

        if ($height !== null) {
            $mock->expects($this->atLeastOnce())
                ->method('getHeight')
                ->will($this->returnValue($height));
        }

        return $mock;
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function exceptionIfFootersHeightIsNull()
    {
        $footer = new Container();

        $this->page->setFooter($footer);
    }

    /**
     * @test
     */
    public function addingHeader()
    {
        $boundary = new Boundary();

        $headerHeight = 25;
        $mock = $this->createFooterOrHeaderMock($boundary, 25);
        $this->page->setMargin(20);
        $pageBoundary = clone $this->page->getBoundary();
        $this->page->setHeader($mock);

        $realHeight = $this->page->getRealHeight();
        $this->assertEquals($pageBoundary[0], $boundary[0]);
        $this->assertEquals($pageBoundary[1], $boundary[1]);
        $this->assertEquals($pageBoundary[1]->translate(0, $headerHeight), $boundary[2]);
        $this->assertEquals($pageBoundary[0]->translate(0, $headerHeight), $boundary[3]);

        $this->assertTrue($this->page->getPlaceholder('header') === $mock);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function exceptionIfHeadersHeightIsNull()
    {
        $header = new Container();

        $this->page->setHeader($header);
    }

    /**
     * @test
     * @expectedException \LogicException
     */
    public function exceptionIfPageContextNotFound()
    {
        $this->page->getContext();
    }

    /**
     * @test
     */
    public function gettingContext()
    {
        $dynamicPage = new DynamicPage();
        $context = new PageContext(1, $dynamicPage);
        $this->page->setContext($context);

        $this->assertEquals($context, $this->page->getContext());
    }

    /**
     * @test
     */
    public function copyOfPageCloneAlsoBoundary()
    {
        $copy = $this->page->copy();

        $this->assertTrue($copy->getBoundary() !== null && $this->page->getBoundary() !== null);
        $this->assertFalse($copy->getBoundary() === $this->page->getBoundary());

        $copyBoundary = $copy->getBoundary();
        $boundary = $this->page->getBoundary();
        foreach ($copyBoundary as $i => $point) {
            $this->assertTrue($point === $boundary[$i]);
        }
    }

    /**
     * @test
     * @dataProvider booleanProvider
     */
    public function drawingTasksFromPlaceholderAreInResultOfGetDrawingTasksIfPrepareTemplateMethodHasNotBeenInvoked($invoke)
    {
        $tasks = [
            new DrawingTask(
                function () {
                }
            ),
            new DrawingTask(
                function () {
                }
            ),
        ];

        $header = $this->getMock('PHPPdf\Core\Node\Container', ['format', 'getHeight', 'collectOrderedDrawingTasks']);
        $header->expects($this->once())
            ->method('format');
        $header->expects($this->atLeastOnce())
            ->method('getHeight')
            ->will($this->returnValue(10));
        $header->expects($this->once())
            ->method('collectOrderedDrawingTasks')
            ->will($this->returnValue($tasks));

        $this->page->setHeader($header);

        if ($invoke) {
            $this->page->prepareTemplate($this->document);
        }

        $actualTasks = new DrawingTaskHeap();
        $this->page->collectOrderedDrawingTasks($this->document, $actualTasks);

        foreach ($actualTasks as $task) {
            $this->assertEquals(!$invoke, in_array($task, $tasks));
        }
    }

    public function booleanProvider()
    {
        return [
            [false],
            [true],
        ];
    }

    /**
     * @test
     */
    public function formatMethodDosntInvokePlaceholdersFormatMethod()
    {
        $header = $this->getPlaceholderMockWithNeverFormatMethodInvocation();
        $footer = $this->getPlaceholderMockWithNeverFormatMethodInvocation();
        $watermark = $this->getPlaceholderMockWithNeverFormatMethodInvocation();

        $this->page->setHeader($header);
        $this->page->setHeader($footer);
        $this->page->setWatermark($watermark);

        $this->page->format($this->createDocumentStub());
    }

    private function getPlaceholderMockWithNeverFormatMethodInvocation()
    {
        $header = $this->getMock('PHPPdf\Core\Node\Container', ['format', 'getHeight']);
        $header->expects($this->never())
            ->method('format');
        $header->expects($this->any())
            ->method('getHeight')
            ->will($this->returnValue(10));

        return $header;
    }

    /**
     * @test
     */
    public function pageCopingDosntCreateGraphicContextIfNotExists()
    {
        $this->assertNull($this->readAttribute($this->page, 'graphicsContext'));

        $copyPage = $this->page->copy();

        $this->assertNull($this->readAttribute($this->page, 'graphicsContext'));
        $this->assertNull($this->readAttribute($copyPage, 'graphicsContext'));
    }

    /**
     * @test
     * @dataProvider pageSizesProvider
     */
    public function resizeBoundaryWhenPageSizeIsSet($width, $height, array $margins)
    {
        foreach ($margins as $name => $value) {
            $this->page->setAttribute($name, $value);
        }

        $this->page->setAttribute('page-size', sprintf('%d:%d', $width, $height));

        $expectedTopLeftPoint = [$this->page->getMarginLeft(), $height - $this->page->getMarginTop()];
        $expectedBottomRightPoint = [$width - $this->page->getMarginRight(), $this->page->getMarginBottom()];

        $this->assertEquals($expectedTopLeftPoint, $this->page->getFirstPoint()->toArray());
        $this->assertEquals($expectedBottomRightPoint, $this->page->getDiagonalPoint()->toArray());
    }

    public function pageSizesProvider()
    {
        return [
            [100, 50, ['margin' => 0]],
            [77, 55, ['margin' => '2 3 4 5']],
        ];
    }

    /**
     * @test
     * @dataProvider humanReadablePageSizeProvider
     */
    public function allowHumanReadablePageSizeAttribute($pageSize, $expectedSize)
    {
        $this->page->setAttribute('page-size', $pageSize);

        list($expectedWidth, $expectedHeight) = explode(':', $expectedSize);

        $this->assertEquals($expectedWidth, $this->page->getWidth());
        $this->assertEquals($expectedHeight, $this->page->getHeight());
    }

    public function humanReadablePageSizeProvider(): iterable
    {
        $landscape = static function (string $size): string {
            return implode(':', array_reverse(explode(':', $size)));
        };

        return [
            ['100px:100px', '100:100'],
            ['4a0', Page::SIZE_4A0],
            ['4A0', Page::SIZE_4A0],
            ['4a0-landscape', $landscape(Page::SIZE_4A0)],
            ['4a0_landscape', $landscape(Page::SIZE_4A0)],
            ['2a0', Page::SIZE_2A0],
            ['2A0', Page::SIZE_2A0],
            ['2a0-landscape', $landscape(Page::SIZE_2A0)],
            ['2a0_landscape', $landscape(Page::SIZE_2A0)],
            ['a0', Page::SIZE_A0],
            ['A0', Page::SIZE_A0],
            ['a0-landscape', $landscape(Page::SIZE_A0)],
            ['a0_landscape', $landscape(Page::SIZE_A0)],
            ['a1', Page::SIZE_A1],
            ['A1', Page::SIZE_A1],
            ['a1-landscape', $landscape(Page::SIZE_A1)],
            ['a1_landscape', $landscape(Page::SIZE_A1)],
            ['a2', Page::SIZE_A2],
            ['A2', Page::SIZE_A2],
            ['a2-landscape', $landscape(Page::SIZE_A2)],
            ['a2_landscape', $landscape(Page::SIZE_A2)],
            ['a3', Page::SIZE_A3],
            ['A3', Page::SIZE_A3],
            ['a3-landscape', $landscape(Page::SIZE_A3)],
            ['a3_landscape', $landscape(Page::SIZE_A3)],
            ['a4', Page::SIZE_A4],
            ['A4', Page::SIZE_A4],
            ['a4-landscape', $landscape(Page::SIZE_A4)],
            ['a4_landscape', $landscape(Page::SIZE_A4)],
            ['a5', Page::SIZE_A5],
            ['A5', Page::SIZE_A5],
            ['a5-landscape', $landscape(Page::SIZE_A5)],
            ['a5_landscape', $landscape(Page::SIZE_A5)],
            ['a6', Page::SIZE_A6],
            ['A6', Page::SIZE_A6],
            ['a6-landscape', $landscape(Page::SIZE_A6)],
            ['a6_landscape', $landscape(Page::SIZE_A6)],
            ['a7', Page::SIZE_A7],
            ['A7', Page::SIZE_A7],
            ['a7-landscape', $landscape(Page::SIZE_A7)],
            ['a7_landscape', $landscape(Page::SIZE_A7)],
            ['a8', Page::SIZE_A8],
            ['A8', Page::SIZE_A8],
            ['a8-landscape', $landscape(Page::SIZE_A8)],
            ['a8_landscape', $landscape(Page::SIZE_A8)],
            ['a9', Page::SIZE_A9],
            ['A9', Page::SIZE_A9],
            ['a9-landscape', $landscape(Page::SIZE_A9)],
            ['a9_landscape', $landscape(Page::SIZE_A9)],
            ['a10', Page::SIZE_A10],
            ['A10', Page::SIZE_A10],
            ['a10-landscape', $landscape(Page::SIZE_A10)],
            ['a10_landscape', $landscape(Page::SIZE_A10)],
            ['b0', Page::SIZE_B0],
            ['B0', Page::SIZE_B0],
            ['b0-landscape', $landscape(Page::SIZE_B0)],
            ['b0_landscape', $landscape(Page::SIZE_B0)],
            ['b1', Page::SIZE_B1],
            ['B1', Page::SIZE_B1],
            ['b1-landscape', $landscape(Page::SIZE_B1)],
            ['b1_landscape', $landscape(Page::SIZE_B1)],
            ['b2', Page::SIZE_B2],
            ['B2', Page::SIZE_B2],
            ['b2-landscape', $landscape(Page::SIZE_B2)],
            ['b2_landscape', $landscape(Page::SIZE_B2)],
            ['b3', Page::SIZE_B3],
            ['B3', Page::SIZE_B3],
            ['b3-landscape', $landscape(Page::SIZE_B3)],
            ['b3_landscape', $landscape(Page::SIZE_B3)],
            ['b4', Page::SIZE_B4],
            ['B4', Page::SIZE_B4],
            ['b4-landscape', $landscape(Page::SIZE_B4)],
            ['b4_landscape', $landscape(Page::SIZE_B4)],
            ['b5', Page::SIZE_B5],
            ['B5', Page::SIZE_B5],
            ['b5-landscape', $landscape(Page::SIZE_B5)],
            ['b5_landscape', $landscape(Page::SIZE_B5)],
            ['b6', Page::SIZE_B6],
            ['B6', Page::SIZE_B6],
            ['b6-landscape', $landscape(Page::SIZE_B6)],
            ['b6_landscape', $landscape(Page::SIZE_B6)],
            ['b7', Page::SIZE_B7],
            ['B7', Page::SIZE_B7],
            ['b7-landscape', $landscape(Page::SIZE_B7)],
            ['b7_landscape', $landscape(Page::SIZE_B7)],
            ['b8', Page::SIZE_B8],
            ['B8', Page::SIZE_B8],
            ['b8-landscape', $landscape(Page::SIZE_B8)],
            ['b8_landscape', $landscape(Page::SIZE_B8)],
            ['b9', Page::SIZE_B9],
            ['B9', Page::SIZE_B9],
            ['b9-landscape', $landscape(Page::SIZE_B9)],
            ['b9_landscape', $landscape(Page::SIZE_B9)],
            ['b10', Page::SIZE_B10],
            ['B10', Page::SIZE_B10],
            ['b10-landscape', $landscape(Page::SIZE_B10)],
            ['b10_landscape', $landscape(Page::SIZE_B10)],
            ['c0', Page::SIZE_C0],
            ['C0', Page::SIZE_C0],
            ['c0-landscape', $landscape(Page::SIZE_C0)],
            ['c0_landscape', $landscape(Page::SIZE_C0)],
            ['c1', Page::SIZE_C1],
            ['C1', Page::SIZE_C1],
            ['c1-landscape', $landscape(Page::SIZE_C1)],
            ['c1_landscape', $landscape(Page::SIZE_C1)],
            ['c2', Page::SIZE_C2],
            ['C2', Page::SIZE_C2],
            ['c2-landscape', $landscape(Page::SIZE_C2)],
            ['c2_landscape', $landscape(Page::SIZE_C2)],
            ['c3', Page::SIZE_C3],
            ['C3', Page::SIZE_C3],
            ['c3-landscape', $landscape(Page::SIZE_C3)],
            ['c3_landscape', $landscape(Page::SIZE_C3)],
            ['c4', Page::SIZE_C4],
            ['C4', Page::SIZE_C4],
            ['c4-landscape', $landscape(Page::SIZE_C4)],
            ['c4_landscape', $landscape(Page::SIZE_C4)],
            ['c5', Page::SIZE_C5],
            ['C5', Page::SIZE_C5],
            ['c5-landscape', $landscape(Page::SIZE_C5)],
            ['c5_landscape', $landscape(Page::SIZE_C5)],
            ['c6', Page::SIZE_C6],
            ['C6', Page::SIZE_C6],
            ['c6-landscape', $landscape(Page::SIZE_C6)],
            ['c6_landscape', $landscape(Page::SIZE_C6)],
            ['c7', Page::SIZE_C7],
            ['C7', Page::SIZE_C7],
            ['c7-landscape', $landscape(Page::SIZE_C7)],
            ['c7_landscape', $landscape(Page::SIZE_C7)],
            ['c8', Page::SIZE_C8],
            ['C8', Page::SIZE_C8],
            ['c8-landscape', $landscape(Page::SIZE_C8)],
            ['c8_landscape', $landscape(Page::SIZE_C8)],
            ['c9', Page::SIZE_C9],
            ['C9', Page::SIZE_C9],
            ['c9-landscape', $landscape(Page::SIZE_C9)],
            ['c9_landscape', $landscape(Page::SIZE_C9)],
            ['c10', Page::SIZE_C10],
            ['C10', Page::SIZE_C10],
            ['c10-landscape', $landscape(Page::SIZE_C10)],
            ['c10_landscape', $landscape(Page::SIZE_C10)],
            ['LETTER-landscape', $landscape(Page::SIZE_LETTER)],
            ['LETTER landscape', $landscape(Page::SIZE_LETTER)],
            ['letter', Page::SIZE_LETTER],
            ['legal', Page::SIZE_LEGAL],
            ['LEGAL landscape', $landscape(Page::SIZE_LEGAL)],
            ['LEGAL-landscape', $landscape(Page::SIZE_LEGAL)],
        ];
    }

    /**
     * @test
     */
    public function watermarkShouldBeInTheMiddleOfPage()
    {
        $watermark = new Container();
        $watermark->setHeight(100);

        $this->page->setWatermark($watermark);

        $this->assertEquals(Node::VERTICAL_ALIGN_MIDDLE, $watermark->getAttribute('vertical-align'));
        $this->assertEquals($this->page->getHeight(), $watermark->getHeight());
        $this->assertEquals($this->page->getWidth(), $watermark->getWidth());
    }

    /**
     * @test
     * @dataProvider pageNumberProvider
     */
    public function loadTemplateDocumentWhileFormattingIfExists($numberOfPage)
    {
        $fileOfSourcePage = 'some/file.pdf';
        $width = 100;
        $height = 50;
        $numberOfSourceGcs = 3;

        $this->page->setAttribute('document-template', $fileOfSourcePage);

        if ($numberOfPage !== null) {
            $pageContext = $this->getMockBuilder('PHPPdf\Core\Node\PageContext')
                ->setMethods(['getPageNumber'])
                ->disableOriginalConstructor()
                ->getMock();

            $pageContext->expects($this->once())
                ->method('getPageNumber')
                ->will($this->returnValue($numberOfPage));
            $this->page->setContext($pageContext);
        }

        $document = $this->getMockBuilder('PHPPdf\Core\Document')
            ->disableOriginalConstructor()
            ->setMethods(['loadEngine'])
            ->getMock();

        $engine = $this->getMockBuilder('PHPPdf\Core\Engine\Engine')
            ->getMock();

        $copiedGc = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
            ->getMock();

        $sourceGcs = [];
        for ($i = 0; $i < $numberOfSourceGcs; $i++) {
            $sourceGc = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
                ->getMock();
            $sourceGcs[] = $sourceGc;
        }

        $document->expects($this->once())
            ->method('loadEngine')
            ->with($fileOfSourcePage, 'utf-8')
            ->will($this->returnValue($engine));

        $engine->expects($this->once())
            ->method('getAttachedGraphicsContexts')
            ->will($this->returnValue($sourceGcs));

        $sourceGcIndex = $numberOfPage === null ? 0 : ($numberOfPage - 1) % $numberOfSourceGcs;

        $sourceGc = $sourceGcs[$sourceGcIndex];

        $sourceGc->expects($this->once())
            ->method('copy')
            ->will($this->returnValue($copiedGc));

        $copiedGc->expects($this->atLeastOnce())
            ->method('getWidth')
            ->will($this->returnValue($width));
        $copiedGc->expects($this->atLeastOnce())
            ->method('getHeight')
            ->will($this->returnValue($height));

        $this->page->format($document);

        $this->assertEquals($width, $this->page->getWidth());
        $this->assertEquals($height, $this->page->getHeight());
    }

    public function pageNumberProvider()
    {
        return [
            [null],
            [1],
            [6],
        ];
    }

    /**
     * @test
     */
    public function setsPageSizeOnWidthOrHeightAttributeSet()
    {
        list($width, $height) = explode(':', $this->page->getAttribute('page-size'));

        $newWidth = 123;
        $this->page->setWidth($newWidth);

        $this->assertEquals($newWidth.':'.$height, $this->page->getAttribute('page-size'));

        $newHeight = 321;
        $this->page->setHeight($newHeight);

        $this->assertEquals($newWidth.':'.$newHeight, $this->page->getAttribute('page-size'));
    }
}
