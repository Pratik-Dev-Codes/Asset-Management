import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function ReportsIndex() {
    const [activeTab, setActiveTab] = useState('asset');

    const reports = {
        asset: [
            { id: 1, name: 'Asset Inventory', description: 'Complete list of all assets with current status' },
            { id: 2, name: 'Asset Depreciation', description: 'Depreciation report for all assets' },
            { id: 3, name: 'Asset Maintenance', description: 'Maintenance history and upcoming schedules' },
            { id: 4, name: 'Asset Audit', description: 'Audit trail for all asset changes' },
        ],
        financial: [
            { id: 5, name: 'Asset Value', description: 'Total value of assets by category' },
            { id: 6, name: 'Purchase History', description: 'Historical purchases and costs' },
            { id: 7, name: 'Maintenance Costs', description: 'Costs associated with asset maintenance' },
        ],
        compliance: [
            { id: 8, name: 'Warranty Expiry', description: 'Assets with warranties expiring soon' },
            { id: 9, name: 'License Expiry', description: 'Software licenses and their expiry dates' },
            { id: 10, name: 'Asset Retirement', description: 'Assets scheduled for retirement' },
        ],
    };

    const generateReport = (reportId) => {
        // In a real app, this would trigger report generation
        alert(`Generating report #${reportId}...`);
    };

    return (
        <div className="py-12">
            <Head title="Reports" />
            <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div className="p-6 bg-white border-b border-gray-200">
                        <h2 className="text-2xl font-semibold mb-6">Reports</h2>
                        
                        <div className="border-b border-gray-200 mb-6">
                            <nav className="-mb-px flex space-x-8">
                                {['asset', 'financial', 'compliance'].map((tab) => (
                                    <button
                                        key={tab}
                                        onClick={() => setActiveTab(tab)}
                                        className={`${activeTab === tab
                                            ? 'border-indigo-500 text-indigo-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                        } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm capitalize`}
                                    >
                                        {tab} Reports
                                    </button>
                                ))}
                            </nav>
                        </div>

                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {reports[activeTab].map((report) => (
                                <div key={report.id} className="border border-gray-200 rounded-lg overflow-hidden shadow-sm">
                                    <div className="p-5">
                                        <h3 className="text-lg font-medium text-gray-900 mb-2">{report.name}</h3>
                                        <p className="text-sm text-gray-500 mb-4">{report.description}</p>
                                        <div className="flex justify-between items-center">
                                            <span className="text-xs text-gray-500">Last run: Never</span>
                                            <button
                                                onClick={() => generateReport(report.id)}
                                                className="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                            >
                                                Generate
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="mt-8 border-t border-gray-200 pt-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">Custom Report</h3>
                            <div className="bg-gray-50 p-4 rounded-md">
                                <p className="text-sm text-gray-500 mb-4">
                                    Create a custom report by selecting specific parameters and fields to include.
                                </p>
                                <button
                                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    Create Custom Report
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

ReportsIndex.layout = page => <AppLayout children={page} />;
