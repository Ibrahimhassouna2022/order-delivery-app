<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Payment\PaymentReviewConflictException;
use App\Actions\Payment\SubmitPaymentProofAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\SubmitPaymentProofRequest;
use App\Http\Resources\Api\V1\CustomerOrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PaymentProofController extends Controller
{
    public function store(
        SubmitPaymentProofRequest $request,
        Order $order,
        SubmitPaymentProofAction $submitProof,
    ): JsonResponse {
        try {
            $submitProof->execute(
                $request->user(),
                $order,
                $request->file('proof'),
                $request->validated('transfer_reference'),
            );
        } catch (PaymentReviewConflictException $exception) {
            return response()->json(
                ['message' => $exception->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        $order = $order->load([
            'restaurant',
            'deliveryLocation',
            'deliveryZone',
            'items',
            'paymentAttempts.reviewedBy',
        ]);

        return CustomerOrderResource::make($order)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
