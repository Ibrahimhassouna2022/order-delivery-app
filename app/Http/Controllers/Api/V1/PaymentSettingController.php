<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PaymentSettingResource;
use App\Models\PaymentSetting;

class PaymentSettingController extends Controller
{
    public function show(): PaymentSettingResource
    {
        return PaymentSettingResource::make(PaymentSetting::current());
    }
}
