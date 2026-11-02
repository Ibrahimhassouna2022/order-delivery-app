import React, { useState, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import './echo';

// Auth Page
import LoginPage from './components/auth/LoginPage';

// Role Portals & Customer Views
import DriverPortal from './components/driver/DriverPortal';
import RestaurantPortal from './components/restaurant/RestaurantPortal';
import OperationsPortal from './components/operations/OperationsPortal';
import RestaurantMenuModal from './components/customer/RestaurantMenuModal';
import PaymentCheckoutModal from './components/customer/PaymentCheckoutModal';
import CustomerTrackingView from './components/customer/CustomerTrackingView';
import CustomerHistoryView from './components/customer/CustomerHistoryView';
import CustomerFareScheduleModal from './components/customer/CustomerFareScheduleModal';
import { apiJson } from './lib/api';

import { 
    Utensils, 
    Bike, 
    Store, 
    ShieldAlert, 
    LogOut, 
    MapPin, 
    Star, 
    ArrowLeft, 
    Clock, 
    CheckCircle2, 
    Phone, 
    XCircle,
    ShoppingBag,
    Search,
    User,
    Calculator
} from 'lucide-react';

// The API uses "admin" for the operations role; the SPA portal is "operations".
const normalizeRole = (role) => {
    const value = (role || 'customer').toLowerCase();
    return value === 'admin' ? 'operations' : value;
};

const initialGazaRestaurants = [
    {
        id: 1,
        name: 'مطعم بالميرا',
        city: 'غزة',
        cuisine: 'شاورما على الفحم • مشاوي • وجبات سريعة وعائلية',
        rating: '4.9',
        distanceKm: 2.5,
        address: 'غزة - شارع عمر المختار',
        phone: '+970 59 111 2233',
        estimatedDeliveryFare: 7.00,
        image: 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=500&auto=format&fit=crop&q=80',
        menuItems: [
            { id: 101, name: 'وجبة شاورما عربي دبل', price: 22.00, desc: 'شاورما دجاج مشوية مع ثومية، بطاطا مقلية ومخللات شامية', category: 'وجبات' },
            { id: 102, name: 'ساندويش شاورما صاروخ صاج', price: 14.00, desc: 'خبز صاج مقرمش، طحينية وسلطة خضار', category: 'ساندويشات' },
            { id: 103, name: 'صحن ميكس مشاوي كباب وشقف', price: 45.00, desc: 'نصف كيلو كباب ولحم مشوي على الفحم مع مقبلات وسلطات', category: 'مشاوي' },
            { id: 104, name: 'كولا بارد 330 مل', price: 4.00, desc: 'مشروب غازي مثلج', category: 'مشروبات' }
        ]
    },
    {
        id: 2,
        name: 'بيتزا روما الإيطالية',
        city: 'غزة',
        cuisine: 'بيتزا إيطالية بالفرن الحجري • معجنات وكالزوني',
        rating: '4.85',
        distanceKm: 3.1,
        address: 'غزة - شارع النصر، بالقرب من مفترق اللبابيدي',
        phone: '+970 59 444 5566',
        estimatedDeliveryFare: 7.70,
        image: 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=500&auto=format&fit=crop&q=80',
        menuItems: [
            { id: 201, name: 'بيتزا مارغريتا عائلية', price: 25.00, desc: 'صلصة طماطم فاخرة، جبنة موزاريلا إيطالية وريحان طازج', category: 'بيتزا' },
            { id: 202, name: 'بيتزا خضار مشكلة سوبريم', price: 28.00, desc: 'فطر طازج، فلفل رومي، زيتون أسود وجبنة موزاريلا', category: 'بيتزا' },
            { id: 203, name: 'كالزوني دجاج بالجبنة الذائبة', price: 20.00, desc: 'عجينة محشوة دجاج متبل وجبنة ذائبة بالفرن', category: 'معجنات' }
        ]
    },
    {
        id: 3,
        name: 'مطعم شاورما الأمير',
        city: 'غزة',
        cuisine: 'أشهى ساندويشات الشاورما والبرجر الطازج',
        rating: '4.90',
        distanceKm: 1.8,
        address: 'غزة - حي الرمال، بالقرب من مفترق ضبيط',
        phone: '+970 59 777 8899',
        estimatedDeliveryFare: 6.15,
        image: 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=500&auto=format&fit=crop&q=80',
        menuItems: [
            { id: 301, name: 'وجبة شاورما عربي سوبر', price: 20.00, desc: 'شاورما مع بطاطا وسلطات وثومية حارة', category: 'وجبات' },
            { id: 302, name: 'برجر لحم بلدي دبل تشيز', price: 18.00, desc: 'شريحتا لحم مع جبنة شيدر وبطاطا مقلية', category: 'ساندويشات' },
            { id: 303, name: 'عصير برتقال طبيعي طازج', price: 6.00, desc: 'كأس عصير برتقال طبيعي بارد', category: 'مشروبات' }
        ]
    },
    {
        id: 4,
        name: 'مطعم البحار للأسماك والمأكولات البحرية',
        city: 'غزة',
        cuisine: 'سمك طازج مقلي ومشوي • جمبري وسلطات بحرية',
        rating: '4.95',
        distanceKm: 3.8,
        address: 'غزة - شارع الرشيد الساحلي، بجوار الميناء',
        phone: '+970 59 222 3344',
        estimatedDeliveryFare: 8.55,
        image: 'https://images.unsplash.com/photo-1534422298391-e4f8c172dddb?w=500&auto=format&fit=crop&q=80',
        menuItems: [
            { id: 401, name: 'وجبة دنيس بلدي مشوي على الفحم', price: 40.00, desc: 'سمكة دنيس مشوية مع أرز صيادية وسلطات وطحينة', category: 'وجبات' },
            { id: 402, name: 'صحن جمبري مقلي كرسبي', price: 35.00, desc: 'جمبري طازج مقرمش مع صوص التارتار والبطاطا', category: 'وجبات' }
        ]
    }
];

export default function App() {
    // Current Authenticated User Session (Single Entry in Production)
    const [user, setUser] = useState(() => {
        try {
            const saved = localStorage.getItem('wasalli_user');
            return saved ? JSON.parse(saved) : null;
        } catch {
            return null;
        }
    });

    // Central Restaurants List in Gaza (Onboarded & Managed by Operations)
    const [restaurants, setRestaurants] = useState(initialGazaRestaurants);

    // Customer Navigation Tab: 'restaurants' | 'tracking' | 'history'
    const [customerTab, setCustomerTab] = useState('restaurants');

    // Active Orders List in Gaza (Supports multiple orders across different restaurants)
    const [activeOrders, setActiveOrders] = useState([]);
    const [selectedOrderId, setSelectedOrderId] = useState(null);
    const [orderHistory, setOrderHistory] = useState([]);

    const activeOrder = activeOrders.find((o) => o.id === selectedOrderId) || activeOrders[0] || null;

    const [selectedRestaurant, setSelectedRestaurant] = useState(null);
    const [searchQuery, setSearchQuery] = useState('');
    const [deliveryZones, setDeliveryZones] = useState([]);
    const [paymentSettings, setPaymentSettings] = useState(null);
    const [createdOrder, setCreatedOrder] = useState(null);
    const [showPaymentModal, setShowPaymentModal] = useState(false);
    const [showFareScheduleModal, setShowFareScheduleModal] = useState(false);

    // Live State for Driver & Restaurant (Synced with real backend APIs)
    const [restaurantOrders, setRestaurantOrders] = useState([]);
    const [driverOffer, setDriverOffer] = useState(null);
    const [driverActiveTrip, setDriverActiveTrip] = useState(null);

    useEffect(() => {
        if (!user) {
            return undefined;
        }

        let cancelled = false;
        const images = [
            'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=500&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=500&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=500&auto=format&fit=crop&q=80',
            'https://images.unsplash.com/photo-1534422298391-e4f8c172dddb?w=500&auto=format&fit=crop&q=80',
        ];

        apiJson('/api/v1/restaurants', { auth: false })
            .then((response) => {
                if (cancelled) {
                    return;
                }

                const remoteRestaurants = response?.data || [];
                if (remoteRestaurants.length === 0) {
                    return;
                }

                setRestaurants(remoteRestaurants.map((restaurant, index) => ({
                    ...restaurant,
                    cuisine: restaurant.description || 'وجبات ومأكولات متنوعة',
                    rating: '4.9',
                    distanceKm: 2.5,
                    image: images[index % images.length],
                    estimatedDeliveryFare: 0,
                    menuItems: (restaurant.menu_items || []).map((item) => ({
                        ...item,
                        price: Number(item.price),
                        desc: item.description,
                        category: 'وجبات',
                    })),
                })));
            })
            .catch(() => {});

        apiJson('/api/v1/delivery-zones', { auth: false })
            .then((response) => {
                if (!cancelled) {
                    setDeliveryZones(response?.data || []);
                }
            })
            .catch(() => {});

        apiJson('/api/v1/payment-settings')
            .then((response) => {
                if (!cancelled) {
                    setPaymentSettings(response?.data || null);
                }
            })
            .catch(() => {});

        // Fetch customer orders on login
        if (normalizeRole(user.role) === 'customer') {
            apiJson('/api/v1/orders')
                .then((res) => {
                    if (cancelled) return;
                    const list = res?.data || [];
                    const mapped = list.map((ord) => {
                        const items = (ord.items || []).map((item) => ({
                            name: item.item_name || item.name || 'وجبة',
                            qty: item.quantity || item.qty || 1,
                            price: Number(item.unit_price || item.price || 0),
                        }));
                        const firstItem = items[0];
                        const isDelivered = ord.status === 'delivered';
                        const isCancelled = ord.status === 'cancelled';
                        const isPaymentApproved = ord.payment_status === 'succeeded' || Boolean(ord.payment_approved_at);
                        const isPaymentFailed = ord.payment_status === 'failed';

                        let statusText = 'بانتظار موافقة المطعم';
                        let statusCode = ord.status || 'placed';

                        if (isDelivered) {
                            statusText = 'تم تسليم الطلب بنجاح ✓';
                            statusCode = 'delivered';
                        } else if (ord.status === 'picked_up') {
                            statusText = `استلم الكابتن ${ord.driver?.name || 'السائق'} الوجبة وهو في الطريق إليك 🛵`;
                            statusCode = 'picked_up';
                        } else if (ord.status === 'assigned') {
                            statusText = `تم إسناد الطلب للكابتن ${ord.driver?.name || 'السائق'} وسيتوجه للمطعم 🛵`;
                            statusCode = 'assigned';
                        } else if (ord.status === 'ready_for_pickup') {
                            statusText = 'الوجبة جاهزة ومعبأة وبانتظار استلام السائق 🛵';
                            statusCode = 'ready';
                        } else if (ord.status === 'preparing' || isPaymentApproved) {
                            statusText = 'تمت موافقة المطعم • جاري التحضير بالمطبخ 🍳';
                            statusCode = 'preparing';
                        } else if (isPaymentFailed) {
                            statusText = 'تم رفض إشعار التحويل • يرجى إعادة رفع صورة الإشعار';
                            statusCode = 'payment_failed';
                        } else {
                            statusText = ord.payment_proof_url ? 'إشعار التحويل قيد المراجعة لدى المطعم' : 'بانتظار موافقة المطعم';
                            statusCode = 'placed';
                        }

                        return {
                            id: ord.id,
                            orderNumber: `#${ord.id}`,
                            name: firstItem?.name || 'طلب طعام',
                            restaurantName: ord.restaurant?.name || 'مطعم غزة',
                            pickupAddress: ord.restaurant?.address || 'غزة',
                            dropoffAddress: ord.delivery_address || 'استلام من المطعم',
                            customerName: user.name,
                            customerPhone: ord.delivery_phone || user.phone || '',
                            status: statusText,
                            statusCode,
                            eta: 'حسب حالة التحضير',
                            date: ord.placed_at ? new Date(ord.placed_at).toLocaleDateString('ar-EG') : 'اليوم',
                            time: ord.placed_at ? new Date(ord.placed_at).toLocaleTimeString('ar-EG') : 'الآن',
                            itemsSubtotal: Number(ord.items_subtotal || 0),
                            deliveryFee: Number(ord.delivery_fee || 0),
                            amount: Number(ord.total || 0),
                            totalAmount: Number(ord.total || 0),
                            itemCount: items.reduce((total, item) => total + item.qty, 0),
                            items,
                            invoiceNumber: ord.invoice_number,
                            paymentStatusCode: ord.payment_status,
                            paymentApprovedAt: ord.payment_approved_at,
                            realOrder: true,
                            driverAssigned: Boolean(ord.driver?.name),
                            driverName: ord.driver?.name || 'سيتم إسناد كابتن بعد اعتماد التحويل',
                            driverPhone: ord.driver?.phone || ord.support_phone || '',
                            rawOrder: ord,
                        };
                    });

                    const active = mapped.filter((o) => !['delivered', 'cancelled'].includes(o.statusCode));
                    const history = mapped.filter((o) => ['delivered', 'cancelled'].includes(o.statusCode));
                    setActiveOrders(active);
                    setOrderHistory(history);
                    if (active.length > 0) {
                        setSelectedOrderId(active[0].id);
                    }
                })
                .catch(() => {});
        }

        return () => {
            cancelled = true;
        };
    }, [user?.id]);

    useEffect(() => {
        if (activeOrders.length === 0) {
            return undefined;
        }

        const pollableOrders = activeOrders.filter(
            (o) => o.realOrder && o.id && !['delivered', 'cancelled'].includes(o.statusCode)
        );

        if (pollableOrders.length === 0) {
            return undefined;
        }

        const timer = window.setInterval(async () => {
            for (const ord of pollableOrders) {
                try {
                    const response = await apiJson(`/api/v1/orders/${ord.id}`);
                    const tracking = response?.data;
                    if (!tracking) continue;

                    setActiveOrders((currentList) => currentList.map((item) => {
                        if (item.id !== ord.id) return item;

                        const paymentStatus = tracking.payment?.status || item.paymentStatusCode;
                        const kitchenStatus = tracking.kitchen_status || tracking.status;
                        const isDelivered = tracking.status === 'delivered';
                        const isPickedUp = tracking.status === 'picked_up';
                        const isAssigned = tracking.status === 'assigned';
                        const isReady = kitchenStatus === 'ready_for_pickup';
                        const isPreparing = kitchenStatus === 'preparing' || tracking.is_preparing;
                        const isPaymentApproved = paymentStatus === 'succeeded' || Boolean(tracking.payment_approved_at);
                        const isPaymentFailed = paymentStatus === 'failed';

                        let statusText = item.status;
                        let statusCode = item.statusCode;

                        if (isDelivered) {
                            statusText = 'تم تسليم الطلب بنجاح ✓';
                            statusCode = 'delivered';
                        } else if (isPickedUp) {
                            statusText = `استلم الكابتن ${tracking.driver?.name || 'السائق'} الوجبة وهو في الطريق إليك 🛵`;
                            statusCode = 'picked_up';
                        } else if (isAssigned) {
                            statusText = `تم إسناد الطلب للكابتن ${tracking.driver?.name || 'السائق'} وسيتوجه للمطعم 🛵`;
                            statusCode = 'assigned';
                        } else if (isReady) {
                            statusText = 'الوجبة جاهزة ومعبأة وبانتظار استلام السائق 🛵';
                            statusCode = 'ready';
                        } else if (isPreparing || isPaymentApproved) {
                            statusText = 'تمت موافقة المطعم • جاري التحضير بالمطبخ 🍳';
                            statusCode = 'preparing';
                        } else if (isPaymentFailed) {
                            statusText = 'تم رفض إشعار التحويل • يرجى إعادة رفع صورة الإشعار';
                            statusCode = 'payment_failed';
                        } else {
                            statusText = tracking.payment?.proof_url ? 'إشعار التحويل قيد المراجعة لدى المطعم' : 'بانتظار موافقة المطعم';
                            statusCode = 'placed';
                        }

                        const driverAssigned = Boolean(tracking.driver?.name);

                        return {
                            ...item,
                            invoiceNumber: tracking.invoice_number || item.invoiceNumber,
                            paymentStatusCode: paymentStatus,
                            paymentApprovedAt: tracking.payment_approved_at || item.paymentApprovedAt,
                            status: statusText,
                            statusCode,
                            is_preparing: isPreparing,
                            driverAssigned,
                            driverName: tracking.driver?.name || (driverAssigned ? item.driverName : ''),
                            driverPhone: tracking.driver?.phone || (driverAssigned ? item.driverPhone : ''),
                            rawOrder: {
                                ...item.rawOrder,
                                ...tracking,
                            },
                        };
                    }));
                } catch {
                    // Keep the last known state
                }
            }
        }, 5000);

        return () => window.clearInterval(timer);
    }, [activeOrders.map((o) => `${o.id}-${o.statusCode}`).join(',')]);

    // Handle Login Success
    const handleLoginSuccess = (userData) => {
        setUser(userData);
        localStorage.setItem('wasalli_user', JSON.stringify(userData));
        if (userData.token) {
            localStorage.setItem('wasalli_token', userData.token);
        }
    };

    // Logout
    const handleLogout = () => {
        const token = localStorage.getItem('wasalli_token');

        if (token) {
            fetch('/api/v1/auth/logout', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token}`
                }
            }).catch(() => {
                // Local session is cleared even if token revocation fails.
            });
        }

        setUser(null);
        localStorage.removeItem('wasalli_user');
        localStorage.removeItem('wasalli_token');
    };

    // Operations Handlers for Onboarding & Menu Management
    const handleAddRestaurant = (newRest) => {
        setRestaurants((prev) => [newRest, ...prev]);
    };

    const handleAddMenuItem = (restaurantId, newItem) => {
        setRestaurants((prev) => prev.map((r) => {
            if (r.id === restaurantId) {
                return { ...r, menuItems: [...r.menuItems, newItem] };
            }
            return r;
        }));
    };

    const handleDeleteMenuItem = (restaurantId, itemId) => {
        setRestaurants((prev) => prev.map((r) => {
            if (r.id === restaurantId) {
                return { ...r, menuItems: r.menuItems.filter((m) => m.id !== itemId) };
            }
            return r;
        }));
    };

    // Customer Creates a Bank-Transfer Order
    const handleOrderCreated = ({ order }) => {
        const items = (order.items || []).map((item) => ({
            name: item.item_name || item.name || 'وجبة',
            qty: item.quantity || item.qty || 1,
            price: Number(item.unit_price || item.price || 0),
        }));
        const firstItem = items[0];

        const mappedOrder = {
            id: order.id,
            orderNumber: `#${order.id}`,
            name: firstItem?.name || 'طلب جديد',
            restaurantName: selectedRestaurant?.name || 'مطعم',
            pickupAddress: selectedRestaurant?.address || '',
            dropoffAddress: order.delivery_address || 'استلام من المطعم',
            customerName: user?.name || 'زبون',
            customerPhone: order.delivery_phone || user?.phone || '',
            status: 'بانتظار موافقة المطعم',
            statusCode: 'placed',
            eta: 'حسب حالة التحضير',
            date: 'اليوم',
            time: 'الآن',
            itemsSubtotal: Number(order.items_subtotal || 0),
            deliveryFee: Number(order.delivery_fee || 0),
            amount: Number(order.total || 0),
            totalAmount: Number(order.total || 0),
            itemCount: items.reduce((total, item) => total + item.qty, 0),
            items,
            invoiceNumber: order.invoice_number,
            paymentStatusCode: order.payment_status,
            realOrder: true,
            driverName: 'سيتم إسناد كابتن بعد اعتماد التحويل',
            driverPhone: order.support_phone || paymentSettings?.support_phone || '',
            rawOrder: order,
        };

        setCreatedOrder(order);
        setActiveOrders((prev) => [mappedOrder, ...prev.filter((o) => o.id !== mappedOrder.id)]);
        setSelectedOrderId(mappedOrder.id);
        setOrderHistory((previous) => [mappedOrder, ...previous]);
        setCustomerTab('tracking');
        setShowPaymentModal(true);
    };

    // Customer Places Order Flow
    const handlePlaceOrder = (newOrder) => {
        setActiveOrder(newOrder);
        setOrderHistory((prev) => [newOrder, ...prev]);
        setCustomerTab('tracking');

        // Send to Restaurant Kitchen
        const newKitchenOrder = {
            id: newOrder.id,
            orderNumber: newOrder.orderNumber,
            customerName: user?.name || 'أحمد خليل',
            customerPhone: newOrder.customerPhone || user?.phone || '+970 59 912 3456',
            customerAddress: newOrder.dropoffAddress || user?.address || 'غزة - حي الرمال',
            items: newOrder.items || [{ name: newOrder.name, qty: newOrder.itemCount || 1, price: newOrder.itemsSubtotal }],
            itemsSubtotal: newOrder.itemsSubtotal,
            deliveryFee: newOrder.deliveryFee,
            distanceKm: newOrder.distanceKm,
            total: newOrder.totalAmount,
            placedAt: 'الآن',
            status: 'incoming'
        };
        setRestaurantOrders((prev) => [newKitchenOrder, ...prev]);

        // Send Fair Dispatch Offer to Drivers
        setDriverOffer({
            id: newOrder.id,
            orderNumber: newOrder.orderNumber,
            merchantName: newOrder.restaurantName,
            pickupAddress: newOrder.pickupAddress,
            dropoffAddress: newOrder.dropoffAddress,
            distance: `${newOrder.distanceKm || 2.5} كم`,
            estimatedPayout: newOrder.deliveryFee || 7.00,
            estimatedTime: '20 دقيقة',
            itemSummary: `${newOrder.itemCount || 1} وجبات`,
            timeLeftSeconds: 30,
            customerName: user?.name || 'أحمد خليل'
        });
    };

    // Driver Accepts Offer
    const handleDriverAcceptOffer = (acceptedOffer) => {
        setDriverActiveTrip({
            id: acceptedOffer.id,
            orderNumber: acceptedOffer.orderNumber || 'ORD-9421',
            customerName: acceptedOffer.customerName || 'أحمد خليل',
            customerPhone: '+970 59 912 3456',
            merchantName: acceptedOffer.merchantName,
            pickupAddress: acceptedOffer.pickupAddress,
            dropoffAddress: acceptedOffer.dropoffAddress,
            deliveryNotes: 'الاتصال عند الوصول',
            step: 'to_pickup',
            payout: acceptedOffer.estimatedPayout || 7.00
        });
        setDriverOffer(null);

        if (activeOrder) {
            setActiveOrder((prev) => ({
                ...prev,
                status: 'تم قبول المشوار من السائق وهو في الطريق للمطعم 🛵'
            }));
        }
    };

    // Restaurant Updates Order Status
    const handleUpdateRestaurantOrderStatus = (orderId, nextStatus) => {
        setRestaurantOrders((prev) => 
            prev.map((o) => o.id === orderId ? { ...o, status: nextStatus } : o)
        );

        if (activeOrder) {
            if (nextStatus === 'preparing') {
                setActiveOrder((prev) => ({ 
                    ...prev, 
                    status: 'تمت موافقة المطعم • جاري التحضير بالمطبخ 🍳',
                    statusCode: 'preparing'
                }));
            } else if (nextStatus === 'ready') {
                setActiveOrder((prev) => ({ 
                    ...prev, 
                    status: 'الوجبة جاهزة ومعبأة وبانتظار استلام السائق 🛵',
                    statusCode: 'ready'
                }));
            } else if (nextStatus === 'picked_up') {
                setActiveOrder((prev) => ({ 
                    ...prev, 
                    status: 'استلم السائق الوجبة وهو في الطريق إليك 🛵',
                    statusCode: 'picked_up'
                }));
            }
        }
    };

    // Driver Updates Step
    const handleDriverUpdateTripStep = () => {
        if (!driverActiveTrip) return;
        if (driverActiveTrip.step === 'to_pickup') {
            setDriverActiveTrip({ ...driverActiveTrip, step: 'at_merchant' });
            if (activeOrder) {
                setActiveOrder((prev) => ({ ...prev, status: 'السائق وصل للمطعم لاستلام الوجبة' }));
            }
        } else if (driverActiveTrip.step === 'at_merchant') {
            setDriverActiveTrip({ ...driverActiveTrip, step: 'to_customer' });
            if (activeOrder) {
                setActiveOrder((prev) => ({ ...prev, status: 'السائق استلم الوجبة وهو في الطريق إليك 🛵' }));
            }
        } else if (driverActiveTrip.step === 'to_customer') {
            setDriverActiveTrip(null);
            if (activeOrder) {
                setActiveOrder((prev) => ({ ...prev, status: 'تم تسليم الطلب بنجاح ✓' }));
            }
        }
    };

    // Cancel Customer Active Order
    const handleCancelActiveOrder = async () => {
        if (!activeOrder) return;
        if (window.confirm('هل تريد بالتأكيد إلغاء هذا الطلب؟')) {
            try {
                if (activeOrder.realOrder && activeOrder.id) {
                    await apiJson(`/api/v1/orders/${activeOrder.id}/cancel`, { method: 'POST' });
                }
            } catch {}
            setActiveOrders((prev) => prev.filter((o) => o.id !== activeOrder.id));
            setDriverOffer(null);
            setDriverActiveTrip(null);
        }
    };

    // Single Entry Gate: If user is not logged in, render LoginPage
    if (!user) {
        return <LoginPage onLoginSuccess={handleLoginSuccess} />;
    }

    // Role-Based Routing in Production: Each user is directed strictly to their allowed portal
    const currentRole = normalizeRole(user.role);

    return (
        <div className="min-h-screen bg-slate-50 text-slate-800 font-sans selection:bg-emerald-500 selection:text-white" dir="rtl">
            
            {/* Top Navigation Bar: Official Production Header (Gaza Focused) */}
            <header className="bg-[#0d3b32] text-white border-b border-[#14493e] sticky top-0 z-30 shadow-sm">
                <div className="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
                    
                    {/* Brand & Gaza Identity */}
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-400/30 flex items-center justify-center text-emerald-300 font-bold text-lg shadow-inner">
                            <Bike className="w-5 h-5 text-emerald-400" />
                        </div>
                        <div>
                            <span className="font-black text-lg text-white block leading-tight">وصلّي</span>
                            <span className="text-[10px] text-emerald-300 font-bold block">منصة طلب وتوصيل الطعام لغزة 🇵🇸</span>
                        </div>
                    </div>

                    {/* Official Role Indicator Badge & City Location */}
                    <div className="flex items-center gap-3">
                        {currentRole === 'customer' && (
                            <div className="flex items-center gap-2">
                                <span className="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 text-xs font-bold flex items-center gap-1.5">
                                    <ShoppingBag className="w-3.5 h-3.5" />
                                    <span>حساب زبون</span>
                                </span>

                                <span className="px-3 py-1 rounded-xl bg-black/20 text-emerald-200 border border-white/10 text-xs font-bold">
                                    مدينة غزة 📍
                                </span>
                            </div>
                        )}

                        {currentRole === 'driver' && (
                            <span className="px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 border border-amber-400/30 text-xs font-bold flex items-center gap-1.5">
                                <Bike className="w-3.5 h-3.5" />
                                <span>بوابة كابتن التوصيل (غزة)</span>
                            </span>
                        )}

                        {currentRole === 'restaurant' && (
                            <span className="px-3 py-1 rounded-full bg-rose-500/20 text-rose-300 border border-rose-400/30 text-xs font-bold flex items-center gap-1.5">
                                <Utensils className="w-3.5 h-3.5" />
                                <span>بوابة تحضير المطبخ (غزة)</span>
                            </span>
                        )}

                        {currentRole === 'operations' && (
                            <span className="px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-400/30 text-xs font-bold flex items-center gap-1.5">
                                <ShieldAlert className="w-3.5 h-3.5" />
                                <span>إدارة وعمليات قطاع غزة</span>
                            </span>
                        )}

                        {/* User Profile & Logout */}
                        <div className="flex items-center gap-3 border-r border-[#14493e] pr-3">
                            <div className="text-left text-xs hidden sm:block">
                                <span className="font-bold text-white block leading-tight">{user.name}</span>
                                <span className="text-[10px] text-emerald-300/70">{user.email}</span>
                            </div>

                            <button
                                onClick={handleLogout}
                                title="تسجيل الخروج"
                                className="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/10 hover:bg-rose-500/20 text-emerald-200 hover:text-rose-300 transition-colors text-xs font-bold"
                            >
                                <LogOut className="w-3.5 h-3.5" />
                                <span>خروج</span>
                            </button>
                        </div>
                    </div>

                </div>
            </header>

            {/* STRICT PRODUCTION ROLE-BASED ROUTING */}
            
            {/* 1. CUSTOMER PORTAL */}
            {currentRole === 'customer' && (
                <main className="max-w-6xl mx-auto p-4 md:p-6 space-y-6">
                    {/* Customer Sub-Nav: Clean & Direct */}
                    <div className="flex items-center justify-between border-b border-slate-200 pb-3">
                        <div className="flex items-center gap-2">
                            <button
                                onClick={() => setCustomerTab('restaurants')}
                                className={`px-4 py-2 rounded-xl text-xs font-bold transition-all ${
                                    customerTab === 'restaurants'
                                        ? 'bg-[#0d3b32] text-white shadow-xs'
                                        : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'
                                }`}
                            >
                                🍽️ مطاعم غزة والمنيو
                            </button>

                            <button
                                onClick={() => setCustomerTab('tracking')}
                                className={`px-4 py-2 rounded-xl text-xs font-bold transition-all relative ${
                                    customerTab === 'tracking'
                                        ? 'bg-[#0d3b32] text-white shadow-xs'
                                        : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'
                                }`}
                            >
                                📍 تتبع طلبي والفاتورة
                                {activeOrders.length > 0 && (
                                    <span className="inline-flex items-center justify-center mr-1.5 px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-500 text-white shadow-xs">
                                        {activeOrders.length}
                                    </span>
                                )}
                            </button>

                            <button
                                onClick={() => setCustomerTab('history')}
                                className={`px-4 py-2 rounded-xl text-xs font-bold transition-all ${
                                    customerTab === 'history'
                                        ? 'bg-[#0d3b32] text-white shadow-xs'
                                        : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'
                                }`}
                            >
                                🕒 طلباتي السابقة
                            </button>

                            <button
                                type="button"
                                onClick={() => setShowFareScheduleModal(true)}
                                className="px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-emerald-50 text-emerald-900 hover:bg-emerald-100 border border-emerald-300 flex items-center gap-1.5 shadow-xs"
                                title="عرض جدول أسعار التوصيل المعتمد لجميع مناطق غزة"
                            >
                                <Calculator className="w-3.5 h-3.5 text-emerald-700" />
                                <span>🛵 جدول تسعيرة التوصيل</span>
                            </button>
                        </div>

                        {/* Search Filter */}
                        {customerTab === 'restaurants' && (
                            <div className="relative w-64 hidden sm:block">
                                <Search className="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2" />
                                <input
                                    type="text"
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    placeholder="ابحث عن مطعم أو أكلة في غزة..."
                                    className="w-full pr-9 pl-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                />
                            </div>
                        )}
                    </div>

                    {/* Active Order Banner */}
                    {activeOrder && customerTab === 'restaurants' && (
                        <div className="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div className="flex items-center gap-3">
                                <div className="w-11 h-11 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shrink-0">
                                    <Bike className="w-6 h-6 animate-pulse" />
                                </div>
                                <div>
                                    <div className="flex items-center gap-2">
                                        <span className="font-extrabold text-emerald-950 text-sm">
                                            طلبك قيد التوصيل الآن (#{activeOrder.orderNumber})
                                        </span>
                                        <span className="px-2 py-0.5 rounded-full bg-emerald-200/60 text-emerald-900 text-[10px] font-bold">
                                            {activeOrder.status}
                                        </span>
                                    </div>
                                    <p className="text-xs text-emerald-800/80 mt-0.5">
                                        من: {activeOrder.restaurantName} • السائق: {activeOrder.driverName} ({activeOrder.eta})
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                <button
                                    onClick={() => setCustomerTab('tracking')}
                                    className="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold transition-all shadow-xs"
                                >
                                    فتح شاشة التتبع والفاتورة المعتمدة
                                </button>
                                <button
                                    onClick={handleCancelActiveOrder}
                                    className="px-3 py-2 bg-white hover:bg-rose-50 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition-all"
                                >
                                    إلغاء الطلب
                                </button>
                            </div>
                        </div>
                    )}

                    {/* Gaza Restaurants Grid */}
                    {customerTab === 'restaurants' && (
                        <div className="space-y-6">
                            <div>
                                <h2 className="text-xl font-bold text-slate-900">
                                    مطاعم غزة المعتمدة والمفتوحة الآن
                                </h2>
                                <p className="text-xs text-slate-500 mt-0.5">
                                    توصيل مباشر داخل أحياء مدينة غزة مع احتساب عادل ودقيق للأجرة بحسب المسافة
                                </p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                                {restaurants
                                    .filter((r) => 
                                        r.name.includes(searchQuery) || 
                                        (r.cuisine && r.cuisine.includes(searchQuery))
                                    )
                                    .map((restaurant) => (
                                        <div 
                                            key={restaurant.id}
                                            className="bg-white rounded-3xl border border-slate-200/80 shadow-xs hover:shadow-md transition-all overflow-hidden flex flex-col justify-between group"
                                        >
                                            <div>
                                                <div className="h-36 w-full overflow-hidden relative bg-slate-100">
                                                    <img 
                                                        src={restaurant.image || 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=500&auto=format&fit=crop&q=80'} 
                                                        alt={restaurant.name}
                                                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                                    />
                                                    <div className="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-full bg-slate-900/80 backdrop-blur text-white text-[10px] font-bold flex items-center gap-1">
                                                        <Star className="w-3 h-3 fill-amber-400 text-amber-400" />
                                                        <span>{restaurant.rating || '4.9'}</span>
                                                    </div>
                                                    <div className="absolute bottom-2.5 right-2.5 px-2 py-0.5 rounded-full bg-slate-900/80 backdrop-blur text-emerald-300 text-[10px] font-extrabold">
                                                        يبعد {restaurant.distanceKm || 2.5} كم
                                                    </div>
                                                </div>

                                                <div className="p-4 space-y-2">
                                                    <div>
                                                        <h3 className="font-bold text-slate-900 text-sm leading-snug">{restaurant.name}</h3>
                                                        <p className="text-[11px] text-slate-500 mt-0.5 line-clamp-1">{restaurant.cuisine}</p>
                                                    </div>

                                                    <p className="text-[10px] text-slate-400 flex items-center gap-1">
                                                        <MapPin className="w-3 h-3 text-slate-400 shrink-0" />
                                                        <span className="truncate">{restaurant.address}</span>
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="p-4 pt-0 space-y-3">
                                                <div className="p-2 bg-emerald-50/70 border border-emerald-200/60 rounded-xl text-[11px] text-emerald-900 flex items-center justify-between">
                                                    <span>أجرة التوصيل:</span>
                                                    <strong className="text-emerald-950 font-black">
                                                        {restaurant.estimatedDeliveryFare ? restaurant.estimatedDeliveryFare.toFixed(2) : '7.00'} ₪
                                                    </strong>
                                                </div>

                                                <button
                                                    onClick={() => setSelectedRestaurant(restaurant)}
                                                    className="w-full py-2 px-3 bg-[#0d3b32] hover:bg-[#092923] text-white font-bold rounded-xl text-xs flex items-center justify-center gap-1.5 shadow-sm transition-all active:scale-[0.98]"
                                                >
                                                    <span>عرض المنيو والطلب</span>
                                                    <ArrowLeft className="w-3.5 h-3.5 text-emerald-300" />
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                            </div>
                        </div>
                    )}

                    {/* Tracking Tab */}
                    {customerTab === 'tracking' && (
                        <div className="space-y-3">
                            {(activeOrder || createdOrder) && (
                                <div className="flex justify-end">
                                    <button
                                        type="button"
                                        onClick={() => setShowPaymentModal(true)}
                                        className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#0d3b32] text-white text-xs font-bold hover:bg-[#092923] shadow-xs"
                                    >
                                        <ShoppingBag className="w-4 h-4 text-emerald-300" />
                                        <span>متابعة حالة الدفع والفاتورة {activeOrder ? `(${activeOrder.restaurantName})` : ''}</span>
                                    </button>
                                </div>
                            )}
                            <CustomerTrackingView 
                                activeOrder={activeOrder} 
                                activeOrders={activeOrders}
                                onSelectOrder={(id) => setSelectedOrderId(id)}
                                onOpenFareSchedule={() => setShowFareScheduleModal(true)}
                            />
                        </div>
                    )}

                    {/* History Tab */}
                    {customerTab === 'history' && (
                        <CustomerHistoryView 
                            deliveries={orderHistory}
                            onSelectOrder={(order) => {}}
                        />
                    )}

                    {/* Menu Order Modal */}
                    {selectedRestaurant && (
                        <RestaurantMenuModal
                            restaurant={selectedRestaurant}
                            isOpen={Boolean(selectedRestaurant)}
                            onClose={() => setSelectedRestaurant(null)}
                            onOrderCreated={handleOrderCreated}
                            currentUser={user}
                            deliveryZones={deliveryZones}
                            onOpenFareSchedule={() => setShowFareScheduleModal(true)}
                        />
                    )}

                    {showPaymentModal && (activeOrder?.rawOrder || createdOrder) && (
                        <PaymentCheckoutModal
                            open={showPaymentModal}
                            order={activeOrder?.rawOrder || createdOrder}
                            paymentSettings={paymentSettings}
                            onClose={() => setShowPaymentModal(false)}
                            onOpenFareSchedule={() => setShowFareScheduleModal(true)}
                            onProofUploaded={(updatedData) => {
                                const currentTargetId = activeOrder?.id || createdOrder?.id;
                                setCreatedOrder((prev) => (prev && prev.id === currentTargetId ? { ...prev, ...updatedData } : prev));
                                setActiveOrders((prev) => prev.map((ord) => {
                                    if (ord.id === currentTargetId) {
                                        return {
                                            ...ord,
                                            status: 'إشعار التحويل قيد المراجعة لدى المطعم',
                                            statusCode: 'placed',
                                            paymentStatusCode: updatedData?.payment_status || 'pending',
                                            paymentApprovedAt: null,
                                            rawOrder: {
                                                ...(ord.rawOrder || {}),
                                                ...updatedData,
                                                payment_status: updatedData?.payment_status || ord.rawOrder?.payment_status,
                                                payment_proof_url: updatedData?.payment_proof_url || ord.rawOrder?.payment_proof_url,
                                                payment: {
                                                    ...(ord.rawOrder?.payment || {}),
                                                    status: updatedData?.payment_status || ord.rawOrder?.payment?.status,
                                                    proof_url: updatedData?.payment_proof_url || ord.rawOrder?.payment?.proof_url,
                                                },
                                            },
                                        };
                                    }
                                    return ord;
                                }));
                            }}
                        />
                    )}

                    {/* Pre-Established Delivery Fare Schedule Modal */}
                    <CustomerFareScheduleModal
                        isOpen={showFareScheduleModal}
                        onClose={() => setShowFareScheduleModal(false)}
                        deliveryZones={deliveryZones}
                    />
                </main>
            )}

            {/* 2. DRIVER PORTAL */}
            {currentRole === 'driver' && (
                <main className="py-4">
                    <DriverPortal 
                        user={user} 
                        onLogout={handleLogout}
                        parentOffer={driverOffer}
                        onAcceptParentOffer={handleDriverAcceptOffer}
                        parentActiveTrip={driverActiveTrip}
                        onUpdateTripStep={handleDriverUpdateTripStep}
                    />
                </main>
            )}

            {/* 3. RESTAURANT KITCHEN PORTAL */}
            {currentRole === 'restaurant' && (
                <main className="py-4">
                    <RestaurantPortal 
                        user={user} 
                        onLogout={handleLogout}
                        sharedOrders={restaurantOrders}
                        onUpdateOrderStatus={handleUpdateRestaurantOrderStatus}
                    />
                </main>
            )}

            {/* 4. OPERATIONS & ADMINISTRATION PORTAL */}
            {currentRole === 'operations' && (
                <main className="py-4">
                    <OperationsPortal 
                        user={user} 
                        onLogout={handleLogout}
                        restaurantsList={restaurants}
                        onAddRestaurant={handleAddRestaurant}
                        onAddMenuItem={handleAddMenuItem}
                        onDeleteMenuItem={handleDeleteMenuItem}
                    />
                </main>
            )}

        </div>
    );
}

// React App Mount
const container = document.getElementById('root');
if (container) {
    const root = createRoot(container);
    root.render(<App />);
}
