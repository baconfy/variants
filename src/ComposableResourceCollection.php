<?php

declare(strict_types=1);

namespace Baconfy\Variants;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ComposableResourceCollection extends AnonymousResourceCollection
{
    private ?string $activeVariant = null;

    private array $extraBlocks = [];

    public function as(string $variant): static
    {
        $this->activeVariant = $variant;

        return $this;
    }

    public function append(string ...$blocks): static
    {
        array_push($this->extraBlocks, ...$blocks);

        return $this;
    }

    public function toArray(Request $request): array
    {
        return $this->collection->map(function (ComposableResource $resource) use ($request): array {
            if ($this->activeVariant !== null) {
                $resource->as($this->activeVariant);
            }

            if ($this->extraBlocks !== []) {
                $resource->append(...$this->extraBlocks);
            }

            return $resource->resolve($request);
        })->all();
    }
}
