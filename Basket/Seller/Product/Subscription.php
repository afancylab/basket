<?php
namespace Basket\Seller\Product;

use Illuminate\Support\Facades\DB;
use Misc\Network;
use Misc\Moment;


class Subscription
{


  /**
   * add subscription
   * 
   * @param int    $id_seller
   * @param string $category
   * @param string $subscription_key    for unique subscription
   * @param string $subscription_name
   * 
   * @param string $price_initial
   * @param string $price_final
   * @param string $currency
   * 
   * @param int    $duration    in second
   * @param bool   $is_duration_mutable - clarify that duration is mutable or not for buyer
   * 
   * @return bool   true if successful otherwise false
   * 
   * @since   🌱 1.0.0
   * @version 🌴 1.2.0
   * @author  ✍ Muhammad Mahmudul Hasan Mithu
   */
  public static function add(
    int    $id_seller,
    string $category,
    string $subscription_key=null,
    string $subscription_name=null,

    string $price_initial,
    string $price_final,
    string $currency,

    int    $duration,
    bool   $is_duration_mutable
  ):bool
  {
    $category          = htmlspecialchars(trim($category));
    $subscription_key  = $subscription_key  ? htmlspecialchars(trim($subscription_key))  : null;
    $subscription_name = $subscription_name ? htmlspecialchars(trim($subscription_name)) : null;

    $price_initial = htmlspecialchars(trim($price_initial));
    $price_final   = htmlspecialchars(trim($price_final));
    $currency = strtoupper(htmlspecialchars(trim($currency)));


    // check unique subscription in db exists or not
    if(
      $subscription_key &&
      DB::table('basket_seller_product_subscriptions')    // is exist ?
         ->where('id_seller', $id_seller)
         ->where('category', $category)
         ->where('subscription_key', $subscription_key)
         ->exists()
    )
    return false;


    if(
          $id_seller>0
      &&  $category
      &&  is_numeric($price_initial)
      &&  is_numeric($price_final)
      &&  $currency
      &&  $duration>0
    ){
      $datetime = Moment::datetime();
      DB::table('basket_seller_product_subscriptions')->insert(
        [
          'id_seller'=>$id_seller,
          'category'=>$category,
          'subscription_key'=>$subscription_key,
          'subscription_name'=>$subscription_name,
          
          'price_initial'=>$price_initial,
          'price_final'=>$price_final,
          'currency'=>$currency,

          'duration'=>$duration,
          'is_duration_mutable'=>$is_duration_mutable,
          'status'=>'active',
          'created_at'=>$datetime,
          'updated_at'=>$datetime,
          'ip'=>json_encode(Network::ip())
        ]
      );
      return true;
    }


    return false;
  }


}
