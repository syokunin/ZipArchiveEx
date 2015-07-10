<?php

namespace Syokunin\ZipArchiveEx;

use ErrorException;

class SymlinkerUnix extends  Symlinker
{
    protected function makeSymlink($target, $link)
    {
        if (! @symlink($target, $link)) {
            $error = error_get_last();
            throw new ErrorException(sprintf("%s: %s -> %s\n", $error['message'], $link, $target));
        }
    }
}