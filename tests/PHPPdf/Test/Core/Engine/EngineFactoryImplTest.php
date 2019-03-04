<?php

namespace PHPPdf\Test\Core\Engine;

use PHPPdf\Core\Engine\EngineFactoryImpl;
use PHPPdf\PHPUnit\Framework\TestCase;

class EngineFactoryImplTest extends TestCase
{
    /**
     * @var EngineFactoryImpl
     */
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new EngineFactoryImpl();
    }

    /**
     * @test
     * @dataProvider validTypeProvider
     */
    public function engineCreationSuccess($type, $expectedClass): void
    {
        try {
            $engine = $this->factory->createEngine($type);

            $this->assertInstanceOf($expectedClass, $engine);
        } catch (\Imagine\Exception\RuntimeException $e) {
            $this->markTestSkipped('Exception from Imagine library, propably some graphics library is not installed');
        }
    }

    public function validTypeProvider(): iterable
    {
        return [
            [EngineFactoryImpl::TYPE_IMAGE, 'PHPPdf\Core\Engine\Imagine\Engine'],
            [EngineFactoryImpl::TYPE_PDF, 'PHPPdf\Core\Engine\ZF\Engine'],
        ];
    }

    /**
     * @test
     * @dataProvider invalidTypeProvider
     * @expectedException \PHPPdf\Exception\DomainException
     */
    public function engineCreationFailure($type): void
    {
        $this->factory->createEngine($type);
    }

    public function invalidTypeProvider(): iterable
    {
        return [
            ['some type'],
        ];
    }

    public function validImageTypeProvider(): iterable
    {
        return [
            [EngineFactoryImpl::ENGINE_GD],
            [EngineFactoryImpl::ENGINE_IMAGICK],
            [EngineFactoryImpl::ENGINE_GMAGICK],
        ];
    }

    /**
     * @test
     * @dataProvider invalidImageTypeProvider
     * @expectedException \PHPPdf\Exception\DomainException
     */
    public function imageEngineCreationFailure($type): void
    {
        $this->factory->createEngine(
            EngineFactoryImpl::TYPE_IMAGE,
            [
                EngineFactoryImpl::OPTION_ENGINE => $type,
            ]
        );
    }

    public function invalidImageTypeProvider(): iterable
    {
        return [
            ['some'],
        ];
    }
}
