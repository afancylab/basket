<?php
declare(strict_types=1);
namespace Basket\Buyer\Product;

use DateTime, DateTimeZone, DateInterval;
use Illuminate\Support\Facades\DB;
use Misc\{Moment, Network};


class Subscription
{


  /**
   * add subscription
   * 
   * @param int $id_buyer
   * @param int $id_seller_subscription
   * 
   * @return int 0 if fail otherwise >0 which is the id of buyer subscription
   * 
   * @since   🌱 1.2.0
   * @version 🌴 1.2.0
   * @author  ✍ Muhammad Mahmudul Hasan Mithu
   */
  public static function add(int $id_buyer, int $id_seller_subscription): int
  {
    $seller_subscription = DB::table('basket_seller_product_subscriptions')->where('id', $id_seller_subscription)->get()[0] ?? false;
    if(
      $seller_subscription &&
      !DB::table('basket_buyer_product_subscriptions') // If the buyer does not have a pending or active subscription
        ->where('id_buyer', $id_buyer)
        ->where('id_seller', $seller_subscription->id_seller)
        ->where('subscription_key', $seller_subscription->subscription_key)
        ->where('status', 'pending')
        ->orWhere('status', 'active')
        ->exists()
    ){
      $datetime = Moment::datetime();

      // find out start and end datetime
      $duration = $seller_subscription->duration;
      $start_datetime = $datetime;
      $end_datetime = new DateTime($start_datetime, new DateTimeZone('UTC'));
      $end_datetime = $end_datetime->add(new DateInterval("PT{$duration}S"))->format('Y-m-d H:i:s');

      return
      DB::table('basket_buyer_product_subscriptions')
        ->insertGetId([
          'id_buyer'=>$id_buyer,
          'id_seller'=>$seller_subscription->id_seller,
          'subscription_key'=>$seller_subscription->subscription_key,
          'subscription_name'=>$seller_subscription->subscription_name,
          
          'price_initial'=>$seller_subscription->price_initial,
          'price_final'=>$seller_subscription->price_final,
          'currency'=>$seller_subscription->currency,
          
          'duration'=>$duration,
          'start_datetime'=>$start_datetime,
          'end_datetime'=>$end_datetime,
          
          'status'=>'pending',
          'created_at'=>$datetime,
          'updated_at'=>$datetime,
          'ip'=>json_encode(Network::ip())
        ]);
    }

    return 0;
  }


  /**
   * Check to see if a buyer has subscribed
   * 
   * @param int    $id_buyer
   * @param int    $id_seller
   * @param string $subscription_key
   * 
   * @return bool  true if subscribed otherwise false
   * 
   * @since   🌱 1.2.0
   * @version 🌴 1.2.0
   * @author  ✍ Muhammad Mahmudul Hasan Mithu
   */
  public static function subscribed( int $id_buyer, int $id_seller, string $subscription_key ): bool
  {
    $subscription_key = htmlspecialchars(trim($subscription_key));
    $buyer_subscription =
    DB::table('basket_buyer_product_subscriptions')
      ->where('id_buyer', $id_buyer)
      ->where('id_seller', $id_seller)
      ->where('subscription_key', $subscription_key)
      ->where('status', 'active')
      ->orderBy('id', 'desc')
      ->get()[0] ?? false;
    if($buyer_subscription){
      $datetime = Moment::datetime();
      if($datetime>=$buyer_subscription->start_datetime && $datetime<=$buyer_subscription->end_datetime){
        return true;
      }elseif($datetime>$buyer_subscription->end_datetime){
        DB::table('basket_buyer_product_subscriptions')
          ->where('id', $buyer_subscription->id)
          ->update([
            'status'=>'done',
            'updated_at'=>$datetime,
            'ip'=>json_encode(Network::ip())
          ]);
      }
    }

    return false;
  }


}
