<?php

declare(strict_types=1);

namespace Baconfy\Variants\Exceptions;

final class DuplicateBlockKeyException extends \RuntimeException
{
    public function __construct(
        public readonly string $resource,
        public readonly string $key,
        public readonly string $firstBlock,
        public readonly string $secondBlock,
    ) {
        parent::__construct(
            "Duplicate key [{$key}] in [{$resource}]: produced by both [{$firstBlock}] and [{$secondBlock}]."
        );
    }
}
