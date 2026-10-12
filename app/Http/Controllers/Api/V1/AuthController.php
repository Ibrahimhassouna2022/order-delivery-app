<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\AuthenticateSocialUserAction;
use App\Actions\Auth\AuthenticateUserAction;
use App\Actions\Auth\CreateGuestSessionAction;
use App\Actions\Auth\RegisterCustomerAction;
use App\Actions\Auth\SocialAuthFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\AuthenticateRequest;
use App\Http\Requests\Api\V1\Auth\GuestSessionRequest;
use App\Http\Requests\Api\V1\Auth\RegisterCustomerRequest;
use App\Http\Requests\Api\V1\Auth\SocialAuthRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function register(
        RegisterCustomerRequest $request,
        RegisterCustomerAction $registerCustomer,
    ): JsonResponse {
        ['user' => $user, 'token' => $token] = $registerCustomer->execute($request->validated());

        return UserResource::make($user)
            ->additional(['token' => $token])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function login(
        AuthenticateRequest $request,
        AuthenticateUserAction $authenticateUser,
    ): JsonResponse {

        ['user' => $user, 'token' => $token] =
        $authenticateUser->execute($request->validated());

        return UserResource::make($user)
            ->additional(['token' => $token])
            ->response();
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->noContent();
    }

    /**
     * Guest sign-in for customers who do not want to register (FR-001).
     */
    public function guest(
        GuestSessionRequest $request,
        CreateGuestSessionAction $createGuestSession,
    ): JsonResponse {
        ['user' => $user, 'token' => $token] = $createGuestSession->execute();

        return UserResource::make($user)
            ->additional(['token' => $token])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Exchange a Google identity token for a customer session (FR-001).
     */
    public function google(
        SocialAuthRequest $request,
        AuthenticateSocialUserAction $authenticateSocialUser,
    ): JsonResponse {
        return $this->socialSignIn('google', $request, $authenticateSocialUser);
    }

    /**
     * Exchange an Apple identity token for a customer session (FR-001).
     */
    public function apple(
        SocialAuthRequest $request,
        AuthenticateSocialUserAction $authenticateSocialUser,
    ): JsonResponse {
        return $this->socialSignIn('apple', $request, $authenticateSocialUser);
    }

    private function socialSignIn(
        string $provider,
        SocialAuthRequest $request,
        AuthenticateSocialUserAction $authenticateSocialUser,
    ): JsonResponse {
        try {
            ['user' => $user, 'token' => $token] = $authenticateSocialUser->execute(
                $provider,
                $request->validated('access_token'),
            );
        } catch (SocialAuthFailedException $exception) {
            return response()->json(
                ['message' => $exception->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return UserResource::make($user)
            ->additional(['token' => $token])
            ->response();
    }

    public function user(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }
}
