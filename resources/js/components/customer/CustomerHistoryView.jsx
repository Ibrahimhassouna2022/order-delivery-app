import React, { useState } from 'react';
import { Search, Download, RotateCcw, Calendar, CheckCircle2, Utensils } from 'lucide-react';

export default function CustomerHistoryView({ deliveries, onSelectOrder }) {
    const [search, setSearch] = useState('');

    const filtered = (deliveries || []).filter((item) => 
        (item.name || '').toLowerCase().includes(search.toLowerCase()) ||
        (item.orderNumber || '').toLowerCase().includes(search.toLowerCase()) ||
        (item.dropoffAddress || '').toLowerCase().includes(search.toLowerCase())
    );

    return (
        <div className="space-y-6 font-sans" dir="rtl">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 className="text-xl font-bold text-slate-900">سجل الطلبات السابقة</h2>
                    <p className="text-xs text-slate-500">أرشيف وجباتك وطلباتك المكتملة وتفاصيل التوصيل</p>
                </div>

                <div className="relative w-full sm:w-64">
                    <Search className="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2" />
                    <input
                        type="text"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="بحث برقم الطلب أو المطعم..."
                        className="w-full pr-9 pl-3 py-2 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                    />
                </div>
            </div>

            <div className="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-right text-xs">
                        <thead className="bg-slate-50 text-slate-500 font-bold text-[11px] border-b border-slate-100">
                            <tr>
                                <th className="px-6 py-4">رقم الطلب والتاريخ</th>
                                <th className="px-6 py-4">المطعم والوجبة</th>
                                <th className="px-6 py-4">عنوان الاستلام</th>
                                <th className="px-6 py-4">الحالة</th>
                                <th className="px-6 py-4 text-left">المبلغ</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100 text-slate-700">
                            {filtered.map((item) => (
                                <tr 
                                    key={item.id} 
                                    className="hover:bg-slate-50/80 transition-colors"
                                >
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <p className="font-bold text-slate-900 text-xs">#{item.orderNumber}</p>
                                        <p className="text-[11px] text-slate-400">{item.date} • {item.time}</p>
                                    </td>
                                    <td className="px-6 py-4 font-bold text-slate-800">
                                        {item.name}
                                    </td>
                                    <td className="px-6 py-4 text-slate-600 max-w-xs truncate">
                                        {item.dropoffAddress}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                            <CheckCircle2 className="w-3 h-3 text-emerald-600" />
                                            <span>تم التوصيل</span>
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-left font-black text-slate-900 whitespace-nowrap">
                                        {item.amount ? Number(item.amount).toFixed(2) : '30.00'} ₪
                                    </td>
                                </tr>
                            ))}

                            {filtered.length === 0 && (
                                <tr>
                                    <td colSpan="5" className="text-center py-8 text-slate-400">
                                        لا توجد طلبات سابقة مطابقة
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}
