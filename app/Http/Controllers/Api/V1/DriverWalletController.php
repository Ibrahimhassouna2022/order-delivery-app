<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Driver\CompleteDriverPayoutAction;
use App\Actions\Driver\DriverWorkflowConflictException;
use App\Actions\Driver\GetDriverWalletAction;
use App\Enums\DriverAssignmentStatus;
use App\Enums\WalletEntryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\CompletePayoutRequest;
use App\Http\Resources\Api\V1\DriverWalletEntryResource;
use App\Models\DriverAssignment;
use App\Models\DriverWalletLedgerEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DriverWalletController extends Controller
{
    public function show(Request $request, GetDriverWalletAction $getWallet): JsonResponse
    {
        $user = $request->user();
        ['delivery_credits' => $deliveryCredits, 'entries' => $entries] = $getWallet->execute(
            $user,
        );

        $credits = (float) DriverWalletLedgerEntry::query()
            ->whereBelongsTo($user, 'driver')
            ->where('entry_type', WalletEntryType::DeliveryCredit)
            ->sum('amount');

        $debits = (float) DriverWalletLedgerEntry::query()
            ->whereBelongsTo($user, 'driver')
            ->whereIn('entry_type', [WalletEntryType::PayoutDebit, WalletEntryType::Adjustment])
            ->sum('amount');

        $balance = max(0, $credits - $debits);

        $todayDeliveriesCount = DriverAssignment::query()
            ->whereBelongsTo($user, 'driver')
            ->where('status', DriverAssignmentStatus::Delivered)
            ->where('delivered_at', '>=', now()->startOfDay())
            ->count();

        $completedCount = (int) ($user->driverProfile?->completed_deliveries_count ?? 0);

        return response()->json([
            'data' => [
                'balance' => number_format($balance, 2, '.', ''),
                'completed_deliveries_count' => $completedCount,
                'today_deliveries_count' => $todayDeliveriesCount,
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
