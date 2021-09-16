<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

use App\Models\StoreProduct;
use App\Traits\Products;

class ProductsController extends Controller
{
    use Products;

    public $storeId;
    
    public function __construct()
    {
        /* As the system manages multiple stores a storeBuilder instance would
        normally be passed here with a store object. The id of the example 
        store is being set here for the purpose of the test */
        $this->storeId = 3;

        Session::put('currency', 'EUR');
    }

    public function index()
    {
        $page = request()->input('page');
        $perPage = request()->input('perPage');
        $sort = request()->input('sort');

        return response()->json($this->sectionProducts(section: 'all', number : $perPage, page : $page, sort: $sort ));
    }

    public function bySection(string $section)
    {
        $page = request()->input('page');
        $perPage = request()->input('perPage');
        $sort = request()->input('sort');

        return response()->json($this->sectionProducts(section: $section, number : $perPage, page : $page, sort: $sort ));
    }

}
