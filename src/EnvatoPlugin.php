<?php

declare(strict_types=1);

namespace SzepeViktor\Composer\Envato;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PreFileDownloadEvent;
use Composer\Repository\ArrayRepository;

/**
 * Composer Plugin for Envato Marketplace.
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

    /**
     * @var EnvatoApi
     */
    protected $api;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
        $composerConfig = $composer->getConfig();
        $this->config = new EnvatoConfig($composerConfig);
        if (! $this->config->isValid()) {
            return;
        }

        $this->api = new EnvatoApi($io, $composerConfig, $this->config->getToken());
        $rm = $composer->getRepositoryManager();
        $rm->addRepository($this->generateRepository());
    }

    /**
     * @return void
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    /**
     * @return void
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
    }

    /**
     * @return array<key-of<PluginEvents::*>, (string|array{string, int})>
     */
    public static function getSubscribedEvents()
    {
        return [
            PluginEvents::PRE_FILE_DOWNLOAD => [ 'handlePreDownloadEvent', -1 ],
        ];
    }

    protected function generateRepository(): ArrayRepository
    {
        $api = $this->api;

        return new ArrayRepository(\array_map(
            static function ($packageConfig) use ($api) {
                $package = new EnvatoPackage(
                    $packageConfig['name'],
                    $packageConfig['itemId'],
                    $packageConfig['type'],
                    // TODO There is no DI container in Composer
                    $api
                );
                return $package;
            },
            $this->config->getPackageList()
        ));
    }

    /**
     * Retrieve the item's download URL from the Envato API
     * and use the item's dist URL as the cache key.
     */
    public function handlePreDownloadEvent(PreFileDownloadEvent $event): void
    {
        /**
         * Bail early if this event is not for a package.
         *
         * @see https://github.com/composer/composer/pull/8975
         */
        if ($event->getType() !== 'package') {
            return;
        }

        $processedUrl = $event->getProcessedUrl();
        $downloadUrl  = $this->api->getDownloadUrl($processedUrl);

        // Submit changes to Composer, if any
        if (
            \is_string($downloadUrl) &&
            $downloadUrl !== '' &&
            $downloadUrl !== $processedUrl
        ) {
            $event->setProcessedUrl($downloadUrl);
            $event->setCustomCacheKey($processedUrl);
        }
    }
}
