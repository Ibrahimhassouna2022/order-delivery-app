import React, { useEffect, useRef, useState } from 'react';
import {
    Bell,
    CheckCircle2,
    XCircle,
    ExternalLink,
    MapPin,
    Phone,
    Receipt,
    RefreshCw,
    ShieldCheck,
    Loader2,
} from 'lucide-react';
import { apiJson } from '../../lib/api';
import {
    playNotificationSound,
    requestBrowserNotificationPermission,
    showBrowserNotification,
    unlockNotificationSound,
} from '../../lib/notifications';

const endpointForMode = (mode) => (
    mode === 'operations'
        ? '/api/v1/operations/payment-reviews'
        : '/api/v1/restaurant/payment-reviews'
);

const reviewEndpointForMode = (mode, orderId) => (
    mode === 'operations'
        ? `/api/v1/operations/orders/${orderId}/payment-review`
        : `/api/v1/restaurant/orders/${orderId}/payment-review`
);

export default function PaymentReviewPanel({ mode = 'restaurant', onReviewed }) {
    const [orders, setOrders] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [processingId, setProcessingId] = useState(null);
    const [rejectingId, setRejectingId] = useState(null);
    const [rejectionReason, setRejectionReason] = useState('');
    const seenOrders = useRef(null);

    const loadReviews = async ({ silent = false } = {}) => {
        if (!silent) {
            setLoading(true);
        }

        try {
            const response = await apiJson(endpointForMode(mode));
            const incoming = response?.data || [];
            const keys = new Set(incoming.map((order) => (
                `${order.id}:${order.payment?.proof_url || 'no-proof'}:${order.payment?.status || 'pending'}`
            )));

            if (seenOrders.current !== null) {
                const fresh = incoming.filter((order) => !seenOrders.current.has(
                    `${order.id}:${order.payment?.proof_url || 'no-proof'}:${order.payment?.status || 'pending'}`,
                ));
                if (fresh.length > 0) {
                    playNotificationSound();
                    showBrowserNotification(
                        'إشعار تحويل جديد',
                        `يوجد ${fresh.length} إشعار تحويل بانتظار مراجعتك.`,
                    );
                }
            }

            seenOrders.current = keys;
            setOrders(incoming);
            setError('');
        } catch (requestError) {
            setError(requestError?.data?.message || requestError?.message || 'تعذر تحميل المدفوعات المعلقة.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        const unlock = () => {
            unlockNotificationSound();
            requestBrowserNotificationPermission();
        };

        document.addEventListener('click', unlock, { once: true });
        loadReviews();

        const timer = window.setInterval(() => loadReviews({ silent: true }), 10000);

        return () => {
            document.removeEventListener('click', unlock);
            window.clearInterval(timer);
        };
    }, [mode]);

    const review = async (order, decision, reason = null) => {
        setProcessingId(order.id);
        setError('');

        try {
            await apiJson(reviewEndpointForMode(mode, order.id), {
                method: 'POST',
                body: {
                    decision,
                    rejection_reason: reason,
                },
            });

            setOrders((current) => current.filter((item) => item.id !== order.id));
            setRejectingId(null);
            setRejectionReason('');
            playNotificationSound();
            onReviewed?.(order, decision, reason);
        } catch (requestError) {
            setError(requestError?.data?.message || requestError?.message || 'تعذر مراجعة التحويل.');
        } finally {
            setProcessingId(null);
        }
    };

    return (
        <section className="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden" dir="rtl">
            <div className="p-5 border-b border-slate-100 flex items-center justify-between gap-3 bg-slate-50/60">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-2xl bg-amber-50 border border-amber-200 text-amber-700 flex items-center justify-center">
                        <Bell className="w-5 h-5" />
                    </div>
                    <div>
                        <h3 className="font-bold text-slate-900 text-sm">إشعارات التحويل بانتظار المراجعة</h3>
                        <p className="text-[11px] text-slate-500 mt-0.5">
                            يصل إشعار صوتي عند رفع الزبون صورة جديدة. راجع المبلغ قبل اعتماد الطلب.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    onClick={() => loadReviews()}
                    className="p-2 rounded-xl border border-slate-200 text-slate-500 hover:bg-white hover:text-slate-800 transition-colors"
                    title="تحديث"
                >
                    <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
                </button>
            </div>

            <div className="p-4 space-y-3">
                {error && (
                    <div className="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">
                        {error}
                    </div>
                )}

                {loading && orders.length === 0 ? (
                    <div className="py-8 flex items-center justify-center gap-2 text-xs text-slate-500">
                        <Loader2 className="w-4 h-4 animate-spin" />
                        جارٍ تحميل إشعارات التحويل...
                    </div>
                ) : orders.length === 0 ? (
                    <div className="py-7 text-center text-xs text-slate-500">
                        <ShieldCheck className="w-8 h-8 text-emerald-500 mx-auto mb-2" />
                        لا توجد تحويلات معلقة حالياً.
                    </div>
                ) : (
                    orders.map((order) => (
                        <article key={order.id} className="rounded-2xl border border-amber-200/70 bg-amber-50/30 p-4 space-y-3">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <h4 className="font-bold text-slate-900 text-sm">
                                        طلب #{order.id} — {order.customer?.name || 'زبون'}
                                    </h4>
                                    <p className="text-[11px] text-slate-500 mt-1">
                                        {order.restaurant?.name} • {order.payment?.method === 'bank_transfer' ? 'تحويل بنكي' : order.payment?.method}
                                    </p>
                                </div>
                                <span className="text-[10px] font-bold text-amber-800 bg-amber-100 border border-amber-200 px-2 py-1 rounded-full">
                                    بانتظار الاعتماد
                                </span>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-2 text-[11px]">
                                <div className="bg-white rounded-xl border border-amber-100 p-2.5">
                                    <span className="text-slate-500 block">المبلغ المحوّل المطلوب</span>
                                    <strong className="text-slate-900 text-sm">
                                        {Number(order.payment?.amount || 0).toFixed(2)} ₪
                                    </strong>
                                </div>
                                <div className="bg-white rounded-xl border border-amber-100 p-2.5">
                                    <span className="text-slate-500 block">أجرة التوصيل عند التسليم</span>
                                    <strong className="text-slate-900 text-sm">
                                        {Number(order.delivery_fee || 0).toFixed(2)} ₪
                                    </strong>
                                </div>
                                <div className="bg-white rounded-xl border border-amber-100 p-2.5">
                                    <span className="text-slate-500 block">الإجمالي</span>
                                    <strong className="text-emerald-800 text-sm">
                                        {Number(order.total || 0).toFixed(2)} ₪
                                    </strong>
                                </div>
                            </div>

                            <div className="flex flex-wrap items-center gap-3 text-[11px] text-slate-600">
                                <span className="flex items-center gap-1">
                                    <MapPin className="w-3.5 h-3.5" />
                                    {order.delivery_zone?.name || 'استلام من المطعم'} — {order.delivery_address || '—'}
                                </span>
                                <span className="flex items-center gap-1">
                                    <Phone className="w-3.5 h-3.5" />
                                    {order.delivery_phone || '—'}
                                </span>
                            </div>

                            {order.payment?.proof_url && (
                                <a
                                    href={order.payment.proof_url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-white border border-amber-200 text-amber-900 text-xs font-bold hover:bg-amber-50"
                                >
                                    <Receipt className="w-4 h-4" />
                                    عرض صورة إشعار التحويل
                                    <ExternalLink className="w-3.5 h-3.5" />
                                </a>
                            )}

                            {rejectingId === order.id ? (
                                <div className="space-y-2">
                                    <textarea
                                        value={rejectionReason}
                                        onChange={(event) => setRejectionReason(event.target.value)}
                                        placeholder="سبب رفض التحويل (سيظهر للزبون)..."
                                        rows={2}
                                        className="w-full px-3 py-2 rounded-xl border border-rose-200 bg-white text-xs text-slate-800"
                                    />
                                    <div className="flex gap-2">
                                        <button
                                            type="button"
                                            onClick={() => review(order, 'rejected', rejectionReason)}
                                            disabled={!rejectionReason.trim() || processingId === order.id}
                                            className="px-3 py-2 rounded-xl bg-rose-600 text-white text-xs font-bold disabled:opacity-50"
                                        >
                                            تأكيد الرفض
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setRejectingId(null);
                                                setRejectionReason('');
                                            }}
                                            className="px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-600 text-xs font-bold"
                                        >
                                            إلغاء
                                        </button>
                                    </div>
                                </div>
                            ) : (
                                <div className="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        onClick={() => review(order, 'approved')}
                                        disabled={processingId === order.id}
                                        className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-700 text-white text-xs font-bold hover:bg-emerald-800 disabled:opacity-50"
                                    >
                                        {processingId === order.id ? (
                                            <Loader2 className="w-4 h-4 animate-spin" />
                                        ) : (
                                            <CheckCircle2 className="w-4 h-4" />
                                        )}
                                        اعتماد التحويل وإصدار الفاتورة
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setRejectingId(order.id)}
                                        className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-rose-200 text-rose-700 text-xs font-bold hover:bg-rose-50"
                                    >
                                        <XCircle className="w-4 h-4" />
                                        رفض
                                    </button>
                                </div>
                            )}
                        </article>
                    ))
                )}
            </div>
        </section>
    );
}
