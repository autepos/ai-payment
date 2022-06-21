<?php

namespace Autepos\AiPayment\Models\Factories;


use Illuminate\Database\Eloquent\Factories\Factory;
use Autepos\AiPayment\Models\PaymentProviderCustomer;



class PaymentProviderCustomerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PaymentProviderCustomer::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
       return [
            'payment_provider'=>$this->faker->word(),
        ];
    }

    

    
}
