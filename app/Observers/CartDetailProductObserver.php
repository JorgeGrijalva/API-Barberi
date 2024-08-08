<?php
declare(strict_types=1);

namespace App\Observers;

use App\Models\CartDetailProduct;

class CartDetailProductObserver
{

    /**
     * Handle the Category "deleted" event.
     *
     * @param CartDetailProduct $cartDetailProduct
     * @return void
     */
    public function deleting(CartDetailProduct $cartDetailProduct): void
    {
        $cartDetailProduct->galleries()->delete();
    }

}
