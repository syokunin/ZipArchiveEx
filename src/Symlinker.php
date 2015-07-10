<?php

namespace Syokunin\ZipArchiveEx;

use ErrorException;

abstract class Symlinker
{
    abstract protected function makeSymlink($target, $link);

    /**
     * Create symlink file
     *
     * @param string $target
     * @param string $link
     */
    public function symlink($target, $link)
    {
        $this->makeLinkDir($link);
        $this->makeSymlink($target, $link);
    }

    protected function makeLinkDir($link)
    {
        $dir = dirname($link);
        if (! file_exists($dir)) {
            if (! @mkdir($dir, 0777, true)) {
                $error = error_get_last();

                throw new ErrorException(sprintf("%s: %s\n", $error['message'], $dir));
            }
        }
    }
}
