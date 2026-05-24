<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;
    
    protevted $fillable = [
        'name',
        'slug',
        'description'
    ];
    
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
