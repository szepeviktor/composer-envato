<?php

declare(strict_types=1);

namespace SzepeViktor\Composer\Envato;

use Composer\Config;

class EnvatoConfig
{
    public const ENVATO_CONFIG = 'envato';

    /**
     * @var array<mixed>
     */
    protected $config;

    /**
     * @var bool
     */
    protected $valid;

    public function __construct(Config $composerConfig)
    {
        $envatoConfig = $composerConfig->get(self::ENVATO_CONFIG);
        $this->config = \is_array($envatoConfig) ? $envatoConfig : [];

        $this->valid = \array_key_exists('token', $this->config)
            && \is_string($this->config['token'])
            && $this->config['token'] !== ''
            && \array_key_exists('packages', $this->config)
            && \is_array($this->config['packages'])
            && $this->config['packages'] !== []
        ;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getToken(): string
    {
        return $this->config['token'];
    }

    /**
     * Return details of all packages.
     *
     * @return array<int, array{name:string, itemId:int, type:string}>
     */
    public function getPackageList(): array
    {
        return \array_map(
            static function ($name, $data) {
                return [
                    'name' => (string)$name,
                    'itemId' => $data['item-id'] ?? 0,
                    'type' => $data['type'] ?? 'wordpress-theme',
                ];
            },
            \array_keys($this->config['packages']),
            $this->config['packages']
        );
    }
}
