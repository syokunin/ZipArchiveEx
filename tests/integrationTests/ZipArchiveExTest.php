<?php

use Syokunin\ZipArchiveEx\ZipArchiveEx;

class ZipArchiveExTest extends TestCase
{
    /** @var  string */
    protected $zipfile;

    /** @var  ZipArchiveEx */
    protected $archive;

    protected function setUp()
    {
        parent::setUp();

        $this->zipfile = "tests/datas/sample.zip";

        $this->archive = new ZipArchiveEx();
        $this->archive->open($this->zipfile);
    }

    protected function tearDown()
    {
        $this->archive->close();

        parent::tearDown();
    }

    /**
     * @group main
     */
    public function testExtractTo_should_extract_all_files()
    {
        $this->archive->extractTo($this->output_dir);

        if ($this->isWindows()) {
          $this->assertFileExistsArray([
              'a_command1.sh',
              'command1.sh',
              'doc1.txt',
              'links/command1.sh',
              'links/command2.sh',
              'z_files/command2.sh',
              'z_files/doc2.txt',
          ]);

          $this->assertFileNotExistsArray([
              'a_doc1.txt',
              'links/doc1.txt',
              'links/doc2.txt',
              'links/z_files',
          ]);
        } else {
          $this->assertFileExistsArray([
              'a_command1.sh',
              'a_doc1.txt',
              'command1.sh',
              'doc1.txt',
              'links/command1.sh',
              'links/command2.sh',
              'links/doc1.txt',
              'links/doc2.txt',
              'links/z_files',
              'z_files/command2.sh',
              'z_files/doc2.txt',
          ]);
        }
    }

    public function testExtractTo_should_extract_one_file()
    {
        $this->archive->extractTo($this->output_dir, 'doc1.txt');

        $this->assertFileExists($this->outputFilePath('/doc1.txt'));

        $this->assertFileNotExistsArray([
            'a_command1.sh',
            'a_doc1.txt',
            'command1.sh',
            'links/command1.sh',
            'links/command2.sh',
            'links/doc1.txt',
            'links/doc2.txt',
            'links/z_files',
            'z_files/command2.sh',
            'z_files/doc2.txt',
        ]);
    }

    public function testExtractTo_should_not_extract_one_file_with_invalid_filename()
    {
        $this->archive->extractTo($this->output_dir, 'invalid_filename');

        $this->assertFileNotExistsArray([
            'a_command1.sh',
            'a_doc1.txt',
            'command1.sh',
            'doc1.txt',
            'links/command1.sh',
            'links/command2.sh',
            'links/doc1.txt',
            'links/doc2.txt',
            'links/z_files',
            'z_files/command2.sh',
            'z_files/doc2.txt',
        ]);
    }

    public function testExtractTo_should_extract_some_files()
    {
        $this->archive->extractTo($this->output_dir, [
            'doc1.txt',
            'z_files/doc2.txt',
        ]);

        $this->assertFileExistsArray([
            'doc1.txt',
            'z_files/doc2.txt',
        ]);

        $this->assertFileNotExistsArray([
            'a_command1.sh',
            'a_doc1.txt',
            'command1.sh',
            'links/command1.sh',
            'links/command2.sh',
            'links/doc1.txt',
            'links/doc2.txt',
            'links/z_files',
            'z_files/command2.sh',
        ]);
    }

    public function testExtractTo_should_not_extract_any_files_with_invalid_filenames()
    {
        $this->archive->extractTo($this->output_dir, ['foo', 'bar']);

        $this->assertFileNotExistsArray([
            'a_command1.sh',
            'a_doc1.txt',
            'command1.sh',
            'doc1.txt',
            'links/command1.sh',
            'links/command2.sh',
            'links/doc1.txt',
            'links/doc2.txt',
            'links/z_files',
            'z_files/command2.sh',
            'z_files/doc2.txt',
        ]);
    }

    public function testExtractTo_should_make_symlink()
    {
        $this->archive->extractTo($this->output_dir);

        $files = [
          'a_command1.sh',
          'a_doc1.txt',
          'links/command1.sh',
          'links/command2.sh',
          'links/doc1.txt',
          'links/doc2.txt',
          'links/z_files',
        ];

        if ($this->isWindows()) {
            $this->assertFileIsNotLinkArray($files);
        } else {
            $this->assertFileIsLinkArray($files);
        }
    }

    public function testExtractPermission()
    {
        if (! $this->isWindows()) {
            $this->archive->extractTo($this->output_dir);

            $this->assertFilePermission('command1.sh', '755');
            $this->assertFilePermission('doc1.txt', '644');
            $this->assertFilePermission('z_files/command2.sh', '755');
            $this->assertFilePermission('z_files/doc2.txt', '644');
            $this->assertFilePermission('z_files', '755');

            $this->assertFilePermission('a_command1.sh', '755');
            $this->assertFilePermission('a_doc1.txt', '644');
            $this->assertFilePermission('links/command1.sh', '755');
            $this->assertFilePermission('links/command2.sh', '755');
            $this->assertFilePermission('links/doc1.txt', '644');
            $this->assertFilePermission('links/doc2.txt', '644');
            $this->assertFilePermission('links/z_files', '755');
        }
    }
}
