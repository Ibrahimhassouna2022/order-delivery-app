import React from 'react';
import { Navigation2, MapPin, Building2, Store } from 'lucide-react';

export default function MapRouteView({ activeOrder }) {
    return (
        <div className="relative w-full h-56 md:h-64 rounded-2xl overflow-hidden bg-slate-900 border border-slate-800 shadow-inner group">
            {/* Map Background Grid Simulation */}
            <svg className="absolute inset-0 w-full h-full opacity-40" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                        <path d="M 40 0 L 0 0 0 40" fill="none" stroke="#334155" strokeWidth="1" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)" />
            </svg>

            {/* Stylized City Road Network */}
            <svg className="absolute inset-0 w-full h-full" xmlns="http://www.w3.org/2000/svg">
                {/* Background Roads */}
                <path d="M 0 80 Q 180 90 320 60 T 680 120 T 1000 80" fill="none" stroke="#1e293b" strokeWidth="14" />
                <path d="M 120 0 L 140 300" fill="none" stroke="#1e293b" strokeWidth="10" />
                <path d="M 460 0 L 440 300" fill="none" stroke="#1e293b" strokeWidth="12" />
                <path d="M 780 0 L 800 300" fill="none" stroke="#1e293b" strokeWidth="10" />

                {/* Active Delivery Route Polyline */}
                <path
                    d="M 90 140 C 220 140, 260 90, 420 100 S 560 170, 750 150"
                    fill="none"
                    stroke="#10b981"
                    strokeWidth="5"
                    strokeDasharray="8 6"
                    className="animate-[dash_20s_linear_infinite]"
                />
            </svg>

            {/* Origin Node (Store / Restaurant) */}
            <div className="absolute top-[125px] left-[75px] -translate-x-1/2 -translate-y-1/2 z-10 flex flex-col items-center">
                <div className="w-8 h-8 rounded-full bg-slate-800 border-2 border-emerald-400 flex items-center justify-center shadow-lg text-emerald-400">
                    <Store className="w-4 h-4" />
                </div>
                <div className="mt-1 px-2 py-0.5 rounded bg-slate-900/90 border border-slate-700 text-[10px] font-semibold text-slate-200 whitespace-nowrap shadow">
                    Lincoln & 71st
                </div>
            </div>

            {/* Active Moving Courier Pin with Pulsing Effect */}
            <div className="absolute top-[88px] left-[420px] -translate-x-1/2 -translate-y-1/2 z-20 flex flex-col items-center">
                <div className="relative">
                    <span className="absolute -inset-2 rounded-full bg-emerald-400/30 animate-ping"></span>
                    <div className="relative w-9 h-9 rounded-full bg-emerald-500 border-2 border-white flex items-center justify-center text-slate-900 shadow-xl font-bold">
                        <Navigation2 className="w-5 h-5 text-slate-950 rotate-45" />
                    </div>
                </div>
                <div className="mt-1.5 px-2.5 py-0.5 rounded-full bg-emerald-500 text-slate-950 text-[10px] font-extrabold uppercase tracking-wider shadow-lg flex items-center gap-1">
                    <span className="w-1.5 h-1.5 rounded-full bg-slate-950 animate-pulse"></span>
                    Driver On Way
                </div>
            </div>

            {/* Destination Node (Customer Location) */}
            <div className="absolute top-[135px] left-[750px] -translate-x-1/2 -translate-y-1/2 z-10 flex flex-col items-center">
                <div className="w-8 h-8 rounded-full bg-rose-600 border-2 border-white flex items-center justify-center shadow-lg text-white">
                    <MapPin className="w-4 h-4" />
                </div>
                <div className="mt-1 px-2 py-0.5 rounded bg-slate-900/90 border border-slate-700 text-[10px] font-semibold text-slate-200 whitespace-nowrap shadow">
                    84 King Street
                </div>
            </div>

            {/* Map Overlay Controls */}
            <div className="absolute bottom-3 right-3 z-10 flex items-center gap-2 bg-slate-900/80 backdrop-blur px-3 py-1.5 rounded-xl border border-slate-700/60 text-xs text-slate-300">
                <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Live GPS Radar • 30 FPS</span>
            </div>
        </div>
    );
}
