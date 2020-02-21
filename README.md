# Envato plugin for Composer

A [Composer plugin](https://getcomposer.org/doc/articles/plugins.md)
to load WordPress [themes](https://themeforest.net/category/wordpress)
and [plugins](https://codecanyon.net/category/wordpress) from [Envato](https://envato.com/).

### Installation

This Composer plugin must be installed globally as it adds a virtual package repository.

```shell
composer global require --update-no-dev szepeviktor/composer-envato
```

### Configuration

Add all your products to your `config.json` (in `$COMPOSER_HOME`) as packages.

```json
{
  "config": {
    "envato": {
      "token": "YOUR ENVATO PERSONAL TOKEN FROM https://build.envato.com/create-token",
      "packages": {
        "envato/avada-theme": {
          "item-id": 2833226,
          "type": "wordpress-theme"
        },
        "envato/layerslider-plugin": {
          "item-id": 1362246,
          "type": "wordpress-plugin"
        }
      }
    }
  }
}
```

:bulb: All packages have to have the vendor name `envato`.

### Usage

Once the plugin is installed and configured,
you can simply install any of the listed products as Composer packages.

:bulb: Envato API has [dynamic rate limiting](https://build.envato.com/api/#rate-limit)

### Behind the scenes

1. This package is a Composer plugin
1. In the `activate` method it creates an `ArrayRepository`
   with package data from `config.json`
1. Package version is queried from Envato API
1. When installing a package its URL is also queried from Envato API

- Pretty package version is e.g. `v1.2`
- Normalized package version is e.g. `1.2.0.0`
