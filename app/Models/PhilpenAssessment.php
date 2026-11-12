<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhilpenAssessment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'assessed_at' => 'date:Y-m-d',
            'hx_ha_stroke_ckd' => 'boolean',
            'family_history' => 'boolean',
            'second_hand_smoke' => 'boolean',
            'physically_active' => 'boolean',
            'fruit_veg_intake' => 'boolean',
            'high_fat_salt_sugar' => 'boolean',
            'needs_counselling' => 'boolean',
            'waist_cm' => 'float',
        ];
    }
}
