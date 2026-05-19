<?php

declare(strict_types=1);

namespace Baconfy\Variants\Tests\Fixtures;

final class Product
{
    public function __construct(
        public string $name,
        public string $sku,
        public float $price,
        public ?string $description = null,
    ) {}
}
