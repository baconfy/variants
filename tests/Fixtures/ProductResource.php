<?php

declare(strict_types=1);

namespace Baconfy\Variants\Tests\Fixtures;

use Baconfy\Variants\ComposableResource;
use Illuminate\Http\Request;

final class ProductResource extends ComposableResource
{
    /** @var Product */
    public $resource;

    protected function core(): array
    {
        return [
            'name' => $this->resource->name,
            'sku' => $this->resource->sku,
        ];
    }

    protected function pricing(): array
    {
        return [
            'price' => $this->resource->price,
        ];
    }

    protected function full(): array
    {
        return [
            'description' => $this->resource->description,
        ];
    }

    protected function locale(Request $request): array
    {
        return [
            'locale' => $request->header('Accept-Language', 'en'),
        ];
    }

    protected function variants(): array
    {
        return [
            'minimal' => ['core'],
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
