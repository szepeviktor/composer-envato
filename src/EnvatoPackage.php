<?php

declare(strict_types=1);

namespace SzepeViktor\Composer\Envato;

use Composer\Package\Package;
use Composer\Package\Version\VersionParser;

class EnvatoPackage extends Package
{
    /**
     * @var int
     */
    protected $itemId;

    /**
     * @var EnvatoApi
     */
    protected $api;

    public function __construct(string $name, int $itemId, string $type, EnvatoApi $api)
    {
        $this->itemId = $itemId;
        $this->type = $type;
        $this->api = $api;

        // Set fake versions to avoid API call
        parent::__construct($name, '0.0.0.0', '0.0.0');
    }

    public function isDev(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getDistType(): string
    {
        return 'zip';
    }

    /**
     * {@inheritDoc}
     */
    public function getVersion(): string
    {
        if ($this->version !== '0.0.0.0') {
            return $this->version;
        }

        $this->processItemVersion();

        return $this->version;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrettyVersion(): string
    {
        if ($this->prettyVersion !== '0.0.0') {
            return $this->prettyVersion;
        }

        $this->processItemVersion();

        return $this->prettyVersion;
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceUrl(): ?string
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getDistUrl(): ?string
    {
        if ($this->distUrl !== null) {
            return $this->distUrl;
        }

        /**
         * Use the Envato API URL to retrieve the item's download URL
         * as the package's dist URL to serve as its cache key.
         */
        $this->distUrl = $this->api->getDownloadRequestUrl($this->itemId, $this->getPrettyVersion());

        return $this->distUrl;
    }

    /**
     * @return bool
     */
    public function isAbandoned()
    {
        return false;
    }

    /**
     * Retrieve the item's version from the Envato API.
     */
    protected function processItemVersion(): void
    {
        // Query Envato API
        $this->prettyVersion = $this->api->getVersion($this->itemId);
        $versionParser = new VersionParser();
        $this->version = $versionParser->normalize($this->prettyVersion);
    }
}
