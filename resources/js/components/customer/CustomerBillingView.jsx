import React, { useState } from 'react';
import { CreditCard, Plus, ShieldCheck, Check, ArrowUpRight, DollarSign, Wallet } from 'lucide-react';

export default function CustomerBillingView() {
    const [cards, setCards] = useState([
        { id: 1, brand: 'Visa', last4: '4242', exp: '08/28', isDefault: true, bg: 'from-slate-900 to-slate-800' },
        { id: 2, brand: 'Mastercard', last4: '8810', exp: '11/27', isDefault: false, bg: 'from-emerald-950 to-emerald-900' },
    ]);

    const [walletBalance, setWalletBalance] = useState(48.50);

    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-xl font-bold text-slate-900">Billing & Wallet</h2>
                <p className="text-xs text-slate-500">Manage payment methods, Dashly Cash credits, and invoices</p>
            </div>

            {/* Wallet & Credits Card */}
            <div className="bg-gradient-to-r from-[#0d3b32] via-[#092923] to-[#051a16] rounded-3xl p-6 text-white shadow-md flex flex-col md:flex-row md:items-center justify-between gap-6 border border-emerald-800/40">
                <div className="flex items-center gap-4">
                    <div className="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-400/30 flex items-center justify-center text-emerald-300">
                        <Wallet className="w-6 h-6" />
                    </div>
                    <div>
                        <span className="text-[10px] font-bold uppercase tracking-wider text-emerald-300/80 block">
                            Dashly Cash Balance
                        </span>
                        <h3 className="text-3xl font-black text-white">${walletBalance.toFixed(2)}</h3>
                        <p className="text-xs text-emerald-100/70">Applied automatically to delivery fees at checkout</p>
                    </div>
                </div>

                <div className="flex items-center gap-3">
                    <button 
                        onClick={() => setWalletBalance((b) => b + 25)}
                        className="px-5 py-2.5 rounded-xl bg-emerald-400 hover:bg-emerald-300 text-slate-950 font-bold text-xs shadow-md transition-all active:scale-95"
                    >
                        + Top-up $25
                    </button>
                </div>
            </div>

            {/* Saved Payment Cards */}
            <div className="space-y-3">
                <div className="flex items-center justify-between">
                    <h3 className="font-bold text-slate-900 text-sm">Payment Methods</h3>
                    <button className="text-xs font-bold text-emerald-700 hover:text-emerald-800 flex items-center gap-1">
                        <Plus className="w-3.5 h-3.5" />
                        <span>Add New Card</span>
                    </button>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {cards.map((card) => (
                        <div
                            key={card.id}
                            className={`p-6 rounded-2xl text-white bg-gradient-to-br ${card.bg} shadow-md border border-slate-700 flex flex-col justify-between h-40 relative overflow-hidden`}
                        >
                            <div className="flex items-center justify-between">
                                <span className="font-extrabold tracking-widest text-sm uppercase">{card.brand}</span>
                                {card.isDefault && (
                                    <span className="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 text-[10px] font-bold">
                                        Default Card
                                    </span>
                                )}
                            </div>

                            <p className="text-lg font-mono tracking-widest">•••• •••• •••• {card.last4}</p>

                            <div className="flex items-center justify-between text-xs text-slate-400">
                                <span>Cardholder: ALEX MORGAN</span>
                                <span>Expires: {card.exp}</span>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
