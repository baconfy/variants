<p align="center">
    <img src="https://raw.githubusercontent.com/baconfy/variants/main/docs/presentation.jpg" width="100%" alt="Prompt">
</p>

[![Tests](https://github.com/baconfy/variants/actions/workflows/tests.yml/badge.svg)](https://github.com/baconfy/variants/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/baconfy/variants.svg)](https://packagist.org/packages/baconfy/variants)
[![License](https://img.shields.io/packagist/l/baconfy/variants.svg)](https://packagist.org/packages/baconfy/variants)
[![Total Downloads](https://img.shields.io/packagist/dt/baconfy/variants.svg)](https://packagist.org/packages/baconfy/variants)
[![PHP Version](https://img.shields.io/packagist/php-v/baconfy/variants.svg)](https://packagist.org/packages/baconfy/variants)

# baconfy/variants

Composable API resources for Laravel. Define named blocks, group them into variants, and select the right shape per endpoint — all in one class, no controllers bloated with conditional fields.

## Installation

```bash
composer require baconfy/variants
```

Requires PHP 8.2+ and Laravel 12+.

## Minimal example

### 1. Define a resource

```php
use Baconfy\Variants\ComposableResource;
use Illuminate\Http\Request;

final class ProductResource extends ComposableResource
{
    protected function core(): array
    {
        return [
            'id'   => $this->resource->id,
            'name' => $this->resource->name,
        ];
    }

    protected function pricing(): array
    {
        return ['price' => $this->resource->price];
    }

    protected function full(): array
    {
        return ['description' => $this->resource->description];
    }

    protected function locale(Request $request): array
    {
        return ['locale' => $request->header('Accept-Language', 'en')];
    }

    protected function variants(): array
    {
        return [
            'list' => ['core', 'pricing'],
            'show' => ['core', 'pricing', 'full'],
            'i18n' => ['core', 'locale'],
        ];
    }

    protected function default(): string
    {
        return 'list';
    }
}
```

### 2. Use it

```php
// Default variant (list → core + pricing)
ProductResource::make($product);

// Named variant
ProductResource::make($product)->as('show');

// Variant + extra block
ProductResource::make($product)->as('list')->with('full');

// Ad-hoc selection — no variant, just the blocks you name
ProductResource::make($product)->only('core', 'full');

// Collection — variant propagates to every item
ProductResource::collection($products)->as('list');

// Collection — variant + extra blocks
ProductResource::collection($products)->as('list')->with('full');
```

### 3. Resolve in a controller

```php
public function index(): JsonResponse
{
    return ProductResource::collection(Product::paginate())
        ->as('list')
        ->response();
}

public function show(Product $product): JsonResponse
{
    return ProductResource::make($product)
        ->as('show')
        ->response();
}
```

## API reference

| Method | Description |
|---|---|
| `::make($model)` | Single resource, default variant |
| `::collection($items)` | Collection, default variant |
| `->as(string $variant)` | Switch to a named variant |
| `->with(string ...$blocks)` | Append extra blocks (additive, chainable) |
| `->only(string ...$blocks)` | Replace selection with exactly these blocks, no variant |
| `->resolve($request?)` | Resolve to plain array |
| `->response($request?)` | Return `JsonResponse` |

## How blocks work

A block is any `protected` method on your resource class that:
- returns `array`
- is listed in at least one variant inside `variants()`
- optionally accepts `Illuminate\Http\Request` as its first (and only) parameter

Helper methods on the resource class that are **not** listed in `variants()` cannot be called via `with()` or `only()` — this is intentional. The `variants()` array is the single source of truth for what is a block.

## Exceptions

All exceptions extend `\RuntimeException`.

### `UnknownVariantException`

Thrown by `->as()` when the variant name is not a key in `variants()`. Also thrown at first instantiation if `default()` returns a name not in `variants()`.

```
Unknown variant [xyz] on [App\Http\Resources\ProductResource]. Available: [list, show, i18n].
```

### `UnknownBlockException`

Thrown by `->with()` and `->only()` immediately (not at resolve time) when a block name is not referenced in any variant. Also thrown at first instantiation if a variant references a method that does not exist on the class.

```
Unknown block [xyz] on [App\Http\Resources\ProductResource]. Available: [core, pricing, full, locale].
```

### `DuplicateBlockKeyException`

Thrown at resolve time when two blocks in the active selection return the same top-level key.

```
Duplicate key [name] in [App\Http\Resources\ProductResource]: produced by both [core] and [alias].
```

## Notes on `with()` and PHP compatibility

`JsonResource::with($request)` is an existing method used internally by Laravel's resource response layer. PHP's method compatibility rules prevent overriding it with `with(string ...$blocks): static`, so the parameter type is declared `mixed`. The method body validates string types and guards against the internal `Request` call. Runtime behaviour is identical to what the spec describes.

## License

AGPL-3.0-only — see [LICENSE](LICENSE).
