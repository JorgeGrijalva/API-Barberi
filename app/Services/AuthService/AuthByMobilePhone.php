<?php
declare(strict_types=1);

namespace App\Services\AuthService;

use App\Helpers\ResponseError;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\CoreService;
use App\Services\SMSGatewayService\SMSBaseService;
use App\Services\UserServices\UserService;
use App\Services\UserServices\UserWalletService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Throwable;

class AuthByMobilePhone extends CoreService
{
    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return User::class;
    }

    /**
     * @param array $array
     * @return JsonResponse
     */
    public function authentication(array $array): JsonResponse
    {
        $phone = preg_replace('/\D/', '', data_get($array, 'phone'));

        $sms = (new SMSBaseService)->smsGateway($phone);

        if (!data_get($sms, 'status')) {

            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_400,
                'message' => data_get($sms, 'message', '')
            ]);

        }

        return $this->successResponse(__('errors.' . ResponseError::SUCCESS, locale: $this->language), [
            'verifyId'  => data_get($sms, 'verifyId'),
            'phone'     => data_get($sms, 'phone'),
            'message'   => data_get($sms, 'message', '')
        ]);
    }

    /**
     * @param array $array
     * @return JsonResponse
     */
    public function confirmOPTCode(array $array): JsonResponse
    {
        if (data_get($array, 'type') !== 'firebase') {

            $data = Cache::get('sms-' . data_get($array, 'verifyId'));

            if (empty($data)) {
                return $this->onErrorResponse([
                    'code'      => ResponseError::ERROR_404,
                    'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
                ]);
            }

            if (Carbon::parse(data_get($data, 'expiredAt')) < now()) {
                return $this->onErrorResponse([
                    'code'      => ResponseError::ERROR_203,
                    'message'   => __('errors.' . ResponseError::ERROR_203, locale: $this->language)
                ]);
            }

            if ((int)data_get($data, 'OTPCode') !== (int)data_get($array, 'verifyCode')) {
                return $this->onErrorResponse([
                    'code'      => ResponseError::ERROR_201,
                    'message'   => __('errors.' . ResponseError::ERROR_201, locale: $this->language)
                ]);
            }

            $user = $this->model()->where('phone', data_get($data, 'phone'))->first();

        } else {
            $phone = preg_replace('/\D/', '', (string)data_get($array, 'phone'));
            $data['phone']      = $phone;
            $data['email']      = data_get($array, 'email');
            $data['referral']   = data_get($array, 'referral');
            $data['firstname']  = data_get($array, 'firstname', $phone);
            $data['lastname']   = data_get($array, 'lastname');
            $data['password']   = data_get($array, 'password');
            $data['gender']     = data_get($array, 'gender', 'male');
        }

        if (empty($user)) {
            try {
                $phone = preg_replace('/\D/', '', (string)data_get($data, 'phone'));
                $user = $this->model()
                    ->updateOrCreate([
                        'phone'             => $phone
                    ], [
                        'phone'             => $phone,
                        'email'             => data_get($data, 'email'),
                        'referral'          => data_get($data, 'referral'),
                        'active'            => 1,
                        'phone_verified_at' => now(),
                        'firstname'         => data_get($data, 'firstname', $phone),
                        'lastname'          => data_get($data, 'lastname'),
                        'gender'            => data_get($data, 'gender'),
                        'password'          => bcrypt(data_get($data, 'password', 'password')),
                    ]);
            } catch (Throwable $e) {
                $this->error($e);
                return $this->onErrorResponse([
                    'code'    => ResponseError::ERROR_400,
                    'message' => $e->getMessage(),
                ]);
            }

            (new UserService)->notificationSync($user);

            $user->emailSubscription()->updateOrCreate([
                'user_id' => $user->id
            ], [
                'active' => true
            ]);
        }

        if (!$user->hasAnyRole(Role::query()->pluck('name')->toArray())) {
            $user->syncRoles('user');
        }

        if (empty($user->wallet?->uuid)) {
            $user = (new UserWalletService)->create($user);
        }

        $token = $user->createToken('api_token')->plainTextToken;

        Cache::forget('sms-' . data_get($array, 'verifyId'));

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), [
            'access_token'  => $token,
            'token_type'    => 'Bearer',
            'user'          => UserResource::make($user),
        ]);

    }

    /**
     * @param array $data
     * @return JsonResponse
     */
    public function forgetPasswordBefore(array $data): JsonResponse
    {
        $user = User::where('phone', data_get($data, 'phone'))->first();

        if (empty($user)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        return $this->successResponse(__('errors.' . ResponseError::ERROR_400, locale: $this->language));
    }

    /**
     * @param array $data
     * @return JsonResponse
     */
    public function forgetPasswordVerify(array $data): JsonResponse
    {
        $user = User::where('phone', str_replace('+', '', data_get($data, 'phone')))->first();

        if (empty($user)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $user->update([
            'password' => bcrypt(data_get($data, 'password')),
        ]);

        $token = $user->createToken('api_token')->plainTextToken;

        return $this->successResponse('User successfully login', [
            'access_token'  => $token,
            'user'          => UserResource::make($user),
        ]);
    }

}
