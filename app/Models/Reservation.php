<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Service;
use App\Models\User;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'date_reservation',
        'status',
        'service_id',
        'user_id'
    ];

    public function servicios()
    {
        return $this->belongsTo(Service::class,'service_id','id');
    }

    public function usuarios()
    {
        return $this->belongsTo(User::class,'user_id','id');
    }
}
