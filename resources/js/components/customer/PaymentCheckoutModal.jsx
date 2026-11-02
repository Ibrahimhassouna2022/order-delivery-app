import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
    Check,
    Copy,
    UploadCloud,
    ReceiptText,
    Clock3,
    CheckCircle2,
    XCircle,
    Loader2,
    Phone,
    MapPin,
    Bike,
    ShieldCheck,
    Landmark,
    WalletCards,
    Calculator,
    Clock,
} from 'lucide-react';
import { apiJson } from '../../lib/api';

function copyText(value) {
    if (value && navigator.clipboard) {
        navigator.clipboard.writeText(value).catch(() => {});
    }
}

export default function PaymentCheckoutModal({ open, order, paymentSettings, onClose, onOpenFareSchedule, onProofUploaded }) {
    const [settings, setSettings] = useState(paymentSettings || null);
    const [orderState, setOrderState] = useState(order);
    const [proof, setProof] = useState(null);
    const [transferReference, setTransferReference] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [uploadedSuccess, setUploadedSuccess] = useState(false);
    const [error, setError] = useState('');
    const [copied, setCopied] = useState('');

    const prevOrderIdRef = useRef(order?.id);
    const fileInputRef = useRef(null);

    useEffect(() => {
        if (!order) {
            return;
        }

        const isNewOrder = prevOrderIdRef.current !== order.id;
        if (isNewOrder) {
            prevOrderIdRef.current = order.id;
            setOrderState(order);
            setProof(null);
            setTransferReference('');
            setError('');
            setUploadedSuccess(false);
            if (fileInputRef.current) {
                fileInputRef.current.value = '';
            }
            return;
        }

        // Same order update from polling or parent re-render: preserve user inputs and uploaded proof!
        setOrderState((current) => {
            if (!current) {
                return order;
            }

            const currentHasProof = Boolean(
                current?.payment?.proof_url ||
                current?.payment?.proof_path ||
                current?.payment_proof_url
            );
            const incomingHasProof = Boolean(
                order?.payment?.proof_url ||
                order?.payment?.proof_path ||
                order?.payment_proof_url
            );

            if (currentHasProof && !incomingHasProof) {
                return {
                    ...order,
                    ...current,
                    payment: {
                        ...(order.payment || {}),
                        ...(current.payment || {}),
                    },
                    payment_proof_url: current.payment_proof_url,
                };
            }

            return { ...current, ...order };
        });
    }, [order]);

    useEffect(() => {
        if (!open || settings) {
            return;
        }

        apiJson('/api/v1/payment-settings')
            .then((response) => setSettings(response?.data || null))
            .catch(() => {});
    }, [open, settings]);

    const paymentStatus = orderState?.payment?.status
        || orderState?.payment_status
        || 'pending';
    const invoiceNumber = orderState?.invoice_number;
    const supportPhone = orderState?.support_phone || orderState?.payment?.support_phone || settings?.support_phone;
    const deliveryZoneName = orderState?.delivery_zone?.name;
    const transferAmount = Number(orderState?.items_subtotal ?? orderState?.payment?.amount ?? 0);
    const deliveryFee = Number(orderState?.delivery_fee ?? orderState?.delivery_zone?.fee ?? 0);
    const total = Number(orderState?.total ?? transferAmount + deliveryFee);

    useEffect(() => {
        if (!open || !orderState?.id) {
            return undefined;
        }

        if (paymentStatus === 'succeeded' || paymentStatus === 'failed') {
            return undefined;
        }

        const timer = window.setInterval(async () => {
            try {
                const response = await apiJson(`/api/v1/orders/${orderState.id}`);
                if (response?.data) {
                    setOrderState((current) => ({ ...current, ...response.data }));
                }
            } catch {
                // Polling is a fallback; the submitted receipt remains saved.
            }
        }, 5000);

        return () => window.clearInterval(timer);
    }, [open, orderState?.id, paymentStatus]);

    const handleCopy = (label, value) => {
        copyText(value);
        setCopied(label);
        window.setTimeout(() => setCopied(''), 1500);
    };

    const handleUpload = async (event) => {
        event.preventDefault();

        if (submitting) {
            return;
        }

        if (!proof) {
            setError('اختر صورة إشعار التحويل أولاً.');
            return;
        }

        setSubmitting(true);
        setError('');

        const formData = new FormData();
        formData.append('proof', proof);
        if (transferReference.trim()) {
            formData.append('transfer_reference', transferReference.trim());
        }

        try {
            const response = await apiJson(`/api/v1/orders/${orderState.id}/payment-proof`, {
                method: 'POST',
                body: formData,
            });
            const updated = response?.data || {};
            setUploadedSuccess(true);
            setOrderState((current) => ({
                ...current,
                ...updated,
                payment: {
                    ...(current?.payment || {}),
                    status: updated.payment_status || current?.payment?.status || 'pending',
                    proof_url: updated.payment_proof_url || current?.payment?.proof_url,
                },
                payment_proof_url: updated.payment_proof_url || current?.payment_proof_url,
                payment_status: updated.payment_status || current?.payment_status || 'pending',
            }));
            setProof(null);
            setTransferReference('');
            if (fileInputRef.current) {
                fileInputRef.current.value = '';
            }
            onProofUploaded?.(updated);
        } catch (requestError) {
            setError(requestError?.data?.message || requestError?.message || 'تعذر رفع صورة الإشعار.');
        } finally {
            setSubmitting(false);
        }
    };

    const canResubmit = paymentStatus === 'failed';
    const isWaiting = paymentStatus === 'pending';
    const isApproved = paymentStatus === 'succeeded';
    const hasProof = uploadedSuccess || Boolean(
        orderState?.payment?.proof_url ||
        orderState?.payment?.proof_path ||
        orderState?.payment_proof_url
    );
    const isProofAwaitingReview = isWaiting && hasProof;
    const needsProofUpload = (isWaiting && !hasProof) || canResubmit;

    const statusLabel = useMemo(() => {
        if (isApproved) {
            return 'تم اعتماد التحويل والفاتورة ✓';
        }

        if (paymentStatus === 'failed') {
            return 'تم رفض إشعار التحويل ✗';
        }

        if (hasProof) {
            return 'تم إرسال الإشعار • بانتظار الموافقة ⏳';
        }

        return 'بانتظار رفع صورة الإشعار';
    }, [isApproved, paymentStatus, hasProof]);

    if (!open || !orderState) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm" dir="rtl">
            <div className="bg-white rounded-3xl max-w-xl w-full shadow-2xl border border-slate-100 overflow-hidden max-h-[92vh] flex flex-col">
                <div className="p-5 bg-[#0d3b32] text-white">
                    <div className="flex items-center justify-between gap-3">
                        <div className="flex items-center gap-3">
                            <div className="w-11 h-11 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center">
                                <ReceiptText className="w-5 h-5 text-emerald-200" />
                            </div>
                            <div>
                                <h3 className="font-bold text-base">تأكيد الدفع بالتحويل</h3>
                                <p className="text-[11px] text-emerald-100/80 mt-0.5">
                                    طلب #{orderState.id} • {deliveryZoneName || 'استلام من المطعم'}
                                </p>
                            </div>
                        </div>
                        <span className={`text-[10px] font-bold px-2.5 py-1 rounded-full border ${
                            isApproved
                                ? 'bg-emerald-400/20 border-emerald-300/40 text-emerald-100'
                                : paymentStatus === 'failed'
                                    ? 'bg-rose-400/20 border-rose-300/40 text-rose-100'
                                    : 'bg-amber-400/20 border-amber-300/40 text-amber-100'
                        }`}>
                            {statusLabel}
                        </span>
                    </div>
                </div>

                <div className="flex-1 overflow-y-auto p-5 space-y-4">
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                        <div className="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                            <span className="text-slate-500 block">المبلغ المطلوب تحويله</span>
                            <strong className="text-slate-900 text-lg">{transferAmount.toFixed(2)} ₪</strong>
                            <span className="text-[10px] text-slate-500 block mt-1">قيمة الطلب فقط</span>
                        </div>
                        <div className="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                            <span className="text-slate-500 block">أجرة التوصيل</span>
                            <strong className="text-slate-900 text-lg">{deliveryFee.toFixed(2)} ₪</strong>
                            <span className="text-[10px] text-slate-500 block mt-1">تُدفع للسائق عند التسليم</span>
                        </div>
                        <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-3">
                            <span className="text-emerald-700 block">إجمالي الفاتورة</span>
                            <strong className="text-emerald-900 text-lg">{total.toFixed(2)} ₪</strong>
                            <span className="text-[10px] text-emerald-700 block mt-1">شامل التوصيل</span>
                        </div>
                    </div>

                    {isApproved ? (
                        <div className="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 space-y-3">
                            <div className="flex items-center gap-2 text-emerald-900 font-bold">
                                <CheckCircle2 className="w-6 h-6" />
                                تمت الموافقة على التحويل وإصدار الفاتورة
                            </div>
                            <div className="rounded-2xl bg-white border border-emerald-200 p-3 flex items-center justify-between gap-3">
                                <div>
                                    <span className="text-[11px] text-slate-500 block">رقم الفاتورة</span>
                                    <strong className="font-mono text-sm text-slate-900">{invoiceNumber || '—'}</strong>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => handleCopy('invoice', invoiceNumber)}
                                    className="p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50"
                                    title="نسخ رقم الفاتورة"
                                >
                                    {copied === 'invoice' ? <Check className="w-4 h-4 text-emerald-600" /> : <Copy className="w-4 h-4" />}
                                </button>
                            </div>
                            <p className="text-xs text-emerald-900/80 leading-relaxed">
                                سيتم توصيل طلبك إلى: <strong>{orderState.delivery_address || deliveryZoneName || 'استلام من المطعم'}</strong>.
                                يمكنك التواصل مع الدعم لتتبع طلبك على الرقم <strong dir="ltr">{supportPhone || '—'}</strong>.
                            </p>
                        </div>
                    ) : (
                        <>
                            <div className="rounded-3xl border border-slate-200 p-4 space-y-3">
                                <div className="flex items-center gap-2 text-slate-900 font-bold text-sm">
                                    <Landmark className="w-4 h-4 text-emerald-700" />
                                    بيانات حساب التحويل
                                </div>

                                <div className="space-y-2">
                                    <div className="flex items-center justify-between gap-2 rounded-2xl bg-slate-50 border border-slate-200 p-3">
                                        <div>
                                            <span className="text-[10px] text-slate-500 block">رقم المحفظة / الهاتف</span>
                                            <strong className="font-mono text-xs text-slate-900" dir="ltr">{settings?.phone || '—'}</strong>
                                        </div>
                                        <button type="button" onClick={() => handleCopy('phone', settings?.phone)} className="p-2 rounded-xl bg-white border border-slate-200 text-slate-600">
                                            {copied === 'phone' ? <Check className="w-4 h-4 text-emerald-600" /> : <Copy className="w-4 h-4" />}
                                        </button>
                                    </div>

                                    <div className="flex items-center justify-between gap-2 rounded-2xl bg-slate-50 border border-slate-200 p-3">
                                        <div className="min-w-0">
                                            <span className="text-[10px] text-slate-500 block">رقم الآيبان IBAN</span>
                                            <strong className="font-mono text-xs text-slate-900 break-all" dir="ltr">{settings?.iban || '—'}</strong>
                                        </div>
                                        <button type="button" onClick={() => handleCopy('iban', settings?.iban)} className="p-2 rounded-xl bg-white border border-slate-200 text-slate-600 shrink-0">
                                            {copied === 'iban' ? <Check className="w-4 h-4 text-emerald-600" /> : <Copy className="w-4 h-4" />}
                                        </button>
                                    </div>

                                    <div className="rounded-2xl bg-slate-50 border border-slate-200 p-3">
                                        <span className="text-[10px] text-slate-500 block">اسم الحساب</span>
                                        <strong className="text-xs text-slate-900">{settings?.account_name || '—'}</strong>
                                    </div>
                                </div>

                                <p className="text-[11px] text-slate-500 leading-relaxed">
                                    {settings?.instructions || 'حوّل قيمة الطلب ثم ارفع صورة إشعار التحويل. لا تشمل الحوالة أجرة التوصيل.'}
                                </p>
                            </div>

                            {/* Case 1: Proof is uploaded and awaiting restaurant/admin review */}
                            {isProofAwaitingReview && (
                                <div className="rounded-3xl border-2 border-emerald-300 bg-emerald-50/70 p-5 text-center space-y-4 animate-in fade-in duration-300">
                                    <div className="w-12 h-12 rounded-2xl bg-emerald-100 border border-emerald-200 text-emerald-700 mx-auto flex items-center justify-center shadow-xs">
                                        <CheckCircle2 className="w-7 h-7" />
                                    </div>

                                    <div className="space-y-1">
                                        <h4 className="font-extrabold text-slate-900 text-base">تم إرسال إشعار التحويل بنجاح!</h4>
                                        <p className="text-xs text-slate-600 leading-relaxed max-w-md mx-auto">
                                            تم استلام صورة الإشعار وهي قيد المراجعة والتدقيق لدى المتجر الآن. سيتم اعتماد الفاتورة والبدء بالطهي فور التأكد من التحويل.
                                        </p>
                                    </div>

                                    <div className="p-3 bg-white rounded-2xl border border-emerald-200 text-xs text-emerald-950 flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-2xs">
                                        <div className="flex items-center gap-2">
                                            <Clock3 className="w-4 h-4 text-emerald-600 shrink-0" />
                                            <span className="font-bold">الحالة: بانتظار موافقة واعتماد المطعم ⏳</span>
                                        </div>
                                        {(orderState?.payment?.proof_url || orderState?.payment_proof_url) && (
                                            <a
                                                href={orderState.payment?.proof_url || orderState.payment_proof_url}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="inline-flex items-center gap-1 font-bold text-emerald-700 hover:text-emerald-800 text-[11px] underline"
                                            >
                                                <span>معاينة صورة الإشعار المرفوعة</span>
                                            </a>
                                        )}
                                    </div>

                                    <button
                                        type="button"
                                        disabled={true}
                                        className="w-full py-2.5 rounded-xl bg-slate-200/80 border border-slate-300 text-slate-500 text-xs font-bold flex items-center justify-center gap-2 cursor-not-allowed shadow-none"
                                    >
                                        <Check className="w-4 h-4 text-emerald-600" />
                                        <span>تم إرسال الإشعار • الزر معطل بانتظار الموافقة ✓</span>
                                    </button>
                                </div>
                            )}

                            {/* Case 2: Customer needs to upload proof (First time or Re-upload after rejection) */}
                            {needsProofUpload && (
                                <form onSubmit={handleUpload} className="rounded-3xl border border-slate-200 p-4 space-y-3">
                                    <div className="flex items-center gap-2 text-slate-900 font-bold text-sm">
                                        <UploadCloud className="w-4 h-4 text-emerald-700" />
                                        {canResubmit ? 'إعادة رفع صورة الإشعار بعد الرفض' : 'رفع صورة إشعار التحويل'}
                                    </div>

                                    {paymentStatus === 'failed' && (
                                        <div className="rounded-2xl bg-rose-50 border border-rose-200 p-3 text-xs text-rose-800">
                                            <div className="flex items-center gap-1.5 font-bold mb-1">
                                                <XCircle className="w-4 h-4" />
                                                تم رفض الإشعار السابق
                                            </div>
                                            {orderState.payment?.rejection_reason || orderState.payment_rejection_reason || 'يرجى مراجعة المبلغ وإعادة الرفع.'}
                                        </div>
                                    )}

                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp"
                                        onChange={(event) => {
                                            const file = event.target.files?.[0] || null;
                                            setProof(file);
                                            if (file) {
                                                setError('');
                                            }
                                        }}
                                        className="w-full text-xs rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-3 file:mr-3 file:rounded-xl file:border-0 file:bg-emerald-700 file:px-3 file:py-1.5 file:text-white file:text-xs file:font-bold cursor-pointer hover:border-emerald-500 transition-colors"
                                    />

                                    {proof && (
                                        <div className="p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-900 flex items-center justify-between animate-in fade-in duration-200">
                                            <div className="flex items-center gap-2 truncate">
                                                <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
                                                <span className="font-bold truncate">{proof.name}</span>
                                            </div>
                                            <span className="text-[10px] text-emerald-700 font-bold shrink-0 bg-white/70 px-2 py-0.5 rounded-lg border border-emerald-200">
                                                {(proof.size / 1024).toFixed(0)} KB • جاهز للإرسال
                                            </span>
                                        </div>
                                    )}

                                    <input
                                        type="text"
                                        value={transferReference}
                                        onChange={(event) => setTransferReference(event.target.value)}
                                        placeholder="رقم عملية التحويل (اختياري)"
                                        className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs text-slate-800"
                                    />

                                    {error && (
                                        <div className="rounded-xl bg-rose-50 border border-rose-200 p-2.5 text-[11px] text-rose-800 font-semibold">
                                            {error}
                                        </div>
                                    )}

                                    <button
                                        type="submit"
                                        disabled={submitting || !proof}
                                        className={`w-full py-3 rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition-all ${
                                            !proof || submitting
                                                ? 'bg-slate-200 text-slate-400 cursor-not-allowed shadow-none border border-slate-300'
                                                : 'bg-[#0d3b32] hover:bg-[#092a24] text-white cursor-pointer shadow-md active:scale-[0.99]'
                                        }`}
                                    >
                                        {submitting ? (
                                            <>
                                                <Loader2 className="w-4 h-4 animate-spin text-emerald-300" />
                                                <span>جارٍ رفع وإرسال الإشعار...</span>
                                            </>
                                        ) : (
                                            <>
                                                <UploadCloud className="w-4 h-4" />
                                                <span>{proof ? 'إرسال إشعار التحويل للمراجعة الآن ↗' : 'اختر صورة الإشعار أولاً لتفعيل الإرسال'}</span>
                                            </>
                                        )}
                                    </button>
                                </form>
                            )}
                        </>
                    )}

                    <div className="rounded-2xl bg-slate-50 border border-slate-200 p-3 text-[11px] text-slate-600 space-y-2">
                        <div className="flex items-center gap-2">
                            <MapPin className="w-4 h-4 text-slate-400" />
                            <span>عنوان التوصيل: <strong>{orderState.delivery_address || 'استلام من المطعم'}</strong></span>
                        </div>
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div className="flex items-center gap-2">
                                <Bike className="w-4 h-4 text-emerald-600 shrink-0" />
                                <span>
                                    أجرة التوصيل <strong>{deliveryFee.toFixed(2)} ₪</strong> تُدفع نقداً للسائق عند استلام الوجبة وفق جدول التسعيرة المعتمد لمنطقتك.
                                </span>
                            </div>
                            {onOpenFareSchedule && (
                                <button
                                    type="button"
                                    onClick={onOpenFareSchedule}
                                    className="text-[10px] font-bold text-emerald-800 hover:text-emerald-950 bg-emerald-50 px-2 py-1 rounded-lg border border-emerald-200 shrink-0 flex items-center gap-1 self-start sm:self-auto"
                                >
                                    <Calculator className="w-3 h-3 text-emerald-600" />
                                    <span>جدول التسعيرة والوقت 🛵</span>
                                </button>
                            )}
                        </div>
                        <div className="flex items-center gap-2">
                            <Phone className="w-4 h-4 text-slate-400" />
                            <span>رقم التواصل لتتبع الطلب: <strong dir="ltr">{supportPhone || '—'}</strong></span>
                        </div>
                    </div>
                </div>

                <div className="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between gap-3">
                    <div className="flex items-center gap-2 text-[11px] text-slate-500">
                        <ShieldCheck className="w-4 h-4 text-emerald-600" />
                        {isApproved ? 'الفاتورة جاهزة وطلبك قيد التنفيذ.' : 'يمكنك إغلاق النافذة والعودة لمتابعة الطلب لاحقاً.'}
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-100"
                    >
                        {isApproved ? 'متابعة الطلب' : 'إغلاق'}
                    </button>
                </div>
            </div>
        </div>
    );
}
