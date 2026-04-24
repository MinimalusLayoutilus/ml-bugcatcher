# mnhcc/ml-bugcatcher

Error handling, event system and exception classes for the Minimalus Layoutilus PHP framework.

Provides `Error` (error/exception handler with BugCatcher debug console), `EventManager`,
`EventParms`, and a hierarchy of typed `Exception` classes.

## Requirements

- PHP ≥ 5.4
- [mnhcc/ml-core](https://packagist.org/packages/mnhcc/ml-core)

## Installation

```bash
composer require mnhcc/ml-bugcatcher
```

## Usage

`Error` is instantiated by the framework bootstrap. It registers `set_error_handler`,
`set_exception_handler` and `register_shutdown_function`, and optionally starts output
buffering for blank-screen protection.

```php
// register and raise a 404
Error::getInstance()->raise(404, 'Not found');
```

```php
// fire a custom event
EventManager::raise('myEvent', new EventParms(['key' => 'value']));
```

## License

[LGPL-2.1-only](https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html)
