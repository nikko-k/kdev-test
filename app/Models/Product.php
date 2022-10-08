<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

	protected $table = 'products';
	public $timestamps = false;
	/**
	 * variations
	 *
	 * @return PhoneVariation
	 */
	function variations() {
		return $this->hasMany(ProductVariation::class, 'main_id')->get();
	}
}
