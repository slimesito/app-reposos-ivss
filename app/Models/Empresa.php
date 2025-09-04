<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $connection = 'oracle2';
    protected $table = 'SIRA.EMPRESA';
    public $timestamps = false;

    protected $fillable = [
        'id_empresa',
        'nombre_empresa',
        'id_tipo_empresa',
        'rif',
        'nit',
        'id_riesgo',
        'id_sociedad',
        'id_actividad',
        'fecha_inscripcion',
        'id_catastro',
        'telefono1',
        'telefono2',
        'fax',
        'email_principal',
        'email_secundario',
        'id_estatus',
        'saldo_deuda',
        'saldo_convenio',
        'interes_deuda',
        'cantidad_empleado',
        'id_estado',
        'domicilio_completo',
        'id_zona_postal',
        'clasificacion_empresa',
        'empresa_sane',
        'indemnizacion_factura',
        'id_oficina_ivss',
        'fecha_constitucion',
        'fecha_inactivacion'
    ];
}
