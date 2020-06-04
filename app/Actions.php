<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Actions extends Model
{
    protected $table = 'Acciones';
    
    public function ImprovementOpportunities(){
        return $this->belongsTo('App\ImprovementOpportunities', 'id_oportunidad');
    }
    
    protected $fillable = [
        'id_oportunidad', 'tipo_accion', 'accion', 'fecha_ini_accion',
        'fecha_fin_accion', 'estado', 'observacion_cum', 'estado_seg_linea',
        'observacion_seg_linea', 'soporte', 'soporte2', 'soporte3', 'soporte4',
        'soporte5',
    ];
}
