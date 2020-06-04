<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ImprovementOpportunities extends Model
{
    protected $table = 'OportunidadesMejora';

    public function Plans(){
        return $this->belongsTo('App\Plans', 'id_plan');
    }

    public function Actions(){
       return $this->hasMany('App\Actions', 'id_oportunidad', 'id');
    }
    
    protected $fillable = [
        'id_plan', 'oportunidad_mejora', 'hallazgo', 'analisis', 'riesgo',
        'mesa', 'proceso', 'nom_indicador', 'for_indicador', 'meta', 'cum_pri_linea',
        'cum_seg_linea', 'cum_ter_linea', 'cum_indicador', 'numerador',
        'denominador', 'estado',
    ];
}
