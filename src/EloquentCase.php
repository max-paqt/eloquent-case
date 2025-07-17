<?php

declare(strict_types=1);

namespace Rxak\EloquentCase;

use BackedEnum;

class EloquentCase
{
    /** @var string[] */
    private array $when = [];

    /** @var mixed[] */
    private array $bindings = [];

    private mixed $else;

    private string $as;

    public static function mapValues(string $column, array $values): static
    {
        $builder = new static();

        foreach ($values as $case => $then) {
            $builder->when($column, '=', $case, $then);
        }

        return $builder;
    }

    public function whenRaw(string $raw, mixed $then): static
    {
        $this->when[] = sprintf("WHEN %s THEN ?", $raw);
        $this->bindings[] = $this->getValue($then);

        return $this;
    }

    public function when(string $column, string $operator, mixed $value, mixed $then): static
    {
        $this->when[] = sprintf('WHEN `%s` %s ? THEN ?', $column, $operator);
        $this->bindings[] = $this->getValue($value);
        $this->bindings[] = $this->getValue($then);

        return $this;
    }

    private function getValue(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }

    public function else(mixed $then): static
    {
        $this->else = $then;

        return $this;
    }

    public function as(string $as): static
    {
        $this->as = $as;

        return $this;
    }

    public function toArgs(): array
    {
        $statement = 'CASE ' . implode(' ', $this->when);
        $bindings = $this->bindings;

        if (isset($this->else)) {
            $statement .= ' ELSE ?';
            $bindings[] = $this->getValue($this->else);
        }

        $statement .= ' END';

        if (isset($this->as)) {
            $statement .= sprintf(' AS `%s`', $this->as);
        }

        return [$statement, $bindings];
    }

    public function raw(bool $allowStrings = false): string
    {
        [$statement, $bindings] = $this->toArgs();

        foreach ($bindings as $binding) {
            if (is_null($binding)) {
                $value = 'NULL';
            } elseif (is_string($binding)) {
                $value = $allowStrings ? "'" . addslashes($binding) . "'" : "''";
            } elseif (is_bool($binding)) {
                $value = $binding ? '1' : '0';
            } elseif (is_numeric($binding)) {
                $value = (string) $binding;
            } else {
                $value = $allowStrings ? "'" . str_replace("'", "''", (string) $binding) . "'" : "''";
            }

            $statement = preg_replace('/\?/', $value, $statement, 1);
        }

        return $statement;
    }
}
