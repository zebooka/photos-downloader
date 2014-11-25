<?php

namespace Zebooka\PD;

class AssemblerTest extends \PHPUnit_Framework_TestCase
{
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
            ->once()
            ->andReturn($assembledBasename)
            ->getMock();
        if (false !== $assembledDir) {
            $tokens->shouldReceive('assembleDirectory')
                ->withNoArgs()
                ->once()
                ->andReturn($assembledDir)
                ->getMock();
        }
        return $tokens;
    }

//    private function tokensKeep

    private function photoBunch($originalDir = false)
    {
        $photoBunch = \Mockery::mock('\\Zebooka\\PD\\FileBunch');
        if (false !== $originalDir) {
            $photoBunch->shouldReceive('directory')
                ->withNoArgs()
                ->once()
                ->andReturn($originalDir)
                ->getMock();
        }
        return $photoBunch;
    }

    public function test_assembling_uniqueDir_with_simulation()
    {
        $assembler = new Assembler(
            $this->configure('unique-dir', true, true),
            $this->hashinator()
        );
        $newBunchId = $assembler->assemble($this->tokens(), $this->photoBunch());
        $this->assertEquals('unique-dir' . DIRECTORY_SEPARATOR . 'assembled-dir' . DIRECTORY_SEPARATOR . 'assembled-basename', $newBunchId);
    }

    public function test_assembling_uniqueDir()
    {
        $assembler = new Assembler(
            $this->configure('unique-dir', true, false),
            $this->hashinator()
        );
        $newBunchId = $assembler->assemble($this->tokens(), $this->photoBunch());
        $this->assertEquals('unique-dir' . DIRECTORY_SEPARATOR . 'assembled-dir' . DIRECTORY_SEPARATOR . 'assembled-basename', $newBunchId);
    }

    public function test_assembling_uniqueDir_without_subDirectoriesStructure_with_simulation()
    {
        $assembler = new Assembler(
            $this->configure('unique-dir', false, true),
            $this->hashinator()
        );
        $newBunchId = $assembler->assemble($this->tokens(false), $this->photoBunch());
        $this->assertEquals('unique-dir' . DIRECTORY_SEPARATOR . 'assembled-basename', $newBunchId);
    }

    public function test_assembling_uniqueDir_without_subDirectoriesStructure()
    {
        $assembler = new Assembler(
            $this->configure('unique-dir', false, false),
            $this->hashinator()
        );
        $newBunchId = $assembler->assemble($this->tokens(false), $this->photoBunch());
        $this->assertEquals('unique-dir' . DIRECTORY_SEPARATOR . 'assembled-basename', $newBunchId);
    }

    public function test_assembling_keepInPlace_with_simulation()
    {
        $assembler = new Assembler(
            $this->configure(Configure::KEEP_IN_PLACE, true, true),
            $this->hashinator()
        );
        $newBunchId = $assembler->assemble($this->tokens(false), $this->photoBunch('original-dir'));
        $this->assertEquals('original-dir' . DIRECTORY_SEPARATOR . 'assembled-basename', $newBunchId);
    }

    public function test_assembling_keepInPlace()
    {
        $assembler = new Assembler(
            $this->configure(Configure::KEEP_IN_PLACE, true, false),
            $this->hashinator()
        );
        $newBunchId = $assembler->assemble($this->tokens(false), $this->photoBunch('original-dir'));
        $this->assertEquals('original-dir' . DIRECTORY_SEPARATOR . 'assembled-basename', $newBunchId);
    }

    public function test_assembling_with_real_directory()
    {
        $assembler = new Assembler(
            $this->configure($this->resourceDirectory(), false, true),
            $this->hashinator()
        );
        $newBunchId = $assembler->assemble($this->tokens(false), $this->photoBunch());
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

        $photoBunch = new FileBunch('old-bunchId', array('dng', 'JPG'));
        $tokens = new Tokens(
            array('date', 'time'),
            array('token-1', 'token-2'),
            'author',
            'camera',
            'prefix',
            null
        );
        $newBunchId = $assembler->assemble($tokens, $photoBunch);
        $this->assertEquals($this->resourceDirectory() . DIRECTORY_SEPARATOR . 'prefix_date_time,2_author_camera_token-1_token-2', $newBunchId);

        $photoBunch2 = new FileBunch('old-bunchId-2', array('dng'));
        $tokens2 = new Tokens(
            array('date', 'time'),
            array('token-1', 'token-2'),
            'author',
            'camera',
            'prefix',
            null
        );
        $newBunchId = $assembler->assemble($tokens2, $photoBunch2);
        $this->assertEquals($this->resourceDirectory() . DIRECTORY_SEPARATOR . 'prefix_date_time,3_author_camera_token-1_token-2', $newBunchId);
    }
}
