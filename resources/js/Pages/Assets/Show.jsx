import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function AssetShow({ asset }) {
    const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString();
    };

    const formatCurrency = (amount) => {
        if (!amount) return 'N/A';
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(amount);
    };

    const getStatusBadge = (status) => {
        const statusClasses = {
            active: 'bg-green-100 text-green-800',
            inactive: 'bg-gray-100 text-gray-800',
            maintenance: 'bg-yellow-100 text-yellow-800',
            retired: 'bg-red-100 text-red-800',
        };

        return (
            <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClasses[status] || 'bg-gray-100 text-gray-800'}`}>
                {status.charAt(0).toUpperCase() + status.slice(1).replace(/-/g, ' ')}
            </span>
        );
    };

    return (
        <div className="py-12">
            <Head title={`Asset: ${asset.name}`} />
            <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div className="p-6 bg-white border-b border-gray-200">
                        <div className="flex justify-between items-start mb-6">
                            <div>
                                <h2 className="text-2xl font-semibold">{asset.name}</h2>
                                <div className="mt-1">
                                    {getStatusBadge(asset.status)}
                                    <span className="ml-2 text-sm text-gray-500">
                                        {asset.type.charAt(0).toUpperCase() + asset.type.slice(1)} Asset
                                    </span>
                                </div>
                            </div>
                            <div className="flex space-x-3">
                                <Link
                                    href={route('assets.edit', asset.id)}
                                    className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition"
                                >
                                    Edit
                                </Link>
                                <Link
                                    href={route('assets.index')}
                                    className="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition"
                                >
                                    Back to Assets
                                </Link>
                            </div>
                        </div>

                        <div className="border-t border-gray-200 pt-6">
                            <dl className="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                                <div className="sm:col-span-1">
                                    <dt className="text-sm font-medium text-gray-500">Asset ID</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{asset.id}</dd>
                                </div>
                                <div className="sm:col-span-1">
                                    <dt className="text-sm font-medium text-gray-500">Serial Number</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{asset.serial_number || 'N/A'}</dd>
                                </div>
                                <div className="sm:col-span-1">
                                    <dt className="text-sm font-medium text-gray-500">Model</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{asset.model || 'N/A'}</dd>
                                </div>
                                <div className="sm:col-span-1">
                                    <dt className="text-sm font-medium text-gray-500">Purchase Date</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{formatDate(asset.purchase_date)}</dd>
                                </div>
                                <div className="sm:col-span-1">
                                    <dt className="text-sm font-medium text-gray-500">Purchase Cost</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{formatCurrency(asset.purchase_cost)}</dd>
                                </div>
                                <div className="sm:col-span-1">
                                    <dt className="text-sm font-medium text-gray-500">Warranty Expiry</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{formatDate(asset.warranty_expiry)}</dd>
                                </div>
                                <div className="sm:col-span-2">
                                    <dt className="text-sm font-medium text-gray-500">Notes</dt>
                                    <dd className="mt-1 text-sm text-gray-900 whitespace-pre-line">
                                        {asset.notes || 'No notes available.'}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div className="mt-8 border-t border-gray-200 pt-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Asset History</h3>
                            <div className="bg-gray-50 p-4 rounded-md">
                                <p className="text-sm text-gray-500 italic">
                                    Asset history and audit trail will be displayed here.
                                </p>
                                {/* Asset history and audit trail would be implemented here */}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

Show.layout = page => <AppLayout children={page} />;
