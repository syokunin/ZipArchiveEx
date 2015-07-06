<?php

use Syokunin\ZipArchiveEx\ZipArchiveEx;

class ZipArchiveExTest extends PHPUnit_Framework_TestCase
{
    protected $zipfile;
    protected $output_dir;
    protected $archive;

    protected function setUp()
    {
        $this->zipfile = __DIR__ . "/datas/sample.zip";
        $this->output_dir = __DIR__ . "/tmp";

        $this->removeOutputDir();

        $this->archive = new ZipArchiveEx();
        $this->archive->open($this->zipfile);
    }

    protected function tearDown()
    {
        $this->archive->close();

       $this->removeOutputDir();
    }

    public function testExtractTo()
    {
        $this->archive->extractTo($this->output_dir);

        $this->assertFileExistsArray([
            'doc1.txt',
            'doc2.txt',
            'command1.sh',
            'command2.sh',
            'links/doc1.txt',
            'links/command1.sh',
        ]);
    }

    public function testExtractToWithString()
    {
        $this->archive->extractTo($this->output_dir, 'doc1.txt');

        $this->assertFileExists($this->output_dir.'/doc1.txt');

        $this->assertFileNotExistsArray([
          'doc2.txt',
          'command1.sh',
          'command2.sh',
          'links/doc1.txt',
          'links/command1.sh',
        ]);
    }

    public function testExtractToWithInvalidString()
    {
        $this->archive->extractTo($this->output_dir, 'foobar');

        $this->assertFileNotExistsArray([
          'doc1.txt',
          'doc2.txt',
          'command1.sh',
          'command2.sh',
          'links/doc1.txt',
          'links/command1.sh',
        ]);
    }

    public function testExtractToWithArray()
    {
        $this->archive->extractTo($this->output_dir, ['doc1.txt', 'links/command1.sh']);

        $this->assertFileExistsArray([
          'doc1.txt',
          'links/command1.sh',
        ]);

        $this->assertFileNotExistsArray([
          'doc2.txt',
          'command1.sh',
          'command2.sh',
          'links/doc1.txt',
        ]);
    }

    public function testExtractToWithInvalidArray()
    {
        $this->archive->extractTo($this->output_dir, ['foo', 'bar']);

        $this->assertFileNotExistsArray([
          'doc1.txt',
          'doc2.txt',
          'command1.sh',
          'command2.sh',
          'links/doc1.txt',
          'links/command1.sh',
        ]);
    }

    public function testExtractSymlink()
    {
        $this->archive->extractTo($this->output_dir);

        if ($this->isWindows()) {
            $this->assertNotTrue(is_link($this->output_dir.'/links/doc1.txt'));
            $this->assertNotTrue(is_link($this->output_dir.'/links/command1.sh'));
        } else {
            $this->assertTrue(is_link($this->output_dir.'/links/doc1.txt'));
            $this->assertTrue(is_link($this->output_dir.'/links/command1.sh'));
        }
    }

    public function testExtractPermission()
    {
        if (! $this->isWindows()) {
            $this->archive->extractTo($this->output_dir);

            $this->assertPermission($this->filePath('doc1.txt'), '644');
            $this->assertPermission($this->filePath('command1.sh'), '755');
            $this->assertPermission($this->filePath('links/doc1.txt'), '644');
            $this->assertPermission($this->filePath('links/command1.sh'), '755');
        }
    }

    protected function assertFileExistsArray(array $files)
    {
        foreach($files as $file) {
            $this->assertFileExists($this->output_dir.'/'.$file);
        }
    }

    protected function assertFileNotExistsArray(array $files)
    {
        foreach($files as $file) {
            $this->assertFileNotExists($this->output_dir.'/'.$file);
        }
    }

    protected function assertPermission($filepath, $permission)
    {
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
                exec("rmdir /s /q  {$this->output_dir}");
            } else {
                exec("rm -rf {$this->output_dir}");
            }
        }
    }

    protected function isWindows()
    {
        return substr(PHP_OS, 0, 3) === 'WIN';
    }

    protected function filePath($filename)
    {
        return $this->output_dir.'/'.$filename;
    }
}
