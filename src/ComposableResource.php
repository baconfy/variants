<?php

declare(strict_types=1);

namespace Baconfy\Variants;

use Baconfy\Variants\Exceptions\DuplicateBlockKeyException;
use Baconfy\Variants\Exceptions\UnknownBlockException;
use Baconfy\Variants\Exceptions\UnknownVariantException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use ReflectionMethod;
use ReflectionNamedType;

abstract class ComposableResource extends JsonResource
{
    /** @var array<class-string, array{variants: array, default: string, allBlocks: array, needsRequest: array}> */
    private static array $cache = [];

    private ?string $activeVariant = null;

    private array $extraBlocks = [];

    private ?array $onlyBlocks = null;

    public function __construct(mixed $resource)
    {
        parent::__construct($resource);
        $this->ensureBooted();
    }

    abstract protected function default(): string;

    /** @return array<string, array<int, string>> */
    abstract protected function variants(): array;

    public function as(string $variant): static
    {
        $cache = self::$cache[static::class];

        if (! array_key_exists($variant, $cache['variants'])) {
            throw new UnknownVariantException(static::class, $variant, array_keys($cache['variants']));
        }

        $this->activeVariant = $variant;

        return $this;
    }

    /**
     * Appends extra blocks to the current variant selection.
     *
     * NOTE: The parent JsonResource declares with($request), so the PHP type system
     * requires mixed here. The guard below prevents interference with Laravel's
     * internal ResourceResponse::toResponse() call.
     *
     * @param  string  ...$blocks
     */
    public function with(mixed ...$blocks): static
    {
        // Guard: Laravel's ResourceResponse calls with($request) to retrieve
        // additional response envelope data. Return early without modifying state.
        if (count($blocks) === 1 && reset($blocks) instanceof Request) {
            return $this;
        }

        $cache = self::$cache[static::class];

        foreach ($blocks as $block) {
            if (! array_key_exists($block, $cache['allBlocks'])) {
                throw new UnknownBlockException(static::class, (string) $block, array_keys($cache['allBlocks']));
            }
        }

        array_push($this->extraBlocks, ...$blocks);

        return $this;
    }

    public function only(string ...$blocks): static
    {
        $cache = self::$cache[static::class];

        foreach ($blocks as $block) {
            if (! array_key_exists($block, $cache['allBlocks'])) {
                throw new UnknownBlockException(static::class, $block, array_keys($cache['allBlocks']));
            }
        }

        $this->onlyBlocks = array_values($blocks);

        return $this;
    }

    public function toArray(Request $request): array
    {
        $result = [];
        $keyToBlock = [];

        foreach ($this->resolveActiveBlocks() as $block) {
            $data = $this->callBlock($block, $request);

            foreach ($data as $key => $value) {
                if (array_key_exists($key, $keyToBlock)) {
                    throw new DuplicateBlockKeyException(static::class, (string) $key, $keyToBlock[$key], $block);
                }
                $keyToBlock[$key] = $block;
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public static function collection(mixed $resource): ComposableResourceCollection
    {
        return new ComposableResourceCollection($resource, static::class);
    }

    private function ensureBooted(): void
    {
        $class = static::class;

        if (isset(self::$cache[$class])) {
            return;
        }

        $variants = $this->variants();
        $default = $this->default();

        if (! array_key_exists($default, $variants)) {
            throw new UnknownVariantException($class, $default, array_keys($variants));
        }

        $allBlocks = array_values(array_unique(
            array_merge(...(array_values($variants) ?: [[]]))
        ));

        foreach ($allBlocks as $block) {
            if (! method_exists($this, $block)) {
                throw new UnknownBlockException($class, $block, $allBlocks);
            }
        }

        $needsRequest = [];

        foreach ($allBlocks as $block) {
            $params = (new ReflectionMethod($this, $block))->getParameters();
            $needsRequest[$block] = ! empty($params)
                && $params[0]->hasType()
                && $params[0]->getType() instanceof ReflectionNamedType
                && $params[0]->getType()->getName() === Request::class;
        }

        self::$cache[$class] = [
            'variants' => $variants,
            'default' => $default,
            'allBlocks' => array_flip($allBlocks),
            'needsRequest' => $needsRequest,
        ];
    }

    private function resolveActiveBlocks(): array
    {
        if ($this->onlyBlocks !== null) {
            return $this->onlyBlocks;
        }

        $cache = self::$cache[static::class];
        $variant = $this->activeVariant ?? $cache['default'];

        return array_merge($cache['variants'][$variant], $this->extraBlocks);
    }

    private function callBlock(string $block, Request $request): array
    {
        if (self::$cache[static::class]['needsRequest'][$block]) {
            return $this->$block($request);
        }

        return $this->$block();
    }
}
