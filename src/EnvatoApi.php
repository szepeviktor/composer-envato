<?php

declare(strict_types=1);

namespace SzepeViktor\Composer\Envato;

use Composer\Config;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Util\RemoteFilesystem;

class EnvatoApi
{
    const API_DOMAIN = 'api.envato.com';
    const API_BASE_URL = 'https://api.envato.com/v3';

    /**
     * @var string
     */
    protected $token;

    /**
     * @var RemoteFilesystem
     */
    protected $remoteFilesystem;

    public function __construct(IOInterface $io, Config $config, string $token)
    {
        $this->token = $token;
        $this->remoteFilesystem = Factory::createRemoteFilesystem($io, $config);
    }

    public function getVersion(int $itemId): string
    {
        $responseBody = $this->remoteFilesystem->getContents(
            self::API_DOMAIN,
            self::API_BASE_URL . '/market/catalog/item-version?' . \http_build_query(['id' => $itemId]),
            false,
            ['http' => ['header' => ['Authorization: Bearer ' . $this->token]]]
        );

        // TODO HTTP 429 response. Included in this response is a HTTP header Retry-After
        if ($this->remoteFilesystem->findStatusCode($this->remoteFilesystem->getLastHeaders()) === 200) {
            $versionData = \json_decode($responseBody, true);
            // TODO Check JSON
            if (\array_key_exists('wordpress_theme_latest_version', $versionData)) {
                return $versionData['wordpress_theme_latest_version'];
            }
            if (\array_key_exists('wordpress_plugin_latest_version', $versionData)) {
                return $versionData['wordpress_plugin_latest_version'];
            }
        }

        // In any other case
        return '0.0.0';
    }

    public function getDownloadUrl(int $itemId): string
    {
        $responseBody = $this->remoteFilesystem->getContents(
            self::API_DOMAIN,
            self::API_BASE_URL . '/market/buyer/download?' . \http_build_query(['item_id' => $itemId]),
            false,
            ['http' => ['header' => ['Authorization: Bearer ' . $this->token]]]
        );

        // TODO HTTP 429 response. Included in this response is a HTTP header Retry-After
        if ($this->remoteFilesystem->findStatusCode($this->remoteFilesystem->getLastHeaders()) === 200) {
            $urlData = \json_decode($responseBody, true);
            // TODO Check JSON
            if (\array_key_exists('wordpress_theme', $urlData)) {
                return $urlData['wordpress_theme'];
            }
            if (\array_key_exists('wordpress_plugin', $urlData)) {
                return $urlData['wordpress_plugin'];
            }
        }

        // In any other case
        return '';
    }
}
