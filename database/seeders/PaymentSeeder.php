<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Traits\Loggable;
use Illuminate\Database\Seeder;
use Throwable;

class PaymentSeeder extends Seeder
{
    use Loggable;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        //input sort in ui
        $payments = [
            ['tag' => Payment::TAG_CASH,         'input' => 1],
            ['tag' => Payment::TAG_WALLET,       'input' => 2],
            ['tag' => Payment::TAG_ZAIN_CASH,    'input' => 3],
            ['tag' => Payment::TAG_PAY_TABS,     'input' => 4],
            ['tag' => Payment::TAG_FLUTTER_WAVE, 'input' => 5],
            ['tag' => Payment::TAG_PAY_STACK,    'input' => 6],
            ['tag' => Payment::TAG_MERCADO_PAGO, 'input' => 7],
            ['tag' => Payment::TAG_RAZOR_PAY,    'input' => 8],
            ['tag' => Payment::TAG_STRIPE,       'input' => 9],
            ['tag' => Payment::TAG_PAY_PAL,      'input' => 10],
            ['tag' => Payment::TAG_MOYA_SAR,     'input' => 11],
            ['tag' => Payment::TAG_MOLLIE,       'input' => 12],
            ['tag' => Payment::TAG_IYZICO,       'input' => 13],
            ['tag' => Payment::TAG_MAKSEKESKUS,  'input' => 14],
            ['tag' => Payment::TAG_ORANGE,       'input' => 15],
            ['tag' => Payment::TAG_MTN,          'input' => 16],
        ];

        foreach ($payments as $payment) {
            try {
                Payment::updateOrCreate([
                    'tag'   => data_get($payment, 'tag')
                ], [
                    'input' => data_get($payment, 'input')
                ]);
            } catch (Throwable $e) {
                $this->error($e);
            }
        }

    }

}
