<?php
declare(strict_types=1);
namespace Basket\Buyer;

use Illuminate\Support\Facades\DB;
use Misc\{Moment, Network};
use Sin\Balance;


class Cart
{


  /**
   * get all pending subscriptions
   * 
   * @param int $buyer_id
   * 
   * @return array
   * 
   * @since   🌱 1.7.0
   * @version 🌴 1.7.0
   * @author  ✍ Muhammad Mahmudul Hasan Mithu
   */
  public static function get_all(int $buyer_id): array
  {
    // update subscription term
    $subscription_ids =
    DB::table('basket_buyer_subscriptions')
      ->where('buyer_id', $buyer_id)
      ->where('status', 'pending')
      ->pluck('id');
    foreach($subscription_ids as $id) Subscription::update_term($id);

    // get all the pending subscription details
    if(count($subscription_ids)>0){
      $all_pending_subscriptions =
      DB::table('basket_buyer_subscriptions as bs')
        ->join('basket_seller_subscriptions as ss', 'bs.seller_subscription_id', '=', 'ss.id')
        ->where('bs.buyer_id', $buyer_id)
        ->where('bs.status', 'pending')
        ->addSelect(['bs.id', 'ss.seller_id', 'bs.seller_subscription_id', 'ss.subscription_key'])
        ->addSelect(['bs.term', 'bs.initial_price', 'bs.final_price', 'bs.currency', 'bs.duration', 'bs.valid_from', 'bs.valid_upto', 'bs.created_at', 'bs.updated_at'])
        ->get();
      return $all_pending_subscriptions->all();
    }

    return [];
  }


  /**
   * pay directly by balance
   * 
   * @param int $buyer_id
   * 
   * @return void
   * 
   * @since   🌱 1.7.0
   * @version 🌴 1.7.0
   * @author  ✍ Muhammad Mahmudul Hasan Mithu
   */
  public static function direct_pay(int $buyer_id): void
  {
    $all_products = self::get_all($buyer_id);
    foreach($all_products as $product){
      $currency = $product->currency;
      $price    = $product->final_price;
      $balance  = Balance::amount($buyer_id, $currency);
      if(bccomp($balance, $price, 18)>-1){
        Balance::change($buyer_id, bcmul($price, '-1', 18), $currency);
        Balance::change($product->seller_id, $price, $currency);
        DB::table('basket_buyer_subscriptions')
          ->where('id', $product->id)
          ->update([
            'status'=>'active',
            'updated_at'=>Moment::datetime(),
            'ip'=>json_encode(Network::ip())
          ]);
      }
    }
  }


}
