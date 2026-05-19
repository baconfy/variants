<?php

declare(strict_types=1);

use Baconfy\Variants\ComposableResource;
use Baconfy\Variants\Exceptions\DuplicateBlockKeyException;
use Baconfy\Variants\Exceptions\UnknownBlockException;
use Baconfy\Variants\Exceptions\UnknownVariantException;
use Baconfy\Variants\Tests\Fixtures\Product;
use Baconfy\Variants\Tests\Fixtures\ProductResource;
use Illuminate\Http\Request;

beforeEach(function () {
    $this->product = new Product(
        name: 'Coffee',
        sku: 'COF-001',
        price: 12.50,
        description: 'Single origin',
    );
});

it('uses default variant when none specified', function () {
    $result = ProductResource::make($this->product)->resolve();

    expect($result)->toBe([
        'name' => 'Coffee',
        'sku' => 'COF-001',
        'price' => 12.50,
    ]);
});

it('switches variant via as()', function () {
    $result = ProductResource::make($this->product)->as('minimal')->resolve();

    expect($result)->toBe([
        'name' => 'Coffee',
        'sku' => 'COF-001',
    ]);
});

it('adds extra block via with()', function () {
    $result = ProductResource::make($this->product)
        ->as('minimal')
        ->with('pricing')
        ->resolve();

    expect($result)->toBe([
        'name' => 'Coffee',
        'sku' => 'COF-001',
        'price' => 12.50,
    ]);
});

it('chains with() additively', function () {
    $result = ProductResource::make($this->product)
        ->as('minimal')
        ->with('pricing')
        ->with('full')
        ->resolve();

    expect($result)->toBe([
        'name' => 'Coffee',
        'sku' => 'COF-001',
        'price' => 12.50,
        'description' => 'Single origin',
    ]);
});

it('builds ad-hoc representation with only()', function () {
    $result = ProductResource::make($this->product)
        ->only('core', 'full')
        ->resolve();

    expect($result)->toBe([
        'name' => 'Coffee',
        'sku' => 'COF-001',
        'description' => 'Single origin',
    ]);
});

it('passes Request to blocks that declare it in signature', function () {
    $request = Request::create('/', server: ['HTTP_ACCEPT_LANGUAGE' => 'pt-BR']);

    $result = ProductResource::make($this->product)
        ->as('i18n')
        ->resolve($request);

    expect($result)->toBe([
        'name' => 'Coffee',
        'sku' => 'COF-001',
        'locale' => 'pt-BR',
    ]);
});

it('throws when as() receives unknown variant', function () {
    ProductResource::make($this->product)->as('unknown');
})->throws(UnknownVariantException::class);

it('throws immediately when with() references unknown block', function () {
    ProductResource::make($this->product)->with('xpto');
})->throws(UnknownBlockException::class);

it('throws when only() references unknown block', function () {
    ProductResource::make($this->product)->only('xpto');
})->throws(UnknownBlockException::class);

it('throws when blocks share keys within the same active selection', function () {
    $resource = new class(new Product(name: 'X', sku: 'X', price: 0)) extends ComposableResource
    {
        protected function a(): array
        {
            return ['shared' => 1];
        }

        protected function b(): array
        {
            return ['shared' => 2];
        }

        protected function variants(): array
        {
            return ['default' => ['a', 'b']];
        }

        protected function default(): string
        {
            return 'default';
        }
    };

    $resource->resolve();
})->throws(DuplicateBlockKeyException::class);

it('propagates variant through collection', function () {
    $products = [
        $this->product,
        new Product(name: 'Tea', sku: 'TEA-001', price: 8.00),
    ];

    $result = ProductResource::collection($products)->as('minimal')->resolve(request());

    expect($result)->toBe([
        ['name' => 'Coffee', 'sku' => 'COF-001'],
        ['name' => 'Tea',    'sku' => 'TEA-001'],
    ]);
});

it('propagates variant and with() through collection', function () {
    $result = ProductResource::collection([$this->product])
        ->as('minimal')
        ->with('full')
        ->resolve(request());

    expect($result)->toBe([
        ['name' => 'Coffee', 'sku' => 'COF-001', 'description' => 'Single origin'],
    ]);
});
