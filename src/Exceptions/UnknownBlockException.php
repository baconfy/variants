<?php

declare(strict_types=1);

namespace Baconfy\Variants\Exceptions;

final class UnknownBlockException extends \RuntimeException
{
    public function __construct(
        public readonly string $resource,
        public readonly string $block,
        public readonly array $available = [],
    ) {
        $list = implode(', ', $available);

        parent::__construct(
            "Unknown block [{$block}] on [{$resource}]. Available: [{$list}]."
        );
    }
}
