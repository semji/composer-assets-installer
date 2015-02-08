<?php


/*
 * This file is part of the Composer Assets Installer package.
 *
 * (c) Alban Pommeret <ap@reputationvip.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ReputationVIP\Composer;

use Composer\Composer;
use Composer\Config;
use Composer\Json\JsonFile;
use Composer\IO\IOInterface;
use Composer\Package;
use Symfony\Component\Filesystem\Filesystem;

class AssetsInstaller
{
    const LABEL_ASSETS_DIR = "assets-dir";
    const LABEL_PUBLIC_DIR = "public";

    const LOG_WARNING = 1;
    const LOG_INFO = 2;
    const LOG_ERROR = 3;

    const CODE_INSTALLING_DIR = 1;
    const CODE_NO_MATCHING_DIR = 2;
    const CODE_MATCHING_DIR = 3;
    const CODE_NOT_DIRECTORY = 4;
    const CODE_DIRECTORY = 5;
    const CODE_DIR_INSTALLED = 6;
    const CODE_INSTALLING_PACKAGE = 7;
    const CODE_NO_INSTALL = 8;

    /**
     * @var Composer
     */
    public $composer;
    /**
     * @var IOInterface
     */
    private $io;
    /**
     * @var Package\RootPackageInterface
     */
    private $package;
    /**
     * @var string|null
     */
    private $vendorPath = null;
    /**
     * @var string
     */
    public $assetsDirectories = array(self::LABEL_PUBLIC_DIR => "public/assets/");
    /**
     * @var DirectoryHandler|null
     */
    private $directoryHandler;

    public $packagesStatuses = array();


    /**
     * Initializes the plugin
     * Reads the composer.json file and
     * retrieves the assets-dir set if any.
     * This assets-dir is the path where
     * the other packages assets will be installed
     *
     * @param Filesystem $fs
     * @param Composer $composer
     * @param IOInterface $io
     * @param null $directoryHandler
     */
    public function __construct($composer, $io, $directoryHandler = null, Filesystem $fs = null)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->directoryHandler = (!is_null($directoryHandler)) ? $directoryHandler : new DirectoryHandler();
        $this->fs = $fs ?: new Filesystem();

        // We get the current package instance
        $this->package = $composer->getPackage();
        // We read its extra section of composer.json
        $extra = $this->package->getExtra();
        if (isset($extra[self::LABEL_ASSETS_DIR])) {
            $assetsDirs = $extra[self::LABEL_ASSETS_DIR];
            if (is_string($assetsDirs)) {
                // If the assets dir is a string, it targets the whole public directory, we uniform it
                $assetsDirs = array(self::LABEL_PUBLIC_DIR => $assetsDirs);
            }
            // If an assets directory is specified,
            // This directory will be used to put
            // The other packages assets into
            $this->assetsDirectories = $assetsDirs;
        }
    }

    /**
     * Returns the vendor path of the current package
     * @return string
     */
    public function getVendorPath()
    {
        if (is_null($this->vendorPath)) {
            // We get the current package install path
            $installPath = $this->composer->getInstallationManager()->getInstallPath($this->package);
            // We get the name of the package in order to get its target path
            $targetDir = $this->package->getName();
            // We remove this target path to isolate the vendor path only
            $this->vendorPath = explode($targetDir, $installPath)[0];
        }
        return $this->vendorPath;
    }

    /**
     * Installs all the assets directories of the dependency packages
     * @return $this
     */
    public function install()
    {
        // We get all the packages required
        $packages = $this->composer->getPackage()->getRequires();
        foreach ($packages as $package) {
            $this->packagesStatuses[$package->getTarget()] = array('extra' => null, 'dirs' => null);
            $this->installPackage($package);
        }
        return $this;
    }

    /**
     * Installs all the assets directories of the given package
     * @param Package\Link $package
     */
    public function installPackage($package)
    {
        // We read its composer file
        $jsonData = $this->getPackageJsonFile($package);
        // We get the package assets dirs
        $packagesAssetsDir = $this->getPackageAssetsDirs($jsonData);
        if (!is_null($packagesAssetsDir)) {
            $this->log($package->getTarget(), self::LOG_INFO, self::CODE_INSTALLING_PACKAGE);
            $this->packagesStatuses[$package->getTarget()]['extra'] = 1;
            foreach ($packagesAssetsDir as $namespace => $packageAssetsDir) {
                if (!isset($this->packagesStatuses[$package->getTarget()]['dirs'])) {
                    $this->packagesStatuses[$package->getTarget()]['dirs'] = array();
                }
                $this->packagesStatuses[$package->getTarget()]['dirs'][$namespace] = null;
                // We install each package directory
                $this->installPackageDir($package, $namespace, $packageAssetsDir);
            }
        } else {
            $this->log($package->getTarget(), self::LOG_INFO, self::CODE_NO_INSTALL);
            $this->packagesStatuses[$package->getTarget()]['extra'] = 0;
        }
    }

    /**
     * Returns the Json file of the given Package
     * @param Package\Link $package
     * @return mixed
     */
    public function getPackageJsonFile($package)
    {
        // We get the project vendor path
        $vendorPath = $this->getVendorPath();
        // We get the target dir of the iterated package
        $targetDir = $package->getTarget();
        $jsonPath = $vendorPath . $targetDir . "/composer.json";
        if (method_exists($package, 'getJsonFile')) {
            $jsonFile = $package->getJsonFile($jsonPath);
        } else {
            // @codeCoverageIgnoreStart
            $jsonFile = new JsonFile($jsonPath);
            // @codeCoverageIgnoreEnd
        }

        return $this->fs->exists($jsonPath) ? $jsonFile->read() : null;
    }

    /**
     * Returns all the assets directories contained in the given jsonData
     * @param array $jsonData
     * @return array|null
     */
    public function getPackageAssetsDirs($jsonData)
    {
        $packagesAssetsDir = null;
        // If some assets were set on the package
        if (isset($jsonData["extra"][self::LABEL_ASSETS_DIR])) {
            // We get the assets-dir of this package
            $packagesAssetsDir = $jsonData["extra"][self::LABEL_ASSETS_DIR];
            if (is_string($packagesAssetsDir)) {
                // If we get a string, it's the public shortcut so we uniform the return
                $packagesAssetsDir = array(self::LABEL_PUBLIC_DIR => $packagesAssetsDir);
            }
        }
        return $packagesAssetsDir;
    }

    /**
     * Installs a specific directory from the given package
     * @param Package\Link $package
     * @param string $namespace
     * @param string $packageAssetsDir
     */
    public function installPackageDir($package, $namespace, $packageAssetsDir)
    {
        $this->log($package->getTarget(), self::LOG_INFO, self::CODE_INSTALLING_DIR, array('namespace' => $namespace));
        if (!isset($this->assetsDirectories[$namespace])) {
            $this->log($package->getTarget(), self::LOG_WARNING, self::CODE_NO_MATCHING_DIR, array('namespace' => $namespace));
            $this->packagesStatuses[$package->getTarget()]['dirs'][$namespace]['status'] = 0;
            return;
        }
        $this->log($package->getTarget(), self::LOG_INFO, self::CODE_MATCHING_DIR, array('namespace' => $namespace));
        // We get the project vendor path
        $vendorPath = $this->getVendorPath();
        // We get the target dir of the iterated package
        $targetDir = $package->getTarget();
        // We get the full path of the current assets directory
        $packagePath = $vendorPath
            . $targetDir . "/" . $packageAssetsDir;
        // We check if this path is a directory
        if ($this->directoryHandler->isDirectory($packagePath)) {
            $this->log($package->getTarget(), self::LOG_INFO, self::CODE_DIRECTORY, array('directory' => $this->assetsDirectories[$namespace]));
            $src = $packagePath;
            $dst = $vendorPath . "../" . $this->assetsDirectories[$namespace] . $targetDir;
            // We delete the remaining directory if any
            // In order to update it
            $this->directoryHandler->deleteDirectory($dst);
            // We finally copy the package assets-dir
            // Into the project assets-dir
            $this->directoryHandler->copyDirectory($src, $dst);
            $this->log($package->getTarget(), self::LOG_INFO, self::CODE_DIR_INSTALLED, array('destination' => $dst));
            $this->packagesStatuses[$package->getTarget()]['dirs'][$namespace]['status'] = 1;
        } else {
            $this->log($package->getTarget(), self::LOG_ERROR, self::CODE_NOT_DIRECTORY, array('directory' => $this->assetsDirectories[$namespace]));
            $this->packagesStatuses[$package->getTarget()]['dirs'][$namespace]['status'] = 0;
        }
    }

    private function log($packageName, $type, $code, $extras = array())
    {
        $message = '';
        switch ($code) {
            case self::CODE_INSTALLING_DIR:
                $message = $packageName . ' : Installing assets : "' . $extras['namespace'] . '"...';
                break;
            case self::CODE_NO_MATCHING_DIR:
                $message = $packageName . ' : Assets directory not set in composer.json : "' . $extras['namespace'] . '"';
                break;
            case self::CODE_MATCHING_DIR:
                $message = $packageName . ' : Assets directory matches : "' . $extras['namespace'] . '"';
                break;
            case self::CODE_DIRECTORY:
                $message = $packageName . ' : Directory found : "' . $extras['directory'] . '"';
                break;
            case self::CODE_NOT_DIRECTORY:
                $message = $packageName . ' : Directory not found : "' . $extras['directory'] . '"';
                break;
            case self::CODE_DIR_INSTALLED:
                $message = $packageName . ' : Directory installed : "' . $extras['destination'] . '"';
                break;
            case self::CODE_INSTALLING_PACKAGE:
                $message = $packageName . ' : Installation in progress...';
                break;
            case self::CODE_NO_INSTALL:
                $message = $packageName . ' : No assets to install';
                break;
        }
        switch ($type) {
            case self::LOG_INFO:
                $this->io->write(array('    <info>' . $message . '</info>'));
                break;
            case self::LOG_WARNING:
                $this->io->write(array('    <warning>' . $message . '</warning>'));
                break;
            case self::LOG_ERROR:
                $this->io->write(array('    <error>' . $message . '</error>'));
                break;
        }
    }

}
