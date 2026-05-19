<?php

declare(strict_types=1);

namespace Baconfy\Variants\Exceptions;

final class UnknownVariantException extends \RuntimeException
{
    public function __construct(
        public readonly string $resource,
        public readonly string $variant,
        public readonly array $available = [],
    ) {
        $list = implode(', ', $available);

        parent::__construct(
            "Unknown variant [{$variant}] on [{$resource}]. Available: [{$list}]."
        );
    }
}
