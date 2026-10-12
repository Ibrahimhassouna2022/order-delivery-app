<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\RequestAccountDeletionAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AccountController extends Controller
{
    public function destroy(
        Request $request,
        RequestAccountDeletionAction $requestDeletion,
    ): Response {
        $requestDeletion->execute($request->user());

        return response()->noContent();
    }
}
