# Envato plugin for Composer

A [Composer plugin](https://getcomposer.org/doc/articles/plugins.md)
to load WordPress [themes](https://themeforest.net/category/wordpress)
and [plugins](https://codecanyon.net/category/wordpress) from [Envato](https://envato.com/).

### Installation


```shell
composer require szepeviktor/composer-envato
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
           "item-id": "2833226"
        },
        "envato/layerslider-plugin": {
          "item-id": "1362246"
        }
      }
    }
  }
}
```

:bulb: All packages have to have the vendor name `envato`.

### Usage

Once the plugin is installed and configured,
you can install any of the listed products as normal Composer packages.
