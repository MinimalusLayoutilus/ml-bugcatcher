# Changelog

All notable changes to this package will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and
this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
