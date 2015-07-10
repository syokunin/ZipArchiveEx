<?php

namespace Syokunin\ZipArchiveEx;

use ErrorException;
use Symfony\Component\Process\ProcessUtils;

class SymlinkerWindows extends  Symlinker
{
    protected function makeSymlink($target, $link)
    {
        $absolute_target = $this->getAbsolutePath($target, dirname($link));

        if (! file_exists($absolute_target)) {
            throw new ErrorException(sprintf("Link target doesn't exist.: %s\n", $absolute_target));
        }

        $caller = $this->getCaller($absolute_target);
        if (is_null($caller)) {
            if (! @symlink($target, $link)) {
                $error = error_get_last();
                throw new ErrorException(sprintf("%s: %s -> %s\n", $error['message'], $link, $target));
            }
        } else {
            $this->symlinkWindows($target, $link, $caller);
            $this->symlinkCygwin($target, $link);
        }
    }

    protected function getAbsolutePath($path, $cwd = null)
    {
        if ($path[0] === '/') {
            return $path;
        }

        if (is_null($cwd)) {
            $absolute_path =  getcwd().'/'.$path;
        } else {
            $absolute_path = $this->getAbsolutePath($cwd).'/'.$path;
        }

        return $absolute_path;
    }

    protected function getCaller($target)
    {
        $caller = null;

        if ($handle = @fopen($target, 'r')) {
            $line = fgets($handle);
            fclose($handle);
        } else {
            $error = error_get_last();
            throw new ErrorException(sprintf("%s\n", $error['message']));
        }

        if (preg_match('{^#!/(?:usr/bin/env )?(?:[^/]+/)*(.+)$}m', $line, $match)) {
            $caller = trim($match[1]);
        }

        return $caller;
    }

    protected function symlinkWindows($target, $link, $caller)
    {
        file_put_contents($link.'.bat', $this->generateWindowsProxyCode($target, $caller));
    }

    protected function generateWindowsProxyCode($target, $caller)
    {
        return "@ECHO OFF\r\n".
        "SET BIN_TARGET=%~dp0/".trim(ProcessUtils::escapeArgument($target), '"')."\r\n".
        "{$caller} \"%BIN_TARGET%\" %*\r\n";
    }

    protected function symlinkCygwin($target, $link)
    {
        file_put_contents($link, $this->generateUnixyProxyCode($target));
        if (file_exists($link)) {
            @chmod($link, 0777 & ~umask());
        }
    }

    protected function generateUnixyProxyCode($target)
    {
        return "#!/usr/bin/env sh\n".
        'SRC_DIR="`pwd`"'."\n".
        'cd "`dirname "$0"`"'."\n".
        'cd '.ProcessUtils::escapeArgument(dirname($target))."\n".
        'BIN_TARGET="`pwd`/'.basename($target)."\"\n".
        'cd "$SRC_DIR"'."\n".
        '"$BIN_TARGET" "$@"'."\n";
    }
}