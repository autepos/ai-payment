<?php

namespace Autepos\AiPayment\Providers\Contracts;

use Autepos\AiPayment\Contracts\AddressData;
use Autepos\AiPayment\Contracts\CustomerData;

interface Orderable
{
    /**
     * Update a meta value by key
     *
     * @param string $key
     * @param mixed $val
     * @return bool
     */
    public function updateMetaByKey(string $key, $val): bool;

    /**
     * Get a meta value by key
     *
     * @param string $key
     * @return mixed|null
     */
    public function getMetaByKey(string $key);


    /**
     * Get the name of the primary identifier of the order
     * @return string
     */
    public function getKeyName();

    /**
     * Get the value of the primary identifier of the order
     *
     * @return int|string
     */
    public function getKey();

    /**
     * Get customer data
     *
     */
    public function getCustomer(): CustomerData;


    /**
     * Get order description
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Get the order total amount in currency lowest subunit
     *
     * @return int
     */
    public function getAmount(): int;

    /**
     * Get the order currency e.g GP,USD
     *
     * @return string
     */
    public function getCurrency(): string;

    /**
     * Check if the other is paid
     *
     * @return boolean
     */
    public function isPaid(): bool;

    /**
     * Confirm payment for the orderable
     */
    public function confirmPayment(): bool;

    /**
     * Fulfil the orderable
     */
    public function fulfil(): bool;

    /**
     * Get the billing address
     *
     * @return string
     */
    public function getBillingAddress(): AddressData;

    /**
     * Get the billing email
     *
     * @return string
     */
    public function getBillingEmail(): ?string;

    /**
     * Get the shipping address
     *
     * @return string
     */
    public function getShippingAddress(): ?AddressData;


    /**
     * Get the billing email
     *
     * @return string
     */
    public function getShippingEmail(): ?string;
}
