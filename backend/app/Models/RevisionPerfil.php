<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevisionPerfil extends Model
{
    protected $table = 'revisiones_perfil';

    protected $fillable = ['persona_id', 'accion', 'observacion', 'user_id'];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
