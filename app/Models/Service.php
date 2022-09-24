<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Reservation;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'user_id'
    ];

    public function reservas()
    {
        return $this->hasMany(Reservation::class);
    }
}
