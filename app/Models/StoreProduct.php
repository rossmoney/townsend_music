<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Illuminate\Support\Facades\Session;

class StoreProduct extends Model
{
    use HasFactory;

    public $table = 'store_products';

    public $imagesDomain;

    public function __construct()
    {
        $this->imagesDomain = config('app.images_domain');
    }

    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(
            Section::class,
            'store_products_section',
            'store_product_id',
            'section_id',
            'id',
            'id'
        )
            ->withPivot('position')
            ->orderBy('position', 'ASC');
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class, 'artist_id', 'id');
    }

    public function getTitleAttribute()
    {
        return strlen($this->display_name) > 3 ? $this->display_name : $this->name;
    }

    public function getFormatAttribute()
    {
        return $this->type;
    }

    public function getImageAttribute()
    {
        if (strlen($this->image_format) > 2) {
            return $this->imagesDomain."/$this->id.".$this->image_format;
        } else {
            return $this->imagesDomain."noimage.jpg";
        }
    }

    public function getPriceAttribute()
    {
        switch (Session::get('currency')) {
            case "USD":
                $price = $this->dollar_price;
                break;
            case "EUR":
                $price = $this->euro_price;
                break;
        }

        return $price;
    }

    public function getArtistAttribute()
    {
        return $this->artist()->first();
    }
}
