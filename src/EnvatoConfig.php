<?php

declare(strict_types=1);

namespace SzepeViktor\Composer\Envato;

use Composer\Config;
use Dotenv\Dotenv;

class EnvatoConfig
{
    public const ENVATO_CONFIG = 'envato';

    public const ENV_VAR_TOKEN = 'ENVATO_TOKEN';

    /**
     * @var array{token?:string, packages?:array{item-id:?(int|string), type:?string}}
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

        $this->loadDotenv();
        $this->mergeEnvConfig();

        $this->valid = \array_key_exists('token', $this->config)
            && \is_string($this->config['token'])
            && $this->config['token'] !== ''
            && \array_key_exists('packages', $this->config)
            && \is_array($this->config['packages'])
            && $this->config['packages'] !== []
        ;
    }

    /**
     * @phpstan-assert-if-true !array{} $this->config
     */
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
                    'name'   => (string)$name,
                    'itemId' => (int)($data['item-id'] ?? 0),
                    'type'   => (string)($data['type'] ?? 'wordpress-theme'),
                ];
            },
            \array_keys($this->config['packages']),
            $this->config['packages']
        );
    }

    protected function loadDotenv(): void
    {
        $cwd = \getcwd();
        if (\is_string($cwd) && \file_exists($cwd . DIRECTORY_SEPARATOR . '.env')) {
            Dotenv::createImmutable($cwd)->safeLoad();
        }
    }

    protected function mergeEnvConfig(): void
    {
        if (\array_key_exists(self::ENV_VAR_TOKEN, $_ENV)) {
            $this->config['token'] = $_ENV[self::ENV_VAR_TOKEN];
        }
    }
}
