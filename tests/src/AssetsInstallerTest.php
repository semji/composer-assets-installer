<?php

/*
 * This file is part of the Composer Assets Installer package.
 *
 * (c) Alban Pommeret <ap@reputationvip.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use ReputationVIP\Composer\AssetsInstaller;
use Composer\Config;
use Composer\IO\NullIO;
use Composer\Package;

class AssetsInstallerTest extends \PHPUnit_Framework_TestCase
{
    const NS_DEFAULT = 'default';

    private $mockLinks = array(
        self::NS_DEFAULT => array(
            array(
                'target' => 'default/package',
                'jsonFile' => array(
                    'extra' => array(
                        'assets-dir' => 'web'
                    )
                )
            )
        ),
        'noAssetsDir' => array(
            array(
                'target' => 'default/package',
                'jsonFile' => array()
            )
        )
    );

    private $mockDirectoryHandler = array(
        self::NS_DEFAULT => array(
            'isDirectory' => true
        ),
        'notDirectory' => array(
            'isDirectory' => false
        )
    );

    private $mockPackage = array(
        self::NS_DEFAULT => array(
            'extra' => array(
                'assets-dir' => 'customdir'
            ),
            'name' => 'default/package',
            'installPath' => '/tmp/default/package',
            'target' => 'default/package',
            'jsonFile' => array(
                'extra' => array(
                    'assets-dir' => 'customdir'
                )
            )
        ),
        'noExtra' => array(
            'extra' => null,
            'name' => 'default/package',
            'installPath' => '/tmp/default/package',
            'target' => 'default/package',
            'jsonFile' => array(
                'extra' => null
            )
        ),
        'multipleDirs' => array(
            'extra' => array(
                'assets-dir' => array(
                    'js' => 'public/js',
                    'css' => 'css'
                )
            ),
            'name' => 'default/package',
            'installPath' => '/tmp/default/package',
            'target' => 'default/package',
            'jsonFile' => array(
                'extra' => array(
                    'assets-dir' => array(
                        'js' => 'public/js',
                        'css' => 'css'
                    )
                )
            )
        )
    );

    public function testShouldFormatAssetsDirToArrayWhenStringIsConfigured()
    {
        $assetsInstaller = $this->getAssetsInstaller();
        $this->assertEquals(array('public' => 'customdir'), $assetsInstaller->assetsDirectories);
    }

    public function testShouldSetDefaultAssetsDirWhenNoneIsConfigured()
    {
        $assetsInstaller = $this->getAssetsInstaller('noExtra');
        $this->assertEquals(array('public' => 'public/assets/'), $assetsInstaller->assetsDirectories);
    }

    public function testShouldSetMultipleAssetsDirWhenMultipleAreConfigured()
    {
        $assetsInstaller = $this->getAssetsInstaller('multipleDirs');
        $this->assertEquals(array('js' => 'public/js', 'css' => 'css'), $assetsInstaller->assetsDirectories);
    }

    public function testShouldCorrectlyGetVendorPath()
    {
        $assetsInstaller = $this->getAssetsInstaller();
        $this->assertEquals('/tmp/', $assetsInstaller->getVendorPath());
    }

    public function testShouldReturnThePackageJsonFile()
    {
        $assetsInstaller = $this->getAssetsInstaller();
        $packages = $this->getMockPackagesLinks(self::NS_DEFAULT);
        $package = $packages[0];
        $expectedJsonFile = array(
            'extra' => array(
                'assets-dir' => 'web'
            )
        );
        $this->assertEquals($expectedJsonFile, $assetsInstaller->getPackageJsonFile($package));
    }

    public function testShouldReturnThePublicPackageAssetsDirWhenDirIsString()
    {
        $assetsInstaller = $this->getAssetsInstaller();
        $jsonData = array(
            'extra' => array(
                'assets-dir' => 'web'
            )
        );
        $expectedPackagesAssetsDir = array('public' => 'web');
        $this->assertEquals($expectedPackagesAssetsDir, $assetsInstaller->getPackageAssetsDirs($jsonData));
    }

    public function testShouldReturnSuccessStatusWhenPackageDirIsInstalled()
    {
        $assetsInstaller = $this->getAssetsInstaller();
        $packages = $this->getMockPackagesLinks(self::NS_DEFAULT);
        $package = $packages[0];
        $assetsInstaller->installPackageDir($package, 'public', 'web');
        $this->assertEquals(1, $assetsInstaller->packagesStatuses['default/package']['dirs']['public']['status']);
    }

    public function testShouldReturnErrorStatusWhenAssetsDirIsUndefined()
    {
        $assetsInstaller = $this->getAssetsInstaller();
        $packages = $this->getMockPackagesLinks(self::NS_DEFAULT);
        $package = $packages[0];
        $assetsInstaller->installPackageDir($package, 'undefined', 'web');
        $this->assertEquals(0, $assetsInstaller->packagesStatuses['default/package']['dirs']['undefined']['status']);
    }

    public function testShouldReturnErrorStatusWhenPackagePathIsNotADirectory()
    {
        $assetsInstaller = $this->getAssetsInstaller(self::NS_DEFAULT, self::NS_DEFAULT, 'notDirectory');
        $packages = $this->getMockPackagesLinks(self::NS_DEFAULT);
        $package = $packages[0];
        $assetsInstaller->installPackageDir($package, 'public', 'notADirectory');
        $this->assertEquals(0, $assetsInstaller->packagesStatuses['default/package']['dirs']['public']['status']);
    }

    public function testShouldInitializeStatusesArrayWhenPackageIsInstalled()
    {
        $assetsInstaller = $this->getAssetsInstaller();
        $packages = $this->getMockPackagesLinks(self::NS_DEFAULT);
        $package = $packages[0];
        $assetsInstaller->installPackage($package);
        $this->assertArrayHasKey('public', $assetsInstaller->packagesStatuses['default/package']['dirs']);
    }

    public function testShouldReturnErrorStatusWhenPackageAssetsDirIsNotDefined()
    {
        $assetsInstaller = $this->getAssetsInstaller();
        $packages = $this->getMockPackagesLinks('noAssetsDir');
        $package = $packages[0];
        $assetsInstaller->installPackage($package);
        $this->assertEquals(0, $assetsInstaller->packagesStatuses['default/package']['extra']);
    }

    public function testShouldReturnThePackageAssetsDirWhenDirIsArray()
    {
        $assetsInstaller = $this->getAssetsInstaller();
        $jsonData = array(
            'extra' => array(
                'assets-dir' => array(
                    'js' => 'web/js',
                    'css' => 'web/css'
                )
            )
        );
        $expectedPackagesAssetsDir = array(
            'js' => 'web/js',
            'css' => 'web/css'
        );
        $this->assertEquals($expectedPackagesAssetsDir, $assetsInstaller->getPackageAssetsDirs($jsonData));
    }

    public function testShouldReturnSuccessStatusWhenAllPackagesAreInstalled()
    {
        $assetsInstaller = $this->getAssetsInstaller();
        $assetsInstaller->install();
        $this->assertEquals(1, $assetsInstaller->packagesStatuses['default/package']['dirs']['public']['status']);
    }


    private function getAssetsInstaller($packageNs = self::NS_DEFAULT, $linksNs = self::NS_DEFAULT, $directoryHandlerNs = self::NS_DEFAULT)
    {
        $package = $this->getMockPackage($packageNs, $linksNs);
        $composer = $this->getComposer($package, $packageNs);
        $directoryHandler = $this->getDirectoryHandler($directoryHandlerNs);
        $io = $this->getIO();

        $fsStub = $this
            ->getMockBuilder('Symfony\Component\Filesystem\Filesystem')
            ->getMock();
        $fsStub
            ->method('exists')
            ->willReturn(true);

        return new AssetsInstaller($composer, $io, $directoryHandler, $fsStub);
    }

    private function getMockPackage($packageNs = self::NS_DEFAULT, $linksNs = self::NS_DEFAULT)
    {
        $mockPackageData = $this->mockPackage[$packageNs];

        $jsonFileReader = $this->getMock('JsonFile', array('read'));
        $jsonFileReader->expects($this->any())
            ->method('read')
            ->will($this->returnValue($mockPackageData['jsonFile']));

        $package = $this->getMock('Package', array('getExtra', 'getName', 'getRequires', 'getTarget'));
        $package->expects($this->any())
            ->method('getExtra')
            ->will($this->returnValue($mockPackageData['extra']));
        $package->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($mockPackageData['name']));
        $package->expects($this->any())
            ->method('getTarget')
            ->will($this->returnValue($mockPackageData['target']));
        $package->expects($this->any())
            ->method('getRequires')
            ->will($this->returnValue($this->getMockPackagesLinks($linksNs)));
        $package->expects($this->any())
            ->method('getJsonFile')
            ->will($this->returnValue($jsonFileReader));
        return $package;
    }

    private function getComposer($package, $packageNs = self::NS_DEFAULT)
    {
        $mockPackageData = $this->mockPackage[$packageNs];
        $installationManager = $this->getMock('InstallationManager', array('getInstallPath'));
        $installationManager->expects($this->any())
            ->method('getInstallPath')
            //->with($package)
            ->will($this->returnValue($mockPackageData['installPath']));

        $composer = $this->getMock('Composer', array('getPackage', 'getInstallationManager'));
        $composer->expects($this->any())
            ->method('getPackage')
            ->will($this->returnValue($package));
        $composer->expects($this->any())
            ->method('getInstallationManager')
            ->will($this->returnValue($installationManager));
        return $composer;
    }

    private function getDirectoryHandler($directoryHandlerNs = self::NS_DEFAULT)
    {
        $directoryHandlerData = $this->mockDirectoryHandler[$directoryHandlerNs];
        $directoryHandler = $this->getMock('DirectoryHandler', array('isDirectory', 'copyDirectory', 'deleteDirectory'));
        $directoryHandler->expects($this->any())
            ->method('isDirectory')
            ->will($this->returnValue($directoryHandlerData['isDirectory']));
        return $directoryHandler;
    }

    private function getIO()
    {
        return new NullIO();
    }

    private function getMockPackagesLinks($mockLinksNs)
    {
        $mockLinksData = $this->mockLinks[$mockLinksNs];
        $mockLinks = array();
        foreach ($mockLinksData as $mockLinkData) {
            $mockLinks[] = $this->getMockPackageLink($mockLinkData);
        }
        return $mockLinks;
    }

    private function getMockPackageLink($mockLinkData)
    {
        $jsonFileReader = $this->getMock('JsonFile', array('read'));
        $jsonFileReader->expects($this->any())
            ->method('read')
            ->will($this->returnValue($mockLinkData['jsonFile']));

        $link = $this->getMock('Link', array('getTarget', 'getJsonFile'));
        $link->expects($this->any())
            ->method('getTarget')
            ->will($this->returnValue($mockLinkData['target']));
        $link->expects($this->any())
            ->method('getJsonFile')
            ->will($this->returnValue($jsonFileReader));

        return $link;
    }
}
