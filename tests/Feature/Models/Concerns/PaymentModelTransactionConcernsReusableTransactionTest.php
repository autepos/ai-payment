<?php

namespace Autepos\AiPayment\Tests\Feature\Models\Concerns;


use Mockery;
use Autepos\AiPayment\Tests\TestCase;
use Autepos\AiPayment\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Autepos\AiPayment\Providers\Contracts\Orderable;


class PaymentModelTransactionConcernsReusableTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_use_usable_transaction()
    {

        $orderable_id = 14022022;

        /**
         * @var Transaction
         */
        $transaction = Transaction::factory()
            ->create([
                'orderable_id' => $orderable_id,
                'success' => false,
                'local_status' => Transaction::LOCAL_STATUS_INIT,
            ]);

        $mockOrderableinterface = Mockery::mock(Orderable::class);
        $mockOrderableinterface->shouldReceive('getKey')
            ->atMost(1)
            ->andReturn($orderable_id);

        $this->assertTrue($transaction->isUsableFor($mockOrderableinterface));
    }
    public function test_cannot_use_a_transaction_for_another_orderable()
    {

        $orderable_id = 14022022;

        /**
         * @var Transaction
         */
        $transaction = Transaction::factory()
            ->create([
                'orderable_id' => 1,
                'success' => false,
                'local_status' => Transaction::LOCAL_STATUS_INIT,
            ]);

        $mockOrderableinterface = Mockery::mock(Orderable::class);
        $mockOrderableinterface->shouldReceive('getKey')
            ->atMost(1)
            ->andReturn($orderable_id);

        $this->assertFalse($transaction->isUsableFor($mockOrderableinterface));
    }

    public function test_cannot_use_a_transaction_with_success_status()
    {

        $orderable_id = 14022022;

        /**
         * @var Transaction
         */
        $transaction = Transaction::factory()
            ->create([
                'orderable_id' => $orderable_id,
                'success' => true,
                'local_status' => Transaction::LOCAL_STATUS_INIT,
            ]);

        $mockOrderableinterface = Mockery::mock(Orderable::class);
        $mockOrderableinterface->shouldReceive('getKey')
            ->atMost(1)
            ->andReturn($orderable_id);

        $this->assertFalse($transaction->isUsableFor($mockOrderableinterface));
    }

    public function test_cannot_use_a_transaction_with_local_status_of_complete()
    {

        $orderable_id = 14022022;

        /**
         * @var Transaction
         */
        $transaction = Transaction::factory()
            ->create([
                'orderable_id' => $orderable_id,
                'success' => false,
                'local_status' => Transaction::LOCAL_STATUS_COMPLETE,
            ]);

        $mockOrderableinterface = Mockery::mock(Orderable::class);
        $mockOrderableinterface->shouldReceive('getKey')
            ->atMost(1)
            ->andReturn($orderable_id);

        $this->assertFalse($transaction->isUsableFor($mockOrderableinterface));
    }
}
