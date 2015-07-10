<?php

use Syokunin\ZipArchiveEx\SymlinkerUnix;

class SymlinkerUnixTest extends TestCase
{
    /** @var  SymlinkerUnix */
    protected $symlinker;

    protected function setUp()
    {
        parent::setUp();

        $this->symlinker = new SymlinkerUnix();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testSymlink_should_make_symlink()
    {
        if ($this->isWindows()) {
            return;
        }

        $this->symlinker->symlink('../datas/targets/target_sh', $this->outputFilePath('target_sh'));
        $this->symlinker->symlink('../datas/targets/target_php', $this->outputFilePath('target_php'));

        $this->assertFileIsLink('target_sh');
        $this->assertFileIsLink('target_php');
    }

    public function testSymlink_should_make_symlink_in_subdirectory()
    {
        if ($this->isWindows()) {
            return;
        }

        $this->symlinker->symlink('../../datas/targets/target_php', $this->outputFilePath('subdir/target_php'));

        $this->assertFileIsLink('subdir/target_php');
    }

    public function testSymlink_should_make_bloken_symlink()
    {
        if ($this->isWindows()) {
            return;
        }

        $this->symlinker->symlink('invalid_target', $this->outputFilePath('target_sh'));
        $this->symlinker->symlink('invalid_target', $this->outputFilePath('target_php'));

        // Broken link was created.
        $this->assertFileIsLink('target_sh');
        $this->assertFileIsLink('target_php');
    }
}