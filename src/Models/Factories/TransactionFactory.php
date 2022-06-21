<?php

namespace Autepos\AiPayment\Models\Factories;

use \Autepos\AiPayment\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
       return [
            'payment_provider'=>$this->faker->word(),
            'orderable_amount'=>100,
            'amount'=>0,
        ];
    }

    
}
