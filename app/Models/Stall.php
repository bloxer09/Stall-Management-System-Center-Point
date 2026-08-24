<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stall extends Model
{
    use HasFactory;

    protected $fillable = [
        'stall_number',
        'location_id',
        'status',
        'price',
        'description',
        'image',
        'grid_row',
        'grid_col',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'grid_row' => 'integer',
        'grid_col' => 'integer',
    ];

    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
                return $this->image;
            }
            return asset('storage/' . $this->image);
        }

        return asset('images/default_stall.jpg');
    }

    public function location()
    {
        return $this->belongsTo(StallLocation::class, 'location_id');
    }

    public function mappings()
    {
        return $this->hasMany(StallMapping::class);
    }

    public function activeMapping()
    {
        return $this->hasOne(StallMapping::class)->where('is_active', true);
    }

    public function currentLease()
    {
        return $this->hasOne(StallMapping::class)->where('is_active', true);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Invoice::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
