<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Splitter_box extends Model
{
    use HasFactory;
    protected $table = 'splitterboxes';
    protected $primaryKey = 'splitter_B';

    protected $fillable = [
        'splitter_B', 
        'latitude', 
        'longitude'
    ];
}
