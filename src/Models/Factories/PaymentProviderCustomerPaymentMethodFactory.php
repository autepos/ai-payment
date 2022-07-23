<?php

namespace Autepos\AiPayment\Models\Factories;


use Autepos\AiPayment\PaymentService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Autepos\AiPayment\Models\PaymentProviderCustomerPaymentMethod;



class PaymentProviderCustomerPaymentMethodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PaymentProviderCustomerPaymentMethod::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'pid'=>PaymentService::generatePid(),
            'payment_provider' => $this->faker->word(),
        ];
    }
}
