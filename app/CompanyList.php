<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CompanyList extends Model
{
    protected $fillable = array('cin', 'name', 'url');

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
