<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Impedimentos extends Model
{
    use HasFactory;

    protected $connection = 'datamind';

    protected $table = 'impedimentos_info';
}
