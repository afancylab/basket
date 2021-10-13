<?php
declare(strict_types=1);
namespace Basket\Seller;

use Illuminate\Support\Facades\DB;
use Misc\{Moment, Network};


class Subscription
{


  /**
   * add subscription
   * 
   * |____________________________________________________________________________
   * @param int    $id_seller
   * @param string $subscription_key    for unique subscription
   * 
   * @param string $price_initial
   * @param string $price_final
   * @param string $currency
   * @param int    $duration    in second
   * _____________________________________________________________________________/
   * @return int   0 if fail otherwise >0 which is the id of seller subscription
   * 
   * @since   🌱 1.0.0
   * @version 🌴 1.6.0
   * @author  ✍ Muhammad Mahmudul Hasan Mithu
   */
  public static function add(
    int    $id_seller,
    string $subscription_key,

    string $price_initial,
    string $price_final,
    string $currency,

    int    $duration
  ): int
  {
    $subscription_key  = htmlspecialchars(trim($subscription_key));

    $price_initial = htmlspecialchars(trim($price_initial));
    $price_final   = htmlspecialchars(trim($price_final));
    $currency      = htmlspecialchars(strtoupper(trim($currency)));

    if(
          $id_seller>0
      &&  $subscription_key
      &&  !DB::table('basket_seller_subscriptions') // Check if this subscription is unique or not
             ->where('id_seller', $id_seller)
             ->where('subscription_key', $subscription_key)
             ->exists()
      &&  is_numeric($price_initial)
      &&  is_numeric($price_final)
      &&  $currency
      &&  $duration>0
    ){
      $datetime = Moment::datetime();
      return DB::table('basket_seller_subscriptions')->insertGetId([
        'id_seller'=>$id_seller,
        'subscription_key'=>$subscription_key,
        
        'price_initial'=>$price_initial,
        'price_final'=>$price_final,
        'currency'=>$currency,

        'duration'=>$duration,
        'status'=>'active',
        'created_at'=>$datetime,
        'updated_at'=>$datetime,
        'ip'=>json_encode(Network::ip())
      ]);
    }

    return 0;
  }


  /**
   * get subscription id
   * 
   * @param int    $id_seller
   * @param string $subscription_key
   * 
   * @return int   0 if fail otherwise >0 which is the subscription id
   * 
   * @since   🌱 1.4.0
   * @version 🌴 1.6.0
   * @author  ✍ Muhammad Mahmudul Hasan Mithu
   */
  public static function get_subscription_id(int $id_seller, string $subscription_key): int
  {
    $subscription_key = htmlspecialchars(trim($subscription_key));

    return (int)
    DB::table('basket_seller_subscriptions')
      ->where('id_seller', $id_seller)
      ->where('subscription_key', $subscription_key)
      ->value('id') ?? 0;
  }


}
