<?php

namespace Autepos\AiPayment\Contracts;

abstract class DTO
{

    /**
     * The array of required props
     *
     * @var array
     */
    protected  $_required_ = [];

    /**
     * Key-value associative pair of props and their allowed php type string 
     * names. Supported types includes boolean, integer, float, string, 
     * resource,array,{class-name}
     * e.g: ['id'=>'boolean']
     * 
     *
     * @var array
     */
    protected  $_types_ = [];



    /**
     * Create DTO
     * 
     * @param array $props Key-value pair for DTO fields
     * @throws \InvalidArgumentException if any array key does not have associated class property.
     */
    public function __construct(?array $props = [])
    {
        // Validate required
        foreach ($this->_required_ as $req) {
            if (is_null($props[$req])) {
                throw new \InvalidArgumentException('Property ' . $req . ' is required for ' . get_class($this));
            }
        }

        //
        if ($props) {

            // Validations
            $types = array_keys($this->_types_);
            foreach ($props as $prop => $val) {


                // Valid members
                $this->validateMember($prop);
                

                // Validate type
                if (in_array($prop, $types) and !is_null($val)) {
                    if (!$this->isValidType($this->_types_[$prop], $val)) {
                        throw new \InvalidArgumentException('Property ' . $prop . ' must be of the type, ' . $this->_types_[$prop] . ', for ' . get_class($this));
                    }
                }
            }

            

            // set the values
            foreach ($props as $field => $val) {
                $this->{$field} = $val;
            }
        }
    }

    /**
     * Validate that given property is a defined member of the class.
     * @throws \InvalidArgumentException argument has no associated class property.
     */
    private function validateMember(string $property_name){
        if (!\property_exists(static::class, $property_name)) {
            throw new \InvalidArgumentException('Unknown field: ' . $property_name . ' for ' . get_class($this));
        }
    }
    /**
     * Check if the given value is of the given type
     * 
     */
    private function isValidType(string $type, $value): bool
    {
        switch ($type) {
            case 'integer':
                return is_int($value);
            case 'float':
                return is_float($value);
            case 'string':
                return is_string($value);
            case 'boolean':
                return is_bool($value);
            case 'array':
                return is_array($value);
            case 'resource':
                return is_resource($value);
            default:
                if (class_exists($type)) {
                    return is_a($value, $type);
                }
                return false;
        }
    }

    /**
     * Property assessor
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->{$key};
    }


}
