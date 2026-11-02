import React from 'react';
import { X, MapPin, Bike, CheckCircle2, Clock, Phone, MessageSquare, Download, Share2 } from 'lucide-react';

export default function OrderDetailsDrawer({ order, onClose }) {
    if (!order) return null;

    return (
        <div className="fixed inset-0 z-50 flex justify-end bg-slate-950/40 backdrop-blur-xs animate-in fade-in duration-200">
            <div className="w-full max-w-md bg-white h-full shadow-2xl flex flex-col border-l border-slate-200 animate-in slide-in-from-right duration-300">
                {/* Header */}
                <div className="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div>
                        <span className="text-[10px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                            {order.status}
                        </span>
                        <h2 className="text-base font-bold text-slate-900 mt-1">Delivery #{order.trackingNumber}</h2>
                        <p className="text-xs text-slate-400">{order.date} at {order.time}</p>
                    </div>
                    <button
                        onClick={onClose}
                        className="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition-colors text-slate-500"
                    >
                        <X className="w-4 h-4" />
                    </button>
                </div>

                {/* Content */}
                <div className="p-6 space-y-6 overflow-y-auto flex-1 text-xs">
                    {/* Live Progress Timeline */}
                    <div className="bg-slate-50 rounded-2xl p-4 border border-slate-200/70">
                        <h3 className="font-bold text-slate-800 text-xs mb-3">Shipment Progress</h3>
                        <div className="space-y-4 relative pl-5 before:content-[''] before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-emerald-300">
                            <div className="relative">
                                <span className="absolute -left-5 top-0.5 w-3.5 h-3.5 rounded-full bg-emerald-500 ring-2 ring-emerald-100"></span>
                                <p className="font-bold text-slate-800 text-xs">Order Confirmed</p>
                                <p className="text-[11px] text-slate-400">Merchant prepared package</p>
                            </div>
                            <div className="relative">
                                <span className="absolute -left-5 top-0.5 w-3.5 h-3.5 rounded-full bg-emerald-500 ring-2 ring-emerald-100"></span>
                                <p className="font-bold text-slate-800 text-xs">Courier Assigned</p>
                                <p className="text-[11px] text-slate-400">{order.driverName || 'Courier'} picked up package</p>
                            </div>
                            <div className="relative">
                                <span className={`absolute -left-5 top-0.5 w-3.5 h-3.5 rounded-full ${order.status.toLowerCase() === 'delivered' ? 'bg-emerald-500 ring-2 ring-emerald-100' : 'bg-emerald-500 animate-ping'}`}></span>
                                <p className="font-bold text-emerald-800 text-xs">Out For Delivery</p>
                                <p className="text-[11px] text-slate-400">Estimated delivery: {order.eta || 'Completed'}</p>
                            </div>
                        </div>
                    </div>

                    {/* Addresses */}
                    <div className="space-y-3">
                        <div className="p-3 rounded-xl border border-slate-100 bg-white">
                            <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Origin</span>
                            <p className="font-semibold text-slate-800 text-xs">{order.pickupAddress}</p>
                            <p className="text-[11px] text-slate-400">{order.restaurantName}</p>
                        </div>
                        <div className="p-3 rounded-xl border border-slate-100 bg-white">
                            <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Destination</span>
                            <p className="font-semibold text-slate-800 text-xs">{order.dropoffAddress}</p>
                        </div>
                    </div>

                    {/* Order Financial Summary */}
                    <div className="border-t border-slate-100 pt-4 space-y-2">
                        <h4 className="font-bold text-slate-900 text-xs">Payment Breakdown</h4>
                        <div className="flex justify-between text-slate-500 text-[11px]">
                            <span>Delivery Fee</span>
                            <span>${(order.totalAmount || order.amount || 12).toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between text-slate-500 text-[11px]">
                            <span>Priority Handling</span>
                            <span>$2.50</span>
                        </div>
                        <div className="flex justify-between text-slate-500 text-[11px]">
                            <span>Service & Platform Fee</span>
                            <span>$1.00</span>
                        </div>
                        <div className="flex justify-between text-slate-900 font-bold text-xs pt-2 border-t border-slate-100">
                            <span>Total Paid</span>
                            <span>${((order.totalAmount || order.amount || 12) + 3.50).toFixed(2)}</span>
                        </div>
                    </div>
                </div>

                {/* Footer Actions */}
                <div className="p-4 border-t border-slate-100 bg-slate-50/50 flex items-center gap-2">
                    <button className="flex-1 py-2.5 px-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs flex items-center justify-center gap-1.5 transition-colors">
                        <Download className="w-3.5 h-3.5" />
                        <span>Download Invoice</span>
                    </button>
                    <button className="flex-1 py-2.5 px-3 rounded-xl bg-[#0d3b32] hover:bg-[#092923] text-white font-semibold text-xs flex items-center justify-center gap-1.5 transition-colors">
                        <Share2 className="w-3.5 h-3.5" />
                        <span>Share Tracking</span>
                    </button>
                </div>
            </div>
        </div>
    );
}
