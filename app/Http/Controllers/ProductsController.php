<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

use App\Traits\Products;

use App\store_products;

class ProductsController extends Controller
{
    use Products;

    public $products;
    public $storeId;
    
    public function __construct()
    {
        /* As the system manages multiple stores a storeBuilder instance would
        normally be passed here with a store object. The id of the example 
        store is being set here for the purpose of the test */
        $this->storeId = 3;

        //Session::put('currency', 'EUR');

        $this->products = new store_products();
    }

    public function index()
    {
        $page = request()->input('page');
        $perPage = request()->input('perPage');
        $sort = request()->input('sort');

        return response()->json($this->sectionProducts(section: '%', number : $perPage, page : $page, sort: $sort ));
    }

    public function bySection(string $section)
    {
        $page = request()->input('page');
        $perPage = request()->input('perPage');
        $sort = request()->input('sort');

        return response()->json($this->sectionProducts(section: $section, number : $perPage, page : $page, sort: $sort ));
    }
    
    public function original()
    {
        $page = request()->input('page');
        $perPage = request()->input('perPage');
        $sort = request()->input('sort');
  
        return response()->json($this->products->sectionProducts(3, '%', $perPage, $page, $sort));
    }

    public function originalBySection(string $section)
    {
        $page = request()->input('page');
        $perPage = request()->input('perPage');
        $sort = request()->input('sort');

        return response()->json($this->products->sectionProducts(3, $section, $perPage, $page, $sort));
    }
}
