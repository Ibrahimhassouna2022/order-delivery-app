import React, { useEffect, useState } from 'react';
import {
    Landmark,
    Save,
    Loader2,
    ShieldCheck,
    AlertCircle,
} from 'lucide-react';
import { apiJson } from '../../lib/api';

const emptyForm = {
    phone: '',
    iban: '',
    account_name: '',
    support_phone: '',
    instructions: '',
};

export default function PaymentSettingsPanel() {
    const [form, setForm] = useState(emptyForm);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');

    useEffect(() => {
        apiJson('/api/v1/operations/payment-settings')
            .then((response) => {
                setForm({
                    phone: response?.data?.phone || '',
                    iban: response?.data?.iban || '',
                    account_name: response?.data?.account_name || '',
                    support_phone: response?.data?.support_phone || '',
                    instructions: response?.data?.instructions || '',
                });
            })
            .catch((requestError) => {
                setError(requestError?.data?.message || 'تعذر تحميل إعدادات الدفع.');
            })
            .finally(() => setLoading(false));
    }, []);

    const update = (field, value) => {
        setForm((current) => ({ ...current, [field]: value }));
        setMessage('');
        setError('');
    };

    const save = async (event) => {
        event.preventDefault();
        setSaving(true);
        setMessage('');
        setError('');

        try {
            const response = await apiJson('/api/v1/operations/payment-settings', {
                method: 'PUT',
                body: form,
            });
            setForm((current) => ({ ...current, ...(response?.data || {}) }));
            setMessage('تم حفظ بيانات الحساب بنجاح. ستظهر للزبائن في نافذة الدفع.');
        } catch (requestError) {
            setError(requestError?.data?.message || 'تعذر حفظ إعدادات الدفع.');
        } finally {
            setSaving(false);
        }
    };

    return (
        <section className="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden" dir="rtl">
            <div className="p-5 border-b border-slate-100 flex items-center gap-3 bg-slate-50/60">
                <div className="w-10 h-10 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center justify-center">
                    <Landmark className="w-5 h-5" />
                </div>
                <div>
                    <h3 className="font-bold text-slate-900 text-sm">بيانات حساب التحويل البنكي</h3>
                    <p className="text-[11px] text-slate-500 mt-0.5">
                        تُعرض هذه البيانات للزبون في نافذة الدفع، ويمكن تعديلها في أي وقت.
                    </p>
                </div>
            </div>

            {loading ? (
                <div className="py-10 flex items-center justify-center gap-2 text-xs text-slate-500">
                    <Loader2 className="w-4 h-4 animate-spin" />
                    جارٍ تحميل الإعدادات...
                </div>
            ) : (
                <form onSubmit={save} className="p-5 space-y-4 text-xs">
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label className="block font-bold text-slate-700 mb-1">رقم المحفظة / الهاتف</label>
                            <input
                                type="text"
                                value={form.phone}
                                onChange={(event) => update('phone', event.target.value)}
                                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none text-left font-mono"
                                dir="ltr"
                                placeholder="+970 59 000 0000"
                            />
                        </div>

                        <div>
                            <label className="block font-bold text-slate-700 mb-1">رقم الآيبان IBAN</label>
                            <input
                                type="text"
                                value={form.iban}
                                onChange={(event) => update('iban', event.target.value)}
                                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none text-left font-mono"
                                dir="ltr"
                                placeholder="PS00 XXXX 0000 0000 0000 0000 0000 0"
                            />
                        </div>

                        <div>
                            <label className="block font-bold text-slate-700 mb-1">اسم صاحب الحساب</label>
                            <input
                                type="text"
                                value={form.account_name}
                                onChange={(event) => update('account_name', event.target.value)}
                                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none"
                            />
                        </div>

                        <div>
                            <label className="block font-bold text-slate-700 mb-1">رقم الدعم والتتبع</label>
                            <input
                                type="text"
                                value={form.support_phone}
                                onChange={(event) => update('support_phone', event.target.value)}
                                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none text-left font-mono"
                                dir="ltr"
                            />
                        </div>
                    </div>

                    <div>
                        <label className="block font-bold text-slate-700 mb-1">تعليمات التحويل الظاهرة للزبون</label>
                        <textarea
                            value={form.instructions}
                            onChange={(event) => update('instructions', event.target.value)}
                            rows={3}
                            className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none leading-relaxed"
                        />
                    </div>

                    {message && (
                        <div className="rounded-2xl bg-emerald-50 border border-emerald-200 p-3 text-emerald-800 font-semibold flex items-center gap-2">
                            <ShieldCheck className="w-4 h-4" />
                            {message}
                        </div>
                    )}

                    {error && (
                        <div className="rounded-2xl bg-rose-50 border border-rose-200 p-3 text-rose-800 font-semibold flex items-center gap-2">
                            <AlertCircle className="w-4 h-4" />
                            {error}
                        </div>
                    )}

                    <button
                        type="submit"
                        disabled={saving}
                        className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#0d3b32] text-white font-bold hover:bg-[#092923] disabled:opacity-50"
                    >
                        {saving ? <Loader2 className="w-4 h-4 animate-spin" /> : <Save className="w-4 h-4" />}
                        {saving ? 'جارٍ الحفظ...' : 'حفظ بيانات الحساب'}
                    </button>
                </form>
            )}
        </section>
    );
}
