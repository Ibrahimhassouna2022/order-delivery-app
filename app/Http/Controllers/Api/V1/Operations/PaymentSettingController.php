<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Operations\UpdatePaymentSettingRequest;
use App\Http\Resources\Api\V1\PaymentSettingResource;
use App\Models\PaymentSetting;

class PaymentSettingController extends Controller
{
    public function show(): PaymentSettingResource
    {
        return PaymentSettingResource::make(PaymentSetting::current());
    }

    public function update(UpdatePaymentSettingRequest $request): PaymentSettingResource
    {
        $setting = PaymentSetting::query()->updateOrCreate([], $request->validated());

        return PaymentSettingResource::make($setting);
    }
}
