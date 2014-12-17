<?php
/*
 * AssetsInstallerPlugin.php
 */

namespace ReputationVIP\Composer;

class DirectoryHandler
{

    /**
     * @param string $src
     * @param string $dst
     * @return $this
     */
    public function copyDirectory($src, $dst)
    {
        $dir = opendir($src);
        $separator = DIRECTORY_SEPARATOR;
        if (!is_dir($dst)) {
            mkdir($dst, 0777, true);
        }
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $srcFile = $src . $separator . $file;
                $dstFile = $dst . $separator . $file;
                if (is_dir($srcFile)) {
                    $this->copyDirectory($srcFile, $dstFile);
                } else {
                    copy($srcFile, $dstFile);
                }
            }
        }
        closedir($dir);
    }

    /**
     * @param string $dirPath
     * @return $this
     */
    public function deleteDirectory($dirPath)
    {
        if (!is_dir($dirPath)) {
            return false;
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != DIRECTORY_SEPARATOR) {
            $dirPath .= DIRECTORY_SEPARATOR;
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->deleteDirectory($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
        return $this;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function isDirectory($path)
    {
        return is_dir($path);
    }
}
