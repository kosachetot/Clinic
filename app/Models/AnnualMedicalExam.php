<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnualMedicalExam extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'exam_date' => 'date:Y-m-d',
            'xray_normal' => 'boolean',
            'glucose' => 'float',
            'hba1c' => 'float',
            'cholesterol' => 'float',
            'triglyceride' => 'float',
            'hdl' => 'float',
            'ldl' => 'float',
        ];
    }
}
