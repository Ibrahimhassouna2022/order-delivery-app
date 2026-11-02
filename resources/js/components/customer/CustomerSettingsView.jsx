import React, { useState } from 'react';
import { User, MapPin, Bell, Shield, Check, Plus, Home, Briefcase } from 'lucide-react';

export default function CustomerSettingsView({ user }) {
    const [savedAddresses, setSavedAddresses] = useState([
        { id: 1, label: 'Home Apartment', address: '84 King Street, Apt 4B, New York, NY 10014', icon: Home, isDefault: true },
        { id: 2, label: 'Tech Workspace', address: '200 Mercer St, Office 3, New York, NY 10012', icon: Briefcase, isDefault: false },
    ]);

    const [contactless, setContactless] = useState(true);
    const [smsUpdates, setSmsUpdates] = useState(true);
    const [gateCode, setGateCode] = useState('#4092');

    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-xl font-bold text-slate-900">Account Preferences</h2>
                <p className="text-xs text-slate-500">Manage your profile, dropoff addresses, and delivery instructions</p>
            </div>

            {/* Profile Overview */}
            <div className="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4">
                <h3 className="font-bold text-slate-900 text-sm">Personal Information</h3>
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div>
                        <label className="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Full Name</label>
                        <input
                            type="text"
                            defaultValue={user?.name || 'Alex Morgan'}
                            className="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-800"
                        />
                    </div>
                    <div>
                        <label className="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Email Address</label>
                        <input
                            type="email"
                            defaultValue={user?.email || 'alex@dashly.app'}
                            className="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-800"
                        />
                    </div>
                    <div>
                        <label className="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Phone Number</label>
                        <input
                            type="tel"
                            defaultValue="+1 (555) 234-5678"
                            className="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-800"
                        />
                    </div>
                </div>
            </div>

            {/* Saved Addresses */}
            <div className="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h3 className="font-bold text-slate-900 text-sm">Saved Delivery Addresses</h3>
                        <p className="text-xs text-slate-500">Pre-fill addresses with 1-click at checkout</p>
                    </div>
                    <button className="text-xs font-bold text-emerald-700 hover:text-emerald-800 flex items-center gap-1">
                        <Plus className="w-3.5 h-3.5" />
                        <span>Add Address</span>
                    </button>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {savedAddresses.map((addr) => {
                        const Icon = addr.icon;
                        return (
                            <div key={addr.id} className="p-4 rounded-2xl border border-slate-200 bg-slate-50/60 flex items-start justify-between">
                                <div className="flex items-start gap-3">
                                    <div className="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0">
                                        <Icon className="w-4 h-4" />
                                    </div>
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <h4 className="font-bold text-xs text-slate-900">{addr.label}</h4>
                                            {addr.isDefault && (
                                                <span className="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[9px] font-extrabold uppercase">
                                                    Default
                                                </span>
                                            )}
                                        </div>
                                        <p className="text-xs text-slate-500 mt-1">{addr.address}</p>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>

            {/* Dropoff Instructions & Toggles */}
            <div className="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4 text-xs">
                <h3 className="font-bold text-slate-900 text-sm">Default Delivery Instructions</h3>
                
                <div className="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70">
                    <div>
                        <p className="font-bold text-slate-900">Contactless Dropoff</p>
                        <p className="text-slate-500 text-[11px]">Courier will leave package at door and send photo verification</p>
                    </div>
                    <button
                        onClick={() => setContactless(!contactless)}
                        className={`w-11 h-6 flex items-center rounded-full p-1 transition-colors ${contactless ? 'bg-emerald-600' : 'bg-slate-300'}`}
                    >
                        <span className={`bg-white w-4 h-4 rounded-full shadow-md transform transition-transform ${contactless ? 'translate-x-5' : 'translate-x-0'}`} />
                    </button>
                </div>

                <div className="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70">
                    <div>
                        <p className="font-bold text-slate-900">SMS Driver Tracking Link</p>
                        <p className="text-slate-500 text-[11px]">Send automatic SMS notification with live GPS link when driver picks up</p>
                    </div>
                    <button
                        onClick={() => setSmsUpdates(!smsUpdates)}
                        className={`w-11 h-6 flex items-center rounded-full p-1 transition-colors ${smsUpdates ? 'bg-emerald-600' : 'bg-slate-300'}`}
                    >
                        <span className={`bg-white w-4 h-4 rounded-full shadow-md transform transition-transform ${smsUpdates ? 'translate-x-5' : 'translate-x-0'}`} />
                    </button>
                </div>
            </div>
        </div>
    );
}
