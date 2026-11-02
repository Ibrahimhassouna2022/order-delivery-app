import React, { useState } from 'react';
import { 
    Clock, 
    CheckCircle2, 
    XCircle, 
    RotateCcw, 
    ChevronRight, 
    Search,
    Utensils, 
    FileText,
    ArrowUpRight
} from 'lucide-react';

export default function RecentDeliveriesTable({ deliveries, onSelectOrder }) {
    const [filter, setFilter] = useState('all');

    const filteredDeliveries = deliveries.filter((item) => {
        if (filter === 'all') return true;
        return item.status.toLowerCase() === filter.toLowerCase();
    });

    const getStatusBadge = (status) => {
        switch (status.toLowerCase()) {
            case 'delivered':
                return (
                    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <CheckCircle2 className="w-3 h-3 text-emerald-500" />
                        Delivered
                    </span>
                );
            case 'in transit':
                return (
                    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                        <span className="w-1.5 h-1.5 rounded-full bg-sky-500 animate-pulse"></span>
                        In Transit
                    </span>
                );
            case 'cancelled':
                return (
                    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                        <XCircle className="w-3 h-3 text-rose-500" />
                        Cancelled
                    </span>
                );
            default:
                return (
                    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700">
                        {status}
                    </span>
                );
        }
    };

    return (
        <div className="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            {/* Table Header & Filters */}
            <div className="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 className="font-bold text-slate-900 text-sm">Recent Deliveries</h3>
                    <p className="text-xs text-slate-500">Track and review your past orders and receipts</p>
                </div>

                {/* Filter Pills */}
                <div className="flex items-center gap-1 bg-slate-100 p-1 rounded-xl text-xs font-medium self-start sm:self-auto">
                    {['all', 'delivered', 'in transit'].map((tab) => (
                        <button
                            key={tab}
                            onClick={() => setFilter(tab)}
                            className={`px-3 py-1 rounded-lg capitalize transition-all ${
                                filter === tab
                                    ? 'bg-white text-slate-900 font-semibold shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            {tab}
                        </button>
                    ))}
                </div>
            </div>

            {/* Table Content */}
            <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                    <thead className="bg-slate-50/70 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-100 text-[10px]">
                        <tr>
                            <th className="px-6 py-3.5">Merchant & Order</th>
                            <th className="px-6 py-3.5">Dropoff Address</th>
                            <th className="px-6 py-3.5">Date & Time</th>
                            <th className="px-6 py-3.5">Status</th>
                            <th className="px-6 py-3.5 text-right">Amount</th>
                            <th className="px-6 py-3.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 text-slate-700">
                        {filteredDeliveries.map((item) => (
                            <tr 
                                key={item.id} 
                                className="hover:bg-slate-50/80 transition-colors group cursor-pointer"
                                onClick={() => onSelectOrder(item)}
                            >
                                <td className="px-6 py-4">
                                    <div className="flex items-center gap-3">
                                        <div className={`w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs ${item.iconBg || 'bg-amber-100 text-amber-800'}`}>
                                            {item.iconText || <Utensils className="w-4 h-4" />}
                                        </div>
                                        <div>
                                            <p className="font-bold text-slate-900 text-xs">{item.name}</p>
                                            <p className="text-[11px] text-slate-400">#{item.orderNumber}</p>
                                        </div>
                                    </div>
                                </td>
                                <td className="px-6 py-4 max-w-xs truncate text-slate-600">
                                    {item.dropoffAddress}
                                </td>
                                <td className="px-6 py-4 text-slate-500 whitespace-nowrap">
                                    {item.date} • {item.time}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    {getStatusBadge(item.status)}
                                </td>
                                <td className="px-6 py-4 text-right font-bold text-slate-900 whitespace-nowrap">
                                    ${item.amount.toFixed(2)}
                                </td>
                                <td className="px-6 py-4 text-right whitespace-nowrap">
                                    <div className="flex items-center justify-end gap-2" onClick={(e) => e.stopPropagation()}>
                                        <button 
                                            title="View receipt"
                                            className="p-1.5 rounded-lg border border-slate-200 hover:border-slate-300 text-slate-500 hover:text-slate-800 transition-colors"
                                        >
                                            <FileText className="w-3.5 h-3.5" />
                                        </button>
                                        <button 
                                            title="Reorder"
                                            className="p-1.5 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors"
                                        >
                                            <RotateCcw className="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Table Footer */}
            <div className="p-4 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Showing {filteredDeliveries.length} of {deliveries.length} orders</span>
                <button className="font-semibold text-emerald-700 hover:text-emerald-800 flex items-center gap-1">
                    <span>View all order receipts</span>
                    <ArrowUpRight className="w-3.5 h-3.5" />
                </button>
            </div>
        </div>
    );
}
