import React, { useState } from 'react';
import { X, Lock, Mail, User, ShieldCheck, ArrowRight, CheckCircle2 } from 'lucide-react';

export default function AuthModal({ isOpen, onClose, onLoginSuccess }) {
    if (!isOpen) return null;

    const [isRegister, setIsRegister] = useState(false);
    const [selectedRole, setSelectedRole] = useState('customer');
    const [email, setEmail] = useState('alex@dashly.app');
    const [password, setPassword] = useState('password123');
    const [name, setName] = useState('Alex Morgan');
    const [loading, setLoading] = useState(false);
    const [successMessage, setSuccessMessage] = useState('');

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);

        // Simulated or API call to /api/v1/auth/login or register
        setTimeout(() => {
            setLoading(false);
            setSuccessMessage(`Authenticated as ${selectedRole.toUpperCase()}`);
            setTimeout(() => {
                onLoginSuccess({
                    name: isRegister ? name : (selectedRole === 'driver' ? 'David Vance' : 'Alex Morgan'),
                    email,
                    role: selectedRole
                });
                onClose();
            }, 800);
        }, 600);
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs animate-in fade-in duration-200">
            <div className="bg-white rounded-3xl max-w-md w-full shadow-2xl border border-slate-100 overflow-hidden flex flex-col">
                {/* Header */}
                <div className="p-6 bg-[#0d3b32] text-white flex items-center justify-between">
                    <div>
                        <span className="text-[10px] font-bold uppercase tracking-wider text-emerald-300 bg-emerald-950/40 px-2 py-0.5 rounded-full border border-emerald-800">
                            Unified Authentication
                        </span>
                        <h2 className="text-lg font-bold mt-1">
                            {isRegister ? 'Create Dashly Account' : 'Welcome to Dashly'}
                        </h2>
                    </div>
                    <button
                        onClick={onClose}
                        className="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors text-white"
                    >
                        <X className="w-4 h-4" />
                    </button>
                </div>

                {/* Role Selector Tabs */}
                <div className="p-4 bg-slate-50 border-b border-slate-100">
                    <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-2">
                        Sign In Role:
                    </span>
                    <div className="grid grid-cols-4 gap-1 text-[11px] font-bold">
                        {['customer', 'driver', 'restaurant', 'operations'].map((role) => (
                            <button
                                key={role}
                                type="button"
                                onClick={() => setSelectedRole(role)}
                                className={`py-1.5 px-1 rounded-lg capitalize transition-all text-center truncate ${
                                    selectedRole === role
                                        ? 'bg-[#0d3b32] text-white shadow-xs'
                                        : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-100'
                                }`}
                            >
                                {role}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit} className="p-6 space-y-4 text-xs">
                    {successMessage && (
                        <div className="p-3 bg-emerald-50 text-emerald-800 rounded-xl border border-emerald-200 flex items-center gap-2 font-bold">
                            <CheckCircle2 className="w-4 h-4 text-emerald-600" />
                            <span>{successMessage}</span>
                        </div>
                    )}

                    {isRegister && (
                        <div>
                            <label className="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                                Full Name
                            </label>
                            <div className="relative">
                                <User className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                                <input
                                    type="text"
                                    required
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                    className="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-slate-800"
                                />
                            </div>
                        </div>
                    )}

                    <div>
                        <label className="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                            Email Address
                        </label>
                        <div className="relative">
                            <Mail className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                            <input
                                type="email"
                                required
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                className="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-slate-800"
                            />
                        </div>
                    </div>

                    <div>
                        <label className="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                            Password
                        </label>
                        <div className="relative">
                            <Lock className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                            <input
                                type="password"
                                required
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                className="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-slate-800"
                            />
                        </div>
                    </div>

                    <div className="pt-2">
                        <button
                            type="submit"
                            disabled={loading}
                            className="w-full py-3 rounded-xl bg-[#0d3b32] hover:bg-[#092923] text-white font-bold text-xs flex items-center justify-center gap-2 shadow-sm transition-all active:scale-[0.98]"
                        >
                            <span>{loading ? 'Authenticating...' : (isRegister ? 'Complete Registration' : 'Sign In to Dashboard')}</span>
                            <ArrowRight className="w-4 h-4 text-emerald-300" />
                        </button>
                    </div>

                    <div className="text-center pt-2">
                        <button
                            type="button"
                            onClick={() => setIsRegister(!isRegister)}
                            className="text-slate-500 hover:text-slate-800 font-semibold"
                        >
                            {isRegister ? 'Already have an account? Sign in' : "Don't have an account? Create one"}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
