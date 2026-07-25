<?php
$file = 'app/Services/VendeurService.php';
$content = file_get_contents($file);

// Replace Shop -> Restaurant
$content = str_replace('App\Models\Shop', 'App\Models\Restaurant', $content);
$content = preg_replace('/\bShop\b/', 'Restaurant', $content);
$content = preg_replace('/\bshop\b/', 'restaurant', $content);
$content = preg_replace('/\bshopId\b/', 'restaurantId', $content);
$content = preg_replace('/\bshopData\b/', 'restaurantData', $content);
$content = preg_replace('/\bshop_id\b/', 'restaurant_id', $content);

// Replace Product -> Dish
$content = str_replace('App\Models\Product', 'App\Models\Dish', $content);
$content = preg_replace('/\bProduct\b/', 'Dish', $content);
$content = preg_replace('/\bproduct\b/', 'dish', $content);
$content = preg_replace('/\bproductId\b/', 'dishId', $content);
$content = preg_replace('/\bproduct_id\b/', 'dish_id', $content);
$content = preg_replace('/\bproducts\b/', 'dishes', $content);

// Fix createShop method name back to what controller expects
$content = str_replace('createRestaurant(', 'createShop(', $content);

// Remove category methods
$pattern = '/\/\*\*\s*\*\s*Create a category.*?\s*public function deleteCategory.*?\}\s*(?=\/\*\*|\z)/s';
$content = preg_replace($pattern, '', $content);
$content = str_replace('use App\Models\Category;', '', $content);

// Fix the refuseOrder method stock issue since stock_qty is removed
$content = preg_replace('/if \(\$dish && \$dish->stock_qty !== null\) \{.*?\}/s', '', $content);
$content = preg_replace('/\/\/ Restock items.*?foreach \(\$order->items as \$item\) \{.*?\}[\s\n]*\}/s', '', $content);

file_put_contents($file, $content);
echo "Done";
