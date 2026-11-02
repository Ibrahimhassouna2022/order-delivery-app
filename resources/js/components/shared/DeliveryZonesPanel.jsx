import React, { useEffect, useState } from 'react';
import {
    MapPin,
    Plus,
    Trash2,
    Save,
    Loader2,
    AlertCircle,
    Check,
} from 'lucide-react';
import { apiJson } from '../../lib/api';

export default function DeliveryZonesPanel() {
    const [zones, setZones] = useState([]);
    const [drafs, setDrafts] = useState({});
    const [loading, setLoading] = useState(true);
    const [savingId, setSavingId] = useState(null);
    const [adding, setAdding] = useState(false);
    const [error, setError] = useState('');
    const [message, setMessage] = useState('');
    const [newZone, setNewZone] = useState({ name: '', fee: '', sort_order: 0 });

    const loadZones = async () => {
        try {
            const response = await apiJson('/api/v1/operations/delivery-zones');
            const incoming = response?.data || [];
            setZones(incoming);
            setDrafts(Object.fromEntries(incoming.map((zone) => [
                zone.id,
                {
                    fee: String(zone.fee),
                    currency_code: zone.currency_code,
                    is_active: Boolean(zone.is_active),
                    sort_order: Number(zone.sort_order),
                },
            ])));
            setError('');
        } catch (requestError) {
            setError(requestError?.data?.message || 'تعذر تحميل مناطق التوصيل.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadZones();
    }, []);

    const updateDraft = (id, field, value) => {
        setDrafts((current) => ({
            ...current,
            [id]: { ...current[id], [field]: value },
        }));
        setMessage('');
    };

    const saveZone = async (zone) => {
        const draft = drafts[zone.id];
        setSavingId(zone.id);
        setError('');

        try {
            await apiJson(`/api/v1/operations/delivery-zones/${zone.id}`, {
                method: 'PATCH',
                body: {
                    fee: Number(draft.fee),
                    currency_code: draft.currency_code,
                    is_active: draft.is_active,
                    sort_order: Number(draft.sort_order),
                },
            });
            setMessage(`تم تحديث منطقة ${zone.name}.`);
            await loadZones();
        } catch (requestError) {
            setError(requestError?.data?.message || 'تعذر تحديث المنطقة.');
        } finally {
            setSavingId(null);
        }
    };

    const addZone = async (event) => {
        event.preventDefault();
        setAdding(true);
        setError('');

        try {
            await apiJson('/api/v1/operations/delivery-zones', {
                method: 'POST',
                body: {
                    name: newZone.name.trim(),
                    fee: Number(newZone.fee),
                    currency_code: 'ILS',
                    is_active: true,
                    sort_order: Number(newZone.sort_order || 0),
                },
            });
            setNewZone({ name: '', fee: '', sort_order: 0 });
            setMessage('تمت إضافة منطقة التوصيل.');
            await loadZones();
        } catch (requestError) {
            setError(requestError?.data?.message || 'تعذر إضافة المنطقة.');
        } finally {
            setAdding(false);
        }
    };

    const deleteZone = async (zone) => {
        if (!window.confirm(`حذف منطقة ${zone.name}؟`)) {
            return;
        }

        setSavingId(zone.id);
        setError('');

        try {
            await apiJson(`/api/v1/operations/delivery-zones/${zone.id}`, {
                method: 'DELETE',
            });
            setZones((current) => current.filter((item) => item.id !== zone.id));
            setMessage(`تم حذف منطقة ${zone.name}.`);
        } catch (requestError) {
            setError(requestError?.data?.message || 'تعذر حذف المنطقة.');
        } finally {
            setSavingId(null);
        }
    };

    return (
        <section className="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden" dir="rtl">
            <div className="p-5 border-b border-slate-100 flex items-center gap-3 bg-slate-50/60">
                <div className="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-200 text-indigo-700 flex items-center justify-center">
                    <MapPin className="w-5 h-5" />
                </div>
                <div>
                    <h3 className="font-bold text-slate-900 text-sm">قائمة أسعار مناطق التوصيل</h3>
                    <p className="text-[11px] text-slate-500 mt-0.5">
                        اختيار المنطقة يحدد أجرة التوصيل التي تُدفع نقداً للسائق عند التسليم.
                    </p>
                </div>
            </div>

            <div className="p-5 space-y-4">
                <form onSubmit={addZone} className="grid grid-cols-1 sm:grid-cols-[1fr_120px_100px_auto] gap-2 items-end">
                    <div>
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">اسم المنطقة</label>
                        <input
                            type="text"
                            required
                            value={newZone.name}
                            onChange={(event) => setNewZone((current) => ({ ...current, name: event.target.value }))}
                            placeholder="مثال: الرمال"
                            className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs"
                        />
                    </div>
                    <div>
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">الأجرة (₪)</label>
                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            required
                            value={newZone.fee}
                            onChange={(event) => setNewZone((current) => ({ ...current, fee: event.target.value }))}
                            className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs"
                        />
                    </div>
                    <div>
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">الترتيب</label>
                        <input
                            type="number"
                            min="0"
                            value={newZone.sort_order}
                            onChange={(event) => setNewZone((current) => ({ ...current, sort_order: event.target.value }))}
                            className="w-full px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs"
                        />
                    </div>
                    <button
                        type="submit"
                        disabled={adding}
                        className="inline-flex items-center justify-center gap-1.5 h-[38px] px-3 rounded-xl bg-[#0d3b32] text-white text-xs font-bold disabled:opacity-50"
                    >
                        {adding ? <Loader2 className="w-4 h-4 animate-spin" /> : <Plus className="w-4 h-4" />}
                        إضافة
                    </button>
                </form>

                {message && (
                    <div className="rounded-2xl bg-emerald-50 border border-emerald-200 p-3 text-xs text-emerald-800 font-semibold flex items-center gap-2">
                        <Check className="w-4 h-4" />
                        {message}
                    </div>
                )}

                {error && (
                    <div className="rounded-2xl bg-rose-50 border border-rose-200 p-3 text-xs text-rose-800 font-semibold flex items-center gap-2">
                        <AlertCircle className="w-4 h-4" />
                        {error}
                    </div>
                )}

                {loading ? (
                    <div className="py-8 flex items-center justify-center gap-2 text-xs text-slate-500">
                        <Loader2 className="w-4 h-4 animate-spin" />
                        جارٍ تحميل المناطق...
                    </div>
                ) : zones.length === 0 ? (
                    <p className="text-center text-xs text-slate-500 py-6">لا توجد مناطق توصيل مضافة.</p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-right text-xs">
                            <thead>
                                <tr className="border-b border-slate-200 text-slate-500">
                                    <th className="pb-3 font-bold">المنطقة</th>
                                    <th className="pb-3 font-bold">الأجرة</th>
                                    <th className="pb-3 font-bold">الترتيب</th>
                                    <th className="pb-3 font-bold">مفعّلة</th>
                                    <th className="pb-3 font-bold text-center">الإجراء</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {zones.map((zone) => {
                                    const draft = drafts[zone.id] || {};
                                    return (
                                        <tr key={zone.id} className="hover:bg-slate-50/80">
                                            <td className="py-3 font-bold text-slate-900">{zone.name}</td>
                                            <td className="py-3">
                                                <input
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    value={draft.fee ?? ''}
                                                    onChange={(event) => updateDraft(zone.id, 'fee', event.target.value)}
                                                    className="w-24 px-2 py-1.5 rounded-lg border border-slate-200 bg-white text-xs"
                                                />
                                            </td>
                                            <td className="py-3">
                                                <input
                                                    type="number"
                                                    min="0"
                                                    value={draft.sort_order ?? 0}
                                                    onChange={(event) => updateDraft(zone.id, 'sort_order', event.target.value)}
                                                    className="w-20 px-2 py-1.5 rounded-lg border border-slate-200 bg-white text-xs"
                                                />
                                            </td>
                                            <td className="py-3">
                                                <label className="inline-flex items-center gap-2 cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        checked={Boolean(draft.is_active)}
                                                        onChange={(event) => updateDraft(zone.id, 'is_active', event.target.checked)}
                                                        className="w-4 h-4 accent-emerald-700"
                                                    />
                                                    <span className="text-[11px] text-slate-600">{draft.is_active ? 'نعم' : 'لا'}</span>
                                                </label>
                                            </td>
                                            <td className="py-3 text-center">
                                                <div className="inline-flex items-center gap-1.5">
                                                    <button
                                                        type="button"
                                                        onClick={() => saveZone(zone)}
                                                        disabled={savingId === zone.id}
                                                        className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] font-bold"
                                                    >
                                                        {savingId === zone.id ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Save className="w-3.5 h-3.5" />}
                                                        حفظ
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => deleteZone(zone)}
                                                        disabled={savingId === zone.id}
                                                        className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-[11px] font-bold"
                                                    >
                                                        <Trash2 className="w-3.5 h-3.5" />
                                                        حذف
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </section>
    );
}
