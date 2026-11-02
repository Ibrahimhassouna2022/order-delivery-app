import React from 'react';
import { User, Bike, UtensilsCrossed, ShieldAlert } from 'lucide-react';

export default function RoleSwitcher({ currentRole, onChangeRole }) {
    const roles = [
        {
            id: 'customer',
            label: 'Customer',
            desc: 'Order & Track',
            icon: User,
            color: 'emerald',
            badgeBg: 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20'
        },
        {
            id: 'driver',
            label: 'Driver Courier',
            desc: 'Offers & GPS Trips',
            icon: Bike,
            color: 'amber',
            badgeBg: 'bg-amber-500/10 text-amber-700 border-amber-500/20'
        },
        {
            id: 'restaurant',
            label: 'Restaurant / Kitchen',
            desc: 'KDS & Menu Prep',
            icon: UtensilsCrossed,
            color: 'rose',
            badgeBg: 'bg-rose-500/10 text-rose-700 border-rose-500/20'
        },
        {
            id: 'operations',
            label: 'Operations & Dispatch',
            desc: 'Fleet & Dispatch',
            icon: ShieldAlert,
            color: 'indigo',
            badgeBg: 'bg-indigo-500/10 text-indigo-700 border-indigo-500/20'
        }
    ];

    return (
        <div className="bg-white border-b border-slate-200/80 px-8 py-2.5 flex items-center justify-between">
            <div className="flex items-center gap-2">
                <span className="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                    Switch Role View:
                </span>
                <div className="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl">
                    {roles.map((role) => {
                        const Icon = role.icon;
                        const isCurrent = currentRole === role.id;
                        return (
                            <button
                                key={role.id}
                                onClick={() => onChangeRole(role.id)}
                                className={`flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all ${
                                    isCurrent
                                        ? 'bg-white text-slate-900 shadow-xs ring-1 ring-slate-950/5'
                                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/60'
                                }`}
                            >
                                <Icon className={`w-3.5 h-3.5 ${isCurrent ? 'text-emerald-700' : 'text-slate-400'}`} />
                                <span>{role.label}</span>
                            </button>
                        );
                    })}
                </div>
            </div>

            <div className="hidden lg:flex items-center gap-2">
                <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span className="text-[11px] font-semibold text-slate-500">
                    Active Environment: <strong className="text-slate-800">Unified Multi-Role SPA</strong>
                </span>
            </div>
        </div>
    );
}
