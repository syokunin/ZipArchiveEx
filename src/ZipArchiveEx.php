<?php

namespace Syokunin\ZipArchiveEx;

use ZipArchive;
use ErrorException;

/**
 * Class ZipArchiveEx
 *
 * Enhancements ZipArchive
 *   Keep Symlink
 *   Keep Permission
 */
class ZipArchiveEx extends ZipArchive
{
    const S_IFLNK = 0120000;        // Symlink
    const ALL_PERMIT = 07777;
    const SHORT_SIZE = 16;

    /** @var  Symlinker */
    protected $symlinker;

    /** @var  string */
    protected $directory;           // Output Directory

    /** @var  array */
    protected $delayed_symlinks;

    public function __construct()
    {
        if ($this->isWindows()) {
            $this->symlinker = new SymlinkerWindows();
        } else {
            $this->symlinker = new SymlinkerUnix();
        }

        $this->delayed_symlinks = [];
    }

    /**
     * Extract one or more file from a zip archive into the given directory.
     *
     * @param string $directory
     * @param mixed|null $entries
     */
    public function extractTo($directory, $entries = null)
    {
        $this->directory = $directory;

        if (is_null($entries)) {
            for ($idx = 0; $stat = $this->statIndex($idx); $idx++) {
                $this->extractFileByName($stat['name']);
            }
            $this->createDelayedSymlinks();
        } else {
            if (is_string($entries)) {
                $this->extractFileByName($entries);
            } elseif (is_array($entries)) {
                foreach ($entries as $entry) {
                    $this->extractFileByName($entry);
                }
                $this->createDelayedSymlinks();
            }
        }
    }

    /**
     * Extract file or symlink from a zip.
     *
     * @param $filename
     */
    protected function extractFileByName($filename)
    {
        $st_mode = $this->getStMode($filename);

        if ($this->isSymlink($st_mode)) {
            $this->delayed_symlinks[] = $filename;
        } else {
            $this->extractFile($filename, $this->getPermission($st_mode));
        }
    }

    /**
     * Extract a file from a zip with permission.
     *
     * @param $filename
     * @param $permission
     */
    protected function extractFile($filename, $permission)
    {
        parent::extractTo($this->directory, $filename);
        if (file_exists($this->getOutputPath($filename))) {
            @chmod($this->getOutputPath($filename), $permission);
        }
    }

    /**
     * Create symlinks
     *
     * When the windows, in order to create a symlink,
     * it is necessary that the target file exists.
     *
     */
    protected function createDelayedSymlinks()
    {
        foreach ($this->delayed_symlinks as $filename) {
            try {
                $this->symlinker->symlink($this->getFromName($filename), $this->getOutputPath($filename));
            } catch (ErrorException $e) {
                fputs(STDERR, $e->getMessage());
            }
        }
    }

    /**
     * Return whether attribute is symlink
     *
     * @param int $attribute
     * @return bool
     */
    protected function isSymlink($st_mode)
    {
        return ($st_mode & ZipArchiveEx::S_IFLNK) === ZipArchiveEx::S_IFLNK;
    }

    /**
     * Return permission
     *
     * @param int $st_mode
     * @return int
     */
    protected function getPermission($st_mode)
    {
        return ($st_mode & ZipArchiveEx::ALL_PERMIT);
    }

    /**
     * Return Stat_mode
     *
     * @param $attribute
     * @return int
     */
    protected function getStMode($filename)
    {
        $opsys = 0;
        $attribute = 0;

        $this->getExternalAttributesName($filename, $opsys, $attribute);

        return ($attribute >> ZipArchiveEx::SHORT_SIZE);
    }

    /**
     * Return Output file path
     *
     * @param $filename
     * @return string
     */
    protected function getOutputPath($filename)
    {
        return $this->directory.'/'.$filename;
    }

    /**
     * Return whether environment is windows
     *
     * @return bool
     */
    protected function isWindows()
    {
        return substr(PHP_OS, 0, 3) === 'WIN';
    }
}
