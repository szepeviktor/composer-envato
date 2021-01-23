<?php

declare(strict_types=1);

namespace SzepeViktor\Composer\Envato;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Repository\ArrayRepository;

/**
 * Composer Plugin for Envato Marketplace.
 */
class EnvatoPlugin implements PluginInterface
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
}
