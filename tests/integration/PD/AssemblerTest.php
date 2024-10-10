<?php

namespace Zebooka\PD;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;

class AssemblerTest extends TestCase
{
    use InputTrait;

    public function tearDown(): void
    {
        \Mockery::close();
    }

    private function resourceDirectory()
    {
        return realpath(__DIR__ . '/../../res/assembler');
    }

    private function configure($isKeepInPlace)
    {
        return \Mockery::mock(Configure::class)
            ->shouldReceive('isKeepInPlace')
            ->withNoArgs()
            ->andReturn($isKeepInPlace)
            ->getMock();
    }

    private function hashinator()
    {
        return \Mockery::mock(Hashinator::class);
    }

    private function tokens($assembledDir = 'assembled-dir', $assembledBasename = 'assembled-basename')
    {
        $tokens = \Mockery::mock(Tokens::class)
            ->shouldReceive('assembleBasename')
            ->withNoArgs()
            ->twice()
            ->andReturn($assembledBasename)
            ->getMock();
        if (false !== $assembledDir) {
            $tokens->shouldReceive('assembleDirectory')
                ->with(\Mockery::type(InputInterface::class))
                ->twice()
                ->andReturn($assembledDir)
                ->getMock();
        }
        return $tokens;
    }

    private function fileBunch($originalDir = false)
    {
        $fileBunch = \Mockery::mock(FileBunch::class);
        if (false !== $originalDir) {
            $fileBunch->shouldReceive('directory')
                ->withNoArgs()
                ->twice()
                ->andReturn($originalDir)
                ->getMock();
        }
        return $fileBunch;
    }

    public function test_assembling_uniqueDir_with_simulation()
    {
        $assembler = new Assembler(
            $this->configure(false),
            $this->input([
                Command::TO => 'unique-dir',
                Command::NO_SUBDIRS => false,
                Command::SIMULATE => true,
            ]),
            $this->hashinator()
        );
        $newBunchId = $assembler->assemble($this->tokens(), $this->fileBunch());
        $this->assertEquals('unique-dir' . DIRECTORY_SEPARATOR . 'assembled-dir' . DIRECTORY_SEPARATOR . 'assembled-basename', $newBunchId);
    }

    public function test_assembling_uniqueDir()
    {
        $assembler = new Assembler(
            $this->configure(false),
            $this->input([
                Command::TO => 'unique-dir',
                Command::NO_SUBDIRS => false,
                Command::SIMULATE => false,
            ]),
            $this->hashinator()
        );
        $newBunchId = $assembler->assemble($this->tokens(), $this->fileBunch());
        $this->assertEquals('unique-dir' . DIRECTORY_SEPARATOR . 'assembled-dir' . DIRECTORY_SEPARATOR . 'assembled-basename', $newBunchId);
    }

    public function test_assembling_uniqueDir_without_subDirectoriesStructure_with_simulation()
    {
        $assembler = new Assembler(
            $this->configure(false),
            $this->input([
                Command::TO => 'unique-dir',
                Command::NO_SUBDIRS => true,
                Command::SIMULATE => true,
            ]),
            $this->hashinator()
        );
        $newBunchId = $assembler->assemble($this->tokens(false), $this->fileBunch());
        $this->assertEquals('unique-dir' . DIRECTORY_SEPARATOR . 'assembled-basename', $newBunchId);
    }

    public function test_assembling_uniqueDir_without_subDirectoriesStructure()
    {
        $assembler = new Assembler(
            $this->configure(false),
            $this->input([
                Command::TO => 'unique-dir',
                Command::NO_SUBDIRS => true,
                Command::SIMULATE => false,
            ]),
            $this->hashinator()
        );
        $newBunchId = $assembler->assemble($this->tokens(false), $this->fileBunch());
        $this->assertEquals('unique-dir' . DIRECTORY_SEPARATOR . 'assembled-basename', $newBunchId);
    }

    public function test_assembling_keepInPlace_with_simulation()
    {
        $assembler = new Assembler(
            $this->configure(true),
            $this->input([
                Command::TO => '-',
                Command::NO_SUBDIRS => true,
                Command::SIMULATE => true,
            ]),
            $this->hashinator()
        );
        $newBunchId = $assembler->assemble($this->tokens(false), $this->fileBunch('original-dir'));
        $this->assertEquals('original-dir' . DIRECTORY_SEPARATOR . 'assembled-basename', $newBunchId);
    }

    public function test_assembling_keepInPlace()
    {
        $assembler = new Assembler(
            $this->configure(true),
            $this->input([
                Command::TO => '-',
                Command::NO_SUBDIRS => true,
                Command::SIMULATE => false,
            ]),
            $this->hashinator()
        );
        $newBunchId = $assembler->assemble($this->tokens(false), $this->fileBunch('original-dir'));
        $this->assertEquals('original-dir' . DIRECTORY_SEPARATOR . 'assembled-basename', $newBunchId);
    }

    public function test_assembling_with_real_directory()
    {
        $assembler = new Assembler(
            $this->configure(false),
            $this->input([
                Command::TO => '-',
                Command::NO_SUBDIRS => false,
                Command::SIMULATE => true,
            ]),
            $this->hashinator()
        );
        $newBunchId = $assembler->assemble($this->tokens(false), $this->fileBunch($this->resourceDirectory()));
        $this->assertEquals($this->resourceDirectory() . DIRECTORY_SEPARATOR . 'assembled-basename', $newBunchId);
    }

    public function test_assembling_with_real_directory_and_taken_basename()
    {
        $hashinator = $this->hashinator()
            ->shouldReceive('equal')
            ->with($this->resourceDirectory() . DIRECTORY_SEPARATOR . 'prefix_date_time_author_camera_token-1_token-2.jpg', 'old-bunchId.JPG')
            ->once()
            ->andReturn(false)
            ->getMock()
            ->shouldReceive('equal')
            ->with('old-bunchId.dng', 'old-bunchId-2.dng')
            ->once()
            ->andReturn(false)
            ->getMock();

        $assembler = new Assembler(
            $this->configure(false),
            $this->input([
                Command::TO => $this->resourceDirectory(),
                Command::NO_SUBDIRS => false,
                Command::SIMULATE => true,
            ]),
            $hashinator
        );

        $fileBunch = new FileBunch('old-bunchId', array('dng', 'JPG'));
        $tokens = new Tokens(
            array('date', 'time'),
            array('token-1', 'token-2'),
            'author',
            'camera',
            'prefix',
            null
        );
        $newBunchId = $assembler->assemble($tokens, $fileBunch);
        $this->assertEquals($this->resourceDirectory() . DIRECTORY_SEPARATOR . 'prefix_date_time_2_author_camera_token-1_token-2', $newBunchId);

        $fileBunch2 = new FileBunch('old-bunchId-2', array('dng'));
        $tokens2 = new Tokens(
            array('date', 'time'),
            array('token-1', 'token-2'),
            'author',
            'camera',
            'prefix',
            null
        );
        $newBunchId = $assembler->assemble($tokens2, $fileBunch2);
        $this->assertEquals($this->resourceDirectory() . DIRECTORY_SEPARATOR . 'prefix_date_time_3_author_camera_token-1_token-2', $newBunchId);
    }

    public function test_assembling_with_real_directory_and_taken_basename_with_shot_1()
    {
        $hashinator = $this->hashinator()
            ->shouldReceive('equal')
            ->with($this->resourceDirectory() . DIRECTORY_SEPARATOR . 'date_time_1.jpg', 'old-bunchId.JPG')
            ->once()
            ->andReturn(false)
            ->getMock()
            ->shouldReceive('equal')
            ->with('old-bunchId.dng', 'old-bunchId-2.dng')
            ->once()
            ->andReturn(false)
            ->getMock();

        $assembler = new Assembler(
            $this->configure(false),
            $this->input([
                Command::TO => $this->resourceDirectory(),
                Command::NO_SUBDIRS => false,
                Command::SIMULATE => true,
            ]),
            $hashinator
        );

        $fileBunch = new FileBunch('old-bunchId', array('dng', 'JPG'));
        $tokens = new Tokens(array('date', 'time'));
        $newBunchId = $assembler->assemble($tokens, $fileBunch);
        $this->assertEquals($this->resourceDirectory() . DIRECTORY_SEPARATOR . 'date_time_2', $newBunchId);

        $fileBunch2 = new FileBunch('old-bunchId-2', array('dng'));
        $tokens2 = new Tokens(array('date', 'time'));
        $newBunchId = $assembler->assemble($tokens2, $fileBunch2);
        $this->assertEquals($this->resourceDirectory() . DIRECTORY_SEPARATOR . 'date_time_3', $newBunchId);
    }
}
