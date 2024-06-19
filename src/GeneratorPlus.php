<?php

declare(strict_types=1);

namespace Mano\GeneratorPlus;

use Closure;
use Generator;
use Iterator;
use Mano\GeneratorPlus\Exception\GeneratorRewindException;

/**
 * @mixin Generator
 * @implements Iterator<mixed, mixed>
 */
final class GeneratorPlus implements \Iterator
{
    private Generator $generator;
    private mixed $current;
    private mixed $key;

    private function __construct(
        Generator $generator,
    ) {

        $this->generator = $generator;

        $this->current = $this->generator->current();
        $this->key = $this->generator->key();
    }

    /**
     * @param Closure(): Generator<mixed> $callable
     */
    public static function createFromCallable(Closure $callable): self
    {
        return new self($callable());
    }

    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        $this->generator->rewind();
        $this->current = $this->generator->current();
        $this->key = $this->generator->key();
    }

    /**
     * @inheritDoc
     */
    public function current(): mixed
    {
        return $this->current;
    }

    /**
     * @inheritDoc
     */
    public function key(): mixed
    {
        return $this->key;
    }

    /**
     * @inheritDoc
     */
    public function next(): void
    {
        $this->generator->next();
        $this->current = $this->generator->current();
        $this->key = $this->generator->key();
    }

    /**
     * @inheritDoc
     */
    public function valid(): bool
    {
        return $this->generator->valid();
    }

    /**
     * @see Generator::send()
     */
    public function send(mixed $value): mixed
    {
        return $this->generator->send($value);
    }

    /**
     * @see Generator::getReturn()
     */
    public function getReturn(): mixed
    {
        return $this->generator->getReturn();
    }

    /**
     * @see Generator::__wakeup()
     */
    public function __wakeup(): void
    {
        $this->generator->__wakeup();
    }
}
