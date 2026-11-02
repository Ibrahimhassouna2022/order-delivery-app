import React from 'react';
import { 
    LayoutDashboard, 
    Package, 
    MapPin, 
    Clock, 
    CreditCard, 
    Settings, 
    LogOut,
    Sparkles,
    ShieldCheck
} from 'lucide-react';

export default function Sidebar({ currentTab, setCurrentTab, activeDeliveriesCount = 1, user, onLogout }) {
    const navItems = [
        { id: 'dashboard', label: 'Dashboard', icon: LayoutDashboard },
        { id: 'deliveries', label: 'My Deliveries', icon: Package, badge: activeDeliveriesCount },
        { id: 'tracking', label: 'Courier & Landmark', icon: MapPin },
        { id: 'history', label: 'Order History', icon: Clock },
        { id: 'settings', label: 'Preferences', icon: Settings },
    ];

    return (
        <aside className="w-64 bg-[#0d3b32] text-slate-100 flex flex-col shrink-0 border-r border-[#134e42] select-none h-screen sticky top-0">
            {/* Brand Logo & Header */}
            <div className="p-6 border-b border-[#14493e] flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-400/30 flex items-center justify-center text-emerald-400 font-bold text-xl shadow-inner">
                        <Package className="w-5 h-5 text-emerald-400" />
                    </div>
                    <div>
                        <span className="font-extrabold tracking-tight text-lg text-white block">
                            Dashly
                        </span>
                        <span className="text-[11px] font-medium tracking-wide text-emerald-300/70 block uppercase">
                            Customer Suite
                        </span>
                    </div>
                </div>
            </div>

            {/* Navigation Links */}
            <div className="flex-1 px-3 py-6 space-y-1 overflow-y-auto">
                <div className="px-3 pb-2 text-[11px] font-semibold text-emerald-200/50 uppercase tracking-wider">
                    Customer Menu
                </div>

                {navItems.map((item) => {
                    const Icon = item.icon;
                    const isActive = currentTab === item.id;
                    return (
                        <button
                            key={item.id}
                            onClick={() => setCurrentTab(item.id)}
                            className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 ${
                                isActive
                                    ? 'bg-emerald-500 text-white shadow-md shadow-emerald-950/20 font-semibold'
                                    : 'text-emerald-100/75 hover:bg-emerald-900/40 hover:text-white'
                            }`}
                        >
                            <div className="flex items-center gap-3">
                                <Icon className={`w-4 h-4 ${isActive ? 'text-white' : 'text-emerald-300/70'}`} />
                                <span>{item.label}</span>
                            </div>
                            {item.badge ? (
                                <span className={`text-xs px-2 py-0.5 rounded-full font-bold ${
                                    isActive 
                                        ? 'bg-emerald-700/60 text-white' 
                                        : 'bg-emerald-500/20 text-emerald-300 border border-emerald-400/20'
                                }`}>
                                    {item.badge}
                                </span>
                            ) : null}
                        </button>
                    );
                })}
            </div>

            {/* User Profile Bar with Logout */}
            <div className="p-4 border-t border-[#14493e] bg-[#092923]/60 flex items-center justify-between">
                <div className="flex items-center gap-3 min-w-0">
                    <div className="relative shrink-0">
                        <div className="w-9 h-9 rounded-full bg-emerald-700 text-emerald-100 flex items-center justify-center font-bold text-sm border-2 border-emerald-400/40">
                            {user?.name ? user.name.slice(0, 2).toUpperCase() : 'AM'}
                        </div>
                        <span className="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-400 border-2 border-[#092923] rounded-full"></span>
                    </div>
                    <div className="truncate">
                        <p className="text-xs font-semibold text-white leading-tight truncate">{user?.name || 'Alex Morgan'}</p>
                        <p className="text-[11px] text-emerald-200/60 leading-tight truncate">{user?.email || 'alex@dashly.app'}</p>
                    </div>
                </div>
                
                <button
                    onClick={onLogout}
                    title="Sign Out"
                    className="p-1.5 rounded-lg text-emerald-300/70 hover:text-white hover:bg-emerald-800/50 transition-colors shrink-0"
                >
                    <LogOut className="w-4 h-4" />
                </button>
            </div>
        </aside>
    );
}
