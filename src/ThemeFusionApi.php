<?php

declare(strict_types=1);

namespace SzepeViktor\Composer\Envato;

use Composer\Config;
use Composer\Factory;
use Composer\Downloader\TransportException;
use Composer\IO\IOInterface;
use Composer\Util\HttpDownloader;

/**
 * ThemeFusion update server, used by Avada to install its premium plugins.
 *
 * @see Avada::get_bundled_plugins()
 * @see Avada_Remote_Installer::get_package()
 */
class ThemeFusionApi
{
    public const API_BASE_URL = 'https://updates.theme-fusion.com/';

    /**
     * @var HttpDownloader
     */
    protected $httpDownloader;

    public function __construct(IOInterface $io, Config $config)
    {
        $this->httpDownloader = Factory::createHttpDownloader($io, $config);
    }

    /**
     * Query the premium plugins available for a theme version.
     *
     * @return array<int, array{name:string, slug:string, version:string, required:bool}>
     */
    public function getPlugins(string $themeVersion): array
    {
        try {
            $response = $this->httpDownloader->get(
                self::API_BASE_URL . '?' . \http_build_query(['avada_action' => 'get_plugins', 'avada_version' => $themeVersion])
            );
        } catch (TransportException $exception) {
            return [];
        }

        $pluginData = \json_decode($response->getBody() ?? '', true);
        if ($response->getStatusCode() !== 200 || ! \is_array($pluginData)) {
            return [];
        }

        $plugins = [];
        foreach ($pluginData as $plugin) {
            // Non-premium plugins come from wordpress.org
            if (! \is_array($plugin) || empty($plugin['premium'])) {
                continue;
            }

            $plugins[] = [
                'name'     => (string)$plugin['plugin_name'],
                'slug'     => (string)$plugin['slug'],
                'version'  => (string)$plugin['latest_version'],
                'required' => ! empty($plugin['required']),
            ];
        }

        return $plugins;
    }

    /**
     * Return the download URL without credentials.
     * It is stored in composer.lock and serves as the download's cache key.
     *
     * @return non-empty-string
     */
    public function getDownloadRequestUrl(string $name, string $themeVersion): string
    {
        return self::API_BASE_URL . '?' . \http_build_query([
            'avada_action' => 'get_download',
            'item_name'    => $name,
            'ver'          => $themeVersion,
        ]);
    }

    /**
     * Return the download URL authorized by the theme's Envato purchase code.
     *
     * @return non-empty-string
     */
    public function getDownloadUrl(string $downloadRequestUrl, string $purchaseCode): string
    {
        return $downloadRequestUrl . '&' . \http_build_query(['code' => $purchaseCode]);
    }
}
