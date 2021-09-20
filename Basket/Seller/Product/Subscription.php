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
   * @param string $subscription_key
   * @param string $subscription_name
   * 
   * @param string $price_initial
   * @param string $price_final
   * @param string $currency_unit
   * 
   * @param int    $duration
   * @param bool   $is_duration_mutable - clarify that duration is mutable or not for buyer
   * 
   * @return void
   * 
   * @since   🌱 1.0.0
   * @version 🌴 1.0.0
   * @author  ✍ Muhammad Mahmudul Hasan Mithu
   */
  public static function add(
    int    $id_seller,
    string $subscription_key,
    string $subscription_name=NULL,

    string $price_initial,
    string $price_final,
    string $currency_unit,

    int    $duration,
    bool   $is_duration_mutable
  ):void
  {
    $price_initial = htmlspecialchars(trim($price_initial));
    $price_final   = htmlspecialchars(trim($price_final));

    if(is_numeric($price_initial) && is_numeric($price_final)):
      DB::table('Basket_Seller_Product_Subscriptions')->insert(
        [
          'id_seller'=>$id_seller,
          'subscription_key'=>htmlspecialchars(trim($subscription_key)),
          'subscription_name'=> ($subscription_name) ? htmlspecialchars(trim($subscription_name)) : NULL,
          
          'price_initial'=>$price_initial,
          'price_final'=>$price_final,
          'currency_unit'=>strtoupper($currency_unit),

          'duration'=>$duration,
          'is_duration_mutable'=>$is_duration_mutable,
          'status'=>'active',
          'ip'=>json_encode(Network::ip()),
          'datetime'=>Moment::datetime()
        ]
      );
    endif;
  }


}
