<?php

declare(strict_types=1);

namespace SzepeViktor\Composer\Envato;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\Link;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PreFileDownloadEvent;
use Composer\Repository\ArrayRepository;
use Composer\Semver\Constraint\MatchAllConstraint;

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

    /**
     * @var ThemeFusionApi
     */
    protected $themeFusionApi;

    /**
     * Envato item ID of the theme whose purchase code authorizes ThemeFusion downloads.
     *
     * @var int|null
     */
    protected $themeFusionItemId;

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
        $this->themeFusionApi = new ThemeFusionApi($io, $composerConfig);
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
        $packages = [];

        foreach ($this->config->getPackageList() as $packageConfig) {
            $package = new EnvatoPackage(
                $packageConfig['name'],
                $packageConfig['itemId'],
                $packageConfig['type'],
                // TODO There is no DI container in Composer
                $this->api
            );
            $packages[] = $package;

            if ($packageConfig['themeFusion']) {
                // ponytail: one ThemeFusion theme per project (Avada)
                $this->themeFusionItemId = $packageConfig['itemId'];
                \array_push($packages, ...$this->generateThemeFusionPackages($package));
            }
        }

        return new ArrayRepository($packages);
    }

    /**
     * Add the theme's premium plugins from ThemeFusion,
     * and require the ones the theme marks as required (as Avada does).
     *
     * @return list<ThemeFusionPackage>
     */
    protected function generateThemeFusionPackages(EnvatoPackage $theme): array
    {
        $themeVersion = $theme->getPrettyVersion();
        $packages = [];
        $requires = [];

        foreach ($this->themeFusionApi->getPlugins($themeVersion) as $plugin) {
            $package = new ThemeFusionPackage(
                $plugin['slug'],
                $plugin['version'],
                $this->themeFusionApi->getDownloadRequestUrl($plugin['name'], $themeVersion),
                $plugin['name']
            );
            $packages[] = $package;

            if ($plugin['required']) {
                $requires[$package->getName()] = new Link(
                    $theme->getName(),
                    $package->getName(),
                    new MatchAllConstraint(),
                    Link::TYPE_REQUIRE,
                    '*'
                );
            }
        }

        $theme->setRequires($requires);

        return $packages;
    }

    /**
     * Retrieve the item's download URL from the Envato API
     * and use the item's dist URL as the cache key.
     */
    public function handlePreDownloadEvent(PreFileDownloadEvent $event): void
    {
        if ($event->getType() !== 'package') {
            return;
        }

        $processedUrl = $event->getProcessedUrl();
        $isThemeFusion = \strpos($processedUrl, ThemeFusionApi::API_BASE_URL) === 0;

        if (! $this->config->isValid()) {
            // Otherwise the download goes out unauthorized and fails as an empty zip
            if ($isThemeFusion) {
                throw new \RuntimeException('Set ENVATO_TOKEN to download ThemeFusion plugins.');
            }
            return;
        }
        // Match on URL: when installing from composer.lock the package is not a ThemeFusionPackage
        if ($isThemeFusion) {
            $downloadUrl = $this->getThemeFusionDownloadUrl($processedUrl);
            // Composer downloads packages with the package's transport options, not the event's
            $package = $event->getContext();
            if ($package instanceof PackageInterface) {
                $package->setTransportOptions(\array_merge_recursive(
                    $package->getTransportOptions(),
                    ['http' => ['header' => ['Referer: ' . $this->getThemeFusionDomain()]]]
                ));
            }
        } else {
            $downloadUrl = $this->api->getDownloadUrl($processedUrl);
        }

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

    /**
     * Authorize a ThemeFusion download with the theme's Envato purchase code.
     */
    protected function getThemeFusionDownloadUrl(string $processedUrl): string
    {
        $purchaseCode = $this->themeFusionItemId === null ? null : $this->api->getPurchaseCode($this->themeFusionItemId);
        if ($purchaseCode === null) {
            throw new \RuntimeException(
                'No Envato purchase code found for the theme flagged "theme-fusion". The Envato token needs the "List purchases you\'ve made" permission.'
            );
        }

        return $this->themeFusionApi->getDownloadUrl($processedUrl, $purchaseCode);
    }

    /**
     * ThemeFusion only serves downloads to the domain the purchase code is registered on.
     */
    protected function getThemeFusionDomain(): string
    {
        $domain = $this->config->getThemeFusionDomain();
        if ($domain === null) {
            throw new \RuntimeException(
                'Set THEME_FUSION_DOMAIN (or WP_HOME) to the site URL Avada is registered on to download ThemeFusion plugins.'
            );
        }

        return $domain;
    }
}
