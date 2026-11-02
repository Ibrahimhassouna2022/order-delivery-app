import React, { useState } from 'react';
import { X, MapPin, Package, Clock, Shield, Sparkles, Check, ArrowRight } from 'lucide-react';

export default function NewDeliveryModal({ isOpen, onClose, onCreateOrder }) {
    if (!isOpen) return null;

    const [pickup, setPickup] = useState('742 Evergreen Terrace');
    const [dropoff, setDropoff] = useState('120 Broadway St, Suite 400');
    const [packageType, setPackageType] = useState('food');
    const [speed, setSpeed] = useState('priority');
    const [notes, setNotes] = useState('Please leave with building reception.');

    const packageOptions = [
        { id: 'food', label: 'Food & Meals', icon: '🍲', desc: 'Insulated thermal bag' },
        { id: 'docs', label: 'Documents', icon: '📄', desc: 'Waterproof envelope' },
        { id: 'parcel', label: 'Box / Parcel', icon: '📦', desc: 'Up to 15 kg' },
        { id: 'fragile', label: 'Fragile Item', icon: '🍷', desc: 'Extra padded handling' },
    ];

    const speeds = [
        { id: 'standard', label: 'Standard', time: '35-45 mins', price: '$8.50' },
        { id: 'priority', label: 'Express Priority', time: '15-25 mins', price: '$14.00', recommended: true },
    ];

    const handleSubmit = (e) => {
        e.preventDefault();
        const newOrder = {
            id: Date.now(),
            trackingNumber: `DSH-${Math.floor(100000 + Math.random() * 900000)}`,
            name: packageType === 'food' ? 'Fresh Gourmet Drop' : 'Priority Parcel',
            restaurantName: 'Direct Pickup',
            pickupAddress: pickup,
            dropoffAddress: dropoff,
            status: 'Out for delivery',
            eta: speed === 'priority' ? '18 mins' : '35 mins',
            date: 'Today',
            time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
            totalAmount: speed === 'priority' ? 14.00 : 8.50,
            amount: speed === 'priority' ? 14.00 : 8.50,
            driverName: 'Marcus Bennett',
            vehicleInfo: 'Honda Metro Scooter',
            itemCount: 1,
            orderNumber: `ORD-${Math.floor(1000 + Math.random() * 9000)}`
        };
        onCreateOrder(newOrder);
        onClose();
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs animate-in fade-in duration-200">
            <div className="bg-white rounded-3xl max-w-lg w-full shadow-2xl border border-slate-100 overflow-hidden flex flex-col max-h-[90vh]">
                {/* Modal Header */}
                <div className="p-6 bg-[#0d3b32] text-white flex items-center justify-between">
                    <div>
                        <div className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-400/20 text-emerald-300 text-[10px] font-bold uppercase tracking-wider mb-1">
                            <Sparkles className="w-3 h-3" />
                            New Dispatch Request
                        </div>
                        <h2 className="text-lg font-bold">Send a Delivery</h2>
                        <p className="text-xs text-emerald-200/70">Connect with nearest available courier in real-time</p>
                    </div>
                    <button
                        onClick={onClose}
                        className="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors text-white"
                    >
                        <X className="w-4 h-4" />
                    </button>
                </div>

                {/* Form Body */}
                <form onSubmit={handleSubmit} className="p-6 space-y-5 overflow-y-auto flex-1 text-xs">
                    {/* Addresses */}
                    <div className="space-y-3">
                        <div>
                            <label className="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                                Pickup Location
                            </label>
                            <div className="relative">
                                <MapPin className="w-4 h-4 text-emerald-600 absolute left-3 top-1/2 -translate-y-1/2" />
                                <input
                                    type="text"
                                    required
                                    value={pickup}
                                    onChange={(e) => setPickup(e.target.value)}
                                    className="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-slate-800"
                                />
                            </div>
                        </div>

                        <div>
                            <label className="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                                Destination Address
                            </label>
                            <div className="relative">
                                <MapPin className="w-4 h-4 text-rose-500 absolute left-3 top-1/2 -translate-y-1/2" />
                                <input
                                    type="text"
                                    required
                                    value={dropoff}
                                    onChange={(e) => setDropoff(e.target.value)}
                                    className="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-slate-800"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Package Type */}
                    <div>
                        <label className="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">
                            Package Category
                        </label>
                        <div className="grid grid-cols-2 gap-2">
                            {packageOptions.map((opt) => (
                                <button
                                    type="button"
                                    key={opt.id}
                                    onClick={() => setPackageType(opt.id)}
                                    className={`p-3 rounded-xl border text-left flex items-start gap-2.5 transition-all ${
                                        packageType === opt.id
                                            ? 'border-emerald-500 bg-emerald-50/50 ring-1 ring-emerald-500'
                                            : 'border-slate-200 hover:border-slate-300'
                                    }`}
                                >
                                    <span className="text-xl">{opt.icon}</span>
                                    <div>
                                        <p className="font-bold text-slate-800 text-xs">{opt.label}</p>
                                        <p className="text-[10px] text-slate-500">{opt.desc}</p>
                                    </div>
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Speed / Service Level */}
                    <div>
                        <label className="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">
                            Service Level & Estimated Time
                        </label>
                        <div className="grid grid-cols-2 gap-2">
                            {speeds.map((s) => (
                                <button
                                    type="button"
                                    key={s.id}
                                    onClick={() => setSpeed(s.id)}
                                    className={`p-3 rounded-xl border text-left relative transition-all ${
                                        speed === s.id
                                            ? 'border-emerald-500 bg-emerald-50/50 ring-1 ring-emerald-500'
                                            : 'border-slate-200 hover:border-slate-300'
                                    }`}
                                >
                                    {s.recommended && (
                                        <span className="absolute top-2 right-2 px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-emerald-600 text-white uppercase">
                                            Popular
                                        </span>
                                    )}
                                    <p className="font-bold text-slate-900 text-xs">{s.label}</p>
                                    <p className="text-[11px] text-slate-500 mt-0.5">{s.time}</p>
                                    <p className="text-sm font-extrabold text-emerald-700 mt-1">{s.price}</p>
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Driver Delivery Instructions */}
                    <div>
                        <label className="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                            Delivery Notes (Optional)
                        </label>
                        <textarea
                            rows="2"
                            value={notes}
                            onChange={(e) => setNotes(e.target.value)}
                            placeholder="Gate code, specific door instructions, etc..."
                            className="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 text-slate-800 text-xs resize-none"
                        />
                    </div>

                    {/* Submission CTA */}
                    <div className="pt-2">
                        <button
                            type="submit"
                            className="w-full py-3.5 px-4 bg-[#0d3b32] hover:bg-[#092923] text-white font-bold rounded-xl text-xs flex items-center justify-center gap-2 shadow-md shadow-emerald-950/20 active:scale-[0.98] transition-all"
                        >
                            <span>Confirm & Request Courier ({speed === 'priority' ? '$14.00' : '$8.50'})</span>
                            <ArrowRight className="w-4 h-4 text-emerald-300" />
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
