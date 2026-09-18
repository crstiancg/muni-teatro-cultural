<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profesion extends Model
{
    /** @use HasFactory<\Database\Factories\ProfesionFactory> */
    use HasFactory;

    protected $table = 'profesiones';
    protected $fillable = ['nombre']; 
}
