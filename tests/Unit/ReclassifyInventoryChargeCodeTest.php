<?php

namespace Tests\Unit;

use App\Console\Commands\ReclassifyInventoryChargeCode;
use App\Models\Pharmacy\DrugPrice;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ReclassifyInventoryChargeCodeTest extends TestCase
{
    public function test_command_is_preview_only_unless_commit_option_is_present()
    {
        $command = new ReclassifyInventoryChargeCode();

        $this->assertTrue($command->getDefinition()->hasArgument('source'));
        $this->assertTrue($command->getDefinition()->hasArgument('destination'));
        $this->assertTrue($command->getDefinition()->hasOption('commit'));
        $this->assertFalse($command->getDefinition()->getOption('commit')->getDefault());
    }

    public function test_snapshot_hash_is_independent_of_query_order()
    {
        $command = new ReclassifyInventoryChargeCode();
        $method = new ReflectionMethod($command, 'snapshotHash');
        $method->setAccessible(true);

        $first = [
            ['stock_id' => 2, 'quantity' => 10.0],
            ['stock_id' => 1, 'quantity' => 5.0],
        ];
        $second = array_reverse($first);

        $this->assertSame($method->invoke($command, $first), $method->invoke($command, $second));
    }

    public function test_price_compatibility_includes_valuation_and_compounding()
    {
        $command = new ReclassifyInventoryChargeCode();
        $method = new ReflectionMethod($command, 'pricesMatch');
        $method->setAccessible(true);

        $source = $this->price([
            'dmduprice' => 10,
            'dmselprice' => 14,
            'acquisition_cost' => 10,
            'mark_up' => 4,
            'has_compounding' => false,
            'compounding_fee' => 0,
            'retail_price' => 14,
        ]);
        $same = $this->price($source->getAttributes());
        $different = $this->price(array_merge($source->getAttributes(), ['acquisition_cost' => 11]));

        $this->assertTrue($method->invoke($command, $source, $same));
        $this->assertFalse($method->invoke($command, $source, $different));
    }

    private function price(array $attributes)
    {
        $price = new DrugPrice();
        $price->setRawAttributes($attributes);

        return $price;
    }
}
