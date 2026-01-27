<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paises extends Model
{
    use HasFactory;

    protected $connection = 'sim_staging';

    protected $table = 'SIM_GE_PAIS_RAW';
}
