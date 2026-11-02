import React, { useState, useEffect } from 'react';
import PaymentReviewPanel from '../shared/PaymentReviewPanel';
import { 
    UtensilsCrossed, 
    Clock, 
    CheckCircle2, 
    ChefHat, 
    Flame, 
    Package, 
    Plus, 
    Check, 
    X, 
    Store, 
    Bike, 
    AlertCircle,
    Phone,
    MapPin,
    FileText,
    Receipt,
    ExternalLink,
    RefreshCw,
    Loader2
} from 'lucide-react';
import { apiJson } from '../../lib/api';

export default function RestaurantPortal({ user, onLogout, sharedOrders, onUpdateOrderStatus }) {
    const [kitchenActive, setKitchenActive] = useState(true);
    const [remoteOrders, setRemoteOrders] = useState([]);
    const [loading, setLoading] = useState(false);
    const [actionLoadingId, setActionLoadingId] = useState(null);
    const [actionError, setActionError] = useState('');
    
    // Live orders list - initialized empty until real orders arrive from API
    const defaultOrders = [];
    const initialMenuItems = [];

    const [menuItems, setMenuItems] = useState(initialMenuItems);
    const [localOrders, setLocalOrders] = useState(defaultOrders);

    const normalizeRemoteOrder = (o) => {
        let kanbanStatus = 'incoming';
        if (o.status === 'cancelled') {
            kanbanStatus = 'cancelled';
        } else if (o.status === 'preparing') {
            kanbanStatus = 'preparing';
        } else if (o.status === 'ready_for_pickup' || o.status === 'assigned') {
            kanbanStatus = 'ready';
        } else if (o.status === 'picked_up' || o.status === 'delivered') {
            kanbanStatus = 'picked_up';
        }

        const payment = o.payment || (o.payment_attempts && o.payment_attempts[0]);
        const driver = o.driver || o.assignment?.driver;

        return {
            id: o.id,
            isReal: true,
            orderNumber: o.invoice_number ? o.invoice_number : `ORD-${o.id}`,
            backendStatus: o.status,
            customerName: o.customer?.name || 'زبون',
            customerPhone: o.delivery_phone || '+970 59 912 3456',
            customerAddress: o.delivery_address || o.delivery_location?.address || 'غزة',
            items: (o.items || []).map((it) => ({
                name: it.item_name || it.name,
                qty: it.quantity || it.qty || 1,
                price: Number(it.unit_price || it.price || 0),
                special: it.notes || it.special,
            })),
            itemsSubtotal: Number(o.items_subtotal || 0),
            deliveryFee: Number(o.delivery_fee || 0),
            total: Number(o.total || 0),
            placedAt: o.placed_at ? new Date(o.placed_at).toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' }) : 'الآن',
            status: kanbanStatus,
            paymentApprovedAt: o.payment_approved_at,
            payment: payment ? {
                status: payment.status,
                method: payment.method,
                proofUrl: payment.proof_url || payment.proof_path,
                transferReference: payment.transfer_reference,
            } : null,
            driver: driver ? {
                name: driver.name,
                phone: driver.phone || '+970 59 888 1234',
            } : null,
            assignmentStatus: o.assignment?.status,
        };
    };

    const fetchOrders = async (silent = false) => {
        if (!silent) setLoading(true);
        try {
            const response = await apiJson('/api/v1/restaurant/orders');
            const data = response?.data || [];
            if (Array.isArray(data)) {
                setRemoteOrders(data.map(normalizeRemoteOrder));
            }
        } catch {
            // Keep local state on API failure
        } finally {
            if (!silent) setLoading(false);
        }
    };

    useEffect(() => {
        fetchOrders();
        const interval = window.setInterval(() => fetchOrders(true), 6000);
        return () => window.clearInterval(interval);
    }, []);

    useEffect(() => {
        apiJson('/api/v1/restaurants', { auth: false })
            .then((res) => {
                const list = res?.data || [];
                const myRest = list.find((r) => r.name === user?.name || r.id === user?.restaurant_id) || list[0];
                if (myRest?.menu_items) {
                    setMenuItems(myRest.menu_items.map((it) => ({
                        id: it.id,
                        name: it.name,
                        price: Number(it.price),
                        category: it.category || 'وجبات',
                        inStock: it.is_available !== false,
                    })));
                }
            })
            .catch(() => {});
    }, [user]);

    // Combine remote and local/shared orders (prioritize real backend orders)
    const orders = remoteOrders.length > 0 ? remoteOrders : (sharedOrders && sharedOrders.length > 0 ? sharedOrders : (localOrders || []));

    const updateOrderStatus = (orderId, nextStatus) => {
        if (onUpdateOrderStatus) {
            onUpdateOrderStatus(orderId, nextStatus);
        } else {
            setLocalOrders(orders.map((o) => o.id === orderId ? { ...o, status: nextStatus } : o));
        }
    };

    const handleApproveAndPrepare = async (order) => {
        setActionLoadingId(order.id);
        setActionError('');
        try {
            if (order.isReal) {
                // If payment is pending bank transfer and has proof, approve payment first
                if (order.payment?.method === 'bank_transfer' && order.payment?.status === 'pending' && order.payment?.proofUrl && !order.paymentApprovedAt) {
                    try {
                        await apiJson(`/api/v1/restaurant/orders/${order.id}/payment-review`, {
                            method: 'POST',
                            body: { decision: 'approved' },
                        });
                    } catch (paymentErr) {
                        const msg = (paymentErr?.data?.message || paymentErr?.message || '').toLowerCase();
                        if (!msg.includes('no pending') && !msg.includes('already')) {
                            throw paymentErr;
                        }
                    }
                }

                // If backend requires restaurant_accepted before preparing:
                if (order.backendStatus === 'placed') {
                    try {
                        await apiJson(`/api/v1/restaurant/orders/${order.id}/status`, {
                            method: 'PATCH',
                            body: { status: 'restaurant_accepted' },
                        });
                    } catch (e) {
                        const msg = (e?.data?.message || e?.message || '').toLowerCase();
                        if (!msg.includes('already') && !msg.includes('out of sequence')) {
                            throw e;
                        }
                    }
                }

                await apiJson(`/api/v1/restaurant/orders/${order.id}/status`, {
                    method: 'PATCH',
                    body: { status: 'preparing' },
                });

                await fetchOrders(true);
            } else {
                updateOrderStatus(order.id, 'preparing');
            }
        } catch (err) {
            setActionError(err?.data?.message || err?.message || 'تعذر تحديث حالة الطلب والبدء بالتحضير.');
        } finally {
            setActionLoadingId(null);
        }
    };

    const handleMarkAsReady = async (order) => {
        setActionLoadingId(order.id);
        setActionError('');
        try {
            if (order.isReal) {
                await apiJson(`/api/v1/restaurant/orders/${order.id}/status`, {
                    method: 'PATCH',
                    body: { status: 'ready_for_pickup' },
                });
                await fetchOrders(true);
            } else {
                updateOrderStatus(order.id, 'ready');
            }
        } catch (err) {
            setActionError(err?.data?.message || err?.message || 'تعذر تجهيز الطلب للسائق.');
        } finally {
            setActionLoadingId(null);
        }
    };

    const handleRequestDriver = async (order) => {
        setActionLoadingId(order.id);
        setActionError('');
        try {
            if (order.isReal) {
                await apiJson(`/api/v1/restaurant/orders/${order.id}/dispatch`, {
                    method: 'POST',
                });
                await fetchOrders(true);
            }
        } catch (err) {
            setActionError(err?.data?.message || err?.message || 'تعذر طلب كابتن توصيل حالياً.');
        } finally {
            setActionLoadingId(null);
        }
    };

    const handleHandoverToDriver = async (order) => {
        setActionLoadingId(order.id);
        setActionError('');
        try {
            if (order.isReal) {
                await apiJson(`/api/v1/restaurant/orders/${order.id}/handover`, {
                    method: 'POST',
                });
                await fetchOrders(true);
            } else {
                updateOrderStatus(order.id, 'picked_up');
            }
        } catch (err) {
            setActionError(err?.data?.message || err?.message || 'تعذر تسليم الطلب للسائق.');
        } finally {
            setActionLoadingId(null);
        }
    };

    const toggleStock = (itemId) => {
        setMenuItems(menuItems.map((m) => m.id === itemId ? { ...m, inStock: !m.inStock } : m));
    };

    const incomingCount = orders.filter((o) => o.status === 'incoming').length;
    const preparingCount = orders.filter((o) => o.status === 'preparing').length;
    const readyCount = orders.filter((o) => o.status === 'ready').length;

    return (
        <div className="p-4 md:p-6 space-y-6 max-w-6xl mx-auto w-full font-sans" dir="rtl">
            
            {/* Restaurant Profile Header */}
            <div className="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div className="flex items-center gap-4">
                    <div className="w-13 h-13 rounded-2xl bg-rose-50 text-rose-700 flex items-center justify-center font-bold text-xl border border-rose-200 shrink-0">
                        <Store className="w-7 h-7" />
                    </div>
                    <div>
                        <div className="flex items-center gap-2">
                            <h2 className="text-lg font-bold text-slate-900">{user?.name || 'مطعم بالميرا'}</h2>
                            <span className="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 text-[10px] font-bold border border-emerald-200">
                                شريك معتمد
                            </span>
                        </div>
                        <p className="text-xs text-slate-500 mt-0.5">
                            بوابة المطبخ • استقبال الطلبات والتحقق من العنوان وتجهيز الوجبات للسائق
                        </p>
                    </div>
                </div>

                <div className="flex items-center gap-3">
                    <button
                        type="button"
                        onClick={() => fetchOrders()}
                        className="p-2 rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-800 transition-colors"
                        title="تحديث الطلبات"
                    >
                        <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
                    </button>

                    <button
                        onClick={() => setKitchenActive(!kitchenActive)}
                        className={`flex items-center gap-2 px-4 py-2 rounded-2xl font-bold text-xs transition-all shadow-xs ${
                            kitchenActive 
                                ? 'bg-emerald-600 text-white shadow-emerald-600/20' 
                                : 'bg-slate-200 text-slate-700'
                        }`}
                    >
                        <ChefHat className="w-4 h-4" />
                        <span>{kitchenActive ? 'المطبخ جاهز ويستقبل الطلبات' : 'المطبخ متوقف مؤقتاً'}</span>
                    </button>
                </div>
            </div>

            {/* Quick Metrics Bar */}
            <div className="grid grid-cols-3 gap-4">
                <div className="bg-white rounded-2xl p-4 border border-amber-200 bg-amber-50/40 shadow-xs">
                    <div className="flex items-center justify-between text-amber-900 text-xs font-semibold mb-1">
                        <span>1. طلبات جديدة واردة</span>
                        <AlertCircle className="w-4 h-4 text-amber-600" />
                    </div>
                    <p className="text-2xl font-black text-amber-950">{incomingCount}</p>
                </div>

                <div className="bg-white rounded-2xl p-4 border border-blue-200 bg-blue-50/40 shadow-xs">
                    <div className="flex items-center justify-between text-blue-900 text-xs font-semibold mb-1">
                        <span>2. قيد التحضير في المطبخ</span>
                        <Flame className="w-4 h-4 text-blue-600" />
                    </div>
                    <p className="text-2xl font-black text-blue-950">{preparingCount}</p>
                </div>

                <div className="bg-white rounded-2xl p-4 border border-emerald-200 bg-emerald-50/40 shadow-xs">
                    <div className="flex items-center justify-between text-emerald-900 text-xs font-semibold mb-1">
                        <span>3. جاهزة بانتظار استلام السائق</span>
                        <Bike className="w-4 h-4 text-emerald-600" />
                    </div>
                    <p className="text-2xl font-black text-emerald-950">{readyCount}</p>
                </div>
            </div>

            {actionError && (
                <div className="p-3 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold rounded-2xl">
                    {actionError}
                </div>
            )}

            {/* Payment Review Panel for Bank Transfers */}
            <PaymentReviewPanel mode="restaurant" onReviewed={() => fetchOrders(true)} />

            {/* Kitchen Orders Kanban View (1. New incoming orders, 2. In preparation, 3. Ready) */}
            <div className="space-y-4">
                <h3 className="font-extrabold text-slate-900 text-base flex items-center gap-2">
                    <ChefHat className="w-5 h-5 text-emerald-700" />
                    <span>لوحة المطبخ (موافقة المطعم وإشعار الزبون ومراحل الطهي):</span>
                </h3>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                    
                    {/* Column 1: 1. New incoming orders (Requires Restaurant Approval) */}
                    <div className="bg-slate-100/80 rounded-2xl p-4 border border-slate-200/80 space-y-3">
                        <div className="flex items-center justify-between pb-2 border-b border-slate-200">
                            <div>
                                <span className="font-bold text-xs text-slate-800 block">1. New incoming orders</span>
                                <span className="text-[11px] text-slate-500">طلبات جديدة واردة</span>
                            </div>
                            <span className="text-xs font-black px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900">
                                {incomingCount}
                            </span>
                        </div>

                        {orders.filter((o) => o.status === 'incoming').map((order) => {
                            const isBankTransfer = order.payment?.method === 'bank_transfer';
                            const isCashOnDelivery = order.payment?.method === 'cash_on_delivery' || !order.payment?.method;
                            const isPendingPayment = isBankTransfer && order.payment?.status === 'pending';
                            const isApprovedPayment = isCashOnDelivery || order.payment?.status === 'succeeded' || Boolean(order.paymentApprovedAt);
                            const hasProof = Boolean(order.payment?.proofUrl);

                            return (
                                <div key={order.id} className="bg-white rounded-2xl p-4 border-2 border-amber-300 shadow-sm space-y-3">
                                    <div className="flex justify-between items-center text-xs">
                                        <span className="font-black text-slate-900">{order.orderNumber}</span>
                                        <span className="text-[10px] text-slate-400">{order.placedAt}</span>
                                    </div>

                                    {/* Customer Details & Detailed Address for Restaurant */}
                                    <div className="p-2.5 bg-slate-50 rounded-xl border border-slate-100 text-xs space-y-1">
                                        <p className="font-bold text-slate-900">{order.customerName}</p>
                                        <p className="text-emerald-700 font-mono text-[11px] flex items-center gap-1" dir="ltr">
                                            <Phone className="w-3 h-3 text-emerald-600" />
                                            <span>{order.customerPhone || '+970 59 912 3456'}</span>
                                        </p>
                                        <p className="text-slate-500 text-[11px] flex items-center gap-1">
                                            <MapPin className="w-3 h-3 text-slate-400 shrink-0" />
                                            <span>{order.customerAddress || 'غزة - الرمال'}</span>
                                        </p>
                                    </div>

                                    {/* Items Breakdown */}
                                    <div className="text-xs text-slate-700 space-y-1.5 bg-amber-50/50 p-2.5 rounded-xl border border-amber-100">
                                        {order.items.map((it, idx) => (
                                            <div key={idx} className="flex justify-between font-medium">
                                                <span>{it.qty} × {it.name}</span>
                                                {it.special && <span className="text-[10px] text-amber-800 font-bold">({it.special})</span>}
                                            </div>
                                        ))}
                                    </div>

                                    {/* Payment Receipt Preview & Status */}
                                    <div className="p-2.5 bg-slate-50 rounded-xl border border-slate-200 text-xs space-y-1.5">
                                        <div className="flex items-center justify-between text-[11px]">
                                            <span className="text-slate-500">حالة إشعار الدفع:</span>
                                            {isApprovedPayment && !isCashOnDelivery ? (
                                                <span className="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 font-bold text-[10px]">
                                                    تم اعتماد التحويل والفاتورة ✓
                                                </span>
                                            ) : isCashOnDelivery ? (
                                                <span className="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 font-bold text-[10px]">
                                                    دفع نقدي عند الاستلام (تحصيل السائق)
                                                </span>
                                            ) : isPendingPayment && hasProof ? (
                                                <span className="px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 font-bold text-[10px]">
                                                    إشعار تحويل مرفوع • بانتظار الاعتماد
                                                </span>
                                            ) : (
                                                <span className="px-2 py-0.5 rounded-md bg-slate-200 text-slate-700 font-bold text-[10px]">
                                                    بانتظار رفع الزبون للإشعار
                                                </span>
                                            )}
                                        </div>

                                        {hasProof && (
                                            <a
                                                href={order.payment.proofUrl}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="w-full inline-flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-lg bg-white border border-amber-300 text-amber-900 text-[11px] font-bold hover:bg-amber-50"
                                            >
                                                <Receipt className="w-3.5 h-3.5 text-amber-600" />
                                                <span>عرض صورة إشعار التحويل المرفقة</span>
                                                <ExternalLink className="w-3 h-3 text-slate-400" />
                                            </a>
                                        )}
                                    </div>

                                    {/* Pricing Breakdown */}
                                    <div className="text-[11px] text-slate-500 space-y-0.5 pt-1 border-t border-slate-100">
                                        <div className="flex justify-between">
                                            <span>قيمة الوجبات:</span>
                                            <span className="font-bold text-slate-800">{order.itemsSubtotal ? order.itemsSubtotal.toFixed(2) : order.total.toFixed(2)} ₪</span>
                                        </div>
                                        <div className="flex justify-between text-emerald-800">
                                            <span>أجرة التوصيل (للسائق):</span>
                                            <span className="font-bold">{order.deliveryFee ? order.deliveryFee.toFixed(2) : '7.00'} ₪</span>
                                        </div>
                                        <div className="flex justify-between text-slate-900 font-black text-xs pt-1 border-t border-slate-200">
                                            <span>إجمالي الطلب:</span>
                                            <span className="text-emerald-800 text-sm">{order.total ? order.total.toFixed(2) : '35.00'} ₪</span>
                                        </div>
                                    </div>

                                    {/* Restaurant Approval & Preparation Button */}
                                    {isBankTransfer && isPendingPayment && !hasProof ? (
                                        <div className="w-full py-2.5 px-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-900 text-center text-xs font-bold flex items-center justify-center gap-1.5">
                                            <Clock className="w-4 h-4 text-amber-700 shrink-0" />
                                            <span>بانتظار رفع الزبون لإشعار التحويل ⏳</span>
                                        </div>
                                    ) : (
                                        <button
                                            type="button"
                                            disabled={actionLoadingId === order.id}
                                            onClick={() => handleApproveAndPrepare(order)}
                                            className="w-full py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl font-bold text-xs transition-colors shadow-sm flex items-center justify-center gap-1.5 active:scale-[0.98] disabled:opacity-50"
                                        >
                                            {actionLoadingId === order.id ? (
                                                <Loader2 className="w-4 h-4 animate-spin text-white" />
                                            ) : (
                                                <Check className="w-4 h-4 text-emerald-200" />
                                            )}
                                            <span>{isApprovedPayment ? 'الموافقة والبدء بالتحضير 🍳' : 'الموافقة على الطلب والبدء بالتحضير'}</span>
                                        </button>
                                    )}
                                </div>
                            );
                        })}

                        {incomingCount === 0 && (
                            <div className="py-10 text-center text-slate-400 space-y-1">
                                <p className="text-3xl font-black text-slate-300">0</p>
                                <p className="text-xs font-bold text-slate-500">No new incoming orders</p>
                                <p className="text-[11px] text-slate-400">لا توجد طلبات جديدة واردة</p>
                            </div>
                        )}
                    </div>

                    {/* Column 2: 2. In preparation (in the kitchen) */}
                    <div className="bg-slate-100/80 rounded-2xl p-4 border border-slate-200/80 space-y-3">
                        <div className="flex items-center justify-between pb-2 border-b border-slate-200">
                            <div>
                                <span className="font-bold text-xs text-slate-800 block">2. In preparation (in the kitchen)</span>
                                <span className="text-[11px] text-slate-500">قيد التحضير في المطبخ</span>
                            </div>
                            <span className="text-xs font-black px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-900">
                                {preparingCount}
                            </span>
                        </div>

                        {orders.filter((o) => o.status === 'preparing').map((order) => (
                            <div key={order.id} className="bg-white rounded-2xl p-4 border border-blue-200 shadow-xs space-y-3">
                                <div className="flex justify-between items-center text-xs">
                                    <span className="font-black text-slate-900">{order.orderNumber}</span>
                                    <span className="text-[10px] text-blue-700 font-bold bg-blue-50 px-2 py-0.5 rounded-md">
                                        تمت الموافقة • جاري الطهي 🔥
                                    </span>
                                </div>
                                <p className="text-xs font-bold text-slate-700">العميل: {order.customerName}</p>

                                <div className="text-xs text-slate-600 space-y-1 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                    {order.items.map((it, idx) => (
                                        <div key={idx} className="flex justify-between">
                                            <span>{it.qty} × {it.name}</span>
                                            {it.special && <span className="text-[10px] text-amber-700">({it.special})</span>}
                                        </div>
                                    ))}
                                </div>

                                <div className="p-2 bg-blue-50/70 rounded-xl text-[11px] text-blue-800 border border-blue-100 flex items-center gap-1.5">
                                    <CheckCircle2 className="w-3.5 h-3.5 text-blue-600 shrink-0" />
                                    <span>تم إشعار العميل بقبول الإشعار وبدء الطهي في المطبخ</span>
                                </div>

                                <button
                                    type="button"
                                    disabled={actionLoadingId === order.id}
                                    onClick={() => handleMarkAsReady(order)}
                                    className="w-full py-2.5 bg-[#0d3b32] hover:bg-[#092923] text-white rounded-xl font-bold text-xs transition-colors shadow-xs flex items-center justify-center gap-1.5 disabled:opacity-50"
                                >
                                    {actionLoadingId === order.id ? (
                                        <Loader2 className="w-4 h-4 animate-spin text-white" />
                                    ) : (
                                        <Package className="w-4 h-4 text-emerald-300" />
                                    )}
                                    <span>الوجبة جاهزة ومعبأة • بانتظار استلام السائق</span>
                                </button>
                            </div>
                        ))}

                        {preparingCount === 0 && (
                            <div className="py-10 text-center text-slate-400 space-y-1">
                                <p className="text-3xl font-black text-slate-300">0</p>
                                <p className="text-xs font-bold text-slate-500">No meals currently being cooked</p>
                                <p className="text-[11px] text-slate-400">لا توجد وجبات قيد التحضير حالياً</p>
                            </div>
                        )}
                    </div>

                    {/* Column 3: 3. Ready (awaiting driver pickup) */}
                    <div className="bg-slate-100/80 rounded-2xl p-4 border border-slate-200/80 space-y-3">
                        <div className="flex items-center justify-between pb-2 border-b border-slate-200">
                            <div>
                                <span className="font-bold text-xs text-slate-800 block">3. Ready (awaiting driver pickup)</span>
                                <span className="text-[11px] text-slate-500">جاهزة بانتظار استلام السائق</span>
                            </div>
                            <span className="text-xs font-black px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-900">
                                {readyCount}
                            </span>
                        </div>

                        {orders.filter((o) => o.status === 'ready').map((order) => {
                            const isDriverConfirmed = order.assignmentStatus === 'accepted' || order.assignmentStatus === 'picked_up';
                            const driver = isDriverConfirmed ? order.driver : null;

                            return (
                                <div key={order.id} className="bg-white rounded-2xl p-4 border border-emerald-300 shadow-xs space-y-3">
                                    <div className="flex justify-between items-center text-xs">
                                        <span className="font-black text-slate-900">{order.orderNumber}</span>
                                        <span className="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md">
                                            معبأة ومجهزة ✓
                                        </span>
                                    </div>
                                    
                                    {driver ? (
                                        <div className="p-3 bg-emerald-50 rounded-xl text-xs text-emerald-900 space-y-1.5 border border-emerald-200">
                                            <div className="flex items-center gap-2">
                                                <Bike className="w-4 h-4 text-emerald-700" />
                                                <span className="font-bold">الكابتن المكلّف: {driver.name}</span>
                                            </div>
                                            <div className="flex items-center justify-between text-[11px]">
                                                <span className="text-slate-500">رقم الهاتف المباشر:</span>
                                                <a 
                                                    href={`tel:${driver.phone.replace(/\s+/g, '')}`} 
                                                    className="font-mono font-bold text-emerald-800 flex items-center gap-1 hover:underline"
                                                    dir="ltr"
                                                >
                                                    <Phone className="w-3 h-3 text-emerald-600" />
                                                    {driver.phone}
                                                </a>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="p-3 bg-amber-50 rounded-xl text-xs text-amber-900 flex flex-col sm:flex-row sm:items-center justify-between gap-2 border border-amber-200">
                                            <div className="flex items-center gap-2">
                                                <Clock className="w-4 h-4 text-amber-700 shrink-0" />
                                                <span>بانتظار قبول السائق (العرض سارٍ)...</span>
                                            </div>
                                            <button
                                                type="button"
                                                disabled={actionLoadingId === order.id}
                                                onClick={() => handleRequestDriver(order)}
                                                className="px-2.5 py-1 bg-amber-700 hover:bg-amber-800 text-white rounded-lg font-bold text-[11px] transition-colors shadow-xs flex items-center justify-center gap-1 shrink-0 disabled:opacity-50"
                                                title="إرسال طلب التوصيل للكابتن المتاح الآن"
                                            >
                                                {actionLoadingId === order.id ? (
                                                    <Loader2 className="w-3.5 h-3.5 animate-spin text-white" />
                                                ) : (
                                                    <Bike className="w-3.5 h-3.5 text-amber-200" />
                                                )}
                                                <span>طلب كابتن توصيل 🛵</span>
                                            </button>
                                        </div>
                                    )}

                                    <button
                                        type="button"
                                        disabled={actionLoadingId === order.id}
                                        onClick={() => handleHandoverToDriver(order)}
                                        className="w-full py-2.5 rounded-xl font-bold text-xs transition-colors shadow-xs flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer active:scale-[0.98] disabled:opacity-60"
                                        title={driver ? 'تسليم الوجبة للكابتن للانطلاق للزبون' : 'تسليم الوجبة وانطلاق التوصيل'}
                                    >
                                        {actionLoadingId === order.id ? (
                                            <Loader2 className="w-4 h-4 animate-spin text-white" />
                                        ) : (
                                            <Bike className="w-4 h-4 text-emerald-200" />
                                        )}
                                        <span>{driver ? `تسليم الوجبة للكابتن (${driver.name}) للانطلاق 🛵` : 'تسليم الوجبة وانطلاق التوصيل 🛵'}</span>
                                    </button>
                                </div>
                            );
                        })}

                        {readyCount === 0 && (
                            <div className="py-10 text-center text-slate-400 space-y-1">
                                <p className="text-3xl font-black text-slate-300">0</p>
                                <p className="text-xs font-bold text-slate-500">No meals awaiting a driver</p>
                                <p className="text-[11px] text-slate-400">لا توجد وجبات بانتظار السائق</p>
                            </div>
                        )}
                    </div>

                </div>
            </div>

            {/* Menu Items Quick Availability Toggle */}
            <div className="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4">
                <div>
                    <h3 className="font-bold text-slate-900 text-base">إتاحة الوجبات اليومية في المطبخ</h3>
                    <p className="text-xs text-slate-500 mt-0.5">
                        يمكن للمطبخ إيقاف الوجبة عند نفاد المكونات مؤقتاً (إضافة وحذف الأصناف والأسعار مسؤولية الإدارة)
                    </p>
                </div>

                <div className="divide-y divide-slate-100">
                    {menuItems.map((item) => (
                        <div key={item.id} className="py-3 flex items-center justify-between">
                            <div>
                                <span className="font-bold text-slate-900 text-xs block">{item.name}</span>
                                <span className="text-[11px] text-emerald-800 font-bold">{item.price.toFixed(2)} ₪</span>
                            </div>

                            <button
                                onClick={() => toggleStock(item.id)}
                                className={`flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-colors ${
                                    item.inStock 
                                        ? 'bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100' 
                                        : 'bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100'
                                }`}
                            >
                                {item.inStock ? 'متوفر بالمطبخ ✓' : 'نفذت الكمية ✕'}
                            </button>
                        </div>
                    ))}
                </div>
            </div>

        </div>
    );
}
