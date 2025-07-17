<?php

declare(strict_types=1);

namespace Tests\Rxak\EloquentCase;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rxak\EloquentCase\EloquentCase;

class EscapedCaseTest extends TestCase
{
    #[Test]
    public function it_constructs_case_statements_with_1_case_and_else()
    {
        $case = (new EloquentCase())
            ->when('age', '>', 18, 'adult')
            ->else('minor');

        $this->assertEquals([
            'CASE WHEN `age` > ? THEN ? ELSE ? END',
            [18, 'adult', 'minor'],
        ], $case->toArgs());
    }

    #[Test]
    public function it_adds_as()
    {
        $case = (new EloquentCase())
            ->when('age', '>', 18, 'adult')
            ->else('minor')
            ->as('age_group');

        $this->assertEquals([
            'CASE WHEN `age` > ? THEN ? ELSE ? END AS `age_group`',
            [18, 'adult', 'minor'],
        ], $case->toArgs());
    }

    #[Test]
    public function it_constructs_case_statements_with_multiple_whens_and_else()
    {
        $case = (new EloquentCase())
            ->when('gender', '=', 'm', 'señor')
            ->when('age', '>', 65, 'senior')
            ->when('age', '>', 40, 'adult')
            ->when('age', '>', 18, 'young adult')
            ->else('minor');

        $this->assertEquals([
            'CASE WHEN `gender` = ? THEN ? '
            . 'WHEN `age` > ? THEN ? '
            . 'WHEN `age` > ? THEN ? '
            . 'WHEN `age` > ? THEN ? '
            . 'ELSE ? END',
            ['m', 'señor', 65, 'senior', 40, 'adult', 18, 'young adult', 'minor'],
        ], $case->toArgs());
    }

    #[Test]
    public function it_constructs_case_statements_with_1_case_without_else()
    {
        $case = (new EloquentCase())
            ->when('age', '>', 18, 'adult');

        $this->assertEquals([
            'CASE WHEN `age` > ? THEN ? END',
            [18, 'adult'],
        ], $case->toArgs());
    }

    #[Test]
    public function it_constructs_case_statements_with_multiple_whens_without_else()
    {
        $case = (new EloquentCase())
            ->when('gender', '=', 'm', 'señor')
            ->when('age', '>', 65, 'senior')
            ->when('age', '>', 40, 'adult')
            ->when('age', '>', 18, 'young adult');

        $this->assertEquals([
            'CASE WHEN `gender` = ? THEN ? '
            . 'WHEN `age` > ? THEN ? '
            . 'WHEN `age` > ? THEN ? '
            . 'WHEN `age` > ? THEN ? END',
            ['m', 'señor', 65, 'senior', 40, 'adult', 18, 'young adult'],
        ], $case->toArgs());
    }

    #[Test]
    public function it_uses_enum_values_for_backed_enums()
    {
        $case = (new EloquentCase())
            ->when('my_column', '=', MyEnum::First, MyEnum::Second)
            ->else(MyEnum::Third);

        $this->assertEquals([
            'CASE WHEN `my_column` = ? THEN ? ELSE ? END',
            [MyEnum::First->value, MyEnum::Second->value, MyEnum::Third->value],
        ], $case->toArgs());
    }

    #[Test]
    public function it_allows_raw_when()
    {
        $case = (new EloquentCase())
            ->whenRaw('my risky wen', 'value')
            ->else('else');

        $this->assertEquals([
            'CASE WHEN my risky wen THEN ? ELSE ? END',
            ['value', 'else'],
        ], $case->toArgs());
    }

    #[Test]
    public function it_allows_raw_when_with_enum()
    {
        $case = (new EloquentCase())
            ->whenRaw('my risky wen', MyEnum::First)
            ->else(MyEnum::Second);

        $this->assertEquals([
            'CASE WHEN my risky wen THEN ? ELSE ? END',
            [MyEnum::First->value, MyEnum::Second->value],
        ], $case->toArgs());
    }

    #[Test]
    public function it_can_batch_map_values()
    {
        $case = EloquentCase::mapValues('my_column', [
            'one' => MyEnum::First,
            'two' => MyEnum::Second,
        ]);

        $this->assertEquals([
            'CASE WHEN `my_column` = ? THEN ? WHEN `my_column` = ? THEN ? END',
            ['one', MyEnum::First->value, 'two', MyEnum::Second->value],
        ], $case->toArgs());
    }
}

enum MyEnum: string
{
    case First = 'first';
    case Second = 'second';
    case Third = 'third';
}
