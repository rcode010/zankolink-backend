<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentOfferingQuotas extends Model {
    protected $fillable = ['department_offering_id','locality_type','capacity'];

    public function departmentOffering(): BelongsTo {
        return $this->belongsTo(DepartmentOffering::class);
    }
}
