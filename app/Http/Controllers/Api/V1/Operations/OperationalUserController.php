<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Actions\Operations\CreateOperationalUserAction;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Operations\StoreOperationalUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class OperationalUserController extends Controller
{
    public function store(
        StoreOperationalUserRequest $request,
        CreateOperationalUserAction $createOperationalUser,
    ): JsonResponse {
        $validatedData = $request->safe()->except('role');
        $user = $createOperationalUser->execute(
            $validatedData,
            UserRole::from($request->validated('role')),
        );

        return UserResource::make($user)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
