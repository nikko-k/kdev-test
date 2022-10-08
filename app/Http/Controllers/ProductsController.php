<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductsController extends Controller
{
	/**
     * Get all products.
	 * @param  Request  $request
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function __invoke(Request $request) {
		if( isset( $request['page'] ) ) {
			return Product::paginate(10);
		}

		return Product::all();
	}
	/**
	 * Get Product Categories
	 *
	 * @return void
	 */
	public function getcats() {
		return Product::select('product_department')
			->distinct()
			->get()
			->pluck('product_department');
	}

	/**
	 * Gets variations of product with passedId
	 *
	 * @param [type] $id
	 * @return void
	 */
	public function getVariations($id) {
		return Product::find($id)
			->variations();
	}
}
