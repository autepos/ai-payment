<?php

namespace Autepos\AiPayment\Tests\Feature\Models;


use Autepos\AiPayment\Tests\TestCase;
use Autepos\AiPayment\Events\OrderableTransactionsTotaled;
use Autepos\AiPayment\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class TransactionTest extends TestCase
{


    use RefreshDatabase;

    public function test_can_compute_total_paid_for_an_orderable(){
        
        //

        Event::fake();

        Transaction::factory()
        ->times(6)
        ->sequence([
            'amount'=>1000,
            'amount_refunded'=>0,
            'success'=>true,
            'refund'=>false,
        ],[
            'amount'=>1000,
            'amount_refunded'=>-500,
            'success'=>true,
            'refund'=>false,
        ],[
            'amount'=>0,
            'amount_refunded'=>-500,
            'success'=>true,
            'refund'=>true,
            'display_only'=>true,// For display - so won't count
        ],[
            'amount'=>0,
            'success'=>true,
            'amount_refunded'=>-200,
            'refund'=>true,
        ],[
            'amount'=>300,
            'success'=>false, // Not successful - so won't count
            'amount_refunded'=>0,
            'refund'=>false,
        ],[
            'amount'=>700,
            'success'=>false, // Not successful - so won't count
            'amount_refunded'=>-600,
            'refund'=>false,
        ])->create(['orderable_id'=>14022022]);
        
        //
        $expected_total_paid=(1000-0)+(1000-500)+(0-200);

        $actual_total_paid=Transaction::totalPaid(14022022);
        $this->assertEquals($expected_total_paid,$actual_total_paid);
        
        
    }

    public function test_can_dispatch_orderable_transaction_totaled(){
       
        Event::fake([
            OrderableTransactionsTotaled::class,
        ]);

        $transactions=Transaction::factory()
        ->times(2)
        ->sequence([
            'amount'=>1000,
            'amount_refunded'=>0,
            'success'=>true,
            'refund'=>false,
        ],[
            'amount'=>700,
            'amount_refunded'=>-100,
            'success'=>true,
            'refund'=>false,
        ])->create(['orderable_id'=>14022022]);
        
        //
        $total_paid=(1000-0)+(700-100);



        // Trigger the save event. Just a arbitrary operation to help trigger event.
        $triggeringTransaction=$transactions->first();
        $triggeringTransaction->payment_provider='provider_otu';// 
        $triggeringTransaction->save();


        Event::assertDispatched(function(OrderableTransactionsTotaled $event)use($total_paid,$triggeringTransaction){
            return (
                $event->orderable_id==14022022
                and $event->total_paid==$total_paid
                and ($event->triggeringTransaction->id==$triggeringTransaction->id)
            );
        });

    }


    public function test_can_determine_the_payment_provider_for_a_transaction(){
        $provider='provider_yi';
        $transaction=Transaction::factory()
        ->create([
            'payment_provider'=>$provider,
            'orderable_id'=>14022022,
            'amount'=>1000,
            'amount_refunded'=>0,
            'success'=>true,
            'refund'=>false,
        ]);

        $this->assertTrue($transaction->isForPaymentProvider($provider));
        $this->assertFalse($transaction->isForPaymentProvider('provider_er'));

    }

    public function test_can_validate_refund_amount()
    {
        $provider='provider_yi';
        $transaction=Transaction::factory()
        ->create([
            'payment_provider'=>$provider,
            'orderable_id'=>14022022,
            'amount'=>1000,
            'amount_refunded'=>999,
            'success'=>true,
            'refund'=>false,
        ]);

        $this->assertTrue($transaction->isValidRefundAmount(1));
        $this->assertFalse($transaction->isValidRefundAmount(2));
    }
    
    
}
