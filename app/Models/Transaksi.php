<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
       /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transaksis';
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $guarded = [];

    public function setSelisihAttribute($value)
    {
        $this->attributes['selisih'] = $value;

        // Isi otomatis status berdasarkan logika
        $this->attributes['status'] = $value < 10000000 ? 'warning' : 'good';
    }
    
}
