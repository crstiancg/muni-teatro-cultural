<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevisionEvento extends Model
{
    protected $table = 'revisiones_evento';

    protected $fillable = ['evento_id', 'accion', 'observacion', 'user_id'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
