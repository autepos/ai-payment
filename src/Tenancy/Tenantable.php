<?php
namespace Autepos\AiPayment\Tenancy;

use Autepos\AiPayment\Tenancy\Tenant;
use Illuminate\Database\Eloquent\Builder;
/**
 * This trait should be placed in any model whose table holds rows of 
 * data that belongs to tenants). 
 * The table/model must relate to the tenant through the tenant id.
 */
trait Tenantable{

    /**
     * Method to be Auto booted by Eloquent
     *
     * @return void
     */
    public static function bootTenantable() {
        
        
        static::creating(function ($model) {
            if (Tenant::isMultiTenant()) {
                $tenant_column=Tenant::getColumnName();
                $model->$tenant_column = Tenant::get();
            }
        });

        if (Tenant::isMultiTenant()) {
            static::addGlobalScope(Tenant::globalScopeName(), function (Builder $builder){
                return $builder->where(Tenant::getColumnName(), Tenant::get());
            });
        }
        
    }

    
}