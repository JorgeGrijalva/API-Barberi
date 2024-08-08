<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Models\Payment;
use App\Models\Transaction;
use App\Services\PaymentService\MercadoPagoService;
use Http;
use Illuminate\Http\Request;
use Log;

class MercadoPagoController extends PaymentBaseController
{
    public function __construct(private MercadoPagoService $service)
    {
        parent::__construct($service);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function paymentWebHook(Request $request): array
    {
        Log::error('mercado pago', [
            'all'   => $request->all(),
            'reAll' => request()->all(),
            'input' => @file_get_contents("php://input")
        ]);

        $id = $request->input('data.id');

        if (!$id) {
            return ['message' => 'empty', 'status' => false];
        }

        $payment = Payment::where('tag',Payment::TAG_MERCADO_PAGO)->first();
        $payload = $payment->paymentPayload?->payload;

        $headers = [
            'Authorization' => 'Bearer '. data_get($payload,'token')
        ];

        $response = Http::withHeaders($headers)->get("https://api.mercadopago.com/v1/payments/$id");

        if (!in_array($response->status(), [200, 2001])) {
            return ['message' => 'status not 200,2001', 'status' => false];
        }

        $token = $response->json('additional_info.items.0.id');

        $status = match ($response->json('status')) {
            'succeeded', 'successful', 'success', 'approved'                        => Transaction::STATUS_PAID,
            'failed', 'cancelled', 'reversed', 'chargeback', 'disputed', 'rejected' => Transaction::STATUS_CANCELED,
            default                                                                 => Transaction::STATUS_PROGRESS,
        };

        return $this->service->afterHook($token, $status);

    }

}
