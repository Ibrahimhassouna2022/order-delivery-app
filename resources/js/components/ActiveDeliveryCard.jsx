import React from 'react';
import { 
    Clock, 
    Phone, 
    MessageSquare, 
    ChevronRight, 
    ShieldCheck, 
    Bike, 
    CheckCircle2, 
    ArrowRight,
    MapPin,
    AlertCircle
} from 'lucide-react';

export default function ActiveDeliveryCard({ order, onSelectOrder }) {
    if (!order) {
        return (
            <div className="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm text-center py-12">
                <AlertCircle className="w-10 h-10 text-slate-300 mx-auto mb-3" />
                <h3 className="font-semibold text-slate-800 text-sm">No Active Deliveries</h3>
                <p className="text-xs text-slate-500 mt-1">Ready to ship? Click "Send a delivery" above.</p>
            </div>
        );
    }

    return (
        <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden transition-all hover:shadow-md">
            {/* Card Header */}
            <div className="p-5 border-b border-slate-100 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <span className="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 font-bold">
                        <Bike className="w-4 h-4 text-emerald-600" />
                    </span>
                    <div>
                        <div className="flex items-center gap-2">
                            <h3 className="font-bold text-slate-900 text-sm">Active Delivery</h3>
                            <span className="px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wide uppercase bg-emerald-100 text-emerald-800">
                                {order.status}
                            </span>
                        </div>
                        <p className="text-xs text-slate-400">Tracking ID: #{order.trackingNumber}</p>
                    </div>
                </div>

                <div className="flex items-center gap-2">
                    <div className="flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-50 border border-amber-200/60 text-amber-800 text-xs font-semibold">
                        <Clock className="w-3.5 h-3.5 text-amber-600" />
                        <span>ETA: {order.eta}</span>
                    </div>
                    <button 
                        onClick={() => onSelectOrder(order)}
                        className="text-xs font-semibold text-emerald-700 hover:text-emerald-900 flex items-center gap-1 transition-colors px-2 py-1 rounded-lg hover:bg-emerald-50"
                    >
                        <span>View details</span>
                        <ChevronRight className="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>

            {/* Landmark & Meeting Point Banner (Gaza Low-Data Mode) */}
            <div className="mx-5 my-4 p-4 rounded-2xl bg-amber-50/80 border border-amber-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div className="flex items-center gap-3">
                    <div className="w-9 h-9 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center shrink-0">
                        <MapPin className="w-4 h-4" />
                    </div>
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="text-[10px] font-bold uppercase tracking-wider text-amber-800">
                                Landmark Meeting Point • نقطة الالتقاء بالمعلم
                            </span>
                            <span className="px-1.5 py-0.5 rounded bg-amber-200/60 text-amber-900 text-[9px] font-bold">
                                No GPS Needed
                            </span>
                        </div>
                        <p className="text-xs font-bold text-slate-800 mt-0.5">
                            {order.dropoffAddress || 'Gaza - Saraya Junction (Opposite Al-Shurooq Tower)'}
                        </p>
                    </div>
                </div>

                <div className="flex items-center gap-2 shrink-0">
                    <a
                        href={`tel:${(order.driverPhone || '+970599123456').replace(/\s+/g, '')}`}
                        className="px-3 py-1.5 rounded-xl bg-[#0d3b32] hover:bg-[#092923] text-white text-xs font-bold flex items-center gap-1.5 shadow-xs"
                    >
                        <Phone className="w-3.5 h-3.5 text-emerald-300" />
                        <span>اتصال بالكابتن</span>
                    </a>
                    <a
                        href={`sms:${(order.driverPhone || '+970599123456').replace(/\s+/g, '')}?body=${encodeURIComponent(`مرحباً كابتن، بخصوص طلبي #${order.orderNumber}، أنا في انتظارك عند المعلم: ${order.dropoffAddress}`)}`}
                        className="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold flex items-center gap-1.5 shadow-xs"
                    >
                        <MessageSquare className="w-3.5 h-3.5 text-emerald-300" />
                        <span>SMS</span>
                    </a>
                </div>
            </div>

            {/* Route Details & Driver Info */}
            <div className="p-5 pt-1 grid grid-cols-1 md:grid-cols-2 gap-6">
                {/* Route Points */}
                <div className="space-y-4">
                    <div className="relative pl-6 before:content-[''] before:absolute before:left-2 before:top-3 before:bottom-0 before:w-0.5 before:bg-slate-200">
                        {/* Pickup */}
                        <div className="relative pb-4">
                            <span className="absolute -left-6 top-1 w-4 h-4 rounded-full border-2 border-emerald-500 bg-white flex items-center justify-center">
                                <span className="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            </span>
                            <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Pickup Restaurant</span>
                            <p className="text-xs font-semibold text-slate-800">{order.pickupAddress}</p>
                            <p className="text-[11px] text-slate-400">{order.restaurantName}</p>
                        </div>

                        {/* Dropoff */}
                        <div className="relative">
                            <span className="absolute -left-6 top-1 w-4 h-4 rounded-full border-2 border-rose-500 bg-white flex items-center justify-center">
                                <span className="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                            </span>
                            <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Destination Landmark</span>
                            <p className="text-xs font-semibold text-slate-800">{order.dropoffAddress}</p>
                            <p className="text-[11px] text-slate-400">Handover to client directly</p>
                        </div>
                    </div>
                </div>

                {/* Driver Profile & Contact Actions */}
                <div className="bg-slate-50/80 rounded-xl p-4 border border-slate-100 flex flex-col justify-between">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="relative">
                                <img
                                    src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80"
                                    alt="Driver avatar"
                                    className="w-10 h-10 rounded-full object-cover ring-2 ring-emerald-500/40"
                                />
                                <span className="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[9px] font-bold">
                                    ★
                                </span>
                            </div>
                            <div>
                                <h4 className="text-xs font-bold text-slate-900">{order.driverName}</h4>
                                <p className="text-[11px] text-slate-500">{order.vehicleInfo || 'Motorbike'} • 4.95 Rating</p>
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <a 
                                href={`tel:${(order.driverPhone || '+970599123456').replace(/\s+/g, '')}`}
                                title="Call Driver"
                                className="p-2 rounded-lg bg-white border border-slate-200 text-slate-700 hover:text-emerald-700 hover:border-emerald-300 transition-colors shadow-2xs"
                            >
                                <Phone className="w-3.5 h-3.5" />
                            </a>
                            <a 
                                href={`sms:${(order.driverPhone || '+970599123456').replace(/\s+/g, '')}?body=${encodeURIComponent(`مرحباً كابتن، طلبي #${order.orderNumber}`)}`}
                                title="Message Driver"
                                className="p-2 rounded-lg bg-white border border-slate-200 text-slate-700 hover:text-emerald-700 hover:border-emerald-300 transition-colors shadow-2xs"
                            >
                                <MessageSquare className="w-3.5 h-3.5" />
                            </a>
                        </div>
                    </div>

                    <div className="mt-4 pt-3 border-t border-slate-200/60 flex items-center justify-between text-xs">
                        <span className="text-slate-500">Total Items: <strong className="text-slate-800">{order.itemCount} packages</strong></span>
                        <span className="text-slate-500">Order Total: <strong className="text-slate-900">${order.totalAmount}</strong></span>
                    </div>
                </div>
            </div>
        </div>
    );
}
