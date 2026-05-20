# Changelog

All notable changes to this package will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and
this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] — 2026-05-20

### Changed
- First stable release. Promotes the 0.9.x line to 1.0; no API changes.
- File permissions normalised to executable (`0755`) across all class, interface,
  trait and test files.

## [0.9.2] — 2026-04-28

### Fixed — PHP 8.x compatibility (verified live on PHP 8.5)
- **`ReflectionStaticMethod::invoke` / `invokeArgs` and
  `ReflectionObjectMethod::invoke` LSP-violation under PHP 8.0+.**
  Parent `\ReflectionMethod::invoke()` is `(?object $object, mixed ...$args): mixed`
  on PHP 8 — the framework's old `($parameter = null, $_ = null)`
  fixed-2-arg shape failed PHP 8.5's strict signature check with
  "Declaration of …::invoke() must be compatible with
  ReflectionMethod::invoke(?object, mixed ...): mixed".
  Signatures rewritten to variadic `(\$object = null, ...\$args)`
  (PHP 5.6+ syntax) and stamped with `#[\ReturnTypeWillChange]` so
  the missing `: mixed` return type stops triggering deprecations
  on PHP 8.1+.  Body keeps the framework's historical "all caller
  arguments are method args" semantics — RSM/ROM are bound to a
  class / object via the constructor, so the parent's `$object`
  slot has no semantic role here.  All in-tree callers
  (`Filter::__callStatic`, `Control::actionDefault` /
  `actionDispatch`, `Programm::runn`, `BootstrapHandler::Load`,
  `traits\Prototype::__call`/`__callStatic`) keep working
  unchanged.
- **`ReflectionClass::getMethod` and `getConstants`
  return-type-mismatch deprecation under PHP 8.1+.**  Both override
  internal Reflection methods that PHP 8 declares with `: ?ReflectionMethod`
  / `: array`.  Stamped with `#[\ReturnTypeWillChange]`.
- **`Error::STRICT` no longer references `E_STRICT`.**  The constant is
  deprecated since PHP 8.4 and removed in PHP 9.0; touching it from a
  class-constant initialiser fired `E_DEPRECATED` on every load.
  Replaced with the literal value `2048` (PHP's last numeric value
  for the constant), and the two `case E_STRICT:` branches in the
  error-class handler updated to `case 2048:` so the framework's
  `Error::STRICT` semantics survive into PHP 9+ untouched.
- **`Error::log()` and `Error::report()` accept any `Throwable`,
  not just `\Exception`.**  The PHP 8 runtime throws `\Error`
  descendants (TypeError, ArgumentCountError, ValueError, …) from
  the engine; they are NOT subclasses of `\Exception` but ARE of
  `\Throwable` (PHP 7+).  The historical `(\Exception $e)` type
  hint fataled with "Argument #1 (\$e) must be of type Exception,
  TypeError given" the moment the shutdown handler tried to log a
  PHP 8 runtime fatal.  Type hints removed; bodies only call
  methods on the `\Throwable` interface so behaviour stays
  identical on PHP 5.6.
- **`Error::getLastException()` no longer warns on PHP 7.4+.**  The
  internal `$test` closure compared an Exception against
  `error_get_last()`'s return value; on a request with no fatal
  error the latter is `null`, and PHP 7.4+ emits
  "Trying to access array offset on null" on `$error['message']`.
  Closure now early-returns `false` when `$error` is not an array,
  matching the original "no recent fatal to compare against"
  semantics.
- **`Exception\InvalidArgumentException` raised on PHP 8 fatals
  inside `ExceptionEventParms`.**  The constructor's
  `$parms['exception'] instanceof \Exception` guard rejected
  `\Error` descendants.  Now accepts any `Throwable` (PHP 7+) and
  falls back to the `\Exception` check on PHP 5.6 where `\Throwable`
  doesn't exist.

### Compatibility
- Framework's PHP floor stays at 5.6.  All fixes use 5.6-syntactic
  constructs (variadic `...$args` available since 5.6, `#[…]`
  attribute-syntax parses as a `#`-comment on 5.6/7.x and as a real
  attribute on 8.0+).  No new runtime deps.
- Skeleton (`MinimalusLayoutilus`) and `mn-hegenbarth.de`
  smoke-tested on PHP 5.6 (no behaviour change) and on PHP 8.5
  (page renders byte-identical except the dynamic time-of-day
  greeting and an unrelated counter-service idiosyncrasy).
- `ml-bugcatcher` Suite (13 tests, 42 assertions) green on PHP 5.6
  in DDEV; PHP 8.5 smoke covered by `mn-hegenbarth.de`'s live
  bootstrap.

## [0.9.1] — 2026-04-28

### Added
- **`extra.mnhcc-ml.register: true`** marker — opts the package into
  auto-discovery by the new
  [`mnhcc/ml-composer-plugin`](https://packagist.org/packages/mnhcc/ml-composer-plugin).
  When the plugin is installed alongside ml-bugcatcher, the package's
  absolute install path is written to
  `<vendor>/composer/mnhcc-ml-packages.php` and `BootstrapHandler::initial()`
  (in ml-core ≥ 0.9.4) registers it automatically — so
  `mnhcc\ml\interfaces\*` and `mnhcc\ml\traits\*` files in this
  package are visible to the framework's SPL autoloader without a
  manual `registerPackagePath()` call.  No effect on consumers that
  do not install the plugin.
- **`Error::report(\Exception $e)`** — soft-exception API for non-fatal
  failures.  Always writes through `Error::log()` (framework log), and
  when `DEBUG` is defined and truthy also collects the exception in a
  per-instance registry that the BugCatcher overlay renders as its own
  block ("Reported (non-fatal) [N]") with a distinct `.dbgHeader.reported`
  CSS modifier.  Use this for recoverable failures (a single filter
  blew up but the surrounding render kept going) — never for genuinely
  uncaught exceptions, which still go through `handleException()`.
  Companion accessor: `Error::getSoftExceptions()`.  EventManager (in
  ml-core) routes listener-thrown exceptions here automatically.
- **`mnhcc\ml\classes\Exception\ContentFilterException`** — typed
  exception for ContentFilterReplacer per-match failures.  Carries the
  filter key and the offending token verbatim so the overlay can render
  "filter `counter` failed for `{%counter%}`" without the listener
  author having to format that.

### Removed
- **Event bus moved out** — `EventManager`, `Event`, the base `EventParms`,
  the `Event` trait and the `Event` interface are now part of
  [ml-core](https://packagist.org/packages/mnhcc/ml-core).  FQCNs unchanged
  (`mnhcc\ml\classes\EventManager`, etc.), so consumer code does not need to
  change.  ml-bugcatcher keeps the exception-specific payload class
  `mnhcc\ml\classes\EventParms\ExceptionEventParms` and the
  `Error::onTemplateCreated` listener registration.  Rationale: the bus
  itself has no error-handling dependencies and was being used as a generic
  framework hook from MVC code; that use case lives in core.

### Added
- `mnhcc\ml\classes\Exception\ControllerNotFoundException` — `extends NotFoundException`,
  carries the missing controller class name and chains the originating
  `ConfigNotFoundException` as `$previous`. Used by `Programm::runn()` (in
  `ml-mvc`) when both fallback dispatch and the specific Control lookup fail.

### Changed
- **BugCatcher overlay walks the `getPrevious()` chain** — `Error::renderError`
  used to render only the outermost exception of a wrapped chain. Now every
  predecessor is emitted as its own collapsible frame: the wrapper as the
  primary block (`dbgHeader`) and each predecessor as a "Caused by:" block
  (`dbgHeader caused-by`). Toggle ids are unique across the chain. Helper
  `Error::_renderExceptionFrame` extracted from the previous monolithic body so
  the chain loop and the per-frame layout stay readable.
- **BugCatcher overlay style: full-width, legible, dark-mode aware** — the
  overlay no longer wraps in Bootstrap's `.container`. It uses its own
  `<div class="bugcatcher-overlay">` so it always renders full-width regardless
  of the host page's CSS (no longer squeezed into a 968px column on themed
  layouts). Inline `<style>` extracted into `Error::_overlayStyles()`: white
  background, dark-grey text, properly contrasting alert/code/backtrace blocks;
  primary `dbgHeader` carries a red left-border, `caused-by` carries an orange
  one and indents 1.5em. Toggle script extracted into `Error::_overlayScript()`
  symmetrically.
- **IE11 compatibility preserved** — no CSS custom properties, no `:has()`, no
  `@supports`, no logical properties, classic media queries, plain RGB values.
- **`prefers-color-scheme: dark` honoured** on Chrome 76+, Firefox 67+, Safari
  12.1+, Edge 79+. IE11 ignores the media block and stays on the light defaults.
- `Error::renderTemplate` visibility raised from `protected static` to
  `public static` so consumers (e.g. `TemplateHtml::_renderInlineFallback` in
  `ml-mvc`) can reuse the BugCatcher fallback HTML directly without copy-pasting
  the markup.

## [0.9.0] - 2026-04-25

Initial Packagist release. Extracted from the MinimalusLayoutilus skeleton;
contains the central error / exception handler (`Error`), the static event bus
(`EventManager` + `EventParms` + `ExceptionEventParms`), the BugCatcher debug
overlay, and the `Exception` hierarchy (base `Exception`, `HttpException`,
`NotFoundException`, `ConfigNotFoundException`, `ActionNotFoundException`,
`ViewNotFoundException`, `ComponentRendererNotFoundException`,
`ComponentGetterNotCallableException`, `ModulRendererNotFoundException`,
`ModulGetterNotFoundException`, `ReflectionMethodException`,
`ForbiddenException`, `UnauthorizedException`, `RedirectException`,
`RenderException`, `ErrorException`, `BadFunctionCallException`,
`InvalidArgumentException`, `NotImplementedException`).
