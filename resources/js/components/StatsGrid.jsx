import React from 'react';
import { PackageCheck, Timer, TrendingUp, ShieldCheck } from 'lucide-react';

export default function StatsGrid() {
    const stats = [
        {
            title: 'Active Shipments',
            value: '2 Orders',
            change: '+1 on route',
            isPositive: true,
            icon: PackageCheck,
            color: 'emerald',
            bg: 'bg-emerald-50 text-emerald-700',
        },
        {
            title: 'Average Delivery Time',
            value: '24 mins',
            change: '-4m faster than avg',
            isPositive: true,
            icon: Timer,
            color: 'teal',
            bg: 'bg-teal-50 text-teal-700',
        },
        {
            title: 'On-Time Success Rate',
            value: '99.2%',
            change: 'Top Tier Rating',
            isPositive: true,
            icon: ShieldCheck,
            color: 'blue',
            bg: 'bg-sky-50 text-sky-700',
        },
        {
            title: 'Spent This Month',
            value: '$342.80',
            change: '14 Deliveries',
            isPositive: false,
            icon: TrendingUp,
            color: 'amber',
            bg: 'bg-amber-50 text-amber-700',
        },
    ];

    return (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {stats.map((stat, idx) => {
                const Icon = stat.icon;
                return (
                    <div
                        key={idx}
                        className="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs hover:shadow-xs transition-shadow"
                    >
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-medium text-slate-500">{stat.title}</span>
                            <div className={`w-8 h-8 rounded-xl flex items-center justify-center ${stat.bg}`}>
                                <Icon className="w-4 h-4" />
                            </div>
                        </div>
                        <div className="mt-3 flex items-baseline justify-between">
                            <span className="text-xl font-bold tracking-tight text-slate-900">{stat.value}</span>
                            <span className={`text-[11px] font-semibold ${stat.isPositive ? 'text-emerald-600' : 'text-slate-500'}`}>
                                {stat.change}
                            </span>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
