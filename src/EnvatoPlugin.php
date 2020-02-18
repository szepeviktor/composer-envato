<?php

declare(strict_types=1);

namespace SzepeViktor\Composer\Envato;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\InstallerEvent;
use Composer\Installer\InstallerEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PreFileDownloadEvent;
use Composer\Repository\ArrayRepository;
use Composer\Util\Filesystem;
use Composer\Util\RemoteFilesystem;

/**
 * Composer Plugin for Envato.
 */
class EnvatoPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var EnvatoConfig
     */
    protected $config;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->config = new EnvatoConfig();
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * An array of arrays composed of the method names to call and respective
     * priorities, or 0 if unset
     *
     * @return array<string, array<int, array{0:string, 1:int}>> Event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            InstallerEvents::PRE_DEPENDENCIES_SOLVING => [
                ['addEnvatoRepository', 0],
            ]
        ];
    }

    public function addEnvatoRepository(InstallerEvent $event): void
    {
        $pool = $event->getPool();
        $pool->addRepository($this->generateEnvatoRepository($this->config));
    }

    protected function generateEnvatoRepository(EnvatoConfig $config): ArrayRepository
    {
        return new ArrayRepository(array_map(
            static function ($package) {
                return new EnvatoPackage($package['name']);
            },
            $config->getPackages()
        ));
    }
}
