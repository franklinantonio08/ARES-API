<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Loggable;

class Infractor extends Model
{
    use HasFactory, Loggable;

    protected $connection = 'atlas'; 

    protected $table = 'infractor';

    protected $fillable = [
        'estatus',
        'verificadorId',
        // otros campos que uses
    ];
}
