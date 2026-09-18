<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tipo extends Model
{
    /** @use HasFactory<\Database\Factories\TipoFactory> */
    use HasFactory;

      protected $fillable = [
        'nombre',
      ];
}
