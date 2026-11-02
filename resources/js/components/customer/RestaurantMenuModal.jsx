import React, { useMemo, useState } from 'react';
import {
    X,
    Plus,
    Minus,
    ShoppingBag,
    MapPin,
    Bike,
    Utensils,
    Star,
    Store,
    Upload,
    AlertCircle,
    Clock,
    Calculator,
} from 'lucide-react';
import { apiJson, getAuthToken } from '../../lib/api';

export default function RestaurantMenuModal({
    restaurant,
    isOpen,
    onClose,
    onOrderCreated,
    currentUser,
    deliveryZones = [],
    onOpenFareSchedule,
}) {
    if (!isOpen || !restaurant) return null;

    const [quantities, setQuantities] = useState({});
    const [fulfillmentType, setFulfillmentType] = useState('delivery');
    const [selectedZoneId, setSelectedZoneId] = useState(
        deliveryZones[0]?.id ? String(deliveryZones[0].id) : '',
    );
    const [deliveryAddress, setDeliveryAddress] = useState(
        currentUser?.address || 'غزة - حي الرمال، مفترق السرايا بجوار برج الشروق',
    );
    const [customerPhone, setCustomerPhone] = useState(
        currentUser?.phone || '+970 59 912 3456',
    );
    const [notes, setNotes] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');

    const menuItems = useMemo(
        () => (restaurant.menuItems || []).filter((item) => item.is_available !== false),
        [restaurant.menuItems],
    );

    const updateQuantity = (itemId, delta) => {
        setQuantities((prev) => {
            const current = prev[itemId] || 0;
            const updated = Math.max(0, current + delta);
            return { ...prev, [itemId]: updated };
        });
    };

    const selectedZone = deliveryZones.find((zone) => String(zone.id) === String(selectedZoneId));
    const itemsSubtotal = menuItems.reduce(
        (acc, item) => acc + Number(item.price) * (quantities[item.id] || 0),
        0,
    );
    const totalItemsCount = Object.values(quantities).reduce((acc, q) => acc + q, 0);
    const deliveryFee = fulfillmentType === 'pickup' ? 0 : Number(selectedZone?.fee || 0);
    const totalAmount = itemsSubtotal + deliveryFee;

    const handleConfirm = async (event) => {
        event.preventDefault();

        if (totalItemsCount === 0) {
            setError('الرجاء اختيار وجبة واحدة على الأقل من القائمة.');
            return;
        }

        if (fulfillmentType === 'delivery' && (!selectedZone || deliveryAddress.trim() === '')) {
            setError('اختر منطقة التوصيل وأدخل عنوان السكن أولاً.');
            return;
        }

        setSubmitting(true);
        setError('');

        const payload = {
            restaurant_id: restaurant.id,
            fulfillment_type: fulfillmentType,
            delivery_zone_id: fulfillmentType === 'delivery' ? Number(selectedZoneId) : null,
            delivery_address: fulfillmentType === 'delivery' ? deliveryAddress.trim() : null,
            delivery_phone: customerPhone.trim() || null,
            notes: notes.trim() || null,
            payment_method: 'bank_transfer',
            items: menuItems
                .filter((item) => quantities[item.id] > 0)
                .map((item) => ({
                    menu_item_id: item.id,
                    quantity: quantities[item.id],
                })),
        };

        try {
            const quoteResponse = await apiJson('/api/v1/orders/quote', {
                method: 'POST',
                body: payload,
            });

            const idempotencyKey = typeof crypto !== 'undefined' && crypto.randomUUID
                ? crypto.randomUUID()
                : `order-${Date.now()}-${Math.random().toString(16).slice(2)}`;

            const orderResponse = await apiJson('/api/v1/orders', {
                method: 'POST',
                headers: { 'Idempotency-Key': idempotencyKey },
                body: payload,
            });

            onOrderCreated?.({
                order: orderResponse.data,
                quote: quoteResponse.data,
            });
            onClose?.();
        } catch (requestError) {
            setError(requestError?.data?.message || requestError?.message || 'تعذر إنشاء الطلب. حاول مرة أخرى.');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs animate-in fade-in duration-200" dir="rtl">
            <div className="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-slate-100 overflow-hidden flex flex-col max-h-[92vh]">
                <div className="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                    <div className="flex items-center gap-3">
                        <div className="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-800 flex items-center justify-center border border-emerald-200">
                            <Utensils className="w-6 h-6" />
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <h3 className="font-bold text-slate-900 text-lg">{restaurant.name}</h3>
                                <div className="flex items-center gap-1 text-[11px] font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                                    <Star className="w-3 h-3 fill-amber-400 text-amber-400" />
                                    <span>{restaurant.rating || '4.9'}</span>
                                </div>
                            </div>
                            <p className="text-xs text-slate-500 mt-0.5 flex items-center gap-1.5">
                                <MapPin className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                <span>{restaurant.address}</span>
                            </p>
                        </div>
                    </div>

                    <button
                        onClick={onClose}
                        className="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-200/50 transition-colors"
                        title="إغلاق"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                <div className="flex-1 overflow-y-auto p-6 space-y-6">
                    <div>
                        <h4 className="font-bold text-slate-800 text-sm mb-3">اختر وجباتك من قائمة الطعام:</h4>

                        <div className="space-y-3">
                            {menuItems.map((item) => {
                                const qty = quantities[item.id] || 0;
                                return (
                                    <div
                                        key={item.id}
                                        className="p-4 rounded-2xl border border-slate-200/80 hover:border-emerald-500/40 bg-white transition-all flex items-center justify-between gap-4 group"
                                    >
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-2">
                                                <h5 className="font-bold text-slate-900 text-sm">{item.name}</h5>
                                                <span className="text-[10px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md">
                                                    {item.category || 'وجبات'}
                                                </span>
                                            </div>
                                            <p className="text-xs text-slate-500 mt-1 line-clamp-1">{item.desc || item.description}</p>
                                            <span className="text-sm font-extrabold text-emerald-800 block mt-1.5">
                                                {Number(item.price).toFixed(2)} ₪
                                            </span>
                                        </div>

                                        <div className="flex items-center gap-2 bg-slate-50 p-1.5 rounded-xl border border-slate-200 shrink-0">
                                            <button
                                                type="button"
                                                onClick={() => updateQuantity(item.id, -1)}
                                                disabled={qty === 0}
                                                className="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:hover:bg-white transition-colors"
                                            >
                                                <Minus className="w-3.5 h-3.5" />
                                            </button>
                                            <span className="w-6 text-center text-xs font-bold text-slate-900">
                                                {qty}
                                            </span>
                                            <button
                                                type="button"
                                                onClick={() => updateQuantity(item.id, 1)}
                                                className="w-7 h-7 rounded-lg bg-[#0d3b32] text-white flex items-center justify-center hover:bg-[#092923] transition-colors shadow-xs"
                                            >
                                                <Plus className="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    <div className="space-y-4 pt-3 border-t border-slate-100">
                        <div>
                            <span className="font-bold text-xs text-slate-800 block mb-2">طريقة استلام الطلب:</span>
                            <div className="grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    onClick={() => setFulfillmentType('delivery')}
                                    className={`p-3 rounded-2xl border text-xs font-bold flex items-center justify-center gap-2 transition-colors ${
                                        fulfillmentType === 'delivery'
                                            ? 'bg-emerald-50 border-emerald-300 text-emerald-900'
                                            : 'bg-white border-slate-200 text-slate-600'
                                    }`}
                                >
                                    <Bike className="w-4 h-4" />
                                    توصيل إلى المنزل
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setFulfillmentType('pickup')}
                                    className={`p-3 rounded-2xl border text-xs font-bold flex items-center justify-center gap-2 transition-colors ${
                                        fulfillmentType === 'pickup'
                                            ? 'bg-emerald-50 border-emerald-300 text-emerald-900'
                                            : 'bg-white border-slate-200 text-slate-600'
                                    }`}
                                >
                                    <Store className="w-4 h-4" />
                                    استلام من المطعم
                                </button>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label className="block text-[11px] font-bold text-slate-600 mb-1">
                                    رقم هاتف الزبون للتواصل والتتبع
                                </label>
                                <input
                                    type="text"
                                    required
                                    value={customerPhone}
                                    onChange={(event) => setCustomerPhone(event.target.value)}
                                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-slate-800 focus:bg-white text-left"
                                    dir="ltr"
                                />
                            </div>

                            <div>
                                <div className="flex items-center justify-between mb-1">
                                    <label className="block text-[11px] font-bold text-slate-600">
                                        منطقة التوصيل حسب قائمة الأسعار
                                    </label>
                                    {onOpenFareSchedule && (
                                        <button
                                            type="button"
                                            onClick={onOpenFareSchedule}
                                            className="text-[10px] font-bold text-emerald-800 hover:text-emerald-950 flex items-center gap-1 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-200 hover:bg-emerald-100 transition-colors"
                                        >
                                            <Calculator className="w-3 h-3 text-emerald-700" />
                                            <span>جدول التسعيرة والوقت</span>
                                        </button>
                                    )}
                                </div>
                                <select
                                    value={selectedZoneId}
                                    onChange={(event) => setSelectedZoneId(event.target.value)}
                                    disabled={fulfillmentType === 'pickup'}
                                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:bg-white disabled:opacity-50"
                                >
                                    <option value="">اختر المنطقة</option>
                                    {deliveryZones.map((zone) => (
                                        <option key={zone.id} value={zone.id}>
                                            {zone.name} — {Number(zone.fee).toFixed(2)} ₪
                                        </option>
                                    ))}
                                </select>
                                {fulfillmentType === 'delivery' && selectedZone && (
                                    <div className="mt-1.5 flex items-center justify-between text-[11px] bg-slate-100/90 px-3 py-1.5 rounded-xl border border-slate-200">
                                        <span className="text-slate-600 flex items-center gap-1.5">
                                            <Clock className="w-3.5 h-3.5 text-slate-500" />
                                            الوقت المتوقع: <strong className="text-slate-900">{selectedZone.estimated_time || '20 - 25 دقيقة'}</strong>
                                        </span>
                                        <span className="text-emerald-800 font-extrabold">
                                            الأجرة: {Number(selectedZone.fee).toFixed(2)} ₪
                                        </span>
                                    </div>
                                )}
                            </div>
                        </div>

                        {fulfillmentType === 'delivery' && (
                            <div>
                                <label className="block text-[11px] font-bold text-slate-600 mb-1">
                                    عنوان السكن والمعلم البارز
                                </label>
                                <input
                                    type="text"
                                    required
                                    value={deliveryAddress}
                                    onChange={(event) => setDeliveryAddress(event.target.value)}
                                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:bg-white"
                                />
                            </div>
                        )}

                        <div>
                            <label className="block text-[11px] font-bold text-slate-600 mb-1">
                                ملاحظات خاصة بالوجبة للمطعم (اختياري)
                            </label>
                            <input
                                type="text"
                                value={notes}
                                onChange={(event) => setNotes(event.target.value)}
                                placeholder="مثال: زيادة ثومية، بدون مخلل، شطة خفيفة..."
                                className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:bg-white"
                            />
                        </div>
                    </div>

                    <div className="p-3.5 bg-amber-50/80 border border-amber-200/90 rounded-2xl text-xs text-amber-950 space-y-1.5">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-1.5 font-bold">
                                <Upload className="w-4 h-4 text-amber-700" />
                                <span>الدفع بالتحويل البنكي + أجرة التوصيل للسائق</span>
                            </div>
                            {onOpenFareSchedule && (
                                <button
                                    type="button"
                                    onClick={onOpenFareSchedule}
                                    className="text-[10px] font-bold text-amber-900 hover:underline flex items-center gap-1"
                                >
                                    <Calculator className="w-3 h-3" />
                                    جدول التسعيرة والوقت
                                </button>
                            )}
                        </div>
                        <p className="text-[11px] text-amber-900/90 leading-relaxed">
                            بعد تأكيد الطلب، حوّل قيمة وجبات الطعام فقط للمطعم، ثم ارفع صورة إشعار التحويل للموافقة وبدء الطهي.
                            <br />
                            <strong>مسؤولية الزبون:</strong> يدفع الزبون أجرة التوصيل نقداً للسائق عند استلام الطلب وفق جدول التسعيرة المعتمد مسبقاً بناءً على المنطقة ({selectedZone?.name || 'المنطقة المحددة'}) والوقت التقديري للوصول ({selectedZone?.estimated_time || '20 - 25 دقيقة'}).
                        </p>
                    </div>

                    {error && (
                        <div className="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-start gap-2">
                            <AlertCircle className="w-4 h-4 shrink-0 mt-0.5" />
                            <span>{error}</span>
                        </div>
                    )}
                </div>

                <div className="p-5 border-t border-slate-100 bg-slate-50 space-y-3">
                    <div className="space-y-1.5 text-xs text-slate-600">
                        <div className="flex justify-between">
                            <span>قيمة الطلب المطلوب تحويلها ({totalItemsCount} وجبة):</span>
                            <span className="font-bold text-slate-800">{itemsSubtotal.toFixed(2)} ₪</span>
                        </div>
                        <div className="flex justify-between text-emerald-800">
                            <span className="flex items-center gap-1">
                                <Bike className="w-3.5 h-3.5" />
                                <span>أجرة التوصيل (تُدفع للسائق عند التسليم):</span>
                            </span>
                            <span className="font-bold">{deliveryFee.toFixed(2)} ₪</span>
                        </div>
                        <div className="flex justify-between text-slate-900 font-extrabold text-sm pt-2 border-t border-slate-200">
                            <span>إجمالي الفاتورة:</span>
                            <span className="text-emerald-800 text-base">{totalAmount.toFixed(2)} ₪</span>
                        </div>
                    </div>

                    <button
                        onClick={handleConfirm}
                        disabled={totalItemsCount === 0 || submitting}
                        className="w-full py-3 bg-[#0d3b32] hover:bg-[#092923] text-white font-bold rounded-xl text-sm transition-all shadow-md active:scale-[0.99] flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        <ShoppingBag className="w-4 h-4 text-emerald-300" />
                        <span>{submitting ? 'جارٍ إنشاء الطلب...' : 'تأكيد الطلب والانتقال للدفع'}</span>
                    </button>
                </div>
            </div>
        </div>
    );
}
