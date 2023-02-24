<?php

declare(strict_types=1);

namespace SzepeViktor\Composer\Envato;

use Composer\Config;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Util\HttpDownloader;

class EnvatoApi
{
    public const API_DOMAIN = 'api.envato.com';
    public const API_BASE_URL = 'https://api.envato.com/v3';

    /**
     * @var string
     */
    protected $token;

    /**
     * @var HttpDownloader
     */
    protected $httpDownloader;

    public function __construct(IOInterface $io, Config $config, string $token)
    {
        $this->httpDownloader = Factory::createHttpDownloader($io, $config);
        $this->token = $token;
    }

    /**
     * Query the Envato API for the item's current version.
     *
     * @return non-empty-string
     */
    public function getVersion(int $itemId): string
    {
        $response = $this->httpDownloader->get(
            self::API_BASE_URL . '/market/catalog/item-version?' . \http_build_query(['id' => $itemId]),
            ['http' => ['header' => ['Authorization: Bearer ' . $this->token]]]
        );

        // TODO HTTP 429 response. Included in this response is a HTTP header Retry-After
        if ($response->getStatusCode() === 200) {
            $versionData = \json_decode($response->getBody() ?? '', true);
            // TODO Check JSON
            if (\is_array($versionData)) {
                if (\array_key_exists('wordpress_theme_latest_version', $versionData)) {
                    return $versionData['wordpress_theme_latest_version'];
                }

                if (\array_key_exists('wordpress_plugin_latest_version', $versionData)) {
                    return $versionData['wordpress_plugin_latest_version'];
                }
            }
        }

        // In any other case
        return '0.0.0';
    }

    /**
     * Return the Envato API URL to retrieve the item's download URL.
     *
     * @param  int         $itemId  The item ID to lookup.
     * @param  string|null $version Optional version to append to the URL.
     *     This is used to by the plugin for the download's cache key in Composer.
     * @return non-empty-string
     */
    public function getDownloadRequestUrl(int $itemId, ?string $version = null): string
    {
        $query = ['item_id' => $itemId];

        if ($version !== null) {
            $query['version'] = $version;
        }

        return self::API_BASE_URL . '/market/buyer/download?' . \http_build_query($query);
    }

    /**
     * Query the Envato API to retrieve the item's download URL.
     *
     * @param  int|string $itemIdOrApiUrl The item ID to lookup or the API URL to query.
     * @return non-empty-string|null
     */
    public function getDownloadUrl($itemIdOrApiUrl): ?string
    {
        if (\is_int($itemIdOrApiUrl) && $itemIdOrApiUrl > 0) {
            $apiUrl = $this->getDownloadRequestUrl($itemIdOrApiUrl);
        } elseif (\is_string($itemIdOrApiUrl) && $itemIdOrApiUrl !== '') {
            $apiUrl = $itemIdOrApiUrl;
        } else {
            return null;
        }

        $response = $this->httpDownloader->get(
            $apiUrl,
            ['http' => ['header' => ['Authorization: Bearer ' . $this->token]]]
        );

        // TODO HTTP 429 response. Included in this response is a HTTP header Retry-After
        if ($response->getStatusCode() === 200) {
            $urlData = \json_decode($response->getBody() ?? '', true);
            // TODO Check JSON
            if (\is_array($urlData)) {
                if (\array_key_exists('wordpress_theme', $urlData)) {
                    return $urlData['wordpress_theme'];
                }

                if (\array_key_exists('wordpress_plugin', $urlData)) {
                    return $urlData['wordpress_plugin'];
                }
            }
        }

        // In any other case
        return null;
    }
}
