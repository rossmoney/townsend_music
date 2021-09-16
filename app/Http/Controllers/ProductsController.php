<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

use App\Models\StoreProduct;
use App\store_products;

use Carbon\Carbon;

class ProductsController extends Controller
{
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

    public function getGeocode()
    {
        //Return GB default for the purpose of the test
        return ['country' => 'GB'];
    }

    public function sectionProducts(string $section, $number = null, $page = null, $sort = 0)
    {
        if (empty($this->storeId)) {
            return response()->json(['status' => 0, 'message'=> 'No store specified.']);
        }

        if (!is_numeric($number) || $number < 1) {
            $number = 8;
        }

        if (!is_numeric($page) || $page < 1) {
            $page = 1;
        }

        $sectionField = 'description';
        $sectionCompare = 'LIKE';
        $sectionValue = '%'. $section . '%';
        if (is_numeric($section)) {
            $sectionField = 'id';
            $sectionCompare = '=';
            $sectionValue = $section;
        }

        if ($sort === 0) {
            $sort = "position";
        }

        $storeProducts = StoreProduct::select('store_products.id as id', 'store_products.*');

        //$sort = "position"; //sort was stuck to position on old version not sure if intentional or not, so set sort to position, to test sort, remove
 
        switch ($sort) {
            case "az":
                $storeProducts->orderBy('name');
                break;
            case "za":
                $storeProducts->orderBy('name', 'DESC');
                break;
            case "low":
                $storeProducts->orderBy('price');
                break;
            case "high":
                $storeProducts->orderBy('price', 'DESC');
                break;
            case "old":
                $storeProducts->orderBy('release_date');
                break;
            case "new":
                $storeProducts->orderBy('release_date', 'DESC');
                break;
            case "position":
                if ((isset($section) && ($section == "%" || $section == "all"))) {
                    $storeProducts->orderBy('position')->orderBy('release_date', 'DESC');
                } else {
                    $storeProducts->orderBy('store_products_section.position')->orderBy('release_date', 'DESC');
                }
                break;
        }

        $availableProducts = [];

        if ($section != '%' && strtoupper($section) != 'ALL')
        {
            $storeProducts->join('store_products_section', 'store_products_section.store_product_id', '=', 'store_products.id')
                  ->join('sections', 'store_products_section.section_id', '=', 'sections.id')
                  ->where('sections.' . $sectionField, $sectionCompare, $sectionValue);
        } 

        if ($section == '%' || strtoupper($section) == 'ALL')
        {
            $storeProducts->leftJoin('sections', function($join) {
                $join->on('sections.id', '=', DB::raw(-1));
            });
        }

        $storeProducts->where('store_products.store_id', $this->storeId);
        $storeProducts->where('deleted', 0);
        $storeProducts->where('available', 1);

        $products = $storeProducts->paginate($number, ['image', 'id', 'artist', 'title', 'description', 'price', 'format', 'release_date'], 'page', $page);
        $availableProducts['pages'] = $products->lastPage();

        $now = Carbon::now()->timestamp;

        foreach($products as $product)
        {
            if ($product->launch_date != "0000-00-00 00:00:00" && !empty(Session::get('preview_mode'))) {
                if (Carbon::parse($product->launch_date)->timestamp > $now) {
                    continue;
                }
            }
            if ($product->remove_date != "0000-00-00 00:00:00") {
                if (Carbon::parse($product->remove_date)->timestamp < $now) {
                    $product->available = 0;
                }
            }

            //check territories
            if ($product->disabled_countries != '') {
                $countries = explode(',', $product->disabled_countries);
                $geocode = $this->getGeocode();
                $country_code = $geocode['country'];

                if (in_array($country_code, $countries)) {
                    $product->available = 0;
                }
            }

            if ($product->available == 1)
            {
                $productOutput['image'] = $product->image;
                $productOutput['id'] = $product->id;
                $productOutput['artist'] = $product->artist->name;
                $productOutput['title'] = $product->title;
                $productOutput['description'] = $product->description;
                $productOutput['price'] = $product->price;
                $productOutput['format'] = $product->format;
                $productOutput['release_date'] = $product->release_date;
        
                $availableProducts[] = $productOutput;
            }
        }

        if (!empty($availableProducts)) {
            return $availableProducts;

        } else {
            return false;
        }
    }

    public function index()
    {
        $page = request()->input('page');
        $perPage = request()->input('perPage');
        $sort = request()->input('sort') ?? 0;

        return response()->json($this->sectionProducts(section: '%', number : $perPage, page : $page, sort: $sort ));
    }

    public function bySection(string $section)
    {
        $page = request()->input('page');
        $perPage = request()->input('perPage');
        $sort = request()->input('sort') ?? 0;

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
