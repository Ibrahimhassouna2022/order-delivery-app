import React, { useState, useEffect, useRef } from 'react';
import { 
    Bike, 
    Navigation, 
    MapPin, 
    Clock, 
    CheckCircle, 
    XCircle, 
    Phone, 
    MessageSquare, 
    AlertCircle, 
    ArrowLeft,
    Power,
    Shield,
    CheckCircle2,
    Wallet,
    TrendingUp,
    Store,
    User,
    Loader2,
    RefreshCw
} from 'lucide-react';
import { apiJson } from '../../lib/api';
import { 
    playNotificationSound, 
    showBrowserNotification, 
    requestBrowserNotificationPermission 
} from '../../lib/notifications';

export default function DriverPortal({ user, onLogout, parentOffer, onAcceptParentOffer, parentActiveTrip, onUpdateTripStep }) {
    const [isOnline, setIsOnline] = useState(true);
    const [togglingOnline, setTogglingOnline] = useState(false);
    
    // Remote states from API
    const [remoteOffers, setRemoteOffers] = useState([]);
    const [remoteActiveTrip, setRemoteActiveTrip] = useState(null);
    const [walletData, setWalletData] = useState(null);
    const [loadingAction, setLoadingAction] = useState(false);
    const [errorMessage, setErrorMessage] = useState('');

    // Timer countdown for offer (30 seconds)
    const [countdown, setCountdown] = useState(30);
    const seenOfferIds = useRef(new Set());
    const seenTripIds = useRef(new Set());
    const prevOrderStatusRef = useRef(null);
    const expiringOfferIds = useRef(new Set());

    // Local state (starts empty - loaded from real backend offers)
    const [localOffer, setLocalOffer] = useState(null);
    const [localActiveTrip, setLocalActiveTrip] = useState(null);

    // Fetch live driver data from backend
    const fetchDriverData = async (silent = false) => {
        try {
            // 1. Fetch wallet
            const walletRes = await apiJson('/api/v1/driver/wallet');
            if (walletRes?.data) {
                setWalletData(walletRes.data);
            }

            // 2. Fetch pending offers
            const offersRes = await apiJson('/api/v1/driver/offers');
            const offers = offersRes?.data || [];
            if (offers.length > 0) {
                const mappedOffers = offers.map((o) => ({
                    id: o.id,
                    isReal: true,
                    orderId: o.order_id,
                    merchantName: o.restaurant?.name || 'المطعم',
                    pickupAddress: o.restaurant?.address || 'غزة',
                    dropoffAddress: o.dropoff_address || o.delivery_location?.address || 'غزة',
                    deliveryZone: o.delivery_zone || o.delivery_location?.zone || 'مدينة غزة',
                    distance: o.distance_meters ? `${(o.distance_meters / 1000).toFixed(1)} كم` : '2.5 كم',
                    estimatedPayout: Number(o.fare || 7.00),
                    estimatedTime: '15 - 20 دقيقة',
                    timeLeftSeconds: o.time_left_seconds ?? 30,
                    customerName: o.customer_name || o.delivery_location?.label || 'أحمد خليل',
                    customerPhone: o.delivery_location?.phone || '+970 59 912 3456',
                    itemsSummary: (o.items || []).map((it) => `${it.quantity || 1} × ${it.item_name || it.name}`).join(' • '),
                }));
                setRemoteOffers(mappedOffers);

                // If new offer appeared, play alert sound and push notification
                const newest = mappedOffers[0];
                if (newest && !seenOfferIds.current.has(newest.id)) {
                    seenOfferIds.current.add(newest.id);
                    playNotificationSound();
                    showBrowserNotification('طلب توصيل جديد!', `عرض توصيل جديد إلى ${newest.dropoffAddress} بقيمة ${newest.estimatedPayout} ₪.`);
                    setCountdown(Math.max(0, Math.min(newest.timeLeftSeconds ?? 30, 30)));
                }
            } else {
                setRemoteOffers([]);
            }

            // 3. Fetch active assignments (trips)
            const assignmentsRes = await apiJson('/api/v1/driver/assignments');
            const activeList = (assignmentsRes?.data || []).filter((a) => 
                ['accepted', 'picked_up'].includes(a.status)
            );
            if (activeList.length > 0) {
                const first = activeList[0];
                const currentOrderStatus = first.order_status;
                const prevStatus = prevOrderStatusRef.current;

                if (!seenTripIds.current.has(first.id)) {
                    seenTripIds.current.add(first.id);
                    playNotificationSound();
                    if (first.status === 'picked_up') {
                        showBrowserNotification(
                            'تم تسليم الوجبة لك! 🛵',
                            `استلمت وجبة الطلب ORD-${first.order_id}، توجه مباشرة إلى عنوان الزبون.`
                        );
                    } else if (currentOrderStatus === 'ready_for_pickup') {
                        showBrowserNotification(
                            'الوجبة جاهزة ومعبأة بالمطبخ! 🍽️',
                            `أنهى مطعم ${first.restaurant?.name || 'المطعم'} تحضير الوجبة، وهي بانتظار استلامك.`
                        );
                    }
                } else if (prevStatus && prevStatus !== currentOrderStatus) {
                    if (currentOrderStatus === 'ready_for_pickup') {
                        playNotificationSound();
                        showBrowserNotification(
                            'الوجبة جاهزة ومعبأة بالمطبخ! 🍽️',
                            `أنهى مطعم ${first.restaurant?.name || 'المطعم'} تحضير الوجبة، وهي جاهزة بانتظار استلامك والانطلاق.`
                        );
                    } else if (first.status === 'picked_up' && prevStatus !== 'picked_up') {
                        playNotificationSound();
                        showBrowserNotification(
                            'تم تسليم الوجبة لك! 🛵',
                            'الوجبة الآن بحوزتك، توجه مباشرة إلى عنوان الزبون.'
                        );
                    }
                }
                prevOrderStatusRef.current = currentOrderStatus;

                setRemoteActiveTrip({
                    id: first.id,
                    isReal: true,
                    orderNumber: `ORD-${first.order_id}`,
                    customerName: first.delivery_location?.label || 'زبون',
                    customerPhone: first.delivery_location?.phone || '+970 59 912 3456',
                    merchantName: first.restaurant?.name || 'المطعم',
                    pickupAddress: first.restaurant?.address || 'غزة',
                    dropoffAddress: first.delivery_location?.address || 'غزة',
                    deliveryNotes: first.delivery_location?.delivery_instructions || 'الاتصال عند الوصول',
                    step: first.status === 'picked_up' ? 'to_customer' : 'to_pickup',
                    orderStatus: currentOrderStatus,
                    payout: Number(first.fare || 7.00),
                });
            } else {
                setRemoteActiveTrip(null);
                prevOrderStatusRef.current = null;
            }
        } catch {
            // Keep local fallback
        }
    };

    useEffect(() => {
        requestBrowserNotificationPermission().catch(() => {});
        fetchDriverData();
        const interval = window.setInterval(() => fetchDriverData(true), 4000);
        return () => window.clearInterval(interval);
    }, []);

    // Determine current active trip & offer
    const activeTrip = remoteActiveTrip || parentActiveTrip || localActiveTrip || null;
    const incomingOffer = remoteOffers.length > 0 
        ? remoteOffers[0] 
        : (parentOffer || localOffer || null);

    // Audio Chime Loop: Alert the driver every 4 seconds until the offer is accepted or rejected
    useEffect(() => {
        if (!incomingOffer || activeTrip) {
            return undefined;
        }

        playNotificationSound();
        const soundTimer = window.setInterval(() => {
            playNotificationSound();
        }, 4000);

        return () => window.clearInterval(soundTimer);
    }, [incomingOffer?.id, activeTrip]);

    // Auto-Expire Offer when 30-second window lapses
    const handleExpireOffer = async (offerId) => {
        const targetId = offerId || incomingOffer?.id;
        if (!targetId || expiringOfferIds.current.has(targetId)) return;
        expiringOfferIds.current.add(targetId);

        // Instantly dismiss from UI
        setRemoteOffers((prev) => prev.filter((o) => o.id !== targetId));

        try {
            await apiJson(`/api/v1/driver/offers/${targetId}/expire`, {
                method: 'POST',
            });
        } catch {
            // Ignore if already expired on server
        } finally {
            await fetchDriverData(true);
        }
    };

    // 30-Second Countdown Timer
    useEffect(() => {
        if (!incomingOffer || activeTrip) {
            return undefined;
        }

        const timer = window.setInterval(() => {
            setCountdown((prev) => {
                if (prev <= 1) {
                    window.clearInterval(timer);
                    handleExpireOffer(incomingOffer.id);
                    return 0;
                }
                return prev - 1;
            });
        }, 1000);

        return () => window.clearInterval(timer);
    }, [incomingOffer?.id, activeTrip]);

    // Toggle Online / Offline Availability
    const handleToggleOnline = async () => {
        const nextState = !isOnline;
        setTogglingOnline(true);
        setErrorMessage('');
        try {
            await apiJson('/api/v1/driver/availability', {
                method: 'PUT',
                body: { is_available: nextState },
            });
            setIsOnline(nextState);
        } catch (err) {
            setErrorMessage(err?.data?.message || err?.message || 'تعذر تغيير حالة التوفر.');
            // Toggle local fallback
            setIsOnline(nextState);
        } finally {
            setTogglingOnline(false);
        }
    };

    // Accept Offer Action
    const handleAcceptOffer = async () => {
        if (!incomingOffer) return;
        setLoadingAction(true);
        setErrorMessage('');

        try {
            if (incomingOffer.isReal) {
                await apiJson(`/api/v1/driver/offers/${incomingOffer.id}/accept`, {
                    method: 'POST',
                });
                await fetchDriverData(true);
            } else if (onAcceptParentOffer) {
                onAcceptParentOffer(incomingOffer);
            } else {
                setLocalActiveTrip({
                    id: incomingOffer.id,
                    orderNumber: incomingOffer.orderNumber || `ORD-${incomingOffer.id}`,
                    customerName: incomingOffer.customerName || 'أحمد خليل',
                    customerPhone: '+970 59 912 3456',
                    merchantName: incomingOffer.merchantName,
                    pickupAddress: incomingOffer.pickupAddress,
                    dropoffAddress: incomingOffer.dropoffAddress,
                    deliveryNotes: 'الاتصال فور الوصول',
                    step: 'to_pickup',
                    payout: incomingOffer.estimatedPayout || 8.00
                });
                setLocalOffer(null);
            }
        } catch (err) {
            setErrorMessage(err?.data?.message || err?.message || 'تعذر قبول الطلب.');
        } finally {
            setLoadingAction(false);
        }
    };

    // Decline Offer Action (passes to next driver)
    const handleRejectOffer = async () => {
        if (!incomingOffer) return;
        setLoadingAction(true);
        setErrorMessage('');

        const targetId = incomingOffer.id;
        expiringOfferIds.current.add(targetId);
        setRemoteOffers((prev) => prev.filter((o) => o.id !== targetId));

        try {
            if (incomingOffer.isReal) {
                await apiJson(`/api/v1/driver/offers/${targetId}/reject`, {
                    method: 'POST',
                });
            } else {
                setLocalOffer(null);
            }
        } catch {
            // Handled
        } finally {
            setLoadingAction(false);
            await fetchDriverData(true);
        }
    };

    // Step Progress in Active Trip (Pickup -> Deliver)
    const handleNextTripStep = async () => {
        if (!activeTrip) return;
        setLoadingAction(true);
        setErrorMessage('');

        try {
            if (activeTrip.isReal) {
                if (activeTrip.step === 'to_pickup') {
                    // Mark as Picked Up
                    await apiJson(`/api/v1/driver/assignments/${activeTrip.id}/pickup`, {
                        method: 'POST',
                    });
                    playNotificationSound();
                    await fetchDriverData(true);
                } else if (activeTrip.step === 'to_customer') {
                    // Mark as Delivered
                    await apiJson(`/api/v1/driver/assignments/${activeTrip.id}/deliver`, {
                        method: 'POST',
                    });
                    playNotificationSound();
                    await fetchDriverData(true);
                }
            } else if (onUpdateTripStep) {
                onUpdateTripStep();
            } else {
                if (activeTrip.step === 'to_pickup') {
                    setLocalActiveTrip({ ...activeTrip, step: 'at_merchant' });
                } else if (activeTrip.step === 'at_merchant') {
                    setLocalActiveTrip({ ...activeTrip, step: 'to_customer' });
                } else if (activeTrip.step === 'to_customer') {
                    setLocalActiveTrip(null);
                }
            }
        } catch (err) {
            setErrorMessage(err?.data?.message || err?.message || 'تعذر تحديث مرحلة المشوار.');
        } finally {
            setLoadingAction(false);
        }
    };

    const walletBalance = Number(walletData?.balance ?? 0.00);
    const todayTripsCount = Number(walletData?.today_deliveries_count ?? walletData?.completed_deliveries_count ?? 0);

    return (
        <div className="p-4 md:p-6 space-y-6 max-w-5xl mx-auto w-full font-sans" dir="rtl">
            
            {/* Driver Status & Shift Bar */}
            <div className="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div className="flex items-center gap-4">
                    <div className="w-13 h-13 rounded-2xl bg-amber-50 text-amber-800 flex items-center justify-center font-bold text-xl border border-amber-200 shrink-0">
                        <Bike className="w-6 h-6" />
                    </div>
                    <div>
                        <div className="flex items-center gap-2">
                            <h2 className="text-lg font-bold text-slate-900">{user?.name || 'محمود النجار'}</h2>
                            <span className="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 text-[10px] font-bold border border-emerald-200">
                                كابتن معتمد
                            </span>
                            <span className={`px-2 py-0.5 rounded-full text-[10px] font-bold border ${activeTrip ? 'bg-amber-50 text-amber-800 border-amber-200' : isOnline ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'}`}>
                                {activeTrip ? 'في مشوار نشط (غير متاح)' : isOnline ? 'متاح لاستقبال الطلبات' : 'غير متاح'}
                            </span>
                        </div>
                        <p className="text-xs text-slate-500 mt-0.5">
                            نظام توزيع عادل • الأولوية للسائق الأقل عدداً في التوصيلات المكتملة
                        </p>
                    </div>
                </div>

                {/* Online / Offline Toggle & Sound Test */}
                <div className="flex items-center gap-3">
                    <button
                        type="button"
                        onClick={async () => {
                            await requestBrowserNotificationPermission().catch(() => {});
                            playNotificationSound();
                            showBrowserNotification('🔔 اختبار صوت التنبيهات', 'صوت تنبيه الطلبات يعمل بنجاح، والإشعارات مفعلة!');
                        }}
                        className="px-3 py-2 rounded-xl border border-amber-300 bg-amber-50 text-amber-900 hover:bg-amber-100 transition-colors text-xs font-bold flex items-center gap-1.5 shadow-xs"
                        title="تجربة صوت إشعار الطلبات وتفعيل إشعارات المتصفح"
                    >
                        <span>🔔 اختبار الصوت والإشعارات</span>
                    </button>

                    <button
                        type="button"
                        onClick={() => fetchDriverData()}
                        className="p-2 rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-50 transition-colors"
                        title="تحديث البيانات"
                    >
                        <RefreshCw className="w-4 h-4" />
                    </button>

                    <button
                        onClick={handleToggleOnline}
                        disabled={togglingOnline || Boolean(activeTrip)}
                        className={`flex items-center gap-2 px-4 py-2.5 rounded-2xl font-bold text-xs transition-all shadow-xs ${
                            activeTrip 
                                ? 'bg-amber-100 text-amber-900 border border-amber-300 opacity-90 cursor-not-allowed'
                                : isOnline 
                                    ? 'bg-emerald-600 text-white shadow-emerald-600/20 hover:bg-emerald-700' 
                                    : 'bg-slate-200 text-slate-700 hover:bg-slate-300'
                        }`}
                    >
                        {togglingOnline ? (
                            <Loader2 className="w-4 h-4 animate-spin" />
                        ) : (
                            <Power className="w-4 h-4" />
                        )}
                        <span>
                            {activeTrip 
                                ? 'مشغول بالمشوار (محجوز حتى التسليم)' 
                                : isOnline 
                                    ? 'أنا متاح لاستقبال الطلبات' 
                                    : 'غير متاح (أوفلاين)'}
                        </span>
                    </button>
                </div>
            </div>

            {errorMessage && (
                <div className="p-3 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold rounded-2xl">
                    {errorMessage}
                </div>
            )}

            {/* Quick Wallet Summary */}
            <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div className="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs">
                    <div className="flex items-center justify-between text-slate-500 text-xs mb-1">
                        <span>رصيد المحفظة الحالي</span>
                        <Wallet className="w-4 h-4 text-emerald-600" />
                    </div>
                    <p className="text-2xl font-black text-slate-900">{walletBalance.toFixed(2)} ₪</p>
                    <span className="text-[10px] text-emerald-700 font-semibold">تُضاف الأجرة فور تسليم كل طلب</span>
                </div>

                <div className="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs">
                    <div className="flex items-center justify-between text-slate-500 text-xs mb-1">
                        <span>طلبات تم إنجازها اليوم</span>
                        <CheckCircle2 className="w-4 h-4 text-amber-600" />
                    </div>
                    <p className="text-2xl font-black text-slate-900">{todayTripsCount} مشاوير</p>
                    <span className="text-[10px] text-slate-500">الأولوية للسائق الأقل عدداً في التوصيلات</span>
                </div>

                <div className="col-span-2 md:col-span-1 bg-gradient-to-br from-[#0d3b32] to-[#07241e] text-white rounded-2xl p-4 flex flex-col justify-between shadow-xs">
                    <div>
                        <span className="text-xs text-emerald-200">تحصيل أجرة التوصيل</span>
                        <p className="text-[11px] text-emerald-100/70 mt-0.5">يدفعها الزبون نقداً عند الاستلام</p>
                    </div>
                    <div className="mt-3 py-1.5 px-3 bg-white/10 rounded-xl text-[11px] font-bold text-emerald-200 flex items-center gap-1.5">
                        <CheckCircle className="w-3.5 h-3.5 text-emerald-400" />
                        <span>أجرة ثابتة مسبقة بحسب المنطقة</span>
                    </div>
                </div>
            </div>

            {/* Incoming Offer Modal / Alert Card */}
            {incomingOffer && !activeTrip && (
                <div className="bg-amber-50/95 border-2 border-amber-300 rounded-3xl p-6 shadow-xl animate-in slide-in-from-top duration-300 space-y-4">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <span className="relative flex h-3.5 w-3.5">
                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                <span className="relative inline-flex rounded-full h-3.5 w-3.5 bg-amber-500"></span>
                            </span>
                            <h3 className="font-extrabold text-amber-950 text-base">
                                عرض مشوار توصيل جديد موجه إليك! 🛵
                            </h3>
                        </div>

                        {/* 30 Seconds Countdown */}
                        <div className="flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-amber-200 text-amber-950 font-black text-xs border border-amber-300">
                            <Clock className="w-4 h-4 text-amber-800 animate-spin" />
                            <span>متبقي للقبول: {countdown} ثانية</span>
                        </div>
                    </div>

                    <p className="text-xs text-amber-900">
                        تم ترشيحك وفق معايير التوزيع العادل (أقل السائقين عدداً بالتوصيلات). لديك 30 ثانية للموافقة، وإلا سيتم تمرير الطلب لسائق آخر.
                    </p>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3 bg-white p-4 rounded-2xl border border-amber-200">
                        <div>
                            <span className="text-[10px] text-slate-400 font-bold block mb-0.5">1. استلام الوجبة من المطعم:</span>
                            <p className="font-bold text-slate-900 text-sm flex items-center gap-1.5">
                                <Store className="w-4 h-4 text-emerald-700 shrink-0" />
                                <span>{incomingOffer.merchantName}</span>
                            </p>
                            <p className="text-xs text-slate-600 mt-0.5 flex items-center gap-1">
                                <MapPin className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                <span>{incomingOffer.pickupAddress}</span>
                            </p>
                        </div>

                        <div>
                            <div className="flex items-center justify-between mb-0.5">
                                <span className="text-[10px] text-slate-400 font-bold">2. منطقة وعنوان التسليم للعميل:</span>
                                {incomingOffer.deliveryZone && (
                                    <span className="text-[10px] font-black px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200">
                                        منطقة {incomingOffer.deliveryZone}
                                    </span>
                                )}
                            </div>
                            <p className="font-bold text-rose-950 text-sm flex items-start gap-1.5">
                                <MapPin className="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
                                <span>{incomingOffer.dropoffAddress}</span>
                            </p>
                            <p className="text-xs text-slate-500 mt-1 flex items-center gap-1">
                                <User className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                <span>العميل: <strong className="text-slate-800">{incomingOffer.customerName || 'أحمد خليل'}</strong></span>
                            </p>
                        </div>
                    </div>

                    {incomingOffer.itemsSummary && (
                        <div className="p-3 bg-white rounded-2xl border border-amber-200 text-xs text-slate-700 flex items-center gap-2">
                            <span className="font-bold text-slate-900 shrink-0">🍽️ الوجبات المطلوبة:</span>
                            <span className="text-slate-600 font-medium">{incomingOffer.itemsSummary}</span>
                        </div>
                    )}

                    <div className="flex items-center justify-between pt-2">
                        <div>
                            <span className="text-xs text-slate-600">أجرة التوصيل التي ستحصّلها من الزبون نقداً:</span>
                            <p className="text-2xl font-black text-emerald-800">
                                {Number(incomingOffer.estimatedPayout || 7).toFixed(2)} ₪
                                <span className="text-xs font-normal text-slate-500 mr-2">({incomingOffer.distance || '2.5 كم'})</span>
                            </p>
                        </div>

                        <div className="flex items-center gap-3">
                            <button
                                disabled={loadingAction}
                                onClick={() => handleRejectOffer(false)}
                                className="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-100 font-bold text-xs transition-colors disabled:opacity-50"
                            >
                                رفض
                            </button>
                            <button
                                disabled={loadingAction}
                                onClick={handleAcceptOffer}
                                className="px-7 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md transition-all active:scale-[0.98] flex items-center gap-2 disabled:opacity-50"
                            >
                                {loadingAction ? <Loader2 className="w-4 h-4 animate-spin" /> : <CheckCircle className="w-4 h-4" />}
                                <span>قبول الطلب والبدء فوراً</span>
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Active Delivery Trip Workflow */}
            {activeTrip && (
                <div className="bg-white rounded-3xl p-6 border-2 border-emerald-500/40 shadow-lg space-y-6">
                    <div className="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div>
                            <div className="flex items-center gap-2">
                                <span className="text-[10px] font-bold text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                                    مشوار توصيل جارٍ التنفيذ • #{activeTrip.orderNumber}
                                </span>
                                <span className="text-[10px] font-bold text-rose-700 bg-rose-50 px-2.5 py-0.5 rounded-full border border-rose-200">
                                    حالتك: غير متاح لمشاوير أخرى
                                </span>
                            </div>
                            <h3 className="text-lg font-extrabold text-slate-900 mt-1">
                                {activeTrip.step === 'to_pickup' && 'المرحلة 1: توجه إلى المطعم لاستلام الوجبة'}
                                {activeTrip.step === 'at_merchant' && 'المرحلة 2: في المطعم • بانتظار تجهيز الوجبة'}
                                {activeTrip.step === 'to_customer' && 'المرحلة 3: في الطريق إلى عنوان العميل'}
                            </h3>
                        </div>

                        <div className="text-left">
                            <span className="text-[10px] text-slate-400 block">أجرة المشوار من الزبون:</span>
                            <span className="text-lg font-black text-emerald-800">{activeTrip.payout.toFixed(2)} ₪</span>
                        </div>
                    </div>

                    {/* Step Visualizer */}
                    <div className="grid grid-cols-2 gap-3 text-center text-xs">
                        <div className={`p-3 rounded-2xl font-bold border transition-all ${
                            activeTrip.step === 'to_pickup' 
                                ? 'bg-emerald-50 border-emerald-500 text-emerald-950 shadow-xs' 
                                : 'bg-slate-50 text-slate-400 border-slate-200'
                        }`}>
                            1. التوجه للمطعم واستلام الوجبة
                        </div>
                        <div className={`p-3 rounded-2xl font-bold border transition-all ${
                            activeTrip.step === 'to_customer' 
                                ? 'bg-emerald-50 border-emerald-500 text-emerald-950 shadow-xs' 
                                : 'bg-slate-50 text-slate-400 border-slate-200'
                        }`}>
                            2. الانطلاق وتسليم العميل وتحصيل الأجرة
                        </div>
                    </div>

                    {/* Order Details & Actions */}
                    <div className="bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-3">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs text-slate-500">المطعم المصدر:</p>
                                <p className="font-bold text-slate-900 text-sm flex items-center gap-1.5">
                                    <Store className="w-4 h-4 text-emerald-700" />
                                    <span>{activeTrip.merchantName} ({activeTrip.pickupAddress})</span>
                                </p>
                            </div>
                        </div>

                        <div className="border-t border-slate-200 pt-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <p className="text-xs text-slate-500">عنوان العميل ونقطة اللقاء:</p>
                                <p className="font-bold text-slate-900 text-sm flex items-center gap-1.5">
                                    <MapPin className="w-4 h-4 text-rose-600" />
                                    <span>{activeTrip.customerName} - {activeTrip.dropoffAddress}</span>
                                </p>
                                <p className="text-[11px] text-amber-900 font-medium mt-0.5">
                                    تعليمات التسليم: {activeTrip.deliveryNotes}
                                </p>
                            </div>

                            <a
                                href={`tel:${activeTrip.customerPhone}`}
                                className="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-white border border-emerald-300 rounded-xl text-xs font-bold text-emerald-900 hover:bg-emerald-50 shadow-xs shrink-0"
                            >
                                <Phone className="w-4 h-4 text-emerald-600" />
                                <span>اتصال هاتفي بالعميل ({activeTrip.customerPhone})</span>
                            </a>
                        </div>
                    </div>

                    {/* Step Action Button */}
                    <div className="pt-2">
                        {activeTrip.step === 'to_pickup' && (
                            <button
                                disabled={loadingAction}
                                onClick={handleNextTripStep}
                                className="w-full py-3.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-2xl text-sm transition-all shadow-md flex items-center justify-center gap-2 disabled:opacity-50"
                            >
                                {loadingAction ? <Loader2 className="w-5 h-5 animate-spin" /> : <CheckCircle className="w-5 h-5 text-emerald-200" />}
                                <span>وصلت للمطعم واستلمت الوجبة جاهزة ✓</span>
                            </button>
                        )}

                        {activeTrip.step === 'to_customer' && (
                            <button
                                disabled={loadingAction}
                                onClick={handleNextTripStep}
                                className="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl text-sm transition-all shadow-md flex items-center justify-center gap-2 disabled:opacity-50"
                            >
                                {loadingAction ? <Loader2 className="w-5 h-5 animate-spin" /> : <CheckCircle2 className="w-5 h-5 text-white" />}
                                <span>تم تسليم الطلب للزبون وتحصيل أجرة التوصيل نقداً (+{activeTrip.payout.toFixed(2)} ₪)</span>
                            </button>
                        )}
                    </div>
                </div>
            )}

            {!activeTrip && !incomingOffer && (
                <div className="bg-white rounded-3xl p-10 border border-slate-200/80 shadow-xs text-center space-y-3">
                    <Bike className="w-12 h-12 text-slate-300 mx-auto" />
                    <h3 className="text-base font-bold text-slate-800">
                        {isOnline ? 'الرادار نشط • بانتظار إسناد طلب إليك' : 'أنت الآن في وضع غير متاح (أوفلاين)'}
                    </h3>
                    <p className="text-xs text-slate-500 max-w-md mx-auto">
                        {isOnline 
                            ? 'بمجرد أن يتم تجهيز أي طلب جديد، سيظهر لك إشعار العرض مباشرة مع عداد 30 ثانية.' 
                            : 'قم بتفعيل زر الإتاحة بالأعلى لتتمكن من استقبال طلبات التوصيل.'}
                    </p>
                </div>
            )}
        </div>
    );
}
