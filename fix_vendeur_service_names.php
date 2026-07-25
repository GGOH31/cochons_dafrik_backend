<?php
$file = 'app/Services/VendeurService.php';
$content = file_get_contents($file);

$content = str_replace('App\Models\Shop', 'App\Models\Restaurant', $content);
$content = str_replace('App\Models\Product', 'App\Models\Dish', $content);
$content = str_replace('use App\Models\Category;', '', $content);
$content = preg_replace('/\bShop\b/', 'Restaurant', $content);
$content = preg_replace('/\bshop\b/', 'restaurant', $content);
$content = preg_replace('/\bshopId\b/', 'restaurantId', $content);
$content = preg_replace('/\bshopData\b/', 'restaurantData', $content);
$content = preg_replace('/\bshop_id\b/', 'restaurant_id', $content);
$content = preg_replace('/\bProduct\b/', 'Dish', $content);
$content = preg_replace('/\bproduct\b/', 'dish', $content);
$content = preg_replace('/\bproductId\b/', 'dishId', $content);
$content = preg_replace('/\bproduct_id\b/', 'dish_id', $content);
$content = preg_replace('/\bproducts\b/', 'dishes', $content);

$content = str_replace('createRestaurant(', 'createShop(', $content);

file_put_contents($file, $content);
echo "Done replacing names\n";
