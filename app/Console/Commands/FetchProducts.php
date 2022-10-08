<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;

use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Models\Product;
use App\Models\ProductVariation;

class FetchProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches products from remote server';

	/**
     * Fetched products.
     *
     * @var array
     */
	private $fetched_products;

		/**
     * Fetched products.
     *
     * @var int
     */
	private $max_num_pages;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
		$fetched_products = array();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */

    public function handle()
    {
		$initial_request_url = env('REMOTE_API_URL') . '/products';
		$initial_request = Http::get($initial_request_url);

		if( ! $initial_request->ok() ) {
			return 1;
		}

		$this->max_num_pages = $initial_request->json('lastPage');
		$fetched_products = $initial_request->json('data');

		if( $this->max_num_pages > 1 ) {
			$all_responses = Http::pool( function ($pool) {
				$returned_pool = array();

				for( $i = 2; $i<= $this->max_num_pages; $i++ ) {
					$returned_pool[] = $pool->get(env('REMOTE_API_URL') . '/products?page=' . $i);
				}

				return $returned_pool;
			});
			}

		foreach($all_responses as $response) {
			$fetched_products = array_merge( $fetched_products, $response->json('data') );
		}

		if( empty( $fetched_products ) ) {
			return 2;
		}

		$product_groups = array_chunk( $fetched_products, 3 );

		foreach( $product_groups as $group ) {
			$main_product = array_shift( $group );
			$main_product_obj = new Product();

			try{
				$main_product_obj = Product::where('foreign_id', $main_product['_id'])->firstOrFail();
			} catch(ModelNotFoundException $e) {
				$main_product_obj = new Product();
				$main_product_obj->name = $main_product['product_name'];
				$main_product_obj->foreign_id = $main_product['_id'];
				$main_product_obj->image = $main_product['product_image_lg'];
				$main_product_obj->product_department = $main_product['product_department'];
				$main_product_obj->save();
			}


			foreach( $group as $sub_item ) {
				try{
					$sub_product = ProductVariation::where('foreign_id', $sub_item['_id'])->firstOrFail();
				} catch(ModelNotFoundException $e) {
					$sub_product = new ProductVariation();
					$sub_product->foreign_id = $sub_item['_id'];
					$sub_product->main_id = $main_product_obj->id;
					$sub_product->color = $sub_item['product_color'];
					$sub_product->material = $sub_item['product_material'];
					$sub_product->save();
				}
			}
		}

        return 0;
    }
}
