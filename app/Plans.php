<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Plans extends Model
{
   protected $table = 'PlanesMejora';
   
   public function ImprovementOpportunities(){
       return $this->hasMany('App\ImprovementOpportunities', 'id_plan', 'id');
   }
   
   protected $fillable = [
        'nom_plan', 'fecha_ini', 'fecha_fin', 'fuente', 'responsable',
   ];
}
