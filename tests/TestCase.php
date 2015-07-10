<?php

class TestCase extends  PHPUnit_Framework_TestCase
{
    protected $output_dir;

    protected function setUp()
    {
        $this->output_dir = "tests/tmp";

        $this->removeOutputDir();
    }

    protected function tearDown()
    {
        $this->removeOutputDir();
    }

    protected function assertFileExistsArray(array $files)
    {
        foreach ($files as $file) {
            $this->assertFileExists($this->output_dir.'/'.$file);
        }
    }

    protected function assertFileNotExistsArray(array $files)
    {
        foreach ($files as $file) {
            $this->assertFileNotExists($this->output_dir.'/'.$file);
        }
    }

    protected function assertFileIsLink($file)
    {
        $filepath = $this->outputFilePath($file);

        $this->assertTrue(is_link($filepath), "File isn't link: $filepath");
    }

    protected function assertFileIsLinkArray(array $files)
    {
        foreach ($files as $file) {
            $this->assertFileIsLink($file);
        }
    }

    protected function assertFileIsNotLink($file)
    {
        $filepath = $this->outputFilePath($file);

        $this->assertNotTrue(is_link($filepath), "File is link: $filepath");
    }

    protected function assertFileIsNotLinkArray(array $files)
    {
        foreach ($files as $file) {
            $this->assertFileIsNotLink($file);
        }
    }

    protected function assertFilePermission($file, $permission)
    {
        $filepath = $this->outputFilePath($file);

        $this->assertEquals(
          substr(sprintf('%o', fileperms($filepath)), -3),
          $permission,
          "Permission is wrong.: $filepath"
        );
    }

    protected function removeOutputDir()
    {
        if (file_exists($this->output_dir)) {
            if ($this->isWindows()) {
                $output_dir = str_replace('/', '\\', $this->output_dir);
                exec("rmdir /s /q {$output_dir}");
            } else {
                exec("rm -rf {$this->output_dir}");
            }
        }
    }

    protected function isWindows()
    {
        return substr(PHP_OS, 0, 3) === 'WIN';
    }

    protected function outputFilePath($filename)
    {
        return $this->output_dir.'/'.$filename;
    }
}