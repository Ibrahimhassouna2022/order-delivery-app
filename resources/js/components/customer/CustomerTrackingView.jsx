import React, { useState } from 'react';
import { 
    Phone, 
    MessageSquare, 
    ShieldCheck, 
    Clock, 
    Bike, 
    MapPin, 
    CheckCircle2, 
    Copy, 
    Check, 
    AlertCircle, 
    Radio,
    Share2,
    Utensils,
    Store,
    Receipt,
    Calculator
} from 'lucide-react';

export default function CustomerTrackingView({ activeOrder, onOpenFareSchedule, activeOrders = [], onSelectOrder }) {
    const [copiedLandmark, setCopiedLandmark] = useState(false);
    const [landmarkNote, setLandmarkNote] = useState(
        activeOrder?.dropoffAddress || 'غزة - مفترق السرايا (مقابل برج الشروق)'
    );

    React.useEffect(() => {
        if (activeOrder?.dropoffAddress) {
            setLandmarkNote(activeOrder.dropoffAddress);
        }
    }, [activeOrder?.id, activeOrder?.dropoffAddress]);

    if (!activeOrder) {
        return (
            <div className="bg-white rounded-3xl p-12 border border-slate-200/80 shadow-xs text-center space-y-3 font-sans" dir="rtl">
                <Bike className="w-12 h-12 text-slate-300 mx-auto" />
                <h3 className="text-base font-bold text-slate-800">لا يوجد طلب توصيل نشط حالياً</h3>
                <p className="text-xs text-slate-500 max-w-sm mx-auto">
                    عندما تقوم بطلب وجبة طعام من أحد المطاعم، ستصلك هنا موافقة المطعم والفاتورة الرسمية وتتبع السائق.
                </p>
            </div>
        );
    }

    const driverPhone = activeOrder.driverPhone || '+970 59 888 1234';
    const driverName = activeOrder.driverName || 'محمود النجار (الكابتن)';
    const rawOrderNumber = activeOrder.invoiceNumber || activeOrder.orderNumber || 'ORD-9421';
    const orderNumber = String(rawOrderNumber).replace(/^#+/, '');
    const distanceKm = activeOrder.distanceKm || 2.5;

    // Workflow status gates: Payment must be explicitly approved or in active cooking/transit
    const isPaymentRejected = activeOrder.paymentStatusCode === 'failed' || activeOrder.statusCode === 'payment_failed';

    const isApproved = Boolean(
        !isPaymentRejected && (
            activeOrder.paymentStatusCode === 'succeeded' || 
            Boolean(activeOrder.paymentApprovedAt) ||
            ['preparing', 'ready', 'assigned', 'on_way', 'picked_up', 'delivered'].includes(activeOrder.statusCode)
        )
    );

    const isHandedOverToDriver = Boolean(
        isApproved && (
            ['picked_up', 'on_way', 'assigned'].includes(activeOrder.statusCode) || 
            Boolean(activeOrder.driverAssigned)
        )
    );

    const isWaitingApproval = !isApproved && !isPaymentRejected;

    const handleCopyLandmark = () => {
        navigator.clipboard.writeText(`مرحباً كابتن، طلبي رقم #${orderNumber}، نقطة لقائي هي: ${landmarkNote}`);
        setCopiedLandmark(true);
        setTimeout(() => setCopiedLandmark(false), 2500);
    };

    const smsHref = `sms:${driverPhone}?body=${encodeURIComponent(
        `مرحباً كابتن ${driverName}، بخصوص طلب #${orderNumber}، نقطة لقائي هي: ${landmarkNote}`
    )}`;

    return (
        <div className="space-y-6 font-sans" dir="rtl">
            
            {/* Active Orders Switcher Tabs (When ordering from one or multiple restaurants) */}
            {activeOrders && activeOrders.length > 0 && (
                <div className="bg-white p-4 md:p-5 rounded-3xl border-2 border-emerald-500/20 shadow-xs space-y-3">
                    <div className="flex items-center justify-between">
                        <span className="text-xs font-bold text-slate-800 flex items-center gap-2">
                            <Utensils className="w-4 h-4 text-emerald-700" />
                            <span>
                                {activeOrders.length > 1 
                                    ? `لديك (${activeOrders.length}) طلبات جارية من مطاعم مختلفة — اضغط على أي مطعم لمتابعته:` 
                                    : 'الطلب النشط حالياً:'}
                            </span>
                        </span>
                        {activeOrders.length > 1 && (
                            <span className="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 text-[10px] font-bold border border-emerald-200">
                                {activeOrders.length} طلبات نشطة
                            </span>
                        )}
                    </div>
                    <div className="flex items-center gap-3 overflow-x-auto pb-1">
                        {activeOrders.map((ord) => {
                            const isSelected = ord.id === activeOrder?.id;
                            return (
                                <button
                                    key={ord.id}
                                    type="button"
                                    onClick={() => onSelectOrder?.(ord.id)}
                                    className={`px-4 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center gap-2.5 shrink-0 border-2 cursor-pointer shadow-xs ${
                                        isSelected 
                                            ? 'bg-[#0d3b32] text-white border-[#0d3b32] shadow-md ring-4 ring-emerald-500/20 scale-[1.02]' 
                                            : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'
                                    }`}
                                >
                                    <span>🍽️ {ord.restaurantName} ({ord.orderNumber})</span>
                                    <span className={`text-[10px] px-2.5 py-0.5 rounded-full font-bold ${
                                        isSelected ? 'bg-white/20 text-emerald-100' : 'bg-slate-200 text-slate-700'
                                    }`}>
                                        {ord.status}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                </div>
            )}

            {/* Status & Notification Banners */}
            {isHandedOverToDriver ? (
                <div className="bg-emerald-700 text-white p-5 rounded-3xl shadow-md space-y-2 animate-in slide-in-from-top duration-300">
                    <div className="flex items-center justify-between gap-3">
                        <div className="flex items-center gap-3">
                            <div className="w-11 h-11 rounded-2xl bg-amber-400 text-amber-950 flex items-center justify-center font-bold shrink-0 shadow-sm">
                                <Bike className="w-6 h-6 animate-pulse" />
                            </div>
                            <div>
                                <h3 className="font-extrabold text-base">تم تسليم وجبتك لسائق التوصيل وهو في الطريق إليك الآن 🛵</h3>
                                <p className="text-xs text-emerald-100 font-medium">
                                    الكابتن المكلّف: <strong>{driverName}</strong> • رقم هاتفه المباشر متاح أدناه للاتصال والتنسيق.
                                </p>
                            </div>
                        </div>
                        <span className="px-3 py-1.5 bg-white/20 rounded-xl text-xs font-bold shrink-0">
                            {activeOrder.status && !activeOrder.status.includes('مراجعة') ? activeOrder.status : 'معتمد في المطبخ 🍳'}
                        </span>
                    </div>
                </div>
            ) : isPaymentRejected ? (
                <div className="bg-rose-50 border-2 border-rose-300 text-rose-950 p-5 rounded-3xl shadow-xs flex items-center justify-between gap-3 animate-in slide-in-from-top duration-300">
                    <div className="flex items-center gap-3">
                        <div className="w-11 h-11 rounded-2xl bg-rose-100 border border-rose-200 flex items-center justify-center font-bold shrink-0">
                            <AlertCircle className="w-6 h-6 text-rose-700" />
                        </div>
                        <div>
                            <h3 className="font-extrabold text-base">تم رفض إشعار التحويل من قبل {activeOrder.restaurantName || 'المطعم'}</h3>
                            <p className="text-xs text-rose-800 mt-0.5">
                                {activeOrder.rejectionReason || 'يرجى التأكد من صحة المبلغ ومراجعة بيانات التحويل، ثم إعادة رفع صورة الإشعار.'}
                            </p>
                        </div>
                    </div>
                    <span className="px-3 py-1.5 bg-rose-200 text-rose-900 rounded-xl text-xs font-bold shrink-0">
                        مرفوض ✗
                    </span>
                </div>
            ) : isApproved ? (
                <div className="bg-emerald-600 text-white p-5 rounded-3xl shadow-md space-y-3 animate-in slide-in-from-top duration-300">
                    <div className="flex items-center justify-between gap-3">
                        <div className="flex items-center gap-3">
                            <div className="w-11 h-11 rounded-2xl bg-white/20 flex items-center justify-center font-bold shrink-0">
                                <CheckCircle2 className="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <h3 className="font-extrabold text-base">تم قبول إشعار التحويل من قبل {activeOrder.restaurantName || 'المطعم'} بنجاح!</h3>
                                <p className="text-xs text-emerald-100 font-medium">
                                    تم اعتماد الفاتورة والبدء في تحضير وجبتك في المطبخ الآن 🍳
                                </p>
                            </div>
                        </div>
                        <span className="px-3 py-1.5 bg-white/20 rounded-xl text-xs font-bold shrink-0">
                            {activeOrder.status && !activeOrder.status.includes('مراجعة') ? activeOrder.status : 'معتمد في المطبخ 🍳'}
                        </span>
                    </div>

                    <div className="p-3 bg-black/15 rounded-2xl text-xs text-emerald-100 flex items-center gap-2">
                        <Bike className="w-4 h-4 text-emerald-300 shrink-0" />
                        <span>
                            <strong>تنبيه الزبون:</strong> سيتم إشعارك فور تسليم الوجبة لسائق التوصيل مع تزويدك باسمه ورقم هاتفه المباشر لمتابعة وصوله.
                        </span>
                    </div>
                </div>
            ) : (
                <div className="bg-amber-50 border-2 border-amber-300 text-amber-950 p-5 rounded-3xl shadow-xs flex items-center justify-between gap-3 animate-pulse">
                    <div className="flex items-center gap-3">
                        <div className="w-11 h-11 rounded-2xl bg-amber-100 border border-amber-200 flex items-center justify-center font-bold shrink-0">
                            <Clock className="w-6 h-6 text-amber-700" />
                        </div>
                        <div>
                            <h3 className="font-extrabold text-base">طلبك وإشعار التحويل قيد مراجعة واعتماد المطعم ⏳</h3>
                            <p className="text-xs text-amber-900 mt-0.5">
                                تم إرسال إشعار التحويل البنكي ويقوم المطعم بمراجعته وتدقيقه الآن. سيبدأ التحضير بالمطبخ فور الموافقة والاعتماد.
                            </p>
                        </div>
                    </div>
                    <span className="px-3 py-1.5 bg-amber-200 border border-amber-300 text-amber-900 rounded-xl text-xs font-bold shrink-0">
                        {activeOrder.status || 'بانتظار موافقة المطعم'}
                    </span>
                </div>
            )}

            {/* Main Grid: Left for Coordination & Right for Detailed Invoice */}
            <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                {/* Left (7 Cols): Driver & Coordination */}
                <div className="lg:col-span-7 space-y-6">
                    
                    {/* Courier Contact Card */}
                    <div className="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-6">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold text-slate-500">
                                كابتن التوصيل المكلف بالطلب
                            </span>
                            {isHandedOverToDriver ? (
                                <span className="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 text-xs font-bold border border-emerald-200">
                                    كابتن معتمد • في الطريق 🛵
                                </span>
                            ) : isApproved ? (
                                <span className="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 text-xs font-bold border border-emerald-200">
                                    قيد التحضير في المطبخ 🍳
                                </span>
                            ) : isPaymentRejected ? (
                                <span className="px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-800 text-xs font-bold border border-rose-200">
                                    إشعار التحويل مرفوض ✗
                                </span>
                            ) : (
                                <span className="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-900 text-xs font-bold border border-amber-300">
                                    بانتظار موافقة المطعم ⏳
                                </span>
                            )}
                        </div>

                        {isHandedOverToDriver ? (
                            <>
                                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-100">
                                    <div className="flex items-center gap-4">
                                        <div className="w-13 h-13 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-xl border border-amber-200 shadow-xs shrink-0">
                                            <Bike className="w-6 h-6" />
                                        </div>
                                        <div>
                                            <h3 className="font-bold text-slate-900 text-base">{driverName}</h3>
                                            <p className="text-xs text-slate-500">دراجة نارية سريعة</p>
                                            <p className="text-xs font-mono font-black text-emerald-800 mt-0.5" dir="ltr">{driverPhone}</p>
                                        </div>
                                    </div>

                                    <div className="text-right">
                                        <span className="text-[10px] font-bold text-slate-400 block">وقت الوصول التقديري</span>
                                        <span className="text-lg font-black text-slate-900">{activeOrder.eta || '15 - 20 دقيقة'}</span>
                                    </div>
                                </div>

                                {/* Direct Cellular Calls */}
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                    <a
                                        href={`tel:${driverPhone.replace(/\s+/g, '')}`}
                                        className="flex items-center justify-center gap-2 py-3 px-4 rounded-2xl bg-[#0d3b32] hover:bg-[#092923] text-white font-bold text-xs shadow-md transition-all active:scale-[0.98]"
                                    >
                                        <Phone className="w-4 h-4 text-emerald-300" />
                                        <span>اتصال هاتفي مباشر بالكابتن ({driverPhone})</span>
                                    </a>

                                    <a
                                        href={smsHref}
                                        className="flex items-center justify-center gap-2 py-3 px-4 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-md transition-all active:scale-[0.98]"
                                    >
                                        <MessageSquare className="w-4 h-4 text-emerald-300" />
                                        <span>إرسال رسالة SMS بالمعلم</span>
                                    </a>
                                </div>
                            </>
                        ) : isApproved ? (
                            <div className="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                                <div className="flex items-center gap-2 text-slate-800 font-bold">
                                    <Clock className="w-4 h-4 text-amber-600" />
                                    <span>الوجبة قيد الطهي والتحضير في مطبخ المطعم 🍳</span>
                                </div>
                                <p className="text-slate-500 text-[11px] leading-relaxed">
                                    تم اعتماد الفاتورة وقبول إشعار التحويل. سيتم إسناد وتزويدك باسم ورقم هاتف كابتن التوصيل مع إمكانية الاتصال المباشر به فور جاهزية الوجبة وتسليمها له.
                                </p>
                            </div>
                        ) : isPaymentRejected ? (
                            <div className="p-4 rounded-2xl bg-rose-50/70 border border-rose-200 text-xs space-y-2">
                                <div className="flex items-center gap-2 text-rose-900 font-bold">
                                    <AlertCircle className="w-4 h-4 text-rose-700" />
                                    <span>إشعار التحويل البنكي مرفوض ✗</span>
                                </div>
                                <p className="text-rose-800 text-[11px] leading-relaxed">
                                    لم يبدأ الطهي. يرجى إعادة رفع صورة إشعار التحويل الصحيحة عبر زر «متابعة حالة الدفع والفاتورة» بالأعلى.
                                </p>
                            </div>
                        ) : (
                            <div className="p-4 rounded-2xl bg-amber-50/70 border border-amber-200 text-xs space-y-2">
                                <div className="flex items-center gap-2 text-amber-900 font-bold">
                                    <Clock className="w-4 h-4 text-amber-700" />
                                    <span>إشعار التحويل قيد التدقيق لدى المطعم ⏳</span>
                                </div>
                                <p className="text-amber-800 text-[11px] leading-relaxed">
                                    لم يبدأ الطهي بعد؛ يقوم المطعم بمراجعة وتأكيد استلام المبلغ في الحساب. فور الاعتماد سيبدأ الطهي فوراً ويتم إسناد كابتن التوصيل.
                                </p>
                            </div>
                        )}
                    </div>

                    {/* Street Landmark Coordinator */}
                    <div className="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4">
                        <div className="flex items-center gap-2">
                            <MapPin className="w-4 h-4 text-rose-600" />
                            <h3 className="font-bold text-slate-900 text-sm">
                                عنوان التسليم ونقطة اللقاء مع السائق
                            </h3>
                        </div>

                        <div className="p-3 bg-slate-50 rounded-2xl border border-slate-200 text-xs font-medium text-slate-800">
                            {landmarkNote}
                        </div>

                        <div className="flex items-center justify-between pt-1">
                            <button
                                onClick={handleCopyLandmark}
                                className="flex items-center gap-1.5 text-xs font-bold text-emerald-800 hover:text-emerald-900 transition-colors bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-200"
                            >
                                {copiedLandmark ? <Check className="w-3.5 h-3.5" /> : <Copy className="w-3.5 h-3.5" />}
                                <span>{copiedLandmark ? 'تم نسخ العنوان والمعلم!' : 'نسخ العنوان لإرساله للسائق'}</span>
                            </button>

                            <a
                                href={smsHref}
                                className="text-xs font-bold text-slate-600 hover:text-slate-900 flex items-center gap-1"
                            >
                                <Share2 className="w-3.5 h-3.5 text-slate-400" />
                                <span>مشاركة المعلم في رسالة</span>
                            </a>
                        </div>
                    </div>

                </div>

                {/* Right (5 Cols): The Official Approved Detailed Invoice */}
                <div className="lg:col-span-5 space-y-6">
                    <div className="bg-white rounded-3xl p-6 border-2 border-emerald-500/30 shadow-md space-y-5">
                        
                        <div className="flex justify-between items-center border-b border-slate-100 pb-3">
                            <div className="flex items-center gap-2">
                                <Receipt className="w-5 h-5 text-emerald-700" />
                                <h3 className="font-bold text-slate-900 text-base">فاتورة الطلب الرسمية</h3>
                            </div>
                            <span className="font-mono text-xs font-bold text-slate-600">#{orderNumber}</span>
                        </div>

                        {/* Customer & Merchant Information */}
                        <div className="text-xs space-y-1 bg-slate-50 p-3 rounded-2xl border border-slate-200">
                            <div className="flex justify-between">
                                <span className="text-slate-500">المطعم:</span>
                                <span className="font-bold text-slate-900">{activeOrder.restaurantName}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-slate-500">العميل:</span>
                                <span className="font-bold text-slate-900">{activeOrder.customerName || 'أحمد خليل'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-slate-500">رقم الهاتف:</span>
                                <span className="font-mono font-bold text-slate-900" dir="ltr">{activeOrder.customerPhone || '+970 59 912 3456'}</span>
                            </div>
                        </div>

                        {/* Items Table in Invoice */}
                        <div className="space-y-2">
                            <span className="font-bold text-xs text-slate-700 block">تفاصيل الوجبات:</span>
                            <div className="divide-y divide-slate-100 text-xs">
                                {(activeOrder.items || [{ name: activeOrder.name, qty: 1, price: activeOrder.itemsSubtotal || 22.00 }]).map((item, idx) => (
                                    <div key={idx} className="py-2 flex justify-between items-center">
                                        <span className="text-slate-800">
                                            {item.qty} × {item.name}
                                        </span>
                                        <span className="font-bold text-slate-900">
                                            {((item.price || 22.00) * (item.qty || 1)).toFixed(2)} ₪
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Delivery Pricing Breakdown & Official Fare Schedule */}
                        <div className="p-3 bg-amber-50 rounded-2xl border border-amber-200 text-xs space-y-2">
                            <div className="flex items-center justify-between text-amber-950 font-bold">
                                <span className="flex items-center gap-1">
                                    <Calculator className="w-3.5 h-3.5 text-amber-700" />
                                    <span>أجرة التوصيل المعتمدة:</span>
                                </span>
                                <span>{activeOrder.deliveryFee ? Number(activeOrder.deliveryFee).toFixed(2) : '7.00'} ₪</span>
                            </div>
                            <p className="text-[10px] text-amber-800 leading-relaxed">
                                <strong>تنبيه مسؤولية الزبون:</strong> تُدفع أجرة التوصيل نقداً للسائق عند استلام الوجبة وفق جدول التسعيرة المحدد مسبقاً للمنطقة والوقت التقديري للوصول ({activeOrder.eta || '15 - 20 دقيقة'}).
                            </p>
                            {onOpenFareSchedule && (
                                <button
                                    type="button"
                                    onClick={onOpenFareSchedule}
                                    className="w-full text-center py-1.5 px-2 bg-amber-100 hover:bg-amber-200 text-amber-950 rounded-xl font-bold text-[11px] border border-amber-300 transition-colors flex items-center justify-center gap-1.5"
                                >
                                    <Calculator className="w-3.5 h-3.5 text-amber-800" />
                                    <span>عرض جدول تسعيرة التوصيل لجميع مناطق غزة 🛵</span>
                                </button>
                            )}
                        </div>

                        {/* Invoice Summary Totals */}
                        <div className="pt-3 border-t border-slate-200 space-y-1.5 text-xs">
                            <div className="flex justify-between text-slate-600">
                                <span>مجموع الوجبات:</span>
                                <span className="font-bold text-slate-900">
                                    {activeOrder.itemsSubtotal ? Number(activeOrder.itemsSubtotal).toFixed(2) : (Number(activeOrder.totalAmount || 28) - 7).toFixed(2)} ₪
                                </span>
                            </div>
                            <div className="flex justify-between text-slate-600">
                                <span>خدمة التوصيل:</span>
                                <span className="font-bold text-emerald-800">
                                    +{activeOrder.deliveryFee ? Number(activeOrder.deliveryFee).toFixed(2) : '7.00'} ₪
                                </span>
                            </div>
                            <div className="flex justify-between text-slate-900 font-black text-sm pt-2 border-t border-slate-200">
                                <span>المبلغ الكلي المطلوب:</span>
                                <span className="text-emerald-800 text-lg">
                                    {activeOrder.totalAmount ? Number(activeOrder.totalAmount).toFixed(2) : '28.00'} ₪
                                </span>
                            </div>
                            <p className="text-[10px] text-slate-400 text-center pt-1">
                                طريقة الدفع: نقداً عند استلام الطلب من الكابتن (Cash on Delivery)
                            </p>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    );
}
