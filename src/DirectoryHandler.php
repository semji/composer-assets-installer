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
        if (!is_dir($dirPath)) 
        {            
            return false;        
        } 
        
        if (is_dir($dirPath))         
        {             
            $files = scandir($dirPath);             
            foreach ($files as $file) 
            {             
                if ($file != "." && $file != "..")             
                {              
                    if (is_dir($dirPath."/".$file))               
                    $this->deleteDirectory($dirPath."/".$file);             
                }else{               
                    unlink($dirPath."/".$file);             
                }         
            }        
            rmdir($dirPath);         
            return $this;        
        }     
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
