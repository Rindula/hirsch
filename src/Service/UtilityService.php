<?php

/*
 * (c) Sven Nolting, 2023
 */

namespace App\Service;

class UtilityService
{
    /**
     * Generate an MD5 hash string from the contents of a directory.
     */
    public function hashDirectory(string $directory): bool|string
    {
        if (!is_dir($directory) || !is_readable($directory) || !str_starts_with(
            realpath($directory),
            dirname(__DIR__, 2)
        )
        ) {
            return false;
        }

        $files = [];
        $dir = dir($directory);

        if ($dir) {
            while (false !== ($file = $dir->read())) {
                if ('.' !== $file && '..' !== $file) {
                    if (is_dir(sprintf('%s/%s', $directory, $file))) {
                        $files[] = $this->hashDirectory(sprintf('%s/%s', $directory, $file));
                    } elseif (is_file(sprintf('%s/%s', $directory, $file))) {
                        $files[] = md5_file(sprintf('%s/%s', $directory, $file));
                    }
                }
            }

            $dir->close();
        }

        $files = array_filter($files);

        if (0 === count($files)) {
            return false;
        }

        return md5(implode('', $files));
    }
}
