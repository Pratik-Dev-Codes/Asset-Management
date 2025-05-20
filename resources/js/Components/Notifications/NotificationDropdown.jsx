import { Fragment, useEffect, useState } from 'react';
import { Menu, Transition } from '@headlessui/react';
import { BellIcon, CheckIcon, XMarkIcon } from '@heroicons/react/24/outline';
import { router, usePage } from '@inertiajs/react';
import axios from 'axios';

export default function NotificationDropdown() {
    const [notifications, setNotifications] = useState([]);
    const [unreadCount, setUnreadCount] = useState(0);
    const [loading, setLoading] = useState(true);
    const { auth } = usePage().props;

    const fetchNotifications = async () => {
        try {
            const response = await axios.get(route('api.notifications.index'));
            setNotifications(response.data.notifications.data);
            setUnreadCount(response.data.unread_count);
        } catch (error) {
            console.error('Error fetching notifications:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchNotifications();
        
        // Set up polling every 60 seconds
        const interval = setInterval(fetchNotifications, 60000);
        
        return () => clearInterval(interval);
    }, []);

    const markAsRead = async (id, e) => {
        e.preventDefault();
        try {
            await axios.post(route('api.notifications.read', id));
            await fetchNotifications();
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    };

    const markAllAsRead = async (e) => {
        e.preventDefault();
        try {
            await axios.post(route('api.notifications.read-all'));
            await fetchNotifications();
        } catch (error) {
            console.error('Error marking all as read:', error);
        }
    };

    const handleNotificationClick = async (notification, e) => {
        if (!notification.read_at) {
            await markAsRead(notification.id, e);
        }
        // Navigate to the notification URL if it exists
        if (notification.data.url) {
            router.visit(notification.data.url);
        }
    };

    const deleteNotification = async (id, e) => {
        e.stopPropagation();
        try {
            await axios.delete(route('api.notifications.destroy', id));
            setNotifications(notifications.filter(n => n.id !== id));
            setUnreadCount(prev => Math.max(0, prev - 1));
        } catch (error) {
            console.error('Error deleting notification:', error);
        }
    };

    const formatTimeAgo = (dateString) => {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
        if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`;
        return date.toLocaleDateString();
    };

    return (
        <Menu as="div" className="relative ml-3">
            <div>
                <Menu.Button className="relative rounded-full bg-white p-1 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                    <span className="sr-only">View notifications</span>
                    <BellIcon className="h-6 w-6" aria-hidden="true" />
                    {unreadCount > 0 && (
                        <span className="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white">
                            <span className="sr-only">{unreadCount} unread notifications</span>
                        </span>
                    )}
                </Menu.Button>
            </div>
            <Transition
                as={Fragment}
                enter="transition ease-out duration-100"
                enterFrom="transform opacity-0 scale-95"
                enterTo="transform opacity-100 scale-100"
                leave="transition ease-in duration-75"
                leaveFrom="transform opacity-100 scale-100"
                leaveTo="transform opacity-0 scale-95"
            >
                <Menu.Items className="absolute right-0 z-10 mt-2 w-80 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                    <div className="border-b border-gray-200 px-4 py-2">
                        <div className="flex items-center justify-between">
                            <h3 className="text-sm font-medium text-gray-900">Notifications</h3>
                            <div className="flex space-x-2">
                                {unreadCount > 0 && (
                                    <button
                                        onClick={markAllAsRead}
                                        className="text-xs text-primary-600 hover:text-primary-800"
                                    >
                                        Mark all as read
                                    </button>
                                )}
                                <a
                                    href={route('notifications.index')}
                                    className="text-xs text-primary-600 hover:text-primary-800"
                                >
                                    View all
                                </a>
                            </div>
                        </div>
                    </div>
                    <div className="max-h-96 overflow-y-auto">
                        {loading ? (
                            <div className="px-4 py-8 text-center">
                                <div className="animate-pulse">Loading notifications...</div>
                            </div>
                        ) : notifications.length === 0 ? (
                            <div className="px-4 py-8 text-center text-sm text-gray-500">
                                No new notifications
                            </div>
                        ) : (
                            <div className="divide-y divide-gray-100">
                                {notifications.map((notification) => (
                                    <Menu.Item key={notification.id}>
                                        {() => (
                                            <div
                                                onClick={(e) => handleNotificationClick(notification, e)}
                                                className={`relative flex items-start px-4 py-3 hover:bg-gray-50 cursor-pointer ${!notification.read_at ? 'bg-blue-50' : ''}`}
                                            >
                                                <div className="flex-shrink-0 mt-0.5">
                                                    <div className="h-8 w-8 rounded-full bg-primary-100 flex items-center justify-center">
                                                        {notification.data.icon ? (
                                                            <span className="text-primary-600">
                                                                {notification.data.icon}
                                                            </span>
                                                        ) : (
                                                            <BellIcon className="h-5 w-5 text-primary-600" aria-hidden="true" />
                                                        )}
                                                    </div>
                                                </div>
                                                <div className="ml-3 flex-1 min-w-0">
                                                    <p className="text-sm font-medium text-gray-900">
                                                        {notification.data.title || 'New Notification'}
                                                    </p>
                                                    <p className="text-sm text-gray-500">
                                                        {notification.data.message}
                                                    </p>
                                                    <p className="mt-1 text-xs text-gray-400">
                                                        {formatTimeAgo(notification.created_at)}
                                                    </p>
                                                </div>
                                                <div className="ml-4 flex-shrink-0 flex">
                                                    <button
                                                        type="button"
                                                        onClick={(e) => deleteNotification(notification.id, e)}
                                                        className="text-gray-400 hover:text-gray-500"
                                                    >
                                                        <span className="sr-only">Delete notification</span>
                                                        <XMarkIcon className="h-4 w-4" aria-hidden="true" />
                                                    </button>
                                                </div>
                                            </div>
                                        )}
                                    </Menu.Item>
                                ))}
                            </div>
                        )}
                    </div>
                </Menu.Items>
            </Transition>
        </Menu>
    );
}
