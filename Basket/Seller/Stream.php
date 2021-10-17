<?php
declare(strict_types=1);
namespace Basket\Seller;

use Illuminate\Support\Facades\DB;
use Misc\{Moment, Network};


class Stream
{


  /**
   * add stream
   * 
   * |____________________________________________________________________________
   * @param int    $seller_id
   * @param string $unique_key
   * 
   * @param string $initial_price
   * @param string $final_price
   * @param string $currency
   * @param int    $duration    in second
   * _____________________________________________________________________________/
   * @return int   0 if fail otherwise >0 which is the id of stream product
   * 
   * @since   🌱 1.8.0
   * @version 🌴 1.8.0
   * @author  ✍ Muhammad Mahmudul Hasan Mithu
   */
  public static function add(
    int    $seller_id,
    string $unique_key,

    string $initial_price,
    string $final_price,
    string $currency,

    int    $duration
  ): int
  {
    $unique_key  = htmlspecialchars(trim($unique_key));

    $initial_price = htmlspecialchars(trim($initial_price));
    $final_price   = htmlspecialchars(trim($final_price));
    $currency      = htmlspecialchars(strtoupper(trim($currency)));

    if(
          $seller_id>0
      &&  $unique_key
      &&  !DB::table('basket_seller_streams') // Check if this stream product is unique or not
             ->where('seller_id', $seller_id)
             ->where('unique_key', $unique_key)
             ->exists()
      &&  is_numeric($initial_price)
      &&  is_numeric($final_price)
      &&  $currency
      &&  $duration>0
    ){
      $datetime = Moment::datetime();
      return DB::table('basket_seller_streams')->insertGetId([
        'seller_id'=>$seller_id,
        'unique_key'=>$unique_key,
        
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


}
