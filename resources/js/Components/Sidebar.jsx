import { Fragment, useState } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import { 
    XIcon,
    ChevronDownIcon,
    HomeIcon,
    CubeIcon,
    ViewListIcon,
    PlusCircleIcon,
    TagIcon,
    TemplateIcon,
    WrenchScrewdriverIcon,
    CalendarIcon,
    ClockIcon,
    ClipboardDocumentCheckIcon,
    ChartBarIcon,
    CurrencyDollarIcon,
    ShieldCheckIcon,
    DocumentChartBarIcon,
    Cog6ToothIcon,
    UserGroupIcon,
    LockClosedIcon,
    MapPinIcon,
    BuildingOfficeIcon,
    TruckIcon
} from '@heroicons/react/24/outline';
import { Link, usePage } from '@inertiajs/react';

const navigation = [
    { 
        name: 'Dashboard', 
        href: route('dashboard'), 
        icon: 'HomeIcon', 
        current: true 
    },
    { 
        name: 'Assets', 
        href: route('assets.index'), 
        icon: 'CubeIcon', 
        current: false,
        children: [
            { name: 'All Assets', href: route('assets.index'), icon: 'ViewListIcon' },
            { name: 'Add New', href: route('assets.create'), icon: 'PlusCircleIcon' },
            { name: 'Categories', href: route('asset-categories.index'), icon: 'TagIcon' },
            { name: 'Models', href: route('asset-models.index'), icon: 'TemplateIcon' },
        ]
    },
    { 
        name: 'Maintenance', 
        href: route('maintenance.index'), 
        icon: 'WrenchScrewdriverIcon', 
        current: false,
        children: [
            { name: 'Scheduled', href: route('maintenance.scheduled'), icon: 'CalendarIcon' },
            { name: 'History', href: route('maintenance.history'), icon: 'ClockIcon' },
            { name: 'Checklists', href: route('maintenance.checklists'), icon: 'ClipboardDocumentCheckIcon' },
        ]
    },
    { 
        name: 'Reports', 
        href: route('reports.index'), 
        icon: 'ChartBarIcon', 
        current: false,
        children: [
            { name: 'Asset Reports', href: route('reports.assets'), icon: 'CubeIcon' },
            { name: 'Financial', href: route('reports.financial'), icon: 'CurrencyDollarIcon' },
            { name: 'Compliance', href: route('reports.compliance'), icon: 'ShieldCheckIcon' },
            { name: 'Custom Reports', href: route('reports.custom'), icon: 'DocumentChartBarIcon' },
        ]
    },
    { 
        name: 'Administration', 
        href: route('admin.index'), 
        icon: 'Cog6ToothIcon', 
        current: false,
        children: [
            { name: 'Users', href: route('users.index'), icon: 'UserGroupIcon' },
            { name: 'Roles & Permissions', href: route('roles.index'), icon: 'LockClosedIcon' },
            { name: 'Locations', href: route('locations.index'), icon: 'MapPinIcon' },
            { name: 'Departments', href: route('departments.index'), icon: 'BuildingOfficeIcon' },
            { name: 'Vendors', href: route('vendors.index'), icon: 'TruckIcon' },
            { name: 'Settings', href: route('settings.general'), icon: 'Cog8ToothIcon' },
        ]
    },
];

function classNames(...classes) {
    return classes.filter(Boolean).join(' ');
}

export default function Sidebar({ sidebarOpen, setSidebarOpen }) {
    const { url } = usePage();

    // Update current navigation item based on current URL
    const updatedNavigation = navigation.map(item => ({
        ...item,
        current: url.startsWith(item.href)
    }));

    return (
        <>
            {/* Mobile sidebar */}
            <Transition.Root show={sidebarOpen} as={Fragment}>
                <Dialog as="div" className="fixed inset-0 flex z-40 lg:hidden" onClose={setSidebarOpen}>
                    <Transition.Child
                        as={Fragment}
                        enter="transition-opacity ease-linear duration-300"
                        enterFrom="opacity-0"
                        enterTo="opacity-100"
                        leave="transition-opacity ease-linear duration-300"
                        leaveFrom="opacity-100"
                        leaveTo="opacity-0"
                    >
                        <Dialog.Overlay className="fixed inset-0 bg-gray-600 bg-opacity-75" />
                    </Transition.Child>
                    <Transition.Child
                        as={Fragment}
                        enter="transition ease-in-out duration-300 transform"
                        enterFrom="-translate-x-full"
                        enterTo="translate-x-0"
                        leave="transition ease-in-out duration-300 transform"
                        leaveFrom="translate-x-0"
                        leaveTo="-translate-x-full"
                    >
                        <div className="relative flex-1 flex flex-col max-w-xs w-full bg-white">
                            <Transition.Child
                                as={Fragment}
                                enter="ease-in-out duration-300"
                                enterFrom="opacity-0"
                                enterTo="opacity-100"
                                leave="ease-in-out duration-300"
                                leaveFrom="opacity-100"
                                leaveTo="opacity-0"
                            >
                                <div className="absolute top-0 right-0 -mr-12 pt-2">
                                    <button
                                        type="button"
                                        className="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                                        onClick={() => setSidebarOpen(false)}
                                    >
                                        <span className="sr-only">Close sidebar</span>
                                        <XIcon className="h-6 w-6 text-white" aria-hidden="true" />
                                    </button>
                                </div>
                            </Transition.Child>
                            <div className="flex-1 h-0 pt-5 pb-4 overflow-y-auto">
                                <div className="flex-shrink-0 flex items-center px-4">
                                    <img
                                        className="h-8 w-auto"
                                        src="/images/neepco-logo.png"
                                        alt="NEEPCO"
                                    />
                                    <span className="ml-2 text-xl font-bold text-gray-900">Asset Management</span>
                                </div>
                                <nav className="mt-5 px-2 space-y-1">
                                    {updatedNavigation.map((item) => {
                                        const Icon = eval(item.icon) || CubeIcon;
                                        const hasChildren = item.children && item.children.length > 0;
                                        const [isOpen, setIsOpen] = useState(item.current);

                                        return (
                                            <div key={item.name}>
                                                <div className="flex items-center">
                                                    <Link
                                                        href={item.href}
                                                        className={classNames(
                                                            item.current
                                                                ? 'bg-gray-100 text-gray-900'
                                                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                                            'group flex-1 flex items-center px-2 py-2 text-sm font-medium rounded-md'
                                                        )}
                                                    >
                                                        <Icon
                                                            className={classNames(
                                                                item.current ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500',
                                                                'mr-3 flex-shrink-0 h-6 w-6'
                                                            )}
                                                            aria-hidden="true"
                                                        />
                                                        {item.name}
                                                    </Link>
                                                    {hasChildren && (
                                                        <button
                                                            onClick={() => setIsOpen(!isOpen)}
                                                            className="mr-2 text-gray-400 hover:text-gray-500"
                                                        >
                                                            <ChevronDownIcon 
                                                                className={`h-4 w-4 transform transition-transform ${isOpen ? 'rotate-180' : ''}`} 
                                                                aria-hidden="true" 
                                                            />
                                                        </button>
                                                    )}
                                                </div>
                                                {hasChildren && isOpen && (
                                                    <div className="ml-8 mt-1 space-y-1">
                                                        {item.children.map((child) => {
                                                            const ChildIcon = eval(child.icon) || ViewListIcon;
                                                            return (
                                                                <Link
                                                                    key={child.name}
                                                                    href={child.href}
                                                                    className="group flex items-center px-2 py-2 text-sm font-medium text-gray-600 rounded-md hover:text-gray-900 hover:bg-gray-50"
                                                                >
                                                                    <ChildIcon
                                                                        className="mr-3 flex-shrink-0 h-5 w-5 text-gray-400 group-hover:text-gray-500"
                                                                        aria-hidden="true"
                                                                    />
                                                                    {child.name}
                                                                </Link>
                                                            );
                                                        })}
                                                    </div>
                                                )}
                                            </div>
                                        );
                                    })}
                                </nav>
                            </div>
                            <div className="flex-shrink-0 flex border-t border-gray-200 p-4">
                                <a href="#" className="flex-shrink-0 group block">
                                    <div className="flex items-center">
                                        <div>
                                            <div className="h-10 w-10 rounded-full bg-gray-600 flex items-center justify-center text-white font-medium">
                                                {usePage().props.auth.user?.name?.charAt(0) || 'U'}
                                            </div>
                                        </div>
                                        <div className="ml-3">
                                            <p className="text-base font-medium text-gray-700 group-hover:text-gray-900">
                                                {usePage().props.auth.user?.name || 'User'}
                                            </p>
                                            <p className="text-sm font-medium text-gray-500 group-hover:text-gray-700">
                                                View profile
                                            </p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </Transition.Child>
                    <div className="flex-shrink-0 w-14">{/* Force sidebar to shrink to fit close icon */}</div>
                </Dialog>
            </Transition.Root>

            {/* Static sidebar for desktop */}
            <div className="hidden lg:flex lg:flex-shrink-0">
                <div className="flex flex-col w-64">
                    <div className="flex-1 flex flex-col min-h-0 border-r border-gray-200 bg-white">
                        <div className="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
                            <div className="flex items-center flex-shrink-0 px-4">
                                <img
                                    className="h-8 w-auto"
                                    src="/images/neepco-logo.png"
                                    alt="NEEPCO"
                                />
                                <span className="ml-2 text-xl font-bold text-gray-900">Asset Management</span>
                            </div>
                            <nav className="mt-5 flex-1 px-2 space-y-1">
                                {updatedNavigation.map((item) => {
                                    const Icon = eval(item.icon) || CubeIcon;
                                    const hasChildren = item.children && item.children.length > 0;
                                    const [isOpen, setIsOpen] = useState(item.current);

                                    return (
                                        <div key={item.name} className="space-y-1">
                                            <div className="flex items-center">
                                                <Link
                                                    href={item.href}
                                                    className={classNames(
                                                        item.current
                                                            ? 'bg-gray-100 text-gray-900'
                                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                                        'group flex-1 flex items-center px-2 py-2 text-sm font-medium rounded-md'
                                                    )}
                                                >
                                                    <Icon
                                                        className={classNames(
                                                            item.current ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500',
                                                            'mr-3 flex-shrink-0 h-6 w-6'
                                                        )}
                                                        aria-hidden="true"
                                                    />
                                                    {item.name}
                                                </Link>
                                                {hasChildren && (
                                                    <button
                                                        onClick={() => setIsOpen(!isOpen)}
                                                        className="mr-2 text-gray-400 hover:text-gray-500"
                                                    >
                                                        <ChevronDownIcon 
                                                            className={`h-4 w-4 transform transition-transform ${isOpen ? 'rotate-180' : ''}`} 
                                                            aria-hidden="true" 
                                                        />
                                                    </button>
                                                )}
                                            </div>
                                            {hasChildren && isOpen && (
                                                <div className="ml-8 space-y-1">
                                                    {item.children.map((child) => {
                                                        const ChildIcon = eval(child.icon) || ViewListIcon;
                                                        return (
                                                            <Link
                                                                key={child.name}
                                                                href={child.href}
                                                                className="group flex items-center px-2 py-2 text-sm font-medium text-gray-600 rounded-md hover:text-gray-900 hover:bg-gray-50"
                                                            >
                                                                <ChildIcon
                                                                    className="mr-3 flex-shrink-0 h-5 w-5 text-gray-400 group-hover:text-gray-500"
                                                                    aria-hidden="true"
                                                                />
                                                                {child.name}
                                                            </Link>
                                                        );
                                                    })}
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </nav>
                        </div>
                        <div className="flex-shrink-0 flex border-t border-gray-200 p-4">
                            <a href={route('profile.show')} className="flex-shrink-0 w-full group block">
                                <div className="flex items-center">
                                    <div>
                                        <img
                                            className="inline-block h-9 w-9 rounded-full"
                                            src={auth.user?.profile_photo_url}
                                            alt={auth.user?.name}
                                        />
                                    </div>
                                    <div className="ml-3">
                                        <p className="text-sm font-medium text-gray-700 group-hover:text-gray-900">
                                            {auth.user?.name}
                                        </p>
                                        <p className="text-xs font-medium text-gray-500 group-hover:text-gray-700">
                                            View profile
                                        </p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
