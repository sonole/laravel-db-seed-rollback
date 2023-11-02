<?php

namespace Sonole\LaravelDbSeedRollback\Illuminate\Database;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use InvalidArgumentException;

abstract class Seeder extends \Illuminate\Database\Seeder
{
    /**
     * Run the database seeds.
     *
     * @param  array  $parameters
     * @param  string $methodToExecute
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function __invoke(array $parameters = [], string $methodToExecute = 'run')
    {
        if (! method_exists($this, 'run')) {
            throw new InvalidArgumentException('Method [run] missing from '.get_class($this));
        }

        if (in_array('rollback', $parameters)) {
            if (! method_exists($this, 'down')) {
                throw new InvalidArgumentException('Method [down] missing from '.get_class($this));
            }
            unset($parameters[array_flip($parameters)['rollback']]);
            $methodToExecute = 'down';
        }

        $callback = fn () => isset($this->container)
            ? $this->container->call([$this, $methodToExecute], $parameters)
            : $this->run(...$parameters);

        $uses = array_flip(class_uses_recursive(static::class));

        if (isset($uses[WithoutModelEvents::class])) {
            $callback = $this->withoutModelEvents($callback);
        }

        return $callback();
    }
}