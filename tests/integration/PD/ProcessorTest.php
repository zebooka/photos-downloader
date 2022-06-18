<?php

namespace Zebooka\PD;

use Mockery\MockInterface;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Zebooka\Translator\Translator;
use Zebooka\Utils\Executor;

class ProcessorTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    private function resourceDirectory()
    {
        return __DIR__ . '/../../../res/processor';
    }

    /**
     * @return FileBunch
     */
    private function fileBunch()
    {
        return \Mockery::mock(FileBunch::class)
            ->shouldReceive('bunchId')
            ->withNoArgs()
            ->andReturn($this->resourceDirectory() . DIRECTORY_SEPARATOR . 'unique-bunchId')
            ->getMock()
            ->shouldReceive('extensions')
            ->withNoArgs()
            ->andReturn(array('ext', 'ext2'))
            ->getMock();
    }

    /**
     * @param MockInterface $fileBunch
     * @return mixed
     */
    private function upgradeWithExifs($fileBunch) {
        return $fileBunch->shouldReceive('exifs')
            ->withNoArgs()
            ->andReturn($this->exifs())
            ->getMock();
    }

    private function exifs()
    {
        $exif = /** @var Exif $exif */
        $exif = \Mockery::mock(Exif::class);
        $exif->Make = 'Zenit';
        $exif->Model = 'Zenit E';
        $exif->DateTimeOriginal = date('Y-m-d H:i:s O', strtotime('2019-08-14 21:00:00'));

        $exif2 = /** @var Exif $exif2 */
        $exif2 = \Mockery::mock(Exif::class);
        $exif2->DateTimeOriginal = date('Y-m-d H:i:s O', strtotime('2019-08-14 21:00:00'));

        return [
            'ext' => $exif,
            'ext2' => $exif2,
        ];
    }

    /**
     * @return FileBunch
     */
    private function fileBunchWithEmptyExtension()
    {
        return \Mockery::mock(FileBunch::class)
            ->shouldReceive('bunchId')
            ->withNoArgs()
            ->andReturn($this->resourceDirectory() . DIRECTORY_SEPARATOR . 'unique-bunchId')
            ->getMock()
            ->shouldReceive('extensions')
            ->withNoArgs()
            ->andReturn(['ext', ''])
            ->getMock();
    }

    /**
     * @return Tokens
     */
    private function tokens()
    {
        return \Mockery::mock(Tokens::class);
    }

    /**
     * @return Configure
     */
    private function configure(array $cameras = array())
    {
        $configure = \Mockery::mock(Configure::class);
        $configure->cameras = $cameras;
        return $configure;
    }

    /**
     * @return Tokenizer
     */
    private function tokenizer(FileBunch $fileBunch, Tokens $tokens)
    {
        return \Mockery::mock(Tokenizer::class)
            ->shouldReceive('tokenize')
            ->with($fileBunch)
            ->once()
            ->andReturn($tokens)
            ->getMock();
    }

    /**
     * @return Tokenizer
     */
    private function tokenizerException(FileBunch $fileBunch, \Exception $exception)
    {
        return \Mockery::mock(Tokenizer::class)
            ->shouldReceive('tokenize')
            ->with($fileBunch)
            ->once()
            ->andThrow($exception)
            ->getMock();
    }

    /**
     * @return Assembler
     */
    private function assembler(Tokens $tokens, FileBunch $fileBunch, $newBunchId)
    {
        return \Mockery::mock(Assembler::class)
            ->shouldReceive('assemble')
            ->with($tokens, $fileBunch)
            ->once()
            ->andReturn($newBunchId)
            ->getMock();
    }

    /**
     * @return Assembler
     */
    private function assemblerException(Tokens $tokens, FileBunch $fileBunch, $code)
    {
        return \Mockery::mock(Assembler::class)
            ->shouldReceive('assemble')
            ->with($tokens, $fileBunch)
            ->once()
            ->andThrow(new AssemblerException('', $code))
            ->getMock();
    }

    /**
     * @return Assembler
     */
    private function assemblerNeverCalled()
    {
        return \Mockery::mock(Assembler::class)
            ->shouldReceive('assemble')
            ->never()
            ->getMock();
    }

    /**
     * @return BunchCache
     */
    private function bunchCache()
    {
        return \Mockery::mock(BunchCache::class);
    }

    /**
     * @return Executor
     */
    private function executor()
    {
        return \Mockery::mock(Executor::class)
            ->shouldReceive('execute')
            ->with(\Mockery::type('string'))
            ->atLeast()
            ->once()
            ->andReturn(0)
            ->getMock();
    }

    /**
     * @return Executor
     */
    private function executorNeverCalled()
    {
        return \Mockery::mock(Executor::class)
            ->shouldReceive('execute')
            ->never()
            ->getMock();
    }

    /**
     * @return Logger
     */
    private function logger()
    {
        return \Mockery::mock('\\Monolog\\Logger')
            ->shouldIgnoreMissing();
    }

    /**
     * @return Translator|MockInterface
     */
    private function translator()
    {
        return \Mockery::mock(Translator::class)
            ->shouldIgnoreMissing();
    }

    public function test_process()
    {
        $fileBunch = $this->fileBunch();
        $tokens = $this->tokens();
        $processor = new Processor(
            $this->configure(),
            $this->tokenizer($fileBunch, $tokens),
            $this->assembler($tokens, $fileBunch, $this->resourceDirectory() . DIRECTORY_SEPARATOR . 'new-unique-bunchId'),
            $this->bunchCache(),
            $this->executor(),
            $this->logger(),
            $this->translator()
        );

        $this->assertTrue($processor->process($fileBunch));
        $this->assertGreaterThan(5, $processor->bytesProcessed());
    }

    public function test_process_with_empty_extension()
    {
        $fileBunch = $this->fileBunchWithEmptyExtension();
        $tokens = $this->tokens();
        $processor = new Processor(
            $this->configure(),
            $this->tokenizer($fileBunch, $tokens),
            $this->assembler($tokens, $fileBunch, $this->resourceDirectory() . DIRECTORY_SEPARATOR . 'new-unique-bunchId'),
            $this->bunchCache(),
            $this->executor(),
            $this->logger(),
            $this->translator()
        );

        $this->assertTrue($processor->process($fileBunch));
        $this->assertGreaterThan(5, $processor->bytesProcessed());
    }

    public function test_process_stops_if_tokenize_fails()
    {
        $exceptions = array(
            new TokenizerException('', TokenizerException::NO_DATE_TIME_DETECTED),
            new ExifAnalyzerException('', ExifAnalyzerException::DIFFERENT_CAMERAS),
            new ExifAnalyzerException('', ExifAnalyzerException::DIFFERENT_DATES),
            new ExifAnalyzerException('', ExifAnalyzerException::EXIF_EXCEPTION),
        );
        foreach ($exceptions as $exception) {
            $fileBunch = $this->fileBunch();
            $tokens = $this->tokens();
            $processor = new Processor(
                $this->configure(),
                $this->tokenizerException($fileBunch, $exception),
                $this->assemblerNeverCalled(),
                $this->bunchCache(),
                $this->executorNeverCalled(),
                $this->logger(),
                $this->translator()
            );

            $this->assertFalse($processor->process($fileBunch));
            $this->assertEquals(0, $processor->bytesProcessed());
        }
    }

    public function test_process_stops_if_camera_not_in_list()
    {
        $fileBunch = $this->fileBunch();
        $tokens = $this->tokens();
        $processor = new Processor(
            $this->configure(array('camera-1', 'camera-2')),
            $this->tokenizer($fileBunch, $tokens),
            $this->assemblerNeverCalled(),
            $this->bunchCache(),
            $this->executorNeverCalled(),
            $this->logger(),
            $this->translator()
        );

        $this->assertFalse($processor->process($fileBunch));
        $this->assertEquals(0, $processor->bytesProcessed());
    }

    public function test_process_stops_if_assemble_exception()
    {
        $fileBunch = $this->fileBunch();
        $tokens = $this->tokens();
        $processor = new Processor(
            $this->configure(),
            $this->tokenizer($fileBunch, $tokens),
            $this->assemblerException($tokens, $fileBunch, AssemblerException::TEST),
            $this->bunchCache(),
            $this->executorNeverCalled(),
            $this->logger(),
            $this->translator()
        );

        $this->assertFalse($processor->process($fileBunch));
        $this->assertEquals(0, $processor->bytesProcessed());
    }

    public function test_process_stops_if_new_bunchId_is_same_as_old()
    {
        $fileBunch = $this->fileBunch();
        $tokens = $this->tokens();
        $processor = new Processor(
            $this->configure(),
            $this->tokenizer($fileBunch, $tokens),
            $this->assembler($tokens, $fileBunch, $this->resourceDirectory() . DIRECTORY_SEPARATOR . 'unique-bunchId'),
            $this->bunchCache(),
            $this->executorNeverCalled(),
            $this->logger(),
            $this->translator()
        );

        $this->assertFalse($processor->process($fileBunch));
        $this->assertEquals(0, $processor->bytesProcessed());
    }

    public function test_process_skips_by_file_regexp()
    {
        $configure = $this->configure();
        $configure->regexpFilenameFilter = '/\\.test$/i';
        $fileBunch = $this->fileBunch();
        $tokens = $this->tokens();
        $translator = $this->translator()
            ->shouldReceive('translate')
            ->with('originalFileBunchPath', \Mockery::type('array'))
            ->once()
            ->andReturn('unique-message')
            ->getMock()
            ->shouldReceive('translate')
            ->with('skipped/filteredByFileRegExp', \Mockery::type('array'))
            ->twice()
            ->andReturn('unique-message')
            ->getMock();
        $processor = new Processor(
            $configure,
            $this->tokenizer($fileBunch, $tokens),
            $this->assembler($tokens, $fileBunch, 'unique-bunchId2'),
            $this->bunchCache(),
            $this->executorNeverCalled(),
            $this->logger(),
            $translator
        );
        $this->assertTrue($processor->process($fileBunch));
        $this->assertGreaterThan(5, $processor->bytesProcessed());
    }

    public function test_process_skips_by_file_negative_regexp()
    {
        $configure = $this->configure();
        $configure->regexpFilenameNegativeFilter = '/\\.ext2?$/i';
        $fileBunch = $this->fileBunch();
        $tokens = $this->tokens();
        $translator = $this->translator()
            ->shouldReceive('translate')
            ->with('originalFileBunchPath', \Mockery::type('array'))
            ->once()
            ->andReturn('unique-message')
            ->getMock()
            ->shouldReceive('translate')
            ->with('skipped/filteredByFileRegExp', \Mockery::type('array'))
            ->twice()
            ->andReturn('unique-message')
            ->getMock();
        $processor = new Processor(
            $configure,
            $this->tokenizer($fileBunch, $tokens),
            $this->assembler($tokens, $fileBunch, 'unique-bunchId2'),
            $this->bunchCache(),
            $this->executorNeverCalled(),
            $this->logger(),
            $translator
        );
        $this->assertTrue($processor->process($fileBunch));
        $this->assertGreaterThan(5, $processor->bytesProcessed());
    }

    private function make_test_process_processes_by_exif_regexp($configOptionKey, $optionValue)
    {
        $configure = $this->configure();
        $configure->{$configOptionKey} = $optionValue;
        $fileBunch = $this->upgradeWithExifs($this->fileBunch());
        $tokens = $this->tokens();
        $translator = $this->translator();
        $processor = new Processor(
            $configure,
            $this->tokenizer($fileBunch, $tokens),
            $this->assembler($tokens, $fileBunch, 'unique-bunchId2'),
            $this->bunchCache(),
            $this->executor(),
            $this->logger(),
            $translator
        );
        $this->assertTrue($processor->process($fileBunch));
    }

    public function test_process_processes_by_exif_regexp()
    {
        $this->make_test_process_processes_by_exif_regexp('regexpExifFilter', ['Model' => 'Zenit E']);
        $this->make_test_process_processes_by_exif_regexp('regexpExifFilter', ['Model' => '/zenit/i']);
        $this->make_test_process_processes_by_exif_regexp('regexpExifNegativeFilter', ['Model' => 'Leica']);
        $this->make_test_process_processes_by_exif_regexp('regexpExifNegativeFilter', ['Model' => '/leica/i']);
        $this->make_test_process_processes_by_exif_regexp('regexpExifNegativeFilter', ['Model' => '12.4']);
    }

    public function make_test_process_skips_by_exif_regexp($configOptionKey, $optionValue)
    {
        $configure = $this->configure();
        $configure->{$configOptionKey} = $optionValue;
        $fileBunch = $this->upgradeWithExifs($this->fileBunch());
        $tokens = $this->tokens();
        $translator = $this->translator()
            ->shouldReceive('translate')
            ->with('originalFileBunchPath', \Mockery::type('array'))
            ->once()
            ->andReturn('unique-message')
            ->getMock()
            ->shouldReceive('translate')
            ->with('skipped/filteredByExifRegExp', \Mockery::type('array'))
            ->once()
            ->andReturn('unique-message')
            ->getMock();
        $processor = new Processor(
            $configure,
            $this->tokenizer($fileBunch, $tokens),
            $this->assemblerNeverCalled(),
            $this->bunchCache(),
            $this->executorNeverCalled(),
            $this->logger(),
            $translator
        );
        $this->assertFalse($processor->process($fileBunch));
    }

    public function test_process_skips_by_exif_regexp()
    {
        $this->make_test_process_skips_by_exif_regexp('regexpExifFilter', ['Model' => 'Leica']);
        $this->make_test_process_skips_by_exif_regexp('regexpExifFilter', ['Model' => '/leica/i']);
        $this->make_test_process_skips_by_exif_regexp('regexpExifFilter', ['Model' => '12.4']);
        $this->make_test_process_skips_by_exif_regexp('regexpExifNegativeFilter', ['Model' => 'Zenit E']);
        $this->make_test_process_skips_by_exif_regexp('regexpExifNegativeFilter', ['Model' => '/zenit/i']);
    }
}
