<?php

namespace Autepos\AiPayment\Contracts;

class CustomerData extends DTO
{

    protected  $_required_ = [
        'user_type',
    ];

    protected $_types_ = [
        'user_type' => 'string',
        'user_id' => 'string',
        'first_name' => 'string',
        'last_name' => 'string',
        'email' => 'string',
        'phone' => 'string',

    ];



    /**
     * User type(i.e logged in customer, e.g the user eloquent model class) who
     * owns the order.
     * 
     * @var string
     */
    protected $user_type;

    /**
     * The value of the unique identifier of the user(i.e logged in customer) who
     * owns the order.
     * 
     * @var string
     */
    protected $user_id;
    /**
     * First name.
     * @var string
     */
    protected $first_name;

    /**
     * Last name.
     * @var string
     */
    protected $last_name;

    /**
     * Customer email
     *
     * @var string
     */
    protected $email;

    /**
     * Customer phone number
     *
     * @var string
     */
    protected $phone;
}
