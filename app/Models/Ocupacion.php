<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ocupacion extends Model
{
    use HasFactory;

    protected $connection = 'sim_staging';

    protected $table = 'SIM_GE_OCUPACION_RAW';
}
