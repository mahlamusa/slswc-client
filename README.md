# SLSWC Client SDK

PHP SDK for WordPress plugins and themes to check for updates, manage licenses, and enforce DRM against an SLSWC-powered license server.

## Installation

```bash
composer require slswc/client
```

## Usage

### Plugin Integration

```php
use SLSWC\Client\Plugin;

add_action( 'plugins_loaded', function () {
    $client = Plugin::get_instance(
        'https://your-license-server.com/',
        __FILE__,
        array(
            'license_key' => get_option( 'my_plugin_license_key', '' ),
        )
    );
    $client->init_hooks();
}, 11 );
```

### Theme Integration

```php
use SLSWC\Client\Theme;

add_action( 'after_setup_theme', function () {
    $theme = Theme::get_instance(
        'https://your-license-server.com/',
        WP_CONTENT_DIR . '/themes/my-theme',
        array(
            'license_key' => get_option( 'my_theme_license_key', '' ),
        )
    );
    $theme->init_hooks();
} );
```

### Plugin Headers

Add SLSWC headers to your plugin's main file:

```php
/**
 * Plugin Name: My Plugin
 * Version:     1.0.0
 * Text Domain: my-plugin
 *
 * SLSWC:                    plugin
 * SLSWC Documentation URL:  https://example.com/docs
 * SLSWC Compatible To:      6.9
 */
```

### DRM (Optional)

```php
$client = Plugin::get_instance(
    'https://your-license-server.com/',
    __FILE__,
    array(
        'license_key' => get_option( 'my_plugin_license_key', '' ),
        'drm' => array(
            'enabled'      => true,
            'product_name' => 'My Plugin',
        ),
    )
);
```

## Multi-consumer safety

Multiple plugins and themes on the same WordPress install can embed this SDK independently. Each consumer receives its own isolated `Plugin` / `Theme` / `ApiClient` instance, keyed internally by:

- `Plugin::get_instance( $url, $base_file, $args )` — keyed by `$base_file` (the consumer's main plugin file path).
- `Theme::get_instance( $url, $base_file, $args )` — keyed by `$base_file` (the consumer's theme directory path).
- `ApiClient::get_instance( $url, $text_domain )` — keyed by `$text_domain`.

`text_domain`, DRM option keys (`<text_domain>_drm_*`), `LicenseDetails` option key (`<text_domain>_license_details`), DRM admin notice hooks, and scheduled license-check cron events therefore stay isolated across consumers. There is no shared mutable state between two consumers on the same site.

Consumers are expected to pass a stable `$base_file` (a plugin's main `__FILE__` is ideal) and a unique `text_domain` per product. Reusing the same `text_domain` across two consumers will share a registry slot and is unsupported.

## Documentation

Full integration guides: [licenseserver.io/docs](https://licenseserver.io/docs)

## License

GPL-2.0-or-later
