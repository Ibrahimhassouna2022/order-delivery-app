import React from 'react';
import { ArrowUpRight, Zap, Shield, Compass } from 'lucide-react';

export default function QuickBanner({ onOpenNewDelivery }) {
    return (
        <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#0d3b32] via-[#104a3e] to-[#0a2f28] p-6 text-white shadow-md border border-emerald-800/40">
            {/* Background geometric accents */}
            <div className="absolute right-0 top-0 translate-x-12 -translate-y-12 w-64 h-64 rounded-full bg-emerald-400/10 blur-2xl pointer-events-none" />
            <div className="absolute right-48 bottom-0 translate-y-12 w-48 h-48 rounded-full bg-teal-300/10 blur-xl pointer-events-none" />

            <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div className="space-y-2 max-w-xl">
                    <div className="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 text-[11px] font-semibold tracking-wide">
                        <Zap className="w-3.5 h-3.5 text-emerald-300" />
                        Guaranteed Same-Hour Delivery
                    </div>
                    <h2 className="text-xl md:text-2xl font-extrabold tracking-tight text-white">
                        Send a package across the city in minutes.
                    </h2>
                    <p className="text-xs text-emerald-100/80 leading-relaxed">
                        Instant driver dispatch with door-to-door GPS live tracking, contactless proof of delivery, and insured parcel coverage.
                    </p>
                </div>

                <div className="flex items-center gap-3 shrink-0">
                    <button
                        onClick={onOpenNewDelivery}
                        className="group flex items-center gap-3 px-5 py-3 rounded-xl bg-emerald-400 hover:bg-emerald-300 text-slate-900 font-bold text-xs transition-all shadow-lg shadow-emerald-950/30 active:scale-[0.98]"
                    >
                        <span>Request Dispatch Now</span>
                        <div className="w-6 h-6 rounded-lg bg-emerald-900/20 flex items-center justify-center transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5">
                            <ArrowUpRight className="w-3.5 h-3.5 text-slate-900" />
                        </div>
                    </button>
                </div>
            </div>
        </div>
    );
}
