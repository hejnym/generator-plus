<?php

declare(strict_types=1);

namespace ManoTests\GeneratorPlus\Data;

use Mano\GeneratorPlus\EventDispatcher\GeneratorPlusEvent;

class TestEvent implements GeneratorPlusEvent
{
    public function __construct(public int $id) {}
}
