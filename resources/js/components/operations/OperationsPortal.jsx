import React, { useState } from 'react';
import PaymentReviewPanel from '../shared/PaymentReviewPanel';
import PaymentSettingsPanel from '../shared/PaymentSettingsPanel';
import DeliveryZonesPanel from '../shared/DeliveryZonesPanel';
import { 
    ShieldAlert, 
    CreditCard,
    Navigation, 
    AlertTriangle, 
    CheckCircle2, 
    Plus, 
    Store, 
    Bike, 
    Utensils, 
    Trash2, 
    Check, 
    X, 
    Phone, 
    MapPin, 
    DollarSign,
    Users,
    ClipboardList,
    Search,
    Edit3
} from 'lucide-react';

export default function OperationsPortal({ 
    user, 
    onLogout, 
    restaurantsList, 
    onAddRestaurant, 
    onAddMenuItem, 
    onDeleteMenuItem 
}) {
    // Current Active Tab: 'orders' | 'restaurants' | 'drivers'
    const [activeTab, setActiveTab] = useState('orders');

    // Live Orders State
    const [orders, setOrders] = useState([
        {
            id: 101,
            orderNumber: 'ORD-9421',
            restaurant: 'مطعم بالميرا (غزة)',
            customer: 'أحمد خليل',
            status: 'في الطريق للتسليم',
            driver: 'محمود النجار',
            driverPhone: '+970 59 912 3456',
            slaMinutes: 14,
            isDelayed: false
        },
        {
            id: 102,
            orderNumber: 'ORD-9422',
            restaurant: 'مطعم شاورما الأمير (غزة)',
            customer: 'خالد عبد الله',
            status: 'بانتظار سائق (بحث آلي)',
            driver: null,
            slaMinutes: 6,
            isDelayed: true
        },
        {
            id: 103,
            orderNumber: 'ORD-9420',
            restaurant: 'بيتزا روما الإيطالية (غزة)',
            customer: 'سارة إبراهيم',
            status: 'المطعم يجهز الوجبة',
            driver: 'يوسف حماد (محجوز للطلب)',
            slaMinutes: 8,
            isDelayed: false
        }
    ]);

    const [dispatchingId, setDispatchingId] = useState(null);

    // Restaurants Management State (Managed by Operations in Gaza)
    const [restaurants, setRestaurants] = useState(restaurantsList || [
        {
            id: 1,
            name: 'مطعم بالميرا',
            city: 'غزة',
            phone: '+970 59 111 2233',
            address: 'غزة - شارع عمر المختار',
            cuisine: 'شاورما على الفحم • مشاوي • وجبات سريعة وعائلية',
            status: 'active',
            menuItems: [
                { id: 101, name: 'وجبة شاورما عربي دبل', price: 22.00, category: 'وجبات', desc: 'شاورما دجاج مشوية مع ثومية وبطاطا' },
                { id: 102, name: 'ساندويش شاورما صاروخ صاج', price: 14.00, category: 'ساندويشات', desc: 'خبز صاج مقرمش، طحينية وسلطة خضار' },
                { id: 103, name: 'صحن ميكس مشاوي كباب وشقف', price: 45.00, category: 'مشاوي', desc: 'نصف كيلو كباب ولحم مشوي' }
            ]
        },
        {
            id: 2,
            name: 'بيتزا روما الإيطالية',
            city: 'غزة',
            phone: '+970 59 444 5566',
            address: 'غزة - شارع النصر',
            cuisine: 'بيتزا إيطالية • معجنات وكالزوني',
            status: 'active',
            menuItems: [
                { id: 201, name: 'بيتزا مارغريتا عائلية', price: 25.00, category: 'بيتزا', desc: 'صلصة طماطم وجبنة موزاريلا فاخرة' },
                { id: 202, name: 'بيتزا خضار مشكلة سوبريم', price: 28.00, category: 'بيتزا', desc: 'فطر، زيتون أسود وفلفل رومي' }
            ]
        },
        {
            id: 3,
            name: 'مطعم شاورما الأمير',
            city: 'غزة',
            phone: '+970 59 777 8899',
            address: 'غزة - حي الرمال، مفترق ضبيط',
            cuisine: 'شاورما • برجر طازج • عصائر طبيعية',
            status: 'active',
            menuItems: [
                { id: 301, name: 'وجبة شاورما عربي سوبر', price: 20.00, category: 'وجبات', desc: 'شاورما مع بطاطا ومقبلات' },
                { id: 302, name: 'برجر لحم بلدي دبل تشيز', price: 18.00, category: 'ساندويشات', desc: 'لحم طازج مع بطاطا مقلية' }
            ]
        }
    ]);

    // Drivers Fleet State (Managed by Operations in Gaza)
    const [drivers, setDrivers] = useState([
        {
            id: 1,
            name: 'محمود النجار',
            phone: '+970 59 912 3456',
            city: 'غزة',
            vehicle: 'دراجة نارية',
            status: 'active',
            walletBalance: 140.00,
            completedOrders: 42
        },
        {
            id: 2,
            name: 'يوسف حماد',
            phone: '+970 59 888 7766',
            city: 'غزة',
            vehicle: 'دراجة هوائية سريعة',
            status: 'active',
            walletBalance: 85.00,
            completedOrders: 28
        },
        {
            id: 3,
            name: 'طارق المصري',
            phone: '+970 59 333 4455',
            city: 'غزة',
            vehicle: 'دراجة نارية',
            status: 'active',
            walletBalance: 210.00,
            completedOrders: 65
        },
        {
            id: 4,
            name: 'بلال عودة',
            phone: '+970 59 222 1100',
            city: 'غزة',
            vehicle: 'سيارة نقل خفيفة',
            status: 'pending',
            walletBalance: 0.00,
            completedOrders: 0
        }
    ]);

    // Modals & Forms State
    const [showAddRestaurantModal, setShowAddRestaurantModal] = useState(false);
    const [selectedRestaurantForMenu, setSelectedRestaurantForMenu] = useState(null);
    const [showAddDriverModal, setShowAddDriverModal] = useState(false);

    // Form inputs for new restaurant
    const [newRestName, setNewRestName] = useState('');
    const [newRestCity, setNewRestCity] = useState('غزة');
    const [newRestAddress, setNewRestAddress] = useState('');
    const [newRestPhone, setNewRestPhone] = useState('');
    const [newRestCuisine, setNewRestCuisine] = useState('');

    // Form inputs for new menu item
    const [newItemName, setNewItemName] = useState('');
    const [newItemPrice, setNewItemPrice] = useState('');
    const [newItemCategory, setNewItemCategory] = useState('وجبات');
    const [newItemDesc, setNewItemDesc] = useState('');

    // Form inputs for new driver
    const [newDriverName, setNewDriverName] = useState('');
    const [newDriverPhone, setNewDriverPhone] = useState('');
    const [newDriverCity, setNewDriverCity] = useState('غزة');
    const [newDriverVehicle, setNewDriverVehicle] = useState('دراجة نارية');

    // Action: Manual Dispatch
    const handleManualDispatch = (orderId) => {
        setDispatchingId(orderId);
        setTimeout(() => {
            setOrders(orders.map((o) => {
                if (o.id === orderId) {
                    return {
                        ...o,
                        status: 'تم الإسناد لسائق',
                        driver: 'طارق المصري (إسناد يدوي فوري)',
                        isDelayed: false
                    };
                }
                return o;
            }));
            setDispatchingId(null);
        }, 600);
    };

    // Action: Cancel Order
    const handleCancelOrder = (orderId) => {
        setOrders(orders.map((o) => o.id === orderId ? { ...o, status: 'تم الإلغاء من العمليات' } : o));
    };

    // Action: Onboard New Restaurant
    const handleCreateRestaurant = (e) => {
        e.preventDefault();
        if (!newRestName || !newRestAddress) return;

        const newRest = {
            id: Date.now(),
            name: newRestName,
            city: newRestCity,
            address: newRestAddress,
            phone: newRestPhone || '+970 59 000 0000',
            cuisine: newRestCuisine || 'وجبات ومأكولات متنوعة',
            status: 'active',
            menuItems: []
        };

        const updated = [newRest, ...restaurants];
        setRestaurants(updated);
        if (onAddRestaurant) onAddRestaurant(newRest);

        // Reset form
        setNewRestName('');
        setNewRestAddress('');
        setNewRestPhone('');
        setNewRestCuisine('');
        setShowAddRestaurantModal(false);
    };

    // Action: Add Menu Item to Selected Restaurant
    const handleCreateMenuItem = (e) => {
        e.preventDefault();
        if (!newItemName || !newItemPrice || !selectedRestaurantForMenu) return;

        const newItem = {
            id: Date.now(),
            name: newItemName,
            price: parseFloat(newItemPrice) || 15.00,
            category: newItemCategory,
            desc: newItemDesc || ''
        };

        const updatedRestaurants = restaurants.map((r) => {
            if (r.id === selectedRestaurantForMenu.id) {
                const updatedItems = [...r.menuItems, newItem];
                setSelectedRestaurantForMenu({ ...r, menuItems: updatedItems });
                return { ...r, menuItems: updatedItems };
            }
            return r;
        });

        setRestaurants(updatedRestaurants);
        if (onAddMenuItem) onAddMenuItem(selectedRestaurantForMenu.id, newItem);

        // Reset inputs
        setNewItemName('');
        setNewItemPrice('');
        setNewItemDesc('');
    };

    // Action: Delete Menu Item
    const handleDeleteMenuItem = (restaurantId, itemId) => {
        const updatedRestaurants = restaurants.map((r) => {
            if (r.id === restaurantId) {
                const updatedItems = r.menuItems.filter((m) => m.id !== itemId);
                setSelectedRestaurantForMenu({ ...r, menuItems: updatedItems });
                return { ...r, menuItems: updatedItems };
            }
            return r;
        });
        setRestaurants(updatedRestaurants);
        if (onDeleteMenuItem) onDeleteMenuItem(restaurantId, itemId);
    };

    // Action: Onboard New Driver
    const handleCreateDriver = (e) => {
        e.preventDefault();
        if (!newDriverName || !newDriverPhone) return;

        const newDriver = {
            id: Date.now(),
            name: newDriverName,
            phone: newDriverPhone,
            city: newDriverCity,
            vehicle: newDriverVehicle,
            status: 'active',
            walletBalance: 0.00,
            completedOrders: 0
        };

        setDrivers([newDriver, ...drivers]);
        setNewDriverName('');
        setNewDriverPhone('');
        setShowAddDriverModal(false);
    };

    // Action: Toggle Driver Status (تفعيل / تجميد)
    const toggleDriverStatus = (driverId) => {
        setDrivers(drivers.map((d) => {
            if (d.id === driverId) {
                return { ...d, status: d.status === 'active' ? 'suspended' : 'active' };
            }
            return d;
        }));
    };

    return (
        <div className="p-4 md:p-6 space-y-6 max-w-6xl mx-auto w-full font-sans" dir="rtl">
            
            {/* Operations Header & Tabs */}
            <div className="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div className="flex items-center gap-4">
                    <div className="w-13 h-13 rounded-2xl bg-indigo-50 text-indigo-700 flex items-center justify-center font-bold text-xl border border-indigo-200 shrink-0">
                        <ShieldAlert className="w-7 h-7" />
                    </div>
                    <div>
                        <div className="flex items-center gap-2">
                            <h2 className="text-xl font-bold text-slate-900">لوحة تحكم الإدارة والعمليات</h2>
                            <span className="px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-800 text-[10px] font-bold border border-indigo-200">
                                صلاحيات المسؤول الكاملة
                            </span>
                        </div>
                        <p className="text-xs text-slate-500 mt-0.5">
                            الجهة المسؤولة رسمياً عن تسجيل المطاعم وإدراج قوائم الطعام (المنيو) واعتماد وتعيين السائقين
                        </p>
                    </div>
                </div>

                {/* Operations Navigation Tabs */}
                <div className="flex items-center bg-slate-100 p-1.5 rounded-2xl border border-slate-200 text-xs font-bold">
                    <button
                        onClick={() => setActiveTab('orders')}
                        className={`flex items-center gap-1.5 px-3.5 py-2 rounded-xl transition-all ${
                            activeTab === 'orders'
                                ? 'bg-indigo-700 text-white shadow-xs'
                                : 'text-slate-600 hover:text-slate-900'
                        }`}
                    >
                        <ClipboardList className="w-4 h-4" />
                        <span>مراقبة وتوزيع الطلبات</span>
                    </button>

                    <button
                        onClick={() => setActiveTab('restaurants')}
                        className={`flex items-center gap-1.5 px-3.5 py-2 rounded-xl transition-all ${
                            activeTab === 'restaurants'
                                ? 'bg-indigo-700 text-white shadow-xs'
                                : 'text-slate-600 hover:text-slate-900'
                        }`}
                    >
                        <Store className="w-4 h-4" />
                        <span>تسجيل المطاعم والمنيو</span>
                    </button>

                    <button
                        onClick={() => setActiveTab('drivers')}
                        className={`flex items-center gap-1.5 px-3.5 py-2 rounded-xl transition-all ${
                            activeTab === 'drivers'
                                ? 'bg-indigo-700 text-white shadow-xs'
                                : 'text-slate-600 hover:text-slate-900'
                        }`}
                    >
                        <Bike className="w-4 h-4" />
                        <span>اعتماد السائقين والأسطول</span>
                    </button>

                    <button
                        onClick={() => setActiveTab('payments')}
                        className={`flex items-center gap-1.5 px-3.5 py-2 rounded-xl transition-all ${
                            activeTab === 'payments'
                                ? 'bg-indigo-700 text-white shadow-xs'
                                : 'text-slate-600 hover:text-slate-900'
                        }`}
                    >
                        <CreditCard className="w-4 h-4" />
                        <span>الدفع ومناطق التوصيل</span>
                    </button>
                </div>
            </div>

            {/* TAB 1: ORDERS & DISPATCH SUPERVISION */}
            {activeTab === 'orders' && (
                <div className="space-y-6">
                    {/* Live Metric Cards */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div className="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs">
                            <span className="text-xs text-slate-500 block mb-1">الطلبات الجارية الآن</span>
                            <p className="text-2xl font-black text-slate-900">18 طلب</p>
                            <span className="text-[10px] text-emerald-700 font-semibold">توصيل نشط داخل أحياء مدينة غزة 📍</span>
                        </div>

                        <div className="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs">
                            <span className="text-xs text-slate-500 block mb-1">السائقين المتصلين</span>
                            <p className="text-2xl font-black text-slate-900">{drivers.filter(d => d.status === 'active').length} سائق</p>
                            <span className="text-[10px] text-emerald-700 font-semibold">جاهزون لاستقبال العروض</span>
                        </div>

                        <div className="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs">
                            <span className="text-xs text-slate-500 block mb-1">المطاعم المسجلة المعتمدة</span>
                            <p className="text-2xl font-black text-slate-900">{restaurants.length} مطعم</p>
                            <span className="text-[10px] text-slate-500">تم إدراج المنيو من الإدارة</span>
                        </div>

                        <div className="bg-white rounded-2xl p-4 border border-amber-200 bg-amber-50/40 shadow-xs">
                            <span className="text-xs text-amber-900 font-semibold block mb-1">طلبات بانتظار سائق</span>
                            <p className="text-2xl font-black text-amber-950">1 طلب</p>
                            <span className="text-[10px] text-amber-800">تجاوزت مهلة الإسناد الآلي</span>
                        </div>
                    </div>

                    {/* Orders Table */}
                    <div className="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs space-y-4">
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <h3 className="font-bold text-slate-900 text-base">جدول متابعة الطلبات والتدخل الفوري</h3>
                                <p className="text-xs text-slate-500">
                                    مراقبة خوارزمية التوزيع العادل والتدخل للإسناد اليدوي الفوري عند الضرورة
                                </p>
                            </div>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-right text-xs">
                                <thead>
                                    <tr className="border-b border-slate-200 text-slate-500 pb-2">
                                        <th className="pb-3 font-bold">رقم الطلب</th>
                                        <th className="pb-3 font-bold">المطعم</th>
                                        <th className="pb-3 font-bold">العميل</th>
                                        <th className="pb-3 font-bold">الحالة</th>
                                        <th className="pb-3 font-bold">السائق المعين</th>
                                        <th className="pb-3 font-bold text-center">إجراء المشرف</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {orders.map((order) => (
                                        <tr key={order.id} className="hover:bg-slate-50/80 transition-colors">
                                            <td className="py-3.5 font-black text-slate-900">#{order.orderNumber}</td>
                                            <td className="py-3.5 font-bold text-slate-800">{order.restaurant}</td>
                                            <td className="py-3.5 text-slate-600">{order.customer}</td>
                                            <td className="py-3.5">
                                                <span className={`px-2.5 py-1 rounded-full text-[11px] font-bold ${
                                                    order.isDelayed 
                                                        ? 'bg-rose-100 text-rose-800 border border-rose-200 animate-pulse' 
                                                        : 'bg-emerald-50 text-emerald-800 border border-emerald-200'
                                                }`}>
                                                    {order.status}
                                                </span>
                                            </td>
                                            <td className="py-3.5 text-slate-700">
                                                {order.driver ? (
                                                    <span className="font-medium">{order.driver}</span>
                                                ) : (
                                                    <span className="text-amber-800 font-bold">لم يُسند بعد</span>
                                                )}
                                            </td>
                                            <td className="py-3.5 text-center">
                                                <div className="flex items-center justify-center gap-2">
                                                    {order.isDelayed && (
                                                        <button
                                                            onClick={() => handleManualDispatch(order.id)}
                                                            disabled={dispatchingId === order.id}
                                                            className="py-1.5 px-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-[11px] transition-colors"
                                                        >
                                                            {dispatchingId === order.id ? 'جاري الإسناد...' : 'إسناد يدوي فوري'}
                                                        </button>
                                                    )}

                                                    {order.status !== 'تم الإلغاء من العمليات' && (
                                                        <button
                                                            onClick={() => handleCancelOrder(order.id)}
                                                            className="py-1.5 px-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded-lg text-[11px] border border-rose-200 transition-colors"
                                                        >
                                                            إلغاء الطلب
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            )}

            {/* TAB 2: RESTAURANTS & MENU MANAGEMENT (إدارة المطاعم والمنيو) */}
            {activeTab === 'restaurants' && (
                <div className="space-y-6">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h3 className="font-bold text-slate-900 text-lg">إدارة المطاعم وقوائم الطعام (Onboarding)</h3>
                            <p className="text-xs text-slate-500">
                                تسجل الإدارة المطاعم الشريكة شخصياً وتدخل أصناف المنيو وتحدد أسعار الوجبات
                            </p>
                        </div>

                        <button
                            onClick={() => setShowAddRestaurantModal(true)}
                            className="flex items-center gap-2 px-4 py-2.5 bg-[#0d3b32] hover:bg-[#092923] text-white font-bold rounded-xl text-xs transition-all shadow-xs"
                        >
                            <Plus className="w-4 h-4 text-emerald-300" />
                            <span>تسجيل مطعم جديد في النظام</span>
                        </button>
                    </div>

                    {/* Restaurants List */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {restaurants.map((rest) => (
                            <div key={rest.id} className="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs flex flex-col justify-between space-y-4">
                                <div className="space-y-2">
                                    <div className="flex items-center justify-between">
                                        <span className="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 text-[10px] font-bold border border-emerald-200">
                                            معتمد ومسجل
                                        </span>
                                        <span className="text-xs font-bold text-slate-500">{rest.city} 📍</span>
                                    </div>

                                    <h4 className="font-bold text-slate-900 text-base">{rest.name}</h4>
                                    <p className="text-xs text-slate-500">{rest.cuisine}</p>
                                    <p className="text-[11px] text-slate-400 flex items-center gap-1">
                                        <MapPin className="w-3.5 h-3.5 shrink-0" />
                                        <span>{rest.address}</span>
                                    </p>
                                    <p className="text-[11px] text-slate-400 flex items-center gap-1">
                                        <Phone className="w-3.5 h-3.5 shrink-0" />
                                        <span>{rest.phone}</span>
                                    </p>
                                </div>

                                <div className="pt-3 border-t border-slate-100 flex items-center justify-between">
                                    <span className="text-xs font-bold text-slate-700">
                                        عدد أصناف المنيو: {rest.menuItems ? rest.menuItems.length : 0}
                                    </span>
                                    <button
                                        onClick={() => setSelectedRestaurantForMenu(rest)}
                                        className="py-2 px-3 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl font-bold text-xs border border-indigo-200 flex items-center gap-1 transition-colors"
                                    >
                                        <Utensils className="w-3.5 h-3.5" />
                                        <span>إدارة المنيو والأصناف</span>
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* TAB 3: DRIVERS MANAGEMENT (إدارة واعتماد السائقين) */}
            {activeTab === 'drivers' && (
                <div className="space-y-6">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h3 className="font-bold text-slate-900 text-lg">سجل واعتماد كباتن التوصيل (Drivers Fleet)</h3>
                            <p className="text-xs text-slate-500">
                                توثيق واعتماد السائقين وفحص محافظهم المالية والتحقق من نشاطهم في مدن الخدمة
                            </p>
                        </div>

                        <button
                            onClick={() => setShowAddDriverModal(true)}
                            className="flex items-center gap-2 px-4 py-2.5 bg-[#0d3b32] hover:bg-[#092923] text-white font-bold rounded-xl text-xs transition-all shadow-xs"
                        >
                            <Plus className="w-4 h-4 text-emerald-300" />
                            <span>تسجيل واعتماد كابتن جديد</span>
                        </button>
                    </div>

                    {/* Drivers Table */}
                    <div className="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="w-full text-right text-xs">
                                <thead>
                                    <tr className="border-b border-slate-200 text-slate-500 pb-2">
                                        <th className="pb-3 font-bold">اسم الكابتن</th>
                                        <th className="pb-3 font-bold">المدينة</th>
                                        <th className="pb-3 font-bold">رقم الهاتف</th>
                                        <th className="pb-3 font-bold">وسيلة التوصيل</th>
                                        <th className="pb-3 font-bold">رصيد المحفظة</th>
                                        <th className="pb-3 font-bold">الرحلات المكتملة</th>
                                        <th className="pb-3 font-bold">حالة الحساب</th>
                                        <th className="pb-3 font-bold text-center">الإجراء</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {drivers.map((driver) => (
                                        <tr key={driver.id} className="hover:bg-slate-50/80 transition-colors">
                                            <td className="py-3.5 font-bold text-slate-900">{driver.name}</td>
                                            <td className="py-3.5 text-slate-600">{driver.city}</td>
                                            <td className="py-3.5 font-mono text-slate-700">{driver.phone}</td>
                                            <td className="py-3.5 text-slate-600 flex items-center gap-1.5 mt-2">
                                                <Bike className="w-3.5 h-3.5 text-amber-600" />
                                                <span>{driver.vehicle}</span>
                                            </td>
                                            <td className="py-3.5 font-black text-emerald-800">{driver.walletBalance.toFixed(2)} ₪</td>
                                            <td className="py-3.5 font-bold text-slate-700">{driver.completedOrders} رحلة</td>
                                            <td className="py-3.5">
                                                <span className={`px-2.5 py-1 rounded-full text-[10px] font-bold ${
                                                    driver.status === 'active'
                                                        ? 'bg-emerald-50 text-emerald-800 border border-emerald-200'
                                                        : 'bg-rose-50 text-rose-800 border border-rose-200'
                                                }`}>
                                                    {driver.status === 'active' ? 'معتمد ومفعل' : 'معلق / مجمد'}
                                                </span>
                                            </td>
                                            <td className="py-3.5 text-center">
                                                <button
                                                    onClick={() => toggleDriverStatus(driver.id)}
                                                    className={`py-1.5 px-3 rounded-lg text-[11px] font-bold transition-colors ${
                                                        driver.status === 'active'
                                                            ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200'
                                                            : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200'
                                                    }`}
                                                >
                                                    {driver.status === 'active' ? 'تجميد الحساب' : 'تفعيل واعتماد'}
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            )}

            {/* TAB 4: PAYMENT REVIEW, SETTINGS & DELIVERY ZONES */}
            {activeTab === 'payments' && (
                <div className="space-y-6">
                    <PaymentReviewPanel mode="operations" />
                    <PaymentSettingsPanel />
                    <DeliveryZonesPanel />
                </div>
            )}

            {/* MODAL 1: ADD NEW RESTAURANT (تسجيل مطعم جديد) */}
            {showAddRestaurantModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs animate-in fade-in duration-200" dir="rtl">
                    <div className="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                        <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 className="font-bold text-slate-900 text-base">تسجيل مطعم جديد في المنصة (Onboarding)</h3>
                            <button onClick={() => setShowAddRestaurantModal(false)} className="text-slate-400 hover:text-slate-700">
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handleCreateRestaurant} className="space-y-3 text-xs">
                            <div>
                                <label className="block font-bold text-slate-700 mb-1">اسم المطعم</label>
                                <input
                                    type="text"
                                    required
                                    value={newRestName}
                                    onChange={(e) => setNewRestName(e.target.value)}
                                    placeholder="مثال: مطعم شاورما الأمير"
                                    className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block font-bold text-slate-700 mb-1">المدينة</label>
                                    <input
                                        type="text"
                                        readOnly
                                        value="غزة 📍"
                                        className="w-full px-3.5 py-2.5 bg-slate-100 border border-slate-200 rounded-xl font-bold text-slate-800"
                                    />
                                </div>

                                <div>
                                    <label className="block font-bold text-slate-700 mb-1">رقم هاتف المطعم</label>
                                    <input
                                        type="text"
                                        value={newRestPhone}
                                        onChange={(e) => setNewRestPhone(e.target.value)}
                                        placeholder="+970 59 123 4567"
                                        className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none text-left"
                                        dir="ltr"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block font-bold text-slate-700 mb-1">العنوان التفصيلي</label>
                                <input
                                    type="text"
                                    required
                                    value={newRestAddress}
                                    onChange={(e) => setNewRestAddress(e.target.value)}
                                    placeholder="مثال: غزة - شارع الجلاء، بالقرب من مفترق ضبيط"
                                    className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none"
                                />
                            </div>

                            <div>
                                <label className="block font-bold text-slate-700 mb-1">تصنيف المأكولات (الوسوم)</label>
                                <input
                                    type="text"
                                    value={newRestCuisine}
                                    onChange={(e) => setNewRestCuisine(e.target.value)}
                                    placeholder="مثال: شاورما • برجر • وجبات سريعة"
                                    className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none"
                                />
                            </div>

                            <div className="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    onClick={() => setShowAddRestaurantModal(false)}
                                    className="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl font-bold"
                                >
                                    إلغاء
                                </button>
                                <button
                                    type="submit"
                                    className="px-5 py-2 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-xl shadow-xs"
                                >
                                    حفظ وتفعيل المطعم
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* MODAL 2: MANAGE MENU ITEMS FOR RESTAURANT (إدارة قائمة الطعام للمطعم) */}
            {selectedRestaurantForMenu && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs animate-in fade-in duration-200" dir="rtl">
                    <div className="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 max-h-[90vh] flex flex-col space-y-4">
                        <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div>
                                <h3 className="font-bold text-slate-900 text-base">
                                    إدارة قائمة الطعام لـ: {selectedRestaurantForMenu.name}
                                </h3>
                                <p className="text-xs text-slate-500">
                                    تتحكم الإدارة في إدراج الوجبات والأسعار الرسمية بالـ ₪
                                </p>
                            </div>
                            <button onClick={() => setSelectedRestaurantForMenu(null)} className="text-slate-400 hover:text-slate-700">
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        {/* Existing Menu Items List */}
                        <div className="flex-1 overflow-y-auto space-y-2 pr-1">
                            <span className="font-bold text-xs text-slate-700 block mb-1">
                                الأصناف الحالية في القائمة ({selectedRestaurantForMenu.menuItems?.length || 0}):
                            </span>

                            {selectedRestaurantForMenu.menuItems?.map((item) => (
                                <div key={item.id} className="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                                    <div>
                                        <span className="font-bold text-slate-900 text-xs block">{item.name}</span>
                                        <span className="text-[11px] text-slate-500">{item.desc || item.category}</span>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <span className="font-black text-emerald-800 text-xs">{item.price.toFixed(2)} ₪</span>
                                        <button
                                            onClick={() => handleDeleteMenuItem(selectedRestaurantForMenu.id, item.id)}
                                            className="p-1.5 text-rose-500 hover:bg-rose-100 rounded-lg transition-colors"
                                            title="حذف الصنف"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            ))}

                            {(!selectedRestaurantForMenu.menuItems || selectedRestaurantForMenu.menuItems.length === 0) && (
                                <p className="text-xs text-slate-400 py-4 text-center">لا توجد وجبات مدرجة بعد لهذا المطعم</p>
                            )}
                        </div>

                        {/* Add New Menu Item Form */}
                        <div className="pt-3 border-t border-slate-200">
                            <span className="font-bold text-xs text-indigo-900 block mb-2">إدراج وجبة أو صنف جديد:</span>
                            <form onSubmit={handleCreateMenuItem} className="grid grid-cols-1 sm:grid-cols-12 gap-2 text-xs">
                                <div className="sm:col-span-5">
                                    <input
                                        type="text"
                                        required
                                        value={newItemName}
                                        onChange={(e) => setNewItemName(e.target.value)}
                                        placeholder="اسم الوجبة (مثال: وجبة كريسبي)"
                                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white"
                                    />
                                </div>
                                <div className="sm:col-span-3">
                                    <input
                                        type="number"
                                        step="0.5"
                                        required
                                        value={newItemPrice}
                                        onChange={(e) => setNewItemPrice(e.target.value)}
                                        placeholder="السعر (₪)"
                                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white text-left"
                                        dir="ltr"
                                    />
                                </div>
                                <div className="sm:col-span-4">
                                    <select
                                        value={newItemCategory}
                                        onChange={(e) => setNewItemCategory(e.target.value)}
                                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white"
                                    >
                                        <option value="وجبات">وجبات رئيسية</option>
                                        <option value="ساندويشات">ساندويشات</option>
                                        <option value="بيتزا">بيتزا ومعجنات</option>
                                        <option value="مشاوي">مشاوي</option>
                                        <option value="مشروبات">مشروبات ومقبلات</option>
                                    </select>
                                </div>
                                <div className="sm:col-span-9">
                                    <input
                                        type="text"
                                        value={newItemDesc}
                                        onChange={(e) => setNewItemDesc(e.target.value)}
                                        placeholder="مكونات الوجبة وتفاصيلها..."
                                        className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white"
                                    />
                                </div>
                                <div className="sm:col-span-3">
                                    <button
                                        type="submit"
                                        className="w-full py-2 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-xl shadow-xs"
                                    >
                                        + إضافة للمنيو
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}

            {/* MODAL 3: ADD NEW DRIVER (تسجيل واعتماد كابتن جديد) */}
            {showAddDriverModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs animate-in fade-in duration-200" dir="rtl">
                    <div className="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-4">
                        <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 className="font-bold text-slate-900 text-base">تسجيل واعتماد كابتن جديد (Driver Onboarding)</h3>
                            <button onClick={() => setShowAddDriverModal(false)} className="text-slate-400 hover:text-slate-700">
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handleCreateDriver} className="space-y-3 text-xs">
                            <div>
                                <label className="block font-bold text-slate-700 mb-1">اسم الكابتن الكامل</label>
                                <input
                                    type="text"
                                    required
                                    value={newDriverName}
                                    onChange={(e) => setNewDriverName(e.target.value)}
                                    placeholder="مثال: إبراهيم حسونة"
                                    className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block font-bold text-slate-700 mb-1">رقم الهاتف (الخلوي)</label>
                                    <input
                                        type="text"
                                        required
                                        value={newDriverPhone}
                                        onChange={(e) => setNewDriverPhone(e.target.value)}
                                        placeholder="+970 59 123 4567"
                                        className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none text-left"
                                        dir="ltr"
                                    />
                                </div>

                                <div>
                                    <label className="block font-bold text-slate-700 mb-1">مدينة العمل</label>
                                    <input
                                        type="text"
                                        readOnly
                                        value="غزة 📍"
                                        className="w-full px-3.5 py-2.5 bg-slate-100 border border-slate-200 rounded-xl font-bold text-slate-800"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block font-bold text-slate-700 mb-1">نوع وسيلة التوصيل</label>
                                <select
                                    value={newDriverVehicle}
                                    onChange={(e) => setNewDriverVehicle(e.target.value)}
                                    className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none"
                                >
                                    <option value="دراجة نارية">دراجة نارية (موتوسيكل سريع)</option>
                                    <option value="دراجة هوائية">دراجة هوائية</option>
                                    <option value="سيارة نقل خفيفة">سيارة نقل خفيفة</option>
                                </select>
                            </div>

                            <div className="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    onClick={() => setShowAddDriverModal(false)}
                                    className="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl font-bold"
                                >
                                    إلغاء
                                </button>
                                <button
                                    type="submit"
                                    className="px-5 py-2 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-xl shadow-xs"
                                >
                                    اعتماد وتفعيل السائق
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

        </div>
    );
}
