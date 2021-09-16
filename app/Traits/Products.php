<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

use App\Models\StoreProduct;

trait Products
{
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
        if (is_numeric($section)) {
            $sectionField = 'id';
            $sectionCompare = '=';
        }

        if ($sort === 0) {
            $sort = "position";
        }

        $query = StoreProduct::select('store_products.id as id', 'store_products.*');
 
        switch ($sort) {
            case "az":
                $query = $query->orderBy('name');
                break;
            case "za":
                $query = $query->orderBy('name', 'DESC');
                break;
            case "low":
                $query = $query->orderBy('price');
                break;
            case "high":
                $query = $query->orderBy('price', 'DESC');
                break;
            case "old":
                $query = $query->orderBy('release_date');
                break;
            case "new":
                $query = $query->orderBy('release_date', 'DESC');
                break;

            default:
                if ((isset($section) && ($section == "%" || $section == "all"))) {
                    $query = $query->orderBy('position')->orderBy('release_date', 'DESC');
               // } else {
                //    $query = $query->orderBy('store_products_section.position')->orderBy('release_date', 'DESC');
                }
                break;
        }

        $availableProducts = [];

        if ($section != '%' && strtoupper($section) != 'ALL')
        {
            $query->whereHas('sections', function($q) use ($sectionField, $sectionCompare, $section) {
                $q->where($sectionField, $sectionCompare, '%' . $section. '%');
                $q->orderBy('store_products_section.position')->orderBy('release_date', 'DESC');
            });
            /*$query->join('store_products_section', 'store_products_section.store_product_id', '=', 'store_products.id')
                  ->join('sections', 'store_products_section.section_id', '=', 'sections.id')
                  ->where('sections.' . $sectionField, $sectionCompare, '%' . $section. '%');*/
        } 

        if ($section == '%' || strtoupper($section) == 'ALL')
        {
            $query->leftJoin('sections', function($join) {
                $join->on('sections.id', '=', DB::raw(-1));
            });
        }

        $query->where('store_products.store_id', $this->storeId);
        $query->where('deleted', 0);
        $query->where('available', 1);

        if (isset($number) && isset($page) && $page != null)
        {
            $page = ($page-1)*$number;

            $availableProducts['pages'] =  ceil($query->count() / $number);

            $query->skip($page);
        }

        if (isset($number))
        {
            $query->take($number);
        }

        $products = $query->get();

        foreach($products as $product)
        {
            if ($product->launch_date != "0000-00-00 00:00:00" && !empty(Session::get('preview_mode'))) {
                if (strtotime($product->launch_date) > time()) {
                    continue;
                }
            }
            if ($product->remove_date != "0000-00-00 00:00:00") {
                if (strtotime($product->remove_date) < $product->date_time) {
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
                $productOutput['id'] = $product->id;
                $productOutput['artist'] = $product->artist->name;
                $productOutput['title'] = $product->title;
                $productOutput['description'] = $product->description;
                $productOutput['price'] = $product->price;
                $productOutput['format'] = $product->format;
                $productOutput['release_date'] = $product->release_date;

                $product->image = $product->image;
                $product->format = $product->format;
        
                $availableProducts[] = $productOutput;
            }
        }

        if (!empty($availableProducts)) {
            return $availableProducts;

        } else {
            return false;
        }
    }

    public function getGeocode()
    {
        //Return GB default for the purpose of the test
        return ['country' => 'GB'];
    }
}