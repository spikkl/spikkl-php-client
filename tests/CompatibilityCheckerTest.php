<?php

namespace Spikkl\Api;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Spikkl\Api\Exceptions\IncompatiblePlatformException;

class CompatibilityCheckerTest extends TestCase
{
    /**
     * @var CompatibilityChecker|MockObject
     */
    protected $compatibilityChecker;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->compatibilityChecker = $this
            ->getMockBuilder(CompatibilityChecker::class)
            ->setMethods([
                'satisfiesPHPVersion',
                'satisfiesJSONExtension'
            ])
            ->getMock();
    }

    /**
     * @test
     */
    public function check_compatibility_throws_exception_on_php_version()
    {
        $this->expectException(IncompatiblePlatformException::class);
        $this->expectExceptionCode(IncompatiblePlatformException::INCOMPATIBLE_PHP_VERSION);

        $this->compatibilityChecker
            ->expects($this->once())
            ->method('satisfiesPHPVersion')
            ->will($this->returnValue(false));

        $this->compatibilityChecker
            ->expects($this->never())
            ->method('satisfiesJSONExtension');

        $this->compatibilityChecker->checkCompatibility();;
    }

    /**
     * @test
     */
    public function check_compatibility_throws_exception_on_json_extension()
    {
        $this->expectException(IncompatiblePlatformException::class);
        $this->expectExceptionCode(IncompatiblePlatformException::INCOMPATIBLE_JSON_EXTENSION);

        $this->compatibilityChecker
            ->expects($this->once())
            ->method('satisfiesPHPVersion')
            ->will($this->returnValue(true));

        $this->compatibilityChecker
            ->expects($this->once())
            ->method('satisfiesJSONExtension')
            ->will($this->returnValue(false));

        $this->compatibilityChecker->checkCompatibility();;
    }
}