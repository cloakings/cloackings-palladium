Cloakings Palladium
===================

Detect if user is bot or real user using palladium.expert

## Install

```bash
composer require cloakings/cloakings-palladium
```

## Usage

### Basic Usage

Register at https://palladium.expert. Create campaign:
- Link to the target page: `real.php`
- Link for bots: `fake.php`

Click "download code" for plain PHP or Wordpress and look for:
- clientId
- clientCompany
- clientSecret

```php
$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
$cloaker = \Cloakings\CloakingsPalladium\PalladiumCloaker(
    clientId: $clientId,
    clientCompany: $clientCompany,
    clientSecret: $clientSecret,
);
$cloakerResult = $cloaker->handle($request);
```

Check if result mode is `CloakModeEnum::Fake` or `CloakModeEnum::Real` and do something with it.

If you want to render result like the original Palladium library
```php
$baseIncludeDir = __DIR__; // change to your dir with real.php and fake.php
$renderer = \Cloakings\CloakingsPalladium\PalladiumRenderer();
$response = $renderer->render($cloakerResult);
```

If your filenames differ from `real.php` and `fake.php` change params `$fakeTargetContains` and `$realTargetContains`
in `PalladiumCloaker` constructor.

Default traffic source is `PalladiumTrafficSourceEnum::Adwords` but you can change it to `Facebook` or `Tiktok`.

## Original Logic

Original library is located at `doc/original`:
- `php` - plain PHP integration
- `wordpress` - WordPress integration

License for this repository doesn't cover that code.
