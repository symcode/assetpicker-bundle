# AssetPicker Symfony Bundle

This Symfony bundle provides the [AssetPicker](https://github.com/netresearch/assetpicker) sources to Symfony Applications along with view helpers and a proxy controller.

## Installation

1. Install via composer

        composer require assetpicker-bundle

2. [Enable](http://symfony.com/doc/current/bundles/installation.html#b-enable-the-bundle) the bundle
3. Add [AssetPicker configuration](https://github.com/netresearch/assetpicker#config) to your `app/config/config.yml`

    ```yml
    asset_picker:
        storages:
            entermediadb:
                adapter: entermediadb
                url: "http://em9.entermediadb.org/openinstitute"
                proxy: true
            github:
                username: "netresearch"
                repository: "assetpicker"
    ```

4. (Optional) If you want to use the builtin proxy controller, you must include its routes into `app/config/routing.yml` - the correct proxy url will then be set automatically:

    ```yml
    assetpicker_proxy:
        resource: "@AssetPickerBundle/Resources/config/routing.yml"
    ```

5. Clear cache and install the assets

    ```
    php app/console cache:clear
    php app/console assets:install
    ```

## Usage

The bundle provides two twig functions: `assetpicker_config` and `assetpicker_url`. The first returns a JSON representation of the config from your `app/config/config.yml` (eventually with the proxy url added) and the second gives you the url to the picker.js inside your assets path (usually /web/bundles/assetpicker/js/picker.js). You can use them as follows:

```html
<script type="text/javascript" src="{{ assetpicker_url() }}"></script>
<script type="text/javascript">
    new AssetPicker({{ assetpicker_config() }});
</script> 
<button rel="assetpicker">Pick an asset</button>
```