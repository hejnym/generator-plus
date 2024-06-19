<?php

declare(strict_types=1);

namespace Mano\GeneratorPlus\Exception;

class GeneratorRewindException extends \LogicException
{
    public function __construct()
    {
        parent::__construct(message: 'Cannot rewind a generator that was already run');
    }
}
