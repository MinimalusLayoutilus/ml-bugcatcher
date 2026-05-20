# mnhcc/ml-bugcatcher

[![PHP 7.4](https://github.com/MinimalusLayoutilus/ml-bugcatcher/actions/workflows/ci-php74.yml/badge.svg)](https://github.com/MinimalusLayoutilus/ml-bugcatcher/actions/workflows/ci-php74.yml)
[![PHP 8.1](https://github.com/MinimalusLayoutilus/ml-bugcatcher/actions/workflows/ci-php81.yml/badge.svg)](https://github.com/MinimalusLayoutilus/ml-bugcatcher/actions/workflows/ci-php81.yml)
[![PHP 8.5](https://github.com/MinimalusLayoutilus/ml-bugcatcher/actions/workflows/ci-php85.yml/badge.svg)](https://github.com/MinimalusLayoutilus/ml-bugcatcher/actions/workflows/ci-php85.yml)

Error handling and exception classes for the Minimalus Layoutilus PHP framework.

Provides `Error` (error/exception handler with the BugCatcher debug overlay),
`ExceptionEventParms` (the exception payload listened-to via the
[ml-core](https://packagist.org/packages/mnhcc/ml-core) event bus), and a
hierarchy of typed `Exception` classes.

## Requirements

- PHP ≥ 5.4
- [mnhcc/ml-core](https://packagist.org/packages/mnhcc/ml-core)

## Installation

```bash
composer require mnhcc/ml-bugcatcher
```

## What you get

### `Error` — central error / exception handler

Constructed by the framework bootstrap.  Registers
`set_error_handler`, `set_exception_handler` and
`register_shutdown_function`, optionally starts an output buffer for
blank-screen protection, and emits the BugCatcher debug overlay on the
response when `DEBUG` is on.

```php
// raise a 404 with the active Template (if any) so the response
// gets the configured error component instead of a hard die().
Error::getInstance()->raise(404, $previousException, '', $template);
```

### Exception hierarchy

```
mnhcc\ml\classes\Exception                  base
└── HttpException                            carries an HTTP status code
    └── NotFoundException
        ├── ConfigNotFoundException          missing config key/file
        ├── ActionNotFoundException          unknown actionXxx() on a Control
        ├── ControllerNotFoundException      no Control{Segment} class for the URL
        ├── ViewNotFoundException
        ├── ComponentRendererNotFoundException
        ├── ComponentGetterNotCallableException
        ├── ModulRendererNotFoundException
        ├── ModulGetterNotFoundException
        └── ReflectionMethodException
    ├── ForbiddenException                   403
    ├── UnauthorizedException                401
    ├── RedirectException                    3xx
    └── RenderException                      generic render failure
└── ErrorException                           wraps PHP error_handler errors
└── BadFunctionCallException
└── InvalidArgumentException
└── NotImplementedException
```

`Programm::runn()` catches `HttpException` and routes to `Error::raise()`
with the carried status code; concrete subclasses (`ConfigNotFoundException`,
`ControllerNotFoundException`, …) preserve the original cause through PHP's
standard `getPrevious()` chain.

### Events raised by this package

The event bus itself lives in [ml-core](https://packagist.org/packages/mnhcc/ml-core).
This package raises two events through it:

| Event       | Source                       | Payload                |
|-------------|------------------------------|------------------------|
| `exception` | `Error::handleException`     | `ExceptionEventParms`  |
| `shutdown`  | `Error::shutdown`            | `ExceptionEventParms`  |

`Error::__construct` also registers one listener — `Error::onTemplateCreated`
— which adds `error` and `debug` styles to the active Template when the MVC
package raises `templateCreated`.

## BugCatcher debug overlay

When `DEBUG` is on, `Error::documentPrepareHTML()` injects a debug overlay
into the response so you don't have to scroll into framework internals to see
what blew up.

### Layout

- Wrapped in `<div class="bugcatcher-overlay">` — explicitly NOT Bootstrap's
  `.container`, so the overlay always renders full-width regardless of the
  host page's CSS.
- White background, dark grey text, classic alert blocks, collapsible
  per-frame containers.
- Honours `prefers-color-scheme: dark` on browsers that support the media
  feature; IE11 ignores the dark block and stays on the light defaults.

### Exception chain

`renderError($exception, $hash)` walks `$exception->getPrevious()` and emits
one frame per link, so wrapped exceptions surface the **whole** chain instead
of only the outermost throw:

```
[hash] mnhcc\ml\classes\Exception\ControllerNotFoundException(404)        ← primary
       └─ Backtrace

[hash.1] Caused by: mnhcc\ml\classes\Exception\ConfigNotFoundException(404)  ← previous
         └─ Backtrace
```

The "Caused by:" frames are styled with `dbgHeader caused-by` (an orange left
border, indented) so the chain is visually distinct from the primary throw.

### Browser compatibility

- IE11 supported — no CSS custom properties, no `:has()`, no `@supports`,
  classic media queries, plain RGB values.
- `prefers-color-scheme: dark` on Chrome 76+, Firefox 67+, Safari 12.1+,
  Edge 79+.

## License

[LGPL-2.1-only](https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html)
