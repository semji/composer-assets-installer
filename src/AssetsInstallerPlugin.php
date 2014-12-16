<?php
/*
 * AssetsInstallerPlugin.php
 */

namespace Rvip\Composer;

use Composer\Composer;
use Composer\Plugin\PluginInterface;
use Composer\IO\IOInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

class AssetsInstallerPlugin implements PluginInterface, EventSubscriberInterface
{

    private $assetsInstaller;

    /**
     * Initializes the plugin
     * Reads the composer.json file and
     * retrieves the assets-dir set if any.
     * This assets-dir is the path where
     * the other packages assets will be installed
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->assetsInstaller = new AssetsInstaller($composer, $io);
    }

    public static function getSubscribedEvents()
    {
        return array(
            ScriptEvents::POST_UPDATE_CMD => array(
                array('onPostUpdate', 0)
            )
        );
    }

    public function onPostUpdate(Event $event)
    {
        $this->assetsInstaller->install();
    }
}
