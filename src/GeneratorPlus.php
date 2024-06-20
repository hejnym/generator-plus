<?php

declare(strict_types=1);

namespace Mano\GeneratorPlus;

use Iterator;
use Closure;
use Generator;
use Iterator;

/**
 * @template TKey
 * @template TValue
 * @template TSend
 * @template TReturn
 * @implements Iterator<TKey,TValue>
 * @mixin Generator<TKey,TValue, TSend, TReturn>
 */
final class GeneratorPlus implements Iterator
{
	/**
	 * @param Generator<TKey, TValue, TSend, TReturn> $generator
	 */
    private Generator $generator;
    private mixed $current;
    private mixed $key;

    private bool $skipMovingToNext = false;

	/**
	 * @param Generator<TKey, TValue, TSend, TReturn> $generator
	 */
	private function __construct(
        Generator $generator,
    ) {

        $this->generator = $generator;

        $this->current = $this->generator->current();
        $this->key = $this->generator->key();
    }

    /**
	 * @param-immediately-invoked-callable $callable
     * @param Closure(): Generator<TKey, TValue, TSend, TReturn> $callable
	 * @return self<TKey, TValue, TSend, TReturn>
     */
    public static function createFromCallable(Closure $callable): self
    {
        return new self($callable());
    }

	/**
	 * @param TSend $value
	 * @return TValue|null
	 */
    public function sendInForeach(mixed $value): mixed
    {
        if ($this->skipMovingToNext === true) {
            throw new \LogicException('You can only call sendInForeach method once per loop.');
        }

        $value = $this->send($value);
        $this->skipMovingToNext = true;

        return $value;
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
	 * @return TValue
     */
    public function current(): mixed
    {
        return $this->current;
    }

    /**
     * @inheritDoc
	 * @return TKey
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
        if ($this->skipMovingToNext === false) {
            $this->generator->next();
        } else {
            $this->skipMovingToNext = false;
        }

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
	 * @param TSend $value
	 * @return TValue|null
     */
    public function send(mixed $value): mixed
    {
        return $this->generator->send($value);
    }

    /**
     * @see Generator::getReturn()
	 * @return TReturn $value
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
