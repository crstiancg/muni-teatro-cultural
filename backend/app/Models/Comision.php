<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comision extends Model
{
    protected $fillable = ['codigo', 'tipo', 'cod_grupo', 'cod_familia', 'nombre'];
}
