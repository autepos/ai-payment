<?php

namespace Autepos\AiPayment\Tenancy;

use Illuminate\Database\Schema\Blueprint;

class Tenant
{
    /**
     * The key for storing the tenant id is session or other storage means.
     */
    private const TENANT_ID_STORAGE_KEY = 'ai_payment_tenant_id';



    /**
     * The name for accessing config
     */
    private const CONFIG_NAME = 'ai-payment.tenancy';


    /**
     * Get the tenant configuration
     *
     * @return array
     */
    public static function getConfig()
    {
        return config(static::CONFIG_NAME, []);
    }

    /**
     * Add schema column for tenant
     *
     * @param Blueprint $table
     * @return void
     */
    public static function addSchemaColumn(Blueprint $table)
    {
        $config = static::getConfig();
        if (true == $config['is_column_type_integer']) {
            $table->unsignedInteger($config['column_name'])->default($config['default']);
        } else {
            $table->string($config['column_name'])->default($config['default']);
        }
    }


    /**
     * Set the tenant id
     *
     * @param integer|string $tenant_id
     * @return void
     */
    public static function set($tenant_id)
    {

        //$_SESSION[static::TENANT_ID_STORAGE_KEY]=$tenant_id;
        //session()->put(static::TENANT_ID_STORAGE_KEY,$tenant_id);

        // We have to use app binding here because known of the commented code 
        // above using sessions is somehow not working.
        // NOTE: THE USE OF app() HERE ASSUMES THAT WE ARE RUNNING INSIDE A LARAVEL 
        // ENVIRONMENT. THEREFORE THIS PACKAGE IS A LARAVEL-ONLY PACKAGE. 
        app()->bind(static::TENANT_ID_STORAGE_KEY, function () use ($tenant_id) {
            return $tenant_id;
        });
    }

    /**
     * Get the current tenant id
     * @return integer|string
     */
    public static function get()
    {
        return static::getDefined() ?? static::getDefault();
    }

    /**
     * Get the user defined tenant; i.e the non-default value.
     *
     * @return string|int|null
     */
    public static function getDefined()
    {
        //return $_SESSION[static::TENANT_ID_STORAGE_KEY];
        //session()->get(static::TENANT_ID_STORAGE_KEY);

        // We have to use app binding here because known of the commented code 
        // above using sessions is somehow not working. 
        // NOTE: THE USE OF app() HERE ASSUMES THAT WE ARE RUNNING INSIDE A LARAVEL 
        // ENVIRONMENT. THEREFORE THIS PACKAGE IS A LARAVEL-ONLY PACKAGE. 

        $app = app();
        if ($app->has(static::TENANT_ID_STORAGE_KEY)) {
            return $app->make(static::TENANT_ID_STORAGE_KEY);
        }
        return null;
    }

    /**
     * Get column name for tenant
     *
     * @return string
     */
    public static function getColumnName()
    {
        return static::getConfig()['column_name'];
    }
    /**
     * Return the Eloquent global scope name.
     *
     * @return string
     */
    public static function globalScopeName()
    {
        return static::getConfig()['global_scope_name'];
    }

    /**
     * Check if multi tenancy is enabled
     *
     * @return boolean
     */
    public static function isMultiTenant()
    {
        return !!static::getConfig()['enable_multi_tenant'];
    }

    /**
     * Get the default tenant
     *
     * @return string|int|null
     */
    public static function getDefault()
    {
        return static::getConfig()['default'];
    }
}
