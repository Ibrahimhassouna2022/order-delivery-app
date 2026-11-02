import React, { useState } from 'react';
import { 
    Lock, 
    Mail, 
    ArrowLeft, 
    User, 
    Phone, 
    MapPin, 
    Bike, 
    ShieldCheck, 
    CheckCircle2,
    Shield
} from 'lucide-react';

export default function LoginPage({ onLoginSuccess }) {
    const [isRegister, setIsRegister] = useState(false);
    
    // Login form fields
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    
    // Customer Registration fields (الزبون فقط هو من يسجل ويضع بياناته)
    const [name, setName] = useState('');
    const [phone, setPhone] = useState('');
    const [city, setCity] = useState('غزة');
    const [address, setAddress] = useState('');

    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [showDemoAccounts, setShowDemoAccounts] = useState(false);

    // Predefined accounts in the database for each role
    const demoAccounts = [
        {
            role: 'customer',
            label: '🛍️ حساب زبون (أحمد خليل)',
            email: 'customer@wasalli.app',
            password: 'password',
            name: 'أحمد خليل',
            phone: '+970 59 912 3456',
            city: 'غزة',
            address: 'غزة - الرمال، مفترق السرايا بجوار برج الشروق'
        },
        {
            role: 'restaurant',
            label: '🍳 حساب مطعم (مطعم بالميرا)',
            email: 'restaurant@wasalli.app',
            password: 'password',
            name: 'مطعم بالميرا',
            phone: '+970 59 111 2233',
            city: 'غزة',
            address: 'غزة - شارع عمر المختار'
        },
        {
            role: 'driver',
            label: '🛵 سائق 1 متاح (محمود النجار - الأقل حملاً)',
            email: 'driver@wasalli.app',
            password: 'password',
            name: 'محمود النجار',
            phone: '+970 59 888 1234',
            city: 'غزة',
            address: 'غزة - شارع النصر'
        },
        {
            role: 'driver',
            label: '🛵 سائق 2 متاح (يوسف الحسن - البديل بعد 30 ثانية)',
            email: 'driver2@wasalli.app',
            password: 'password',
            name: 'يوسف الحسن',
            phone: '+970 59 777 5678',
            city: 'غزة',
            address: 'غزة - الرمال، شارع الوحدة'
        },
        {
            role: 'operations',
            label: '🛡️ حساب الإدارة والعمليات',
            email: 'admin@wasalli.app',
            password: 'password',
            name: 'فريق العمليات والرقابة',
            phone: '+970 59 000 9999',
            city: 'غزة',
            address: 'المقر الرئيسي للعمليات'
        }
    ];

    // Call the real API and normalise its response for the app shell.
    const requestSession = async (endpoint, payload) => {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify(payload)
        });

        let data = null;

        try {
            data = await response.json();
        } catch {
            data = null;
        }

        if (!response.ok) {
            const validationMessage = data?.errors
                ? Object.values(data.errors).flat()[0]
                : null;

            throw new Error(validationMessage || data?.message || 'تعذر إتمام العملية، تحقق من البيانات وحاول مجدداً.');
        }

        if (!data?.data?.role) {
            throw new Error('استجابة غير صالحة من الخادم. حاول مرة أخرى.');
        }

        return {
            id: data.data.id,
            name: data.data.name,
            email: data.data.email,
            role: data.data.role, // Determined strictly by the backend
            token: data.token
        };
    };

    const handleQuickSelectAccount = async (account) => {
        if (loading) {
            return;
        }

        setEmail(account.email);
        setPassword(account.password);
        setError('');
        setLoading(true);

        try {
            const session = await requestSession('/api/v1/auth/login', {
                email: account.email,
                password: account.password
            });
            onLoginSuccess(session);
        } catch (loginError) {
            setError(loginError.message);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        if (loading) {
            return;
        }

        setLoading(true);
        setError('');

        try {
            const endpoint = isRegister ? '/api/v1/auth/register' : '/api/v1/auth/login';
            const payload = isRegister
                ? {
                    name,
                    email: email.trim(),
                    password,
                    password_confirmation: password,
                    phone,
                    city,
                    address
                }
                : { email: email.trim(), password };

            const session = await requestSession(endpoint, payload);
            onLoginSuccess(session);
        } catch (loginError) {
            setError(loginError.message);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-[#07241e] via-[#0d3b32] to-[#081e19] flex items-center justify-center p-4 selection:bg-emerald-500 selection:text-white font-sans" dir="rtl">
            <div className="w-full max-w-xl bg-white rounded-3xl shadow-2xl border border-emerald-800/40 overflow-hidden p-6 md:p-8 space-y-6">
                
                {/* Brand & Heading */}
                <div className="text-center space-y-2">
                    <div className="w-14 h-14 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-center mx-auto shadow-xs">
                        <Bike className="w-7 h-7" />
                    </div>
                    <h1 className="text-2xl font-black text-slate-900">منصة وصلّي للطلب والتوصيل</h1>
                    <p className="text-xs text-slate-500 max-w-sm mx-auto">
                        {isRegister 
                            ? 'تسجيل حساب زبون جديد مع حفظ العنوان ومعلم التوصيل لسهولة الطلب' 
                            : 'تسجيل الدخول الموحد — يتم التحقق من بيانات الحساب وتوجيهك تلقائياً بحسب دورك المعتمد'}
                    </p>
                </div>

                {error && (
                    <div className="p-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-xs font-semibold">
                        {error}
                    </div>
                )}

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-3.5 text-xs">
                    
                    {/* CUSTOMER REGISTRATION FIELDS ONLY (لا يوجد اختيار أدوار!) */}
                    {isRegister && (
                        <>
                            <div>
                                <label className="block font-bold text-slate-700 mb-1">الاسم الكامل للزبون</label>
                                <div className="relative">
                                    <User className="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2" />
                                    <input
                                        type="text"
                                        required
                                        value={name}
                                        onChange={(e) => setName(e.target.value)}
                                        placeholder="مثال: أحمد خليل"
                                        className="w-full pr-9 pl-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block font-bold text-slate-700 mb-1">رقم الهاتف (للتواصل مع السائق)</label>
                                    <div className="relative">
                                        <Phone className="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2" />
                                        <input
                                            type="text"
                                            required
                                            value={phone}
                                            onChange={(e) => setPhone(e.target.value)}
                                            placeholder="+970 59 912 3456"
                                            className="w-full pr-9 pl-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none text-left"
                                            dir="ltr"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label className="block font-bold text-slate-700 mb-1">المدينة</label>
                                    <input
                                        type="text"
                                        readOnly
                                        value="غزة 📍"
                                        className="w-full px-3 py-2.5 bg-slate-100 border border-slate-200 rounded-xl font-bold text-slate-800"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block font-bold text-slate-700 mb-1">
                                    العنوان التفصيلي والمعلم البارز (يظهر للمطعم والسائق)
                                </label>
                                <div className="relative">
                                    <MapPin className="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2" />
                                    <input
                                        type="text"
                                        required
                                        value={address}
                                        onChange={(e) => setAddress(e.target.value)}
                                        placeholder="مثال: حي الرمال، مفترق السرايا، مقابل برج الشروق، عمارة 4"
                                        className="w-full pr-9 pl-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none"
                                    />
                                </div>
                            </div>
                        </>
                    )}

                    {/* Email Field */}
                    <div>
                        <label className="block font-bold text-slate-700 mb-1">البريد الإلكتروني</label>
                        <div className="relative">
                            <Mail className="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2" />
                            <input
                                type="email"
                                required
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                placeholder="name@example.com"
                                className="w-full pr-9 pl-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 text-left"
                                dir="ltr"
                            />
                        </div>
                    </div>

                    {/* Password Field */}
                    <div>
                        <label className="block font-bold text-slate-700 mb-1">كلمة المرور</label>
                        <div className="relative">
                            <Lock className="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2" />
                            <input
                                type="password"
                                required
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                placeholder="••••••••"
                                className="w-full pr-9 pl-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 text-left"
                                dir="ltr"
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        disabled={loading}
                        className="w-full py-3 bg-[#0d3b32] hover:bg-[#092923] text-white font-bold rounded-xl text-sm transition-all shadow-md active:scale-[0.99] flex items-center justify-center gap-2 mt-4 disabled:opacity-50"
                    >
                        {loading ? (
                            <span>جاري التحقق والربط...</span>
                        ) : (
                            <>
                                <span>{isRegister ? 'إنشاء حساب الزبون والدخول' : 'تسجيل الدخول'}</span>
                                <ArrowLeft className="w-4 h-4 text-emerald-300" />
                            </>
                        )}
                    </button>
                </form>

                {/* Switch between Login and Customer Registration */}
                <div className="text-center pt-2 border-t border-slate-100">
                    <button
                        type="button"
                        onClick={() => {
                            setIsRegister(!isRegister);
                            setError('');
                        }}
                        className="text-xs text-emerald-800 font-bold hover:underline"
                    >
                        {isRegister 
                            ? 'لديك حساب مسجل بالفعل؟ سجّل دخولك' 
                            : 'زبون جديد؟ أنشئ حسابك وسجّل عنوانك مجاناً'}
                    </button>
                </div>

                {/* Collapsible Demo Accounts for Testing & Reviewers */}
                <div className="pt-2 text-center">
                    <button
                        type="button"
                        onClick={() => setShowDemoAccounts(!showDemoAccounts)}
                        className="text-[11px] text-slate-400 hover:text-slate-600 underline"
                    >
                        {showDemoAccounts ? 'إخفاء الحسابات المعتمدة للفحص' : 'عرض حسابات الفحص السريع للأدوار (Demo)'}
                    </button>

                    {showDemoAccounts && (
                        <div className="mt-3 p-3 bg-slate-50 rounded-2xl border border-slate-200 text-right space-y-2 animate-in fade-in">
                            <span className="text-[10px] font-bold text-slate-500 block">
                                انقر على أي حساب لتعبئة بياناته والتحقق من التوجيه التلقائي:
                            </span>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                {demoAccounts.map((acc) => (
                                    <button
                                        key={acc.role}
                                        type="button"
                                        onClick={() => handleQuickSelectAccount(acc)}
                                        className="p-2.5 rounded-xl border border-slate-200 bg-white hover:border-emerald-500 text-right transition-colors"
                                    >
                                        <span className="font-bold text-slate-900 block text-[11px]">{acc.label}</span>
                                        <span className="text-[10px] text-slate-400 font-mono block">{acc.email}</span>
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}
                </div>

            </div>
        </div>
    );
}
