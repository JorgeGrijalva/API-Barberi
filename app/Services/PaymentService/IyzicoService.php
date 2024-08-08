<?php

namespace App\Services\PaymentService;

use App\Models\Payment;
use App\Models\PaymentPayload;
use App\Models\PaymentProcess;
use App\Models\Payout;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Iyzipay\Model\Address;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\Buyer;
use Iyzipay\Model\Currency;
use Iyzipay\Model\Locale;
use Iyzipay\Model\PaymentGroup;
use Iyzipay\Model\PayWithIyzicoInitialize;
use Iyzipay\Model\SubMerchant;
use Iyzipay\Model\SubMerchantType;
use Iyzipay\Options;
use Iyzipay\Request\CreatePayWithIyzicoInitializeRequest;
use Iyzipay\Request\CreateSubMerchantRequest;
use Str;

class IyzicoService extends BaseService
{
    protected function getModelClass(): string
    {
        return Payout::class;
    }

	/**
	 * @param array $data
	 * @return PaymentProcess|Model
	 * @throws Exception
	 */
    public function processTransaction(array $data): Model|PaymentProcess
    {
        $payment        = Payment::where('tag', Payment::TAG_IYZICO)->first();
        $paymentPayload = PaymentPayload::where('payment_id', $payment?->id)->first();
        $payload        = $paymentPayload?->payload;

        $host = request()->getSchemeAndHttpHost();

        [$key, $before] = $this->getPayload($data, $payload);

        $modelId    = data_get($before, 'model_id');
        $totalPrice = ceil(data_get($before, 'total_price'));

        $url  = "$host/order-stripe-success?$key=$modelId&lang=$this->language";

		if (!in_array($this->language, [Locale::TR, Locale::EN])) {
			$this->language = Locale::TR;
		}

        $currency = Str::upper(data_get($before, 'currency'));

		$currencies = [
			Currency::TL,
			Currency::EUR,
			Currency::USD,
			Currency::GBP,
			Currency::IRR,
			Currency::NOK,
			Currency::RUB,
			Currency::CHF,
		];

		if (!in_array($currency, $currencies)) {
			throw new Exception("currency $currency is not supported");
		}

		$id = time();

		$options = new Options();
		$options->setApiKey(data_get($payload, 'api_key'));
		$options->setSecretKey(data_get($payload, 'secret_key'));

        $sandbox = (bool)data_get($payload, 'sandbox');
        $baseUrl = $sandbox ? 'https://sandbox-api.iyzipay.com' : 'https://api.iyzipay.com';

		$options->setBaseUrl($baseUrl);

        if (!data_get($payload, 'sub_merchant_key') && !$sandbox) {

            $request = new CreateSubMerchantRequest();
            $request->setLocale(Locale::TR);
            $request->setConversationId($id);
            $request->setSubMerchantExternalId(Str::uuid());
            $request->setSubMerchantType(SubMerchantType::PRIVATE_COMPANY);
            $request->setAddress('Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1');
            $request->setTaxOffice('Tax office');
            $request->setLegalCompanyTitle('John Doe inc');
            $request->setEmail('example@gmail.com');
            $request->setGsmNumber('+905350000000');
            $request->setName('John\'s market');
            $request->setIban('TR180006200119000006672315');
            $request->setIdentityNumber('31300864726');
            $request->setCurrency(Currency::TL);
            $subMerchant = SubMerchant::create($request, $options);

            $payload['sub_merchant_key'] = $subMerchant->getSubMerchantKey();

            $paymentPayload->update($payload);
        }

		$request = new CreatePayWithIyzicoInitializeRequest();
		$request->setLocale($this->language);
		$request->setConversationId($id);
		$request->setPrice($totalPrice);
		$request->setPaidPrice($totalPrice);
		$request->setCurrency($currency);
		$request->setBasketId($modelId);
		$request->setPaymentGroup(PaymentGroup::PRODUCT);
		$request->setCallbackUrl($url);
		$request->setEnabledInstallments([1]);

		$buyer = new Buyer();
		$buyer->setId($modelId);
		$buyer->setName('User');
		$buyer->setSurname('Name');
		$buyer->setGsmNumber('+905350000000');
		$buyer->setEmail('example@gmail.com');
		$buyer->setIdentityNumber("Buyer-$modelId");
		$buyer->setLastLoginDate(date('Y-m-d H:i:s'));
		$buyer->setRegistrationDate(date('Y-m-d H:i:s'));
		$buyer->setRegistrationAddress('Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1');
		$buyer->setIp(request()->ip());
		$buyer->setCity('Istanbul');
		$buyer->setCountry('Turkey');
		$buyer->setZipCode('34732');
		$request->setBuyer($buyer);

		$shippingAddress = new Address();
		$shippingAddress->setContactName('User Name');
		$shippingAddress->setCity('Istanbul');
		$shippingAddress->setCountry('Turkey');
		$shippingAddress->setAddress('Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1');
		$shippingAddress->setZipCode('34742');
		$request->setShippingAddress($shippingAddress);

		$billingAddress = new Address();
		$billingAddress->setContactName('User Name');
		$billingAddress->setCity('Istanbul');
		$billingAddress->setCountry('Turkey');
		$billingAddress->setAddress('Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1');
		$billingAddress->setZipCode('34742');
		$request->setBillingAddress($billingAddress);

		$basketItems = [];

        $firstBasketItem = new BasketItem();
        $firstBasketItem->setId($modelId);
        $firstBasketItem->setName('product');
        $firstBasketItem->setCategory1('product');
        $firstBasketItem->setCategory2('product');
        $firstBasketItem->setItemType(BasketItemType::PHYSICAL);
        $firstBasketItem->setPrice($totalPrice);

        if (!$sandbox) {
            $firstBasketItem->setSubMerchantKey(data_get($payload, 'sub_merchant_key'));
        }

        $firstBasketItem->setSubMerchantPrice($totalPrice);

        $basketItems[] = $firstBasketItem;

		$request->setBasketItems($basketItems);

		$request = PayWithIyzicoInitialize::create($request, $options);

		if ($request->getErrorCode()) {
			throw new Exception($request->getErrorMessage());
		}

        return PaymentProcess::updateOrCreate([
            'user_id'    => auth('sanctum')->id(),
            'model_type' => data_get($before, 'model_type'),
            'model_id'   => data_get($before, 'model_id'),
        ], [
            'id' => $request->getToken(),
            'data' => [
                'url'        => $request->getPayWithIyzicoPageUrl(),
                'con_id'     => $request->getConversationId(),
                'price'      => $totalPrice,
                'payment_id' => $payment->id,
            ]
        ]);
    }

}
