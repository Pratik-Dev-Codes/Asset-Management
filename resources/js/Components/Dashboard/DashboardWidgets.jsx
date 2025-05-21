import { Link } from '@inertiajs/react';

export default function DashboardWidgets({ stats, recentActivities, upcomingMaintenance }) {
    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {/* Total Assets */}
            <div className="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div className="p-5">
                    <div className="flex items-center">
                        <div className="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                            <svg className="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div className="ml-5 w-0 flex-1">
                            <dl>
                                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Total Assets
                                </dt>
                                <dd className="flex items-baseline">
                                    <div className="text-2xl font-semibold text-gray-900 dark:text-white">
                                        {stats.total_assets.toLocaleString()}
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div className="bg-gray-50 dark:bg-gray-700 px-5 py-3">
                    <div className="text-sm">
                        <Link href={route('assets.index')} className="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                            View all
                        </Link>
                    </div>
                </div>
            </div>

            {/* Available Assets */}
            <div className="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div className="p-5">
                    <div className="flex items-center">
                        <div className="flex-shrink-0 bg-green-500 rounded-md p-3">
                            <svg className="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div className="ml-5 w-0 flex-1">
                            <dl>
                                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Available
                                </dt>
                                <dd className="flex items-baseline">
                                    <div className="text-2xl font-semibold text-gray-900 dark:text-white">
                                        {stats.available_assets.toLocaleString()}
                                    </div>
                                    <div className="ml-2 flex items-baseline text-sm font-semibold text-green-600 dark:text-green-400">
                                        {Math.round((stats.available_assets / stats.total_assets) * 100)}%
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div className="bg-gray-50 dark:bg-gray-700 px-5 py-3">
                    <div className="text-sm">
                        <Link href={route('assets.index', { status: 'available' })} className="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                            View available
                        </Link>
                    </div>
                </div>
            </div>

            {/* Assets in Maintenance */}
            <div className="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div className="p-5">
                    <div className="flex items-center">
                        <div className="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                            <svg className="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div className="ml-5 w-0 flex-1">
                            <dl>
                                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    In Maintenance
                                </dt>
                                <dd className="flex items-baseline">
                                    <div className="text-2xl font-semibold text-gray-900 dark:text-white">
                                        {stats.maintenance_assets.toLocaleString()}
                                    </div>
                                    <div className="ml-2 flex items-baseline text-sm font-semibold text-yellow-600 dark:text-yellow-400">
                                        {Math.round((stats.maintenance_assets / stats.total_assets) * 100)}%
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div className="bg-gray-50 dark:bg-gray-700 px-5 py-3">
                    <div className="text-sm">
                        <Link href={route('maintenance.index')} className="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                            View maintenance
                        </Link>
                    </div>
                </div>
            </div>

            {/* Total Value */}
            <div className="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div className="p-5">
                    <div className="flex items-center">
                        <div className="flex-shrink-0 bg-purple-500 rounded-md p-3">
                            <svg className="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div className="ml-5 w-0 flex-1">
                            <dl>
                                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Total Value
                                </dt>
                                <dd className="flex items-baseline">
                                    <div className="text-2xl font-semibold text-gray-900 dark:text-white">
                                        ${stats.total_value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div className="bg-gray-50 dark:bg-gray-700 px-5 py-3">
                    <div className="text-sm">
                        <Link href={route('reports.financial')} className="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                            View financial report
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}
