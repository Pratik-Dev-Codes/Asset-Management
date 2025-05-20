import { Fragment } from 'react';
import { Menu, Transition } from '@headlessui/react';
import { MenuAlt1Icon } from '@heroicons/react/outline';
import { Link } from '@inertiajs/react';
import NotificationDropdown from './Notifications/NotificationDropdown';

function classNames(...classes) {
    return classes.filter(Boolean).join(' ');
}

export default function Header({ setSidebarOpen, user }) {
    return (
        <div className="relative z-10 flex-shrink-0 flex h-16 bg-white shadow">
            <button
                type="button"
                className="px-4 border-r border-gray-200 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500 lg:hidden"
                onClick={() => setSidebarOpen(true)}
            >
                <span className="sr-only">Open sidebar</span>
                <MenuAlt1Icon className="h-6 w-6" aria-hidden="true" />
            </button>
            
            <div className="flex-1 px-4 flex justify-between">
                <div className="flex-1 flex">
                    {/* Search bar can be added here if needed */}
                </div>
                
                <div className="ml-4 flex items-center lg:ml-6 space-x-2">
                    {/* Notification Dropdown */}
                    <NotificationDropdown />

                    {/* Profile dropdown */}
                    <Menu as="div" className="relative">
                        <div>
                            <Menu.Button className="max-w-xs bg-white flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                <span className="sr-only">Open user menu</span>
                                <div className="h-8 w-8 rounded-full bg-primary-600 flex items-center justify-center text-white font-medium">
                                    {user?.name?.charAt(0) || 'U'}
                                </div>
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
                            <Menu.Items className="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none">
                                <Menu.Item>
                                    {({ active }) => (
                                        <Link
                                            href={route('profile.show')}
                                            className={classNames(
                                                active ? 'bg-gray-100' : '',
                                                'block px-4 py-2 text-sm text-gray-700'
                                            )}
                                        >
                                            Your Profile
                                        </Link>
                                    )}
                                </Menu.Item>
                                <Menu.Item>
                                    {({ active }) => (
                                        <Link
                                            href={route('settings')}
                                            className={classNames(
                                                active ? 'bg-gray-100' : '',
                                                'block px-4 py-2 text-sm text-gray-700'
                                            )}
                                        >
                                            Settings
                                        </Link>
                                    )}
                                </Menu.Item>
                                <Menu.Item>
                                    {({ active }) => (
                                        <Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                            className={classNames(
                                                active ? 'bg-gray-100' : '',
                                                'block w-full text-left px-4 py-2 text-sm text-gray-700'
                                            )}
                                        >
                                            Sign out
                                        </Link>
                                    )}
                                </Menu.Item>
                            </Menu.Items>
                        </Transition>
                    </Menu>
                </div>
            </div>
        </div>
    );
}
