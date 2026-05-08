<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subweb extends Model
{
    protected $table = 'v2_subweb';
    protected $dateFormat = 'U';

    protected $fillable = [
        'domain',
        'owner_email',
        'status',
    ];

    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'payment' => 'array',
    ];
}
