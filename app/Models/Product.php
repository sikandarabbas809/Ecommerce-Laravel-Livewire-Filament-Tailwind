<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['category_id','brand_id','name','slug','images','description',
                            'price','is_active','is_featured','on_sale','in_stock'];
    protected $casts = [
        'images' => 'array'
    ];

    public function brand(){
        return $this->belongsTo(Brand::class);
    }
    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function orderItem(){
        return $this->hasMany(OrderItem::class);
    }
}
