<?php
declare(strict_types=1);
namespace Basket\Buyer;

use Illuminate\Support\Facades\DB;
use Misc\{Moment, Network};
use Sin\Balance;


class Stream
{


  /**
   * add stream
   * 
   * @param int $buyer_id
   * @param int $seller_stream_id
   * 
   * @return int 0 if fail otherwise >0 which is the buyer stream id
   * 
   * @since   🌱 1.8.0
   * @version 🌴 1.8.0
   * @author  ✍ Muhammad Mahmudul Hasan Mithu
   */
  public static function add(int $buyer_id, int $seller_stream_id): int
  {
    $product = DB::table('basket_seller_streams')->where('id', $seller_stream_id)->get()[0] ?? false;
    if($product){
      $currency = $product->currency;
      $price    = $product->final_price;
      $balance  = Balance::amount($buyer_id, $currency);
      if(bccomp($balance, $price, 18)>-1){
        Balance::change($buyer_id, bcmul($price, '-1', 18), $currency);
        Balance::change($product->seller_id, $price, $currency);

        // save data in db and return the id
        return
        DB::table('basket_buyer_streams')
          ->insertGetId([
            'buyer_id'=>$buyer_id,
            'seller_stream_id'=>$seller_stream_id,
            
            'initial_price'=>$product->initial_price,
            'final_price'=>$price,
            'currency'=>$currency,
            
            'duration'=>$product->duration,
            'created_at'=>Moment::datetime(),
            'ip'=>json_encode(Network::ip())
          ]);
      }
    }

    return 0;
  }


}
