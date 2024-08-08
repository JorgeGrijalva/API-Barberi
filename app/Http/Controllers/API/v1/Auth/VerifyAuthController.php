<?php
declare(strict_types=1);

namespace App\Http\Controllers\API\v1\Auth;

use App\Events\Mails\SendEmailVerification;
use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AfterVerifyRequest;
use App\Http\Requests\Auth\PhoneVerifyRequest;
use App\Http\Requests\Auth\ReSendVerifyRequest;
use App\Http\Resources\UserResource;
use App\Models\Notification;
use App\Models\NotificationUser;
use App\Models\PushNotification;
use App\Models\User;
use App\Services\AuthService\AuthByMobilePhone;
use App\Services\UserServices\UserService;
use App\Services\UserServices\UserWalletService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Throwable;

class VerifyAuthController extends Controller
{
    use ApiResponse, \App\Traits\Notification;

    public function verifyPhone(PhoneVerifyRequest $request): JsonResponse
    {
        try {
            if (!config('app.is_demo') && $request->input('type') === 'firebase') {
                Firebase::auth()->verifyIdToken($request->input('id'));
            }
        } catch (Throwable $e) {
            $this->error($e);
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_107,
                'message' => $e->getMessage()
            ]);
        }

        return (new AuthByMobilePhone)->confirmOPTCode($request->all());
    }

    public function resendVerify(ReSendVerifyRequest $request): JsonResponse
    {
        $user = User::where('email', $request->input('email'))
            ->whereNotNull('verify_token')
            ->whereNull('email_verified_at')
            ->first();

        if (!$user) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        event((new SendEmailVerification($user)));

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language));
    }

    public function verifyEmail(?string $verifyToken): JsonResponse
    {
        $user = User::where('verify_token', $verifyToken)
            ->whereNull('email_verified_at')
            ->first();

        if (empty($user)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        try {
            $user->update([
                'email_verified_at' => now(),
            ]);

            return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), [
                'token' => $user->createToken('api_token')->plainTextToken,
                'email' => $user->email
            ]);
        } catch (Throwable) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_501]);
        }
    }

    public function afterVerifyEmail(AfterVerifyRequest $request): JsonResponse
    {
        $user = User::where('email', $request->input('email'))->first();

        if (empty($user)) {
            return $this->onErrorResponse(['code' => ResponseError::ERROR_404]);
        }

        $user->update([
            'firstname' => $request->input('firstname', $user->email),
            'lastname'  => $request->input('lastname', $user->lastname),
            'referral'  => $request->input('referral', $user->referral),
            'gender'    => $request->input('gender','male'),
            'password'  => bcrypt($request->input('password', 'password')),
        ]);

        $referral = User::where('my_referral', $request->input('referral', $user->referral))
            ->first();

        if (!empty($referral) && !empty($referral->firebase_token)) {

            /** @var NotificationUser $notification */
            $notification = $referral->notifications
                ?->where('type', Notification::PUSH)
                ?->first();

            if ($notification?->notification?->active) {
                $this->sendNotification(
                    $referral,
                    is_array($referral->firebase_token) ? $referral->firebase_token : [$referral->firebase_token],
                    'Congratulations!',
                    "By your referral registered new user. $user->name_or_email",
                    [
                        'id'   => $referral->id,
                        'type' => PushNotification::NEW_USER_BY_REFERRAL
                    ],
                    [$referral->id],
                );
            }

        }

        (new UserService)->notificationSync($user);

        $user->emailSubscription()->updateOrCreate([
            'user_id' => $user->id
        ], [
            'active' => true
        ]);

        if (empty($user->wallet?->uuid)) {
            $user = (new UserWalletService)->create($user);
        }

        $token = $user->createToken('api_token')->plainTextToken;

        return $this->successResponse(__('errors.' . ResponseError::NO_ERROR, locale: $this->language), [
            'token' => $token,
            'user'  => UserResource::make($user),
        ]);
    }

}
