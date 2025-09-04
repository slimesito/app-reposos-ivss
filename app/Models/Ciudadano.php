<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ciudadano extends Model
{
    protected $connection = 'oracle2';
    protected $table = 'SIRA.CIUDADANO';
    public $timestamps = false;

    protected $fillable = [
        'id_ciudadano',
        'primer_nombre',
        'segundo_nombre',
        'primer_apellido',
        'segundo_apellido',
        'sexo',
        'rif',
        'condicion_zurdo',
        'id_ocupacion',
        'fecha_nacimiento',
        'id_estado_civil',
        'id_catastro',
        'telefono_hab',
        'telefono_movil',
        'email_principal',
        'email_alterno',
        'fecha_fallecimiento',
        'estatus_afiliado',
        'cedula_extranjero',
        'id_nacionalidad',
    ];

}
