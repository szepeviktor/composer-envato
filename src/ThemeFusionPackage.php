<?php

declare(strict_types=1);

namespace SzepeViktor\Composer\Envato;

use Composer\Package\CompletePackage;
use Composer\Package\Version\VersionParser;

class ThemeFusionPackage extends CompletePackage
{
    public const VENDOR_NAME = 'theme-fusion';

    public function __construct(string $slug, string $prettyVersion, string $distUrl, string $description)
    {
        $versionParser = new VersionParser();
        parent::__construct(self::VENDOR_NAME . '/' . \strtolower($slug), $versionParser->normalize($prettyVersion), $prettyVersion);

        $this->setType('wordpress-plugin');
        $this->setDistType('zip');
        $this->setDistUrl($distUrl);
        $this->setDescription($description);
        // Keep the slug's casing as folder name, e.g. LayerSlider/
        $this->setExtra(['installer-name' => $slug]);
    }
}
