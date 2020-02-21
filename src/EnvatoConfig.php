<?php

declare(strict_types=1);

namespace SzepeViktor\Composer\Envato;

use Composer\Config;

class EnvatoConfig
{
    const ENVATO_CONFIG = 'envato';

    /**
     * @var array<mixed, mixed>
     */
    protected $config;

    /**
     * @var bool
     */
    protected $valid;

    public function __construct(Config $composerConfig)
    {
        $this->config = $composerConfig->get(self::ENVATO_CONFIG);

        $this->valid = $this->config !== null
            && \array_key_exists('token', $this->config)
            && $this->config['token'] !== ''
            && \array_key_exists('packages', $this->config)
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
                    'name' => $name,
                    'itemId' => $data['item-id'],
                    'type' => $data['type'] ?? 'wordpress-theme',
                ];
            },
            \array_keys($this->config['packages']),
            $this->config['packages']
        );
    }
}
