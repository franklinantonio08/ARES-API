<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarnetInfo extends Model
{
    use HasFactory;

    protected $connection = 'datamind';

    protected $table = 'carnet_info';
}
