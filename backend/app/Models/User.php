<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasApiTokens, HasRoles, Notifiable;

    protected $guard_name = 'api';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferencias' => 'array',
        ];
    }

    // Tema del panel (config/panel.php). Siempre devuelve las tres claves; un valor
    // guardado que ya no está entre las opciones curadas vuelve al de por defecto.
    public function preferenciasPanel(): array
    {
        $defecto = config('panel.tema_por_defecto');
        $opciones = [
            'acento' => config('panel.temas.acentos'),
            'neutro' => config('panel.temas.neutros'),
            'modo' => config('panel.temas.modos'),
        ];
        $guardadas = $this->preferencias ?? [];

        return collect($defecto)
            ->map(fn ($valor, $clave) => in_array($guardadas[$clave] ?? null, $opciones[$clave], true) ? $guardadas[$clave] : $valor)
            ->all();
    }

    public function persona()
    {
        return $this->hasOne(Persona::class);
    }

    // ---------- usuarios ocultos (config/acceso.php) ----------

    public static function esOculto(?int $id): bool
    {
        return $id !== null && in_array($id, config('acceso.usuarios_ocultos'), true);
    }

    // quien no es oculto no ve a los ocultos
    public function scopeVisiblesPara(Builder $query, ?User $quien): void
    {
        if (! static::esOculto($quien?->id)) {
            $query->whereNotIn('users.id', config('acceso.usuarios_ocultos'));
        }
    }

    // {usuario} en una ruta: un oculto "no existe" (404) para los demás. Va acá y no
    // en el controller para que también aplique antes de validar (precognition)
    public function resolveRouteBinding($value, $field = null)
    {
        $usuario = parent::resolveRouteBinding($value, $field);

        if ($usuario && static::esOculto($usuario->id) && ! static::esOculto(auth()->id())) {
            return null;
        }

        return $usuario;
    }
}
