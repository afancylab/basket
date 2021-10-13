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
   * @param int    $seller_id
   * @param string $subscription_key    for unique subscription
   * 
   * @param string $initial_price
   * @param string $final_price
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
    int    $seller_id,
    string $subscription_key,

    string $initial_price,
    string $final_price,
    string $currency,

    int    $duration
  ): int
  {
    $subscription_key  = htmlspecialchars(trim($subscription_key));

    $initial_price = htmlspecialchars(trim($initial_price));
    $final_price   = htmlspecialchars(trim($final_price));
    $currency      = htmlspecialchars(strtoupper(trim($currency)));

    if(
          $seller_id>0
      &&  $subscription_key
      &&  !DB::table('basket_seller_subscriptions') // Check if this subscription is unique or not
             ->where('seller_id', $seller_id)
             ->where('subscription_key', $subscription_key)
             ->exists()
      &&  is_numeric($initial_price)
      &&  is_numeric($final_price)
      &&  $currency
      &&  $duration>0
    ){
      $datetime = Moment::datetime();
      return DB::table('basket_seller_subscriptions')->insertGetId([
        'seller_id'=>$seller_id,
        'subscription_key'=>$subscription_key,
        
        'initial_price'=>$initial_price,
        'final_price'=>$final_price,
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
   * @param int    $seller_id
   * @param string $subscription_key
   * 
   * @return int   0 if fail otherwise >0 which is the subscription id
   * 
   * @since   🌱 1.4.0
   * @version 🌴 1.6.0
   * @author  ✍ Muhammad Mahmudul Hasan Mithu
   */
  public static function get_subscription_id(int $seller_id, string $subscription_key): int
  {
    $subscription_key = htmlspecialchars(trim($subscription_key));

    return (int)
    DB::table('basket_seller_subscriptions')
      ->where('seller_id', $seller_id)
      ->where('subscription_key', $subscription_key)
      ->value('id') ?? 0;
  }


}
