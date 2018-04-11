<?php

namespace Zebooka\PD;

use PHPUnit\Framework\TestCase;

class AssemblerTest extends TestCase
{
    public function tearDown()
    {
        \Mockery::close();
    }

    private function resourceDirectory()
    {
        return realpath(__DIR__ . '/../../../res/assembler');
    }

    private function configure($to = Configure::KEEP_IN_PLACE, $subDirectoriesStructure = true, $simulate = false)
    {
        $configure = \Mockery::mock('\\Zebooka\\PD\\Configure');
        $configure->to = $to;
        $configure->subDirectoriesStructure = $subDirectoriesStructure;
        $configure->simulate = $simulate;
        $configure->shouldReceive('isKeepInPlace')
            ->withNoArgs()
            ->andReturn(Configure::KEEP_IN_PLACE === $to);
        return $configure;
    }

    private function hashinator()
    {
        return \Mockery::mock('\\Zebooka\\PD\\Hashinator');
    }

    private function tokens($assembledDir = 'assembled-dir', $assembledBasename = 'assembled-basename')
    {
        $tokens = \Mockery::mock('\\Zebooka\\PD\\Tokens')
            ->shouldReceive('assembleBasename')
            ->withNoArgs()
            ->twice()
            ->andReturn($assembledBasename)
            ->getMock();
        if (false !== $assembledDir) {
            $tokens->shouldReceive('assembleDirectory')
                ->with(\Mockery::type('\\Zebooka\\PD\\Configure'))
                ->twice()
                ->andReturn($assembledDir)
                ->getMock();
        }
        return $tokens;
    }

//    private function tokensKeep

    private function fileBunch($originalDir = false)
    {
        $fileBunch = \Mockery::mock('\\Zebooka\\PD\\FileBunch');
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
            $this->configure('unique-dir', true, true),
            $this->hashinator()
        );
        $newBunchId = $assembler->assemble($this->tokens(), $this->fileBunch());
        $this->assertEquals('unique-dir' . DIRECTORY_SEPARATOR . 'assembled-dir' . DIRECTORY_SEPARATOR . 'assembled-basename', $newBunchId);
    }

    public function test_assembling_uniqueDir()
    {
        $assembler = new Assembler(
            $this->configure('unique-dir', true, false),
            $this->hashinator()
        );
        $newBunchId = $assembler->assemble($this->tokens(), $this->fileBunch());
        $this->assertEquals('unique-dir' . DIRECTORY_SEPARATOR . 'assembled-dir' . DIRECTORY_SEPARATOR . 'assembled-basename', $newBunchId);
    }

    public function test_assembling_uniqueDir_without_subDirectoriesStructure_with_simulation()
    {
        $assembler = new Assembler(
            $this->configure('unique-dir', false, true),
            $this->hashinator()
        );
        $newBunchId = $assembler->assemble($this->tokens(false), $this->fileBunch());
        $this->assertEquals('unique-dir' . DIRECTORY_SEPARATOR . 'assembled-basename', $newBunchId);
    }

    public function test_assembling_uniqueDir_without_subDirectoriesStructure()
    {
        $assembler = new Assembler(
            $this->configure('unique-dir', false, false),
            $this->hashinator()
        );
        $newBunchId = $assembler->assemble($this->tokens(false), $this->fileBunch());
        $this->assertEquals('unique-dir' . DIRECTORY_SEPARATOR . 'assembled-basename', $newBunchId);
    }

    public function test_assembling_keepInPlace_with_simulation()
    {
        $assembler = new Assembler(
            $this->configure(Configure::KEEP_IN_PLACE, true, true),
            $this->hashinator()
        );
        $newBunchId = $assembler->assemble($this->tokens(false), $this->fileBunch('original-dir'));
        $this->assertEquals('original-dir' . DIRECTORY_SEPARATOR . 'assembled-basename', $newBunchId);
    }

    public function test_assembling_keepInPlace()
    {
        $assembler = new Assembler(
            $this->configure(Configure::KEEP_IN_PLACE, true, false),
            $this->hashinator()
        );
        $newBunchId = $assembler->assemble($this->tokens(false), $this->fileBunch('original-dir'));
        $this->assertEquals('original-dir' . DIRECTORY_SEPARATOR . 'assembled-basename', $newBunchId);
    }

    public function test_assembling_with_real_directory()
    {
        $assembler = new Assembler(
            $this->configure($this->resourceDirectory(), false, true),
            $this->hashinator()
        );
        $newBunchId = $assembler->assemble($this->tokens(false), $this->fileBunch());
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
            $this->configure($this->resourceDirectory(), false, true),
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
            $this->configure($this->resourceDirectory(), false, true),
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
