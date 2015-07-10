<?php

use Syokunin\ZipArchiveEx\SymlinkerWindows;

class SymlinkerWindowsTest extends TestCase
{
    /** @var  SymlinkerWindows */
    protected $symlinker;

    protected function setUp()
    {
        parent::setUp();

        $this->symlinker = new SymlinkerWindows();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testSymlink_should_make_symlink()
    {
        $this->symlinker->symlink('../datas/targets/target_sh', $this->outputFilePath('target_sh'));

        $this->assertFileExists($this->outputFilePath('target_sh.bat'));
        $this->assertFileExists($this->outputFilePath('target_sh'));
        $this->assertFileIsNotLink('target_sh.bat');
        $this->assertFileIsNotLink('target_sh');
    }

    public function testSymlink_should_make_symlink_in_subdirectory()
    {
        $this->symlinker->symlink('../../datas/targets/target_php', $this->outputFilePath('subdir/target_php'));

        $this->assertFileExists($this->outputFilePath('subdir/target_php.bat'));
        $this->assertFileExists($this->outputFilePath('subdir/target_php'));
        $this->assertFileIsNotLink('subdir/target_php.bat');
        $this->assertFileIsNotLink('subdir/target_php');
    }

    public function testSymlink_can_not_make_symlink()
    {
        if ($this->isWindows()) {
            try {
                $this->symlinker->symlink('../datas/targets/target.txt', $this->outputFilePath('target.txt'));
            } catch (ErrorException $e) {
                $this->assertRegExp('/symlink(): Cannot create symlink/', $e->getMessage());
            }
        }
    }

    /**
     * @expectedException ErrorException
     * @expectedExceptionMessageRegExp #Link target doesn't exist.:#
     */
    public function testSymlink_should_not_make_bloken_symlink()
    {
        $this->symlinker->symlink('invalid_target', $this->outputFilePath('target_sh'));
    }

    public function testGetAbsolutePath_should_return_absolutePath()
    {
        $bcl1 = Closure::bind(function ($target) {
            return $this->getAbsolutePath($target);
        }, $this->symlinker, $this->symlinker);

        $bcl2 = Closure::bind(function ($target, $link) {
            return $this->getAbsolutePath($target, $link);
        }, $this->symlinker, $this->symlinker);

        $this->assertEquals('/foo/bar', $bcl1('/foo/bar'));
        $this->assertEquals('/foo/bar', $bcl2('/foo/bar', 'baz'));
    }

    public function testGetAbsolutePath_should_return_relativePath()
    {
        $bcl1 = Closure::bind(function ($target) {
            return $this->getAbsolutePath($target);
        }, $this->symlinker, $this->symlinker);

        $bcl2 = Closure::bind(function ($target, $link) {
            return $this->getAbsolutePath($target, $link);
        }, $this->symlinker, $this->symlinker);

        $this->assertEquals(getcwd().'/../foo/bar', $bcl1('../foo/bar'));
        $this->assertEquals(getcwd().'/../tmp/../foo/bar', $bcl2('../foo/bar', '../tmp'));
        $this->assertEquals('/tmp/../foo/bar', $bcl2('../foo/bar', '/tmp'));
    }

    public function testGetCaller_should_return_caller()
    {
        $bcl1 = Closure::bind(function ($target) {
            return $this->getCaller($target);
        }, $this->symlinker, $this->symlinker);

        $this->assertEquals('php', $bcl1('tests/datas/targets/target_php'));
        $this->assertEquals('sh', $bcl1('tests/datas/targets/target_sh'));
    }

    public function testGetCaller_should_return_null()
    {
        $bcl1 = Closure::bind(function ($target) {
            return $this->getCaller($target);
        }, $this->symlinker, $this->symlinker);

        $this->assertEquals(null, $bcl1('tests/datas/sample.zip'));
    }

    /**
     * @expectedException ErrorException
     * @expectedExceptionMessageRegExp #fopen(.*): failed to open stream: No such file or directory#
     */
    public function testGetCaller_should_throw_ErrorException()
    {
        $bcl1 = Closure::bind(function ($target) {
            return $this->getCaller($target);
        }, $this->symlinker, $this->symlinker);

        $bcl1('invalid_target');
    }

    public function testGenerateWindowsProxyCode()
    {
        $bcl1 = Closure::bind(function ($target, $caller) {
            return $this->generateWindowsProxyCode($target, $caller);
        }, $this->symlinker, $this->symlinker);

        $target = "../target";

        $this->assertEquals(
            "@ECHO OFF\r\n" .
              "SET BIN_TARGET=%~dp0/'" . $target . "'\r\n" .
              "php \"%BIN_TARGET%\" %*\r\n",
            $bcl1('../target', 'php')
        );
    }

    public function testGenerateUnixyProxyCode()
    {
        $bcl1 = Closure::bind(function ($target) {
            return $this->generateUnixyProxyCode($target);
        }, $this->symlinker, $this->symlinker);

        $target = "../target";

        $this->assertEquals(
            "#!/usr/bin/env sh\n" .
              'SRC_DIR="`pwd`"' . "\n" .
              'cd "`dirname "$0"`"' . "\n" .
              "cd '" . dirname($target) . "'\n" .
              'BIN_TARGET="`pwd`/' . basename($target) . "\"\n" .
              'cd "$SRC_DIR"' . "\n" .
              '"$BIN_TARGET" "$@"' . "\n",
            $bcl1($target)
        );
    }
}