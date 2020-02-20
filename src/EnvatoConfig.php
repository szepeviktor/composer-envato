<?php

declare(strict_types=1);

namespace SzepeViktor\Composer\Envato;

use Composer\Config;

class EnvatoConfig
{
    const ENVATO_CONFIG = 'envato';

    /**
     * @var array
     */
    protected $config;

    /**
     * @var bool
     */
    protected $valid = false;

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
        // FIXME
        return [['name'=>'envato/avada-theme', 'itemId'=>2833226, 'type'=>'wordpress-theme']];

        return $this->arrayMapAssociative(
            static function ($name, $data) {
                return [
                    'name' => $name,
                    'itemId' => $data['item-id'],
                    'type' => $data['type'] ?? 'wordpress-theme',
                ];
            },
            $this->config['packages']
        );
    }

    protected function arrayMapAssociative(callable $callback, array $array): array
    {
        return \array_column(
            \array_map($callback, \array_keys($array), $array),
            1,
            null
        );
    }
}
