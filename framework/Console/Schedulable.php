<?php

declare(strict_types=1);

namespace Sauerkraut\Console;

interface Schedulable
{
    public function schedule(): Schedule;
}
