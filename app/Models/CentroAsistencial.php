<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CentroAsistencial extends Model
{
    use HasFactory;

    protected $connection = 'oracle';
    protected $table = 'CENTROS_ASISTENCIALES';
    protected $primaryKey = 'id';
    public $incrementing = false; // Importante para IDs manuales
    public $timestamps = false; // Desactiva timestamps automáticos

    protected $fillable = [
        'cod_centro',
        'nombre',
        'cod_estado',
        'es_hospital',
        'cod_tipo',
        'nro_reposo_1473',
        'rango_ip',
        'activo',
        'id_create',
        'fecha_create',
        'id_update',
        'fecha_update',
        'centro_asistencial_id'
    ];

    protected $casts = [
        'fecha_create' => 'datetime:Y-m-d H:i:s',
        'fecha_update' => 'datetime:Y-m-d H:i:s'
    ];

    public function creador()
    {
        return $this->belongsTo(User::class, 'id_create', 'id');
    }

    public function modificador()
    {
        return $this->belongsTo(User::class, 'id_update', 'id');
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class, 'cod_estado', 'id');
    }

    // Relación con el modelo Reposo
    public function reposos()
    {
        return $this->hasMany(Reposo::class, 'id_cent_asist', 'id');
    }
}
