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
     * @return non-empty-string|null
     */
    public function getDownloadUrl(int $itemId): ?string
    {
        $response = $this->httpDownloader->get(
            self::API_BASE_URL . '/market/buyer/download?' . \http_build_query(['item_id' => $itemId]),
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
