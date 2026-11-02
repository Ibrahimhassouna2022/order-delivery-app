import React, { useState } from 'react';
import { 
    Bike, 
    Clock, 
    MapPin, 
    X, 
    Calculator, 
    ShieldCheck, 
    AlertCircle, 
    Search,
    Banknote,
    Navigation,
    CheckCircle2
} from 'lucide-react';

export default function CustomerFareScheduleModal({ isOpen, onClose, deliveryZones = [] }) {
    if (!isOpen) return null;

    const [searchQuery, setSearchQuery] = useState('');

    const defaultZones = [
        { id: 1, name: 'الرمال', fee: '5.000', estimated_time: '15 - 20 دقيقة', currency_code: 'ILS' },
        { id: 2, name: 'تل الهوا', fee: '6.000', estimated_time: '20 - 25 دقيقة', currency_code: 'ILS' },
        { id: 3, name: 'الشيخ رضوان', fee: '6.000', estimated_time: '20 - 25 دقيقة', currency_code: 'ILS' },
        { id: 4, name: 'التفاح', fee: '5.500', estimated_time: '20 - 25 دقيقة', currency_code: 'ILS' },
        { id: 5, name: 'الزيتون', fee: '6.500', estimated_time: '25 - 30 دقيقة', currency_code: 'ILS' },
        { id: 6, name: 'الشجاعية', fee: '7.000', estimated_time: '25 - 30 دقيقة', currency_code: 'ILS' },
        { id: 7, name: 'جباليا', fee: '8.000', estimated_time: '30 - 35 دقيقة', currency_code: 'ILS' },
        { id: 8, name: 'خان يونس', fee: '12.000', estimated_time: '40 - 50 دقيقة', currency_code: 'ILS' },
        { id: 9, name: 'رفح', fee: '15.000', estimated_time: '50 - 60 دقيقة', currency_code: 'ILS' },
    ];

    const zonesList = deliveryZones.length > 0 ? deliveryZones : defaultZones;

    const filteredZones = zonesList.filter((zone) =>
        zone.name.toLowerCase().includes(searchQuery.trim().toLowerCase())
    );

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm animate-in fade-in duration-200" dir="rtl">
            <div className="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-slate-100 overflow-hidden flex flex-col max-h-[92vh]">
                
                {/* Header */}
                <div className="p-5 bg-[#0d3b32] text-white flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="w-12 h-12 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center text-emerald-300">
                            <Calculator className="w-6 h-6" />
                        </div>
                        <div>
                            <h3 className="font-extrabold text-lg text-white">جدول تسعيرة التوصيل المعتمد</h3>
                            <p className="text-xs text-emerald-200 mt-0.5">
                                تسعيرة مسبقة ومعتمدة بحسب المنطقة والوقت التقديري للوصول
                            </p>
                        </div>
                    </div>

                    <button
                        onClick={onClose}
                        className="p-2 rounded-xl text-white/70 hover:text-white hover:bg-white/10 transition-colors"
                        title="إغلاق"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                {/* Content */}
                <div className="flex-1 overflow-y-auto p-5 space-y-4">
                    
                    {/* Important Customer Notice Banner */}
                    <div className="p-4 rounded-2xl bg-amber-50 border-2 border-amber-300/80 text-amber-950 space-y-2">
                        <div className="flex items-center gap-2 font-black text-sm">
                            <Banknote className="w-5 h-5 text-amber-700 shrink-0" />
                            <span>تنبيه هام حول مسؤولية دفع أجرة التوصيل:</span>
                        </div>
                        <p className="text-xs leading-relaxed text-amber-900">
                            <strong>الزبون مسؤول عن دفع أجرة التوصيل للسائق نقداً عند استلام الطلب</strong> وفق جدول التسعيرة المعتمد مسبقاً والموضح أدناه، والمحدد بحسب المنطقة والوقت التقديري لوصول السائق.
                            (قيمة الوجبات فقط يتم دفعها للمطعم مسبقاً، بينما أجرة التوصيل تسلّم كاملة للسائق يداً بيد).
                        </p>
                    </div>

                    {/* Search Filter */}
                    <div className="relative">
                        <Search className="w-4 h-4 text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2" />
                        <input
                            type="text"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            placeholder="ابحث عن منطقتك (مثال: الرمال، تل الهوا، الشجاعية...)"
                            className="w-full pr-10 pl-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        />
                    </div>

                    {/* Fare Table */}
                    <div className="rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
                        <table className="w-full text-right text-xs">
                            <thead className="bg-slate-100/80 text-slate-700 font-bold border-b border-slate-200">
                                <tr>
                                    <th className="py-3 px-4">المنطقة الجغرافية</th>
                                    <th className="py-3 px-4">أجرة التوصيل</th>
                                    <th className="py-3 px-4">الوقت التقديري للوصول</th>
                                    <th className="py-3 px-4">طريقة دفع الأجرة</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {filteredZones.map((zone) => {
                                    const feeNumber = Number(zone.fee || 0);
                                    const estimatedTime = zone.estimated_time || matchEstimatedTime(zone.name);

                                    return (
                                        <tr key={zone.id} className="hover:bg-slate-50 transition-colors">
                                            <td className="py-3 px-4 font-bold text-slate-900 flex items-center gap-2">
                                                <MapPin className="w-4 h-4 text-rose-500 shrink-0" />
                                                <span>{zone.name}</span>
                                            </td>
                                            <td className="py-3 px-4">
                                                <span className="font-black text-emerald-800 text-sm">
                                                    {feeNumber.toFixed(2)} ₪
                                                </span>
                                            </td>
                                            <td className="py-3 px-4 text-slate-600">
                                                <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-800 font-medium text-[11px] border border-blue-100">
                                                    <Clock className="w-3.5 h-3.5 text-blue-600" />
                                                    {estimatedTime}
                                                </span>
                                            </td>
                                            <td className="py-3 px-4 text-slate-500">
                                                <span className="text-[11px] font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200">
                                                    نقداً للسائق عند التسليم
                                                </span>
                                            </td>
                                        </tr>
                                    );
                                })}

                                {filteredZones.length === 0 && (
                                    <tr>
                                        <td colSpan="4" className="py-8 text-center text-slate-400">
                                            لا توجد منطقة مطابقة لبحثك.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Fairness & Criteria Guarantee */}
                    <div className="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200 text-xs space-y-1.5">
                        <div className="flex items-center gap-2 font-bold text-emerald-950">
                            <ShieldCheck className="w-4 h-4 text-emerald-700" />
                            <span>معايير التسعير والتوزيع العادل:</span>
                        </div>
                        <ul className="text-emerald-900/90 text-[11px] space-y-1 list-disc list-inside">
                            <li>تسعيرة التوصيل ثابتة وموحدة لجميع الزبائن لمنع أي استغلال أو تفاوت.</li>
                            <li>يتم إسناد الطلب للسائق المتاح والأقل عدداً في التوصيلات المنجزة تحقيقاً للعدالة بين الكباتن.</li>
                            <li>لدى السائق مهلة 30 ثانية لقبول المشوار، وفي حال عدم الرد يُحوّل فوراً إلى سائق آخر.</li>
                        </ul>
                    </div>

                </div>

                {/* Footer */}
                <div className="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
                    <span className="text-xs text-slate-500">
                        الأسعار تشمل التوصيل حتى باب المنزل أو المعلم المتفق عليه.
                    </span>
                    <button
                        onClick={onClose}
                        className="px-5 py-2 rounded-xl bg-[#0d3b32] text-white text-xs font-bold hover:bg-[#092923] transition-colors"
                    >
                        فهمت ذلك
                    </button>
                </div>

            </div>
        </div>
    );
}

function matchEstimatedTime(zoneName) {
    switch (zoneName) {
        case 'الرمال':
            return '15 - 20 دقيقة';
        case 'تل الهوا':
        case 'الشيخ رضوان':
        case 'التفاح':
            return '20 - 25 دقيقة';
        case 'الزيتون':
        case 'الشجاعية':
            return '25 - 30 دقيقة';
        case 'جباليا':
            return '30 - 35 دقيقة';
        case 'خان يونس':
            return '40 - 50 دقيقة';
        case 'رفح':
            return '50 - 60 دقيقة';
        default:
            return '20 - 30 دقيقة';
    }
}

