<?php
declare(strict_types=1);
namespace Basket\Buyer;

use DateTime, DateTimeZone, DateInterval;
use Illuminate\Support\Facades\DB;
use Misc\{Moment, Network};


class Subscription
{


  /**
   * add subscription
   * 
   * @param int $buyer_id
   * @param int $seller_subscription_id
   * @param int $term  default is 1
   * 
   * @return int 0 if fail otherwise >0 which is the id of buyer subscription
   * 
   * @since   🌱 1.2.0
   * @version 🌴 1.6.0
   * @author  ✍ Muhammad Mahmudul Hasan Mithu
   */
  public static function add(int $buyer_id, int $seller_subscription_id, int $term=1): int
  {
    $seller_subscription = DB::table('basket_seller_subscriptions')->where('id', $seller_subscription_id)->get()[0] ?? false;
    if(
      $seller_subscription &&
      $term>0 &&
      !DB::table('basket_buyer_subscriptions')  // If the buyer does not have a pending or active subscription
        ->where([
          ['buyer_id', '=', $buyer_id],
          ['seller_subscription_id', '=', $seller_subscription_id],
        ])
        ->where(function($query){
          $query->orWhere('status', '=', 'pending')
                ->orWhere('status', '=', 'active');
        })
        ->exists()
    ){
      // find out validity period and current datetime
      $datetime = Moment::datetime();
      $duration = $seller_subscription->duration*$term;
      $valid_from = $datetime;
      $valid_upto = new DateTime($valid_from, new DateTimeZone('UTC'));
      $valid_upto = $valid_upto->add(new DateInterval("PT{$duration}S"))->format('Y-m-d H:i:s');

      // solve price
      $initial_price = bcmul($seller_subscription->initial_price, (string) $term, 18);
      $final_price   = bcmul($seller_subscription->final_price,   (string) $term, 18);

      // save data in db and return the id
      return
      DB::table('basket_buyer_subscriptions')
        ->insertGetId([
          'buyer_id'=>$buyer_id,
          'seller_subscription_id'=>$seller_subscription_id,
          'term'=>$term,
          
          'initial_price'=>$initial_price,
          'final_price'=>$final_price,
          'currency'=>$seller_subscription->currency,
          
          'duration'=>$duration,
          'valid_from'=>$valid_from,
          'valid_upto'=>$valid_upto,
          
          'status'=>'pending',
          'created_at'=>$datetime,
          'updated_at'=>$datetime,
          'ip'=>json_encode(Network::ip())
        ]);
    }

    return 0;
  }


  /**
   * update term, price, validity period of a pending subscription
   * validity period is based on current time
   * 
   * @param int    $subscription_id
   * @param int    $term  default is null aka current term
   * 
   * @return bool  true if successful otherwise false
   * 
   * @since   🌱 1.3.0
   * @version 🌴 1.6.0
   * @author  ✍ Muhammad Mahmudul Hasan Mithu
   */
  public static function update_term(int $subscription_id, int $term=null): bool
  {
    // if the pending subscription exists then collect the proper data
    if(
      $subscription =
      DB::table('basket_buyer_subscriptions')
        ->join('basket_seller_subscriptions', 'basket_buyer_subscriptions.seller_subscription_id', '=', 'basket_seller_subscriptions.id')
        ->where('basket_buyer_subscriptions.id', $subscription_id)
        ->where('basket_buyer_subscriptions.status', 'pending')
        ->addSelect(['basket_buyer_subscriptions.term'])
        ->addSelect([
          'basket_seller_subscriptions.initial_price',
          'basket_seller_subscriptions.final_price',
          'basket_seller_subscriptions.currency',
          'basket_seller_subscriptions.duration'
        ])
        ->get()[0] ?? false
    ){
      // set default term
      if($term<1) $term = $subscription->term;

      // find out validity period and current datetime
      $datetime = Moment::datetime();
      $duration = $subscription->duration*$term;
      $valid_from = $datetime;
      $valid_upto = new DateTime($valid_from, new DateTimeZone('UTC'));
      $valid_upto = $valid_upto->add(new DateInterval("PT{$duration}S"))->format('Y-m-d H:i:s');

      // solve price
      $initial_price = bcmul($subscription->initial_price, (string) $term, 18);
      $final_price   = bcmul($subscription->final_price,   (string) $term, 18);

      // update the subscription
      DB::table('basket_buyer_subscriptions')
        ->where('id', $subscription_id)
        ->update([
          'term'=>$term,
            
          'initial_price'=>$initial_price,
          'final_price'=>$final_price,
          'currency'=>$subscription->currency,
          
          'duration'=>$duration,
          'valid_from'=>$valid_from,
          'valid_upto'=>$valid_upto,
          
          'updated_at'=>$datetime,
          'ip'=>json_encode(Network::ip())
        ]);
      return true;
    }

    return false;
  }


  /**
   * Check to see if a buyer has subscribed
   * 
   * @param int    $buyer_id
   * @param int    $seller_id
   * @param string $subscription_key
   * 
   * @return bool  true if subscribed otherwise false
   * 
   * @since   🌱 1.2.0
   * @version 🌴 1.6.0
   * @author  ✍ Muhammad Mahmudul Hasan Mithu
   */
  public static function subscribed( int $buyer_id, int $seller_id, string $subscription_key ): bool
  {
    $subscription_key = htmlspecialchars(trim($subscription_key));
    $buyer_subscription =
    DB::table('basket_buyer_subscriptions')
      ->join('basket_seller_subscriptions', 'basket_buyer_subscriptions.seller_subscription_id', '=', 'basket_seller_subscriptions.id')
      ->where('basket_buyer_subscriptions.buyer_id', $buyer_id)
      ->where('basket_buyer_subscriptions.status', 'active')
      ->where('basket_seller_subscriptions.seller_id', $seller_id)
      ->where('basket_seller_subscriptions.subscription_key', $subscription_key)
      ->orderBy('basket_buyer_subscriptions.id', 'desc')
      ->addSelect([
        'basket_buyer_subscriptions.id',
        'basket_buyer_subscriptions.valid_from',
        'basket_buyer_subscriptions.valid_upto',
        'basket_buyer_subscriptions.valid_upto',
      ])
      ->get()[0] ?? false;
    if($buyer_subscription){
      $datetime = Moment::datetime();
      if($datetime>=$buyer_subscription->valid_from && $datetime<=$buyer_subscription->valid_upto){
        return true;
      }elseif($datetime>$buyer_subscription->valid_upto){
        DB::table('basket_buyer_subscriptions')
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
