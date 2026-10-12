<?php

/**
 * Core Arabic validation messages.
 *
 * Missing keys fall back to `lang/en/validation.php` via the configured
 * fallback locale, so this file only needs the messages the application
 * actually emits. Extend it as new rules are introduced.
 */

return [

    'accepted' => 'يجب قبول :attribute.',
    'array' => 'يجب أن يكون :attribute قائمة.',
    'boolean' => 'يجب أن تكون قيمة :attribute صحيحة أو خاطئة.',
    'confirmed' => 'تأكيد :attribute غير متطابق.',
    'date' => 'يجب أن يكون :attribute تاريخاً صحيحاً.',
    'email' => 'يجب أن يكون :attribute عنوان بريد إلكتروني صحيح.',
    'exists' => 'القيمة المحددة لـ :attribute غير صحيحة.',
    'image' => 'يجب أن يكون :attribute صورة.',
    'in' => 'القيمة المحددة لـ :attribute غير صحيحة.',
    'integer' => 'يجب أن يكون :attribute عدداً صحيحاً.',
    'max' => [
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :max عناصر.',
        'file' => 'يجب ألا يزيد حجم :attribute على :max كيلوبايت.',
        'numeric' => 'يجب ألا تزيد قيمة :attribute على :max.',
        'string' => 'يجب ألا يزيد عدد أحرف :attribute على :max حرفاً.',
    ],
    'mimes' => 'يجب أن يكون :attribute ملفاً من نوع: :values.',
    'min' => [
        'array' => 'يجب أن يحتوي :attribute على :min عناصر على الأقل.',
        'file' => 'يجب ألا يقل حجم :attribute عن :min كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute :min على الأقل.',
        'string' => 'يجب أن يحتوي :attribute على :min أحرف على الأقل.',
    ],
    'numeric' => 'يجب أن تكون قيمة :attribute رقماً.',
    'required' => 'حقل :attribute مطلوب.',
    'string' => 'يجب أن يكون :attribute نصاً.',
    'unique' => 'قيمة :attribute مستخدمة مسبقاً.',

    'attributes' => [
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'name' => 'الاسم',
        'quantity' => 'الكمية',
        'score' => 'التقييم',
    ],

];
