import React, { useState } from 'react';
import { Package, Clock, CheckCircle2, ChevronRight, Search, MapPin, Bike, ArrowUpRight, Plus } from 'lucide-react';

export default function CustomerDeliveriesView({ activeOrder, deliveries, onSelectOrder, onOpenNewDelivery }) {
    const [tabFilter, setTabFilter] = useState('all');

    const filtered = deliveries.filter((d) => {
        if (tabFilter === 'all') return true;
        if (tabFilter === 'active') return d.status.toLowerCase().includes('delivery') || d.status.toLowerCase().includes('transit');
        if (tabFilter === 'delivered') return d.status.toLowerCase() === 'delivered';
        return true;
    });

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 className="text-xl font-bold text-slate-900">My Deliveries</h2>
                    <p className="text-xs text-slate-500">Track and monitor all your ongoing and scheduled shipments</p>
                </div>
                <button
                    onClick={onOpenNewDelivery}
                    className="flex items-center gap-2 px-4 py-2.5 bg-[#0d3b32] hover:bg-[#092923] text-white rounded-xl text-xs font-bold transition-all shadow-xs"
                >
                    <Plus className="w-4 h-4 text-emerald-300" />
                    <span>Create New Delivery</span>
                </button>
            </div>

            {/* Filter Tabs */}
            <div className="flex items-center gap-2 border-b border-slate-200 pb-2 text-xs font-semibold">
                {['all', 'active', 'delivered'].map((tab) => (
                    <button
                        key={tab}
                        onClick={() => setTabFilter(tab)}
                        className={`px-4 py-1.5 rounded-lg capitalize transition-colors ${
                            tabFilter === tab 
                                ? 'bg-[#0d3b32] text-white shadow-xs' 
                                : 'text-slate-600 hover:bg-slate-100'
                        }`}
                    >
                        {tab === 'all' ? 'All Deliveries' : tab}
                    </button>
                ))}
            </div>

            {/* Grid of Deliveries */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {activeOrder && (tabFilter === 'all' || tabFilter === 'active') && (
                    <div 
                        onClick={() => onSelectOrder(activeOrder)}
                        className="bg-white rounded-2xl p-6 border-2 border-emerald-500 shadow-md cursor-pointer hover:shadow-lg transition-all group"
                    >
                        <div className="flex items-center justify-between mb-4">
                            <span className="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-extrabold uppercase tracking-wider flex items-center gap-1.5">
                                <span className="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-ping"></span>
                                Out For Delivery
                            </span>
                            <span className="text-xs font-bold text-emerald-700">ETA: {activeOrder.eta}</span>
                        </div>

                        <h3 className="font-bold text-slate-900 text-sm">{activeOrder.name}</h3>
                        <p className="text-xs text-slate-400 mb-4">Tracking: #{activeOrder.trackingNumber}</p>

                        <div className="space-y-2 text-xs border-t border-slate-100 pt-3 text-slate-600">
                            <div className="flex items-center gap-2">
                                <MapPin className="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                <span className="truncate">From: {activeOrder.pickupAddress}</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <MapPin className="w-3.5 h-3.5 text-rose-500 shrink-0" />
                                <span className="truncate">To: {activeOrder.dropoffAddress}</span>
                            </div>
                        </div>

                        <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                            <span className="text-xs font-bold text-slate-900">${activeOrder.totalAmount.toFixed(2)}</span>
                            <span className="text-xs font-semibold text-emerald-700 group-hover:translate-x-1 transition-transform flex items-center gap-1">
                                Track Live <ChevronRight className="w-3.5 h-3.5" />
                            </span>
                        </div>
                    </div>
                )}

                {filtered.map((item) => (
                    <div 
                        key={item.id}
                        onClick={() => onSelectOrder(item)}
                        className="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs cursor-pointer hover:shadow-md transition-all group"
                    >
                        <div className="flex items-center justify-between mb-3">
                            <span className={`px-2.5 py-0.5 rounded-full text-[10px] font-bold ${
                                item.status.toLowerCase() === 'delivered' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'
                            }`}>
                                {item.status}
                            </span>
                            <span className="text-xs text-slate-400">{item.date} • {item.time}</span>
                        </div>

                        <h3 className="font-bold text-slate-900 text-sm">{item.name}</h3>
                        <p className="text-xs text-slate-400 mb-3">Order #{item.orderNumber}</p>

                        <div className="text-xs text-slate-600 truncate border-t border-slate-100 pt-3">
                            {item.dropoffAddress}
                        </div>

                        <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                            <span className="text-xs font-bold text-slate-900">${item.amount.toFixed(2)}</span>
                            <span className="text-xs font-semibold text-slate-600 group-hover:text-emerald-700 transition-colors flex items-center gap-1">
                                View Receipt <ChevronRight className="w-3.5 h-3.5" />
                            </span>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
