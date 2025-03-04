<?php

declare(strict_types=1);

namespace BrowscapTest\Writer;

use Browscap\Data\DataCollection;
use Browscap\Data\Division;
use Browscap\Data\Helper\TrimProperty;
use Browscap\Data\UserAgent;
use Browscap\Filter\StandardFilter;
use Browscap\Formatter\JsonFormatter;
use Browscap\Writer\JsonWriter;
use Browscap\Writer\WriterInterface;
use Exception;
use InvalidArgumentException;
use JsonException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionException;
use ReflectionProperty;

use function assert;
use function date;
use function file_get_contents;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

class JsonWriterTest extends TestCase
{
    private const STORAGE_DIR = 'storage';

    private JsonWriter $object;

    private string $file;

    /**
     * @throws InvalidArgumentException
     */
    protected function setUp(): void
    {
        vfsStream::setup(self::STORAGE_DIR);
        $this->file = vfsStream::url(self::STORAGE_DIR) . DIRECTORY_SEPARATOR . 'test.json';

        $logger = $this->createMock(LoggerInterface::class);

        assert($logger instanceof LoggerInterface);
        $this->object = new JsonWriter($this->file, $logger);
    }

    /**
     * @throws void
     */
    protected function teardown(): void
    {
        $this->object->close();

        unlink($this->file);
    }

    /**
     * tests getting the writer type
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testGetType(): void
    {
        static::assertSame(WriterInterface::TYPE_JSON, $this->object->getType());
    }

    /**
     * tests setting and getting a formatter
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testSetGetFormatter(): void
    {
        $mockFormatter = $this->createMock(JsonFormatter::class);

        $this->object->setFormatter($mockFormatter);
        static::assertSame($mockFormatter, $this->object->getFormatter());
    }

    /**
     * tests setting and getting a filter
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testSetGetFilter(): void
    {
        $mockFilter = $this->createMock(StandardFilter::class);

        $this->object->setFilter($mockFilter);
        static::assertSame($mockFilter, $this->object->getFilter());
    }

    /**
     * tests setting a file into silent mode
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testSetGetSilent(): void
    {
        $this->object->setSilent(true);
        static::assertTrue($this->object->isSilent());
    }

    /**
     * tests rendering the start of the file
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testFileStartIfNotSilent(): void
    {
        $this->object->setSilent(false);

        $this->object->fileStart();
        static::assertSame(
            '{' . PHP_EOL,
            file_get_contents($this->file)
        );
    }

    /**
     * tests rendering the start of the file
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testFileStartIfSilent(): void
    {
        $this->object->setSilent(true);

        $this->object->fileStart();
        static::assertSame('', file_get_contents($this->file));
    }

    /**
     * tests rendering the end of the file
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testFileEndIfNotSilent(): void
    {
        $this->object->setSilent(false);

        $this->object->fileEnd();
        static::assertSame('}' . PHP_EOL, file_get_contents($this->file));
    }

    /**
     * tests rendering the end of the file
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testFileEndIfSilent(): void
    {
        $this->object->setSilent(true);

        $this->object->fileEnd();
        static::assertSame('', file_get_contents($this->file));
    }

    /**
     * tests rendering the header information
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws JsonException
     */
    public function testRenderHeaderIfSilent(): void
    {
        $header = ['TestData to be renderd into the Header'];

        $this->object->setSilent(true);

        $this->object->renderHeader($header);
        static::assertSame('', file_get_contents($this->file));
    }

    /**
     * tests rendering the header information
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws JsonException
     */
    public function testRenderHeaderIfNotSilent(): void
    {
        $header = ['TestData to be renderd into the Header', 'more data to be rendered', 'much more data'];

        $this->object->setSilent(false);

        $this->object->renderHeader($header);
        static::assertSame(
            '  "comments": [' . PHP_EOL . '    "TestData to be renderd into the Header",' . PHP_EOL . '    "more data to be rendered",' . PHP_EOL . '    "much more data"' . PHP_EOL . '  ],'
            . PHP_EOL,
            file_get_contents($this->file)
        );
    }

    /**
     * tests rendering the version information
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws JsonException
     */
    public function testRenderVersionIfSilent(): void
    {
        $version = [
            'version' => 'test',
            'released' => date('Y-m-d'),
            'format' => 'TEST',
            'type' => 'full',
        ];

        $this->object->setSilent(true);

        $this->object->renderVersion($version);
        static::assertSame('', file_get_contents($this->file));
    }

    /**
     * tests rendering the version information
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws JsonException
     */
    public function testRenderVersionIfNotSilent(): void
    {
        $version = [
            'version' => 'test',
            'released' => date('Y-m-d'),
            'format' => 'TEST',
            'type' => 'full',
        ];

        $this->object->setSilent(false);

        $this->object->renderVersion($version);
        static::assertSame(
            '  "GJK_Browscap_Version": {' . PHP_EOL . '    "Version": "test",' . PHP_EOL
            . '    "Released": "' . date('Y-m-d') . '"' . PHP_EOL . '  },' . PHP_EOL,
            file_get_contents($this->file)
        );
    }

    /**
     * tests rendering the version information
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws JsonException
     */
    public function testRenderVersionIfNotSilentButWithoutVersion(): void
    {
        $version = [];

        $this->object->setSilent(false);

        $this->object->renderVersion($version);
        static::assertSame(
            '  "GJK_Browscap_Version": {' . PHP_EOL . '    "Version": "0",' . PHP_EOL
            . '    "Released": ""' . PHP_EOL . '  },' . PHP_EOL,
            file_get_contents($this->file)
        );
    }

    /**
     * tests rendering the header for all division
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testRenderAllDivisionsHeader(): void
    {
        $collection = $this->createMock(DataCollection::class);

        assert($collection instanceof DataCollection);
        $this->object->renderAllDivisionsHeader($collection);
        static::assertSame('', file_get_contents($this->file));
    }

    /**
     * tests rendering the header of one division
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testRenderDivisionHeader(): void
    {
        $this->object->setSilent(true);

        $this->object->renderDivisionHeader('test');
        static::assertSame('', file_get_contents($this->file));
    }

    /**
     * tests rendering the header of one section
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws JsonException
     */
    public function testRenderSectionHeaderIfNotSilent(): void
    {
        $this->object->setSilent(false);

        $mockFormatter = $this->getMockBuilder(JsonFormatter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['formatPropertyName'])
            ->getMock();

        $mockFormatter
            ->expects(static::once())
            ->method('formatPropertyName')
            ->willReturn('test');

        assert($mockFormatter instanceof JsonFormatter);
        $this->object->setFormatter($mockFormatter);

        $this->object->renderSectionHeader('test');
        static::assertSame('  test: ', file_get_contents($this->file));
    }

    /**
     * tests rendering the header of one section
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws JsonException
     */
    public function testRenderSectionHeaderIfSilent(): void
    {
        $this->object->setSilent(true);

        $this->object->renderSectionHeader('test');
        static::assertSame('', file_get_contents($this->file));
    }

    /**
     * tests rendering the body of one section
     *
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws JsonException
     */
    public function testRenderSectionBodyIfNotSilent(): void
    {
        $this->object->setSilent(false);

        $section = [
            'Test' => 1,
            'isTest' => true,
            'abc' => 'bcd',
        ];

        $useragent = $this->getMockBuilder(UserAgent::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProperties'])
            ->getMock();

        $useragent
            ->expects(static::once())
            ->method('getProperties')
            ->willReturn([
                'Test' => 'abc',
                'abc' => true,
            ]);

        $mockExpander = $this->getMockBuilder(TrimProperty::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['trim'])
            ->getMock();

        $mockExpander
            ->expects(static::once())
            ->method('trim')
            ->willReturnArgument(0);

        $property = new ReflectionProperty($this->object, 'trimProperty');
        $property->setAccessible(true);
        $property->setValue($this->object, $mockExpander);

        $division = $this->getMockBuilder(Division::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUserAgents'])
            ->getMock();

        $division
            ->expects(static::once())
            ->method('getUserAgents')
            ->willReturn([0 => $useragent]);

        $collection = $this->getMockBuilder(DataCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDefaultProperties'])
            ->getMock();

        $collection
            ->expects(static::once())
            ->method('getDefaultProperties')
            ->willReturn($division);

        $mockFormatter = $this->getMockBuilder(JsonFormatter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['formatPropertyName', 'formatPropertyValue'])
            ->getMock();

        $map1 = [
            ['{"Test":1,"abc":"bcd"}', 'Comment', '{"Test":1,"abc":"bcd"}'],
        ];

        $mockFormatter
            ->expects(static::never())
            ->method('formatPropertyName');
        $mockFormatter
            ->expects(static::once())
            ->method('formatPropertyValue')
            ->willReturnMap($map1);

        assert($mockFormatter instanceof JsonFormatter);
        $this->object->setFormatter($mockFormatter);

        $mockFilter = $this->getMockBuilder(StandardFilter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isOutputProperty'])
            ->getMock();

        $map2 = [
            ['Test', $this->object, true],
            ['isTest', $this->object, false],
            ['abc', $this->object, true],
        ];

        $mockFilter
            ->expects(static::exactly(2))
            ->method('isOutputProperty')
            ->willReturnMap($map2);

        assert($mockFilter instanceof StandardFilter);
        $this->object->setFilter($mockFilter);

        assert($collection instanceof DataCollection);
        $this->object->renderSectionBody($section, $collection);
        static::assertSame(
            '{"Test":1,"abc":"bcd"}',
            file_get_contents($this->file)
        );
    }

    /**
     * tests rendering the body of one section
     *
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws JsonException
     */
    public function testRenderSectionBodyIfNotSilentWithParents(): void
    {
        $this->object->setSilent(false);

        $section = [
            'Parent' => 'X1',
            'Comment' => '1',
            'Win16' => true,
            'Platform' => 'bcd',
        ];

        $sections = [
            'X1' => [
                'Comment' => '12',
                'Win16' => false,
                'Platform' => 'bcd',
            ],
            'X2' => $section,
        ];

        $useragent = $this->getMockBuilder(UserAgent::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProperties'])
            ->getMock();

        $useragent
            ->expects(static::once())
            ->method('getProperties')
            ->willReturn([
                'Comment' => 1,
                'Win16' => true,
                'Platform' => 'bcd',
            ]);

        $mockExpander = $this->getMockBuilder(TrimProperty::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['trim'])
            ->getMock();

        $mockExpander
            ->expects(static::exactly(2))
            ->method('trim')
            ->willReturnArgument(0);

        $property = new ReflectionProperty($this->object, 'trimProperty');
        $property->setAccessible(true);
        $property->setValue($this->object, $mockExpander);

        $division = $this->getMockBuilder(Division::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUserAgents'])
            ->getMock();

        $division
            ->expects(static::once())
            ->method('getUserAgents')
            ->willReturn([0 => $useragent]);

        $collection = $this->getMockBuilder(DataCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDefaultProperties'])
            ->getMock();

        $collection
            ->expects(static::once())
            ->method('getDefaultProperties')
            ->willReturn($division);

        $mockFormatter = $this->getMockBuilder(JsonFormatter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['formatPropertyName', 'formatPropertyValue'])
            ->getMock();

        $mockFormatter
            ->expects(static::never())
            ->method('formatPropertyName')
            ->willReturnArgument(0);
        $mockFormatter
            ->expects(static::once())
            ->method('formatPropertyValue')
            ->willReturnArgument(0);

        assert($mockFormatter instanceof JsonFormatter);
        $this->object->setFormatter($mockFormatter);

        $map = [
            ['Comment', $this->object, true],
            ['Win16', $this->object, false],
            ['Platform', $this->object, true],
            ['Parent', $this->object, true],
        ];

        $mockFilter = $this->getMockBuilder(StandardFilter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isOutputProperty'])
            ->getMock();

        $mockFilter
            ->expects(static::exactly(4))
            ->method('isOutputProperty')
            ->willReturnMap($map);

        assert($mockFilter instanceof StandardFilter);
        $this->object->setFilter($mockFilter);

        assert($collection instanceof DataCollection);
        $this->object->renderSectionBody($section, $collection, $sections);
        static::assertSame(
            '{"Parent":"X1","Comment":"1"}',
            file_get_contents($this->file)
        );
    }

    /**
     * tests rendering the body of one section
     *
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws JsonException
     */
    public function testRenderSectionBodyIfNotSilentWithDefaultPropertiesAsParent(): void
    {
        $this->object->setSilent(false);

        $section = [
            'Parent' => 'DefaultProperties',
            'Comment' => '1',
            'Win16' => true,
            'Platform' => 'bcd',
        ];

        $sections = ['X2' => $section];

        $useragent = $this->getMockBuilder(UserAgent::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProperties'])
            ->getMock();

        $useragent
            ->expects(static::once())
            ->method('getProperties')
            ->willReturn([
                'Comment' => '12',
                'Win16' => true,
                'Platform' => 'bcd',
            ]);

        $mockExpander = $this->getMockBuilder(TrimProperty::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['trim'])
            ->getMock();

        $mockExpander
            ->expects(static::exactly(2))
            ->method('trim')
            ->willReturnArgument(0);

        $property = new ReflectionProperty($this->object, 'trimProperty');
        $property->setAccessible(true);
        $property->setValue($this->object, $mockExpander);

        $division = $this->getMockBuilder(Division::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUserAgents'])
            ->getMock();

        $division
            ->expects(static::once())
            ->method('getUserAgents')
            ->willReturn([0 => $useragent]);

        $collection = $this->getMockBuilder(DataCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDefaultProperties'])
            ->getMock();

        $collection
            ->expects(static::once())
            ->method('getDefaultProperties')
            ->willReturn($division);

        $mockFormatter = $this->getMockBuilder(JsonFormatter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['formatPropertyName', 'formatPropertyValue'])
            ->getMock();

        $mockFormatter
            ->expects(static::never())
            ->method('formatPropertyName')
            ->willReturnArgument(0);
        $mockFormatter
            ->expects(static::once())
            ->method('formatPropertyValue')
            ->willReturnArgument(0);

        assert($mockFormatter instanceof JsonFormatter);
        $this->object->setFormatter($mockFormatter);

        $map = [
            ['Comment', $this->object, true],
            ['Win16', $this->object, false],
            ['Platform', $this->object, true],
            ['Parent', $this->object, true],
        ];

        $mockFilter = $this->getMockBuilder(StandardFilter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isOutputProperty'])
            ->getMock();

        $mockFilter
            ->expects(static::exactly(4))
            ->method('isOutputProperty')
            ->willReturnMap($map);

        assert($mockFilter instanceof StandardFilter);
        $this->object->setFilter($mockFilter);

        assert($collection instanceof DataCollection);
        $this->object->renderSectionBody($section, $collection, $sections);
        static::assertSame(
            '{"Parent":"DefaultProperties","Comment":"1"}',
            file_get_contents($this->file)
        );
    }

    /**
     * tests rendering the body of one section
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws JsonException
     */
    public function testRenderSectionBodyIfSilent(): void
    {
        $this->object->setSilent(true);

        $section = [
            'Test' => 1,
            'isTest' => true,
            'abc' => 'bcd',
        ];

        $collection = $this->createMock(DataCollection::class);

        assert($collection instanceof DataCollection);
        $this->object->renderSectionBody($section, $collection);
        static::assertSame('', file_get_contents($this->file));
    }

    /**
     * tests rendering the footer of one section
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testRenderSectionFooterIfNotSilent(): void
    {
        $this->object->setSilent(false);

        $this->object->renderSectionFooter();
        static::assertSame(',' . PHP_EOL, file_get_contents($this->file));
    }

    /**
     * tests rendering the footer of one section
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testRenderSectionFooterIfSilent(): void
    {
        $this->object->setSilent(true);

        $this->object->renderSectionFooter();
        static::assertSame('', file_get_contents($this->file));
    }

    /**
     * tests rendering the footer of one division
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testRenderDivisionFooter(): void
    {
        $this->object->renderDivisionFooter();
        static::assertSame('', file_get_contents($this->file));
    }

    /**
     * tests rendering the footer after all divisions
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws ExpectationFailedException
     */
    public function testRenderAllDivisionsFooter(): void
    {
        $this->object->renderAllDivisionsFooter();
        static::assertSame('', file_get_contents($this->file));
    }
}
