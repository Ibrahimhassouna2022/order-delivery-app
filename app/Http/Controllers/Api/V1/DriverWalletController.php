<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Driver\CompleteDriverPayoutAction;
use App\Actions\Driver\DriverWorkflowConflictException;
use App\Actions\Driver\GetDriverWalletAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\CompletePayoutRequest;
use App\Http\Resources\Api\V1\DriverWalletEntryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DriverWalletController extends Controller
{
    public function show(Request $request, GetDriverWalletAction $getWallet): JsonResponse
    {
        ['delivery_credits' => $deliveryCredits, 'entries' => $entries] = $getWallet->execute(
            $request->user(),
        );

        return response()->json([
            'data' => [
                'delivery_credits' => $deliveryCredits,
                'entries' => DriverWalletEntryResource::collection($entries)->resolve(),
                'pagination' => [
                    'current_page' => $entries->currentPage(),
                    'last_page' => $entries->lastPage(),
                    'per_page' => $entries->perPage(),
                    'total' => $entries->total(),
                ],
            ],
        ]);
    }

    public function payout(
        CompletePayoutRequest $request,
        CompleteDriverPayoutAction $completePayout,
    ): JsonResponse {
        try {
            ['entry' => $entry, 'created' => $created] = $completePayout->execute(
                $request->user(),
                $request->validated('idempotency_key'),
                $request->validated('currency_code'),
            );
        } catch (DriverWorkflowConflictException $exception) {
            return response()->json(
                ['message' => $exception->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        return DriverWalletEntryResource::make($entry)
            ->response()
            ->setStatusCode($created ? Response::HTTP_CREATED : Response::HTTP_OK);
    }
}
