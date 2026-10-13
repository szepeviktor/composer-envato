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
     * Domain the theme is registered on at ThemeFusion, falls back to Bedrock's WP_HOME.
     */
    public const ENV_VAR_THEME_FUSION_DOMAIN = 'THEME_FUSION_DOMAIN';

    /**
     * @var array{token?:string, packages?:array{item-id:int|string|null, type:string|null, theme-fusion?:bool}}
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
     * @return array<int, array{name:string, itemId:int, type:string, themeFusion:bool}>
     */
    public function getPackageList(): array
    {
        return \array_map(
            static function ($name, $data) {
                return [
                    'name'   => (string)$name,
                    'itemId' => (int)($data['item-id'] ?? 0),
                    'type'   => (string)($data['type'] ?? 'wordpress-theme'),
                    'themeFusion' => (bool)($data['theme-fusion'] ?? false),
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

    public function getThemeFusionDomain(): ?string
    {
        return self::env(self::ENV_VAR_THEME_FUSION_DOMAIN) ?? self::env('WP_HOME');
    }

    protected function mergeEnvConfig(): void
    {
        $token = self::env(self::ENV_VAR_TOKEN);
        if ($token !== null) {
            $this->config['token'] = $token;
        }
    }

    /**
     * Read a variable from .env or the OS environment.
     */
    protected static function env(string $name): ?string
    {
        if (!empty($_ENV[$name]) && \is_string($_ENV[$name])) {
            return $_ENV[$name];
        }

        $value = \getenv($name);

        return $value !== false && $value !== '' ? $value : null;
    }
}
