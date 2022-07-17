<?php

namespace Autepos\AiPayment;

use InvalidArgumentException;

/**
 * @todo Need to convert to enum when in PHP 8.1
 */
class ResponseType
{
    /**
     * The valid types
     */
    public const TYPES = [
        'ping' => 'ping',
        'init' => 'init',
        'update' => 'update',
        'charge' => 'charge',
        'refund' => 'refund',
        'retrieve' => 'retrieve', // Retrieve info from provider
        'sync' => 'sync',
        'save' => 'save',
        'delete' => 'delete',
    ];

    /**
     * The ping type value.
     */
    public const TYPE_PING = self::TYPES['ping'];

    /**
     * The initialisation type value.
     */
    public const TYPE_INIT = self::TYPES['init'];


    /**
     * The update type value.
     */
    public const TYPE_UPDATE = self::TYPES['update'];

    /**
     * The charge type value.
     */
    public const TYPE_CHARGE = self::TYPES['charge'];

    /**
     * The refund type value.
     */
    public const TYPE_REFUND = self::TYPES['refund'];

    /**
     * The retrieve type value.
     */
    public const TYPE_RETRIEVE = self::TYPES['retrieve'];

    /**
     * The sync type value.
     */
    public const TYPE_SYNC = self::TYPES['sync'];

    /**
     * The save type value.
     */
    public const TYPE_SAVE = self::TYPES['save'];

    /**
     * The delete type value.
     */
    public const TYPE_DELETE = self::TYPES['delete'];

    /**
     * The name of type;
     *
     * @var string
     */
    protected $name;



    /**
     * Construct a type
     *
     * @param string $name
     * @throws InvalidArgumentException
     */
    public function __construct(string $name)
    {
        $this->setName($name);
    }

    /**
     * Set the name of the type
     *
     * @param string $name
     * @throws InvalidArgumentException
     */
    public function setName(string $name)
    {
        if (in_array($name, array_keys(static::TYPES))) {
            $this->name = $name;
        } else {
            throw new InvalidArgumentException('`' . $name . '` is an unknown type');
        }
    }
    /**
     * Get the name of the type
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the value of the type
     *
     * @return string
     */
    public function value()
    {
        return static::TYPES[$this->name];
    }
}
