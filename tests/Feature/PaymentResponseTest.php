<?php

namespace Autepos\AiPayment\Tests\Feature;

use Illuminate\Http\Response;
use Autepos\AiPayment\Tests\TestCase;
use Autepos\AiPayment\PaymentResponse;
use Autepos\AiPayment\Models\Transaction;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentResponseTest extends TestCase
{

    public function test_can_set_transaction()
    {
        $paymentResponse = new PaymentResponse(PaymentResponse::newType('init'));
        $transaction = Transaction::factory()->make([
            'id' => 13022022,
        ]);

        $result = $paymentResponse->transaction($transaction);

        //
        $this->assertInstanceOf(PaymentResponse::class, $result);
        $this->assertInstanceOf(Transaction::class, $result->getTransaction());
        $this->assertEquals($transaction->id, $result->getTransaction()->id);
    }
    public function test_can_serialise()
    {
        $paymentResponse = new PaymentResponse(PaymentResponse::newType('init'));
        $serialised_obj = json_decode(json_encode($paymentResponse));

        $keys = ['type', 'success', 'message', 'transaction', 'client_side_data', 'errors'];
        foreach ($keys as $key) {
            $this->assertObjectHasProperty($key, $serialised_obj);
        }
    }
    public function test_can_set_client_side_data()
    {
        $paymentResponse = new PaymentResponse(PaymentResponse::newType('init'));

        $paymentResponse->setClientSideData('token', 123);
        $paymentResponse->setClientSideData('secret', 456);

        $serialised_obj = json_decode(json_encode($paymentResponse));

        $this->assertEquals(123, $serialised_obj->client_side_data->token);
        $this->assertEquals(456, $serialised_obj->client_side_data->secret);
    }

    public function test_can_convert_to_http_response()
    {
        $paymentResponse = new PaymentResponse(PaymentResponse::newType('init'));

        $this->assertInstanceOf(Response::class, $paymentResponse->toHttp());
    }
}
