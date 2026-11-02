import React from 'react';
import { Search, Bell, Plus, MapPin, Sparkles } from 'lucide-react';

export default function Header({ onOpenNewDelivery, searchQuery, setSearchQuery }) {
    const today = new Date().toLocaleDateString('en-US', { 
        weekday: 'long', 
        month: 'short', 
        day: 'numeric' 
    });

    return (
        <header className="bg-white border-b border-slate-200/80 sticky top-0 z-20 px-8 py-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
            {/* Left: Greeting & Date */}
            <div>
                <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-emerald-800">
                    <span className="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Dashly Live</span>
                    <span className="text-slate-300">•</span>
                    <span className="text-slate-500 font-normal">{today}</span>
                </div>
                <h1 className="text-2xl font-bold tracking-tight text-slate-900 mt-0.5">
                    Good morning, Alex.
                </h1>
                <p className="text-xs text-slate-500">
                    Where are you delivering today?
                </p>
            </div>

            {/* Right: Search, Notifications & Primary Action */}
            <div className="flex items-center gap-3">
                {/* Search Bar */}
                <div className="relative w-72">
                    <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                    <input
                        type="text"
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        placeholder="Search restaurants..."
                        className="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                    />
                </div>

                {/* Notifications Bell */}
                <button 
                    title="Notifications"
                    className="relative p-2.5 rounded-xl border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors"
                >
                    <Bell className="w-4 h-4" />
                    <span className="absolute top-2 right-2 w-2 h-2 bg-rose-500 rounded-full ring-2 ring-white"></span>
                </button>

                {/* Primary CTA - "Send a Delivery" */}
                <button
                    onClick={onOpenNewDelivery}
                    className="flex items-center gap-2 px-4 py-2.5 bg-[#0d3b32] hover:bg-[#092923] text-white rounded-xl text-xs font-semibold shadow-sm shadow-emerald-950/20 transition-all hover:shadow active:scale-[0.98]"
                >
                    <Plus className="w-4 h-4 text-emerald-300" />
                    <span>Send a delivery</span>
                </button>
            </div>
        </header>
    );
}
