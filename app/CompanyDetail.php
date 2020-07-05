<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CompanyDetail extends Model
{
    protected $fillable = [
        'company_id', 'corporate_identification_number', 'registration_number', 'company_status', 'age',
        'registration_number', 'category', 'sub_category', 'company_class', 'roc_code', 'members_count', 'email', 'address',
        'is_listed', 'state', 'district', 'city', 'pin', 'section', 'division', 'main_group', 'main_class'
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
