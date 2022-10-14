<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Denuncias extends Model
{
    use HasFactory;


    protected $fillable = [
        'establecimiento',
        'provincia',
        'distrito',
        'corregimiento',
        'referencia',
        'categoria',
        'subcategoria',
        'lat',
        'denuncia_rel',
        'lon',
        'user',
        'img1',
        'img2',
        'img3'

    ];
}
