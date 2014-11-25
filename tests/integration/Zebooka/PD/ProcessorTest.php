<?php

namespace Zebooka\PD;

use Mockery\MockInterface;
use Monolog\Logger;
use Zebooka\Translator\Translator;
use Zebooka\Utils\Executor;

class ProcessorTest extends \PHPUnit_Framework_TestCase
{
    private function resourceDirectory()
    {
        return __DIR__ . '/../../../res/processor';
    }

    /**
     * @return FileBunch
     */
    private function photoBunch()
    {
        return \Mockery::mock('\\Zebooka\\PD\\FileBunch')
            ->shouldReceive('bunchId')
            ->withNoArgs()
            ->andReturn('unique-bunchId')
            ->getMock()
            ->shouldReceive('extensions')
            ->withNoArgs()
            ->andReturn(array('ext', 'EXT2'))
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
    private function tokenizer(FileBunch $photoBunch, Tokens $tokens)
    {
        return \Mockery::mock('\\Zebooka\\PD\\Tokenizer')
            ->shouldReceive('tokenize')
            ->with($photoBunch)
            ->once()
            ->andReturn($tokens)
            ->getMock();
    }

    /**
     * @return Tokenizer
     */
    private function tokenizerException(FileBunch $photoBunch, \Exception $exception)
    {
        return \Mockery::mock('\\Zebooka\\PD\\Tokenizer')
            ->shouldReceive('tokenize')
            ->with($photoBunch)
            ->once()
            ->andThrow($exception)
            ->getMock();
    }

    /**
     * @return Assembler
     */
    private function assembler(Tokens $tokens, FileBunch $photoBunch, $newBunchId)
    {
        return \Mockery::mock('\\Zebooka\\PD\\Assembler')
            ->shouldReceive('assemble')
            ->with($tokens, $photoBunch)
            ->once()
            ->andReturn($newBunchId)
            ->getMock();
    }

    /**
     * @return Assembler
     */
    private function assemblerException(Tokens $tokens, FileBunch $photoBunch, $code)
    {
        return \Mockery::mock('\\Zebooka\\PD\\Assembler')
            ->shouldReceive('assemble')
            ->with($tokens, $photoBunch)
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
     * @return Executor
     */
    private function executor()
    {
        return \Mockery::mock('\\Zebooka\\Utils\\Executor')
            ->shouldReceive('execute')
            ->with(\Mockery::type('string'))
            ->andReturn(0)
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
        $photoBunch = $this->photoBunch();
        $tokens = $this->tokens();
        $processor = new Processor(
            $this->configure(),
            $this->tokenizer($photoBunch, $tokens),
            $this->assembler($tokens, $photoBunch, $this->resourceDirectory() . DIRECTORY_SEPARATOR . 'new-unique-bunchId'),
            $this->executor(),
            $this->logger(),
            $this->translator()
        );

        $this->assertTrue($processor->process($photoBunch));
        $this->assertEquals(0, $processor->bytesProcessed()); // no bytes transfered as files are "deleted", need better test.
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
            $photoBunch = $this->photoBunch();
            $tokens = $this->tokens();
            $processor = new Processor(
                $this->configure(),
                $this->tokenizerException($photoBunch, $exception),
                $this->assemblerNeverCalled(),
                $this->executor(),
                $this->logger(),
                $this->translator()
            );

            $this->assertFalse($processor->process($photoBunch));
            $this->assertEquals(0, $processor->bytesProcessed());
        }
    }

    public function test_process_stops_if_camera_not_in_list()
    {
        $photoBunch = $this->photoBunch();
        $tokens = $this->tokens();
        $processor = new Processor(
            $this->configure(array('camera-1', 'camera-2')),
            $this->tokenizer($photoBunch, $tokens),
            $this->assemblerNeverCalled(),
            $this->executor(),
            $this->logger(),
            $this->translator()
        );

        $this->assertFalse($processor->process($photoBunch));
        $this->assertEquals(0, $processor->bytesProcessed());
    }

    public function test_process_stops_if_assemble_exception()
    {
        $photoBunch = $this->photoBunch();
        $tokens = $this->tokens();
        $processor = new Processor(
            $this->configure(),
            $this->tokenizer($photoBunch, $tokens),
            $this->assemblerException($tokens, $photoBunch, AssemblerException::TEST),
            $this->executor(),
            $this->logger(),
            $this->translator()
        );

        $this->assertFalse($processor->process($photoBunch));
        $this->assertEquals(0, $processor->bytesProcessed());
    }

    public function test_process_stops_if_new_bunchId_is_same_as_old()
    {
        $photoBunch = $this->photoBunch();
        $tokens = $this->tokens();
        $processor = new Processor(
            $this->configure(),
            $this->tokenizer($photoBunch, $tokens),
            $this->assembler($tokens, $photoBunch, 'unique-bunchId'),
            $this->executor(),
            $this->logger(),
            $this->translator()
        );

        $this->assertFalse($processor->process($photoBunch));
        $this->assertEquals(0, $processor->bytesProcessed());
    }

    public function test_process_skips_by_regexp()
    {
        $configure = $this->configure();
        $configure->regexpFilter = '/\\.test$/i';
        $photoBunch = $this->photoBunch();
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
            ->getMock()
        ;
        $processor = new Processor(
            $configure,
            $this->tokenizer($photoBunch, $tokens),
            $this->assembler($tokens, $photoBunch, 'unique-bunchId2'),
            $this->executor(),
            $this->logger(),
            $translator
        );
        $this->assertTrue($processor->process($photoBunch));
        $this->assertEquals(0, $processor->bytesProcessed());
    }
}
