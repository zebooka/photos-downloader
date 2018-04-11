<?php

namespace Zebooka\PD;

use Mockery\MockInterface;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Zebooka\Translator\Translator;
use Zebooka\Utils\Executor;

class ProcessorTest extends TestCase
{
    public function tearDown()
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
        return \Mockery::mock('\\Zebooka\\PD\\FileBunch')
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
     * @return Tokens
     */
    private function tokens()
    {
        return \Mockery::mock('\\Zebooka\\PD\\Tokens');
    }

    /**
     * @return Configure
     */
    private function configure(array $cameras = array())
    {
        $configure = \Mockery::mock('\\Zebooka\\PD\\Configure');
        $configure->cameras = $cameras;
        return $configure;
    }

    /**
     * @return Tokenizer
     */
    private function tokenizer(FileBunch $fileBunch, Tokens $tokens)
    {
        return \Mockery::mock('\\Zebooka\\PD\\Tokenizer')
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
        return \Mockery::mock('\\Zebooka\\PD\\Tokenizer')
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
        return \Mockery::mock('\\Zebooka\\PD\\Assembler')
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
        return \Mockery::mock('\\Zebooka\\PD\\Assembler')
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
        return \Mockery::mock('\\Zebooka\\PD\\Assembler')
            ->shouldReceive('assemble')
            ->never()
            ->getMock();
    }

    /**
     * @return BunchCache
     */
    private function bunchCache()
    {
        return \Mockery::mock('\\Zebooka\\PD\\BunchCache');
    }

    /**
     * @return Executor
     */
    private function executor()
    {
        return \Mockery::mock('\\Zebooka\\Utils\\Executor')
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
        return \Mockery::mock('\\Zebooka\\Utils\\Executor')
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
        return \Mockery::mock('\\Zebooka\\Translator\\Translator')
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
        $this->assertEquals(12, $processor->bytesProcessed());
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

    public function test_process_skips_by_regexp()
    {
        $configure = $this->configure();
        $configure->regexpFilter = '/\\.test$/i';
        $fileBunch = $this->fileBunch();
        $tokens = $this->tokens();
        $translator = $this->translator()
            ->shouldReceive('translate')
            ->with('originalFileBunchPath', \Mockery::type('array'))
            ->once()
            ->andReturn('unique-message')
            ->getMock()
            ->shouldReceive('translate')
            ->with('skipped/filteredByRegExp', \Mockery::type('array'))
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
        $this->assertEquals(12, $processor->bytesProcessed());
    }

    public function test_process_skips_by_negative_regexp()
    {
        $configure = $this->configure();
        $configure->regexpNegativeFilter = '/\\.ext2?$/i';
        $fileBunch = $this->fileBunch();
        $tokens = $this->tokens();
        $translator = $this->translator()
            ->shouldReceive('translate')
            ->with('originalFileBunchPath', \Mockery::type('array'))
            ->once()
            ->andReturn('unique-message')
            ->getMock()
            ->shouldReceive('translate')
            ->with('skipped/filteredByRegExp', \Mockery::type('array'))
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
        $this->assertEquals(12, $processor->bytesProcessed());
    }
}
