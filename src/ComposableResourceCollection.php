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

    /**
     * Stores block names to propagate to each item at resolve time.
     *
     * NOTE: The parent JsonResource declares with($request), so mixed is required
     * here to avoid a fatal signature incompatibility.
     *
     * @param  string  ...$blocks
     */
    public function with(mixed ...$blocks): static
    {
        // Guard for Laravel's internal ResourceResponse::toResponse() call.
        if (count($blocks) === 1 && reset($blocks) instanceof Request) {
            return $this;
        }

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
                $resource->with(...$this->extraBlocks);
            }

            return $resource->resolve($request);
        })->all();
    }
}
