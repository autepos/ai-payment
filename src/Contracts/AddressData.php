<?php

namespace Autepos\AiPayment\Contracts;

class AddressData extends DTO
{

    protected $_types_ = [
        'country_code' => 'string',
    ];

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
     * Phone number
     *
     * @var string
     */
    protected $phone;

    /**
     * Company name
     * @var string
     */
    protected $company;

    /**
     * Address line 1
     * @var string
     */
    protected $line_1;

    /**
     * Address line 2
     * @var string
     */
    protected $line_2;

    /**
     * Address district, suburb, town, or village.
     * @var string
     */
    protected $district;

    /**
     * Two-letter Country code
     * @var string
     */
    protected $country_code;

    /**
     * Address city
     * @var string
     */
    protected $city;

    /**
     * Address province, state, county or region
     * @var string
     */
    protected $province;

    /**
     * Postcode, or zip code
     * @var string
     */
    protected $postcode;

    /**
     * Latitude
     * @var string
     */
    protected $lat;

    /**
     * Longitude
     * @var string
     */
    protected $lng;

    /**
     * What3words
     * @var string
     */
    protected $what3words;
}
