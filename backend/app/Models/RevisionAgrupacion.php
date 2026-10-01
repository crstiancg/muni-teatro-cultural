<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevisionAgrupacion extends Model
{
    protected $table = 'revisiones_agrupacion';

    protected $fillable = ['agrupacion_id', 'accion', 'observacion', 'user_id'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
