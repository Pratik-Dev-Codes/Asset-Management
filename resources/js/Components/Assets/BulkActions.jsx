import { router } from '@inertiajs/react';
import { useState } from 'react';

export default function BulkActions({ selectedItems, onDeselectAll }) {
    const [action, setAction] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);

    const handleBulkAction = () => {
        if (!action || selectedItems.length === 0) return;

        if (['delete', 'checkout', 'checkin', 'maintenance'].includes(action)) {
            setShowConfirm(true);
            return;
        }

        submitAction();
    };

    const submitAction = () => {
        setIsLoading(true);
        
        router.post(route('assets.bulk-action'), {
            ids: selectedItems,
            action: action,
        }, {
            onSuccess: () => {
                onDeselectAll();
                setAction('');
                setShowConfirm(false);
                setIsLoading(false);
            },
            onError: () => {
                setIsLoading(false);
                setShowConfirm(false);
            },
            preserveScroll: true,
        });
    };

    if (selectedItems.length === 0) return null;

    return (
        <div className="bg-indigo-50 dark:bg-indigo-900/30 p-4 rounded-lg mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div className="flex items-center">
                <span className="text-sm font-medium text-indigo-800 dark:text-indigo-200">
                    {selectedItems.length} {selectedItems.length === 1 ? 'item' : 'items'} selected
                </span>
                <button
                    type="button"
                    onClick={onDeselectAll}
                    className="ml-3 text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
                >
                    Deselect all
                </button>
            </div>

            <div className="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                <select
                    value={action}
                    onChange={(e) => setAction(e.target.value)}
                    className="block w-full sm:w-48 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
                    <option value="">Bulk Actions</option>
                    <option value="checkout">Check Out</option>
                    <option value="checkin">Check In</option>
                    <option value="maintenance">Mark for Maintenance</option>
                    <option value="export">Export Selected</option>
                    <option value="delete" className="text-red-600">Delete Selected</option>
                </select>

                <button
                    type="button"
                    onClick={handleBulkAction}
                    disabled={!action || isLoading}
                    className={`inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white ${
                        isLoading ? 'bg-indigo-400' : 'bg-indigo-600 hover:bg-indigo-700'
                    } focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500`}
                >
                    {isLoading ? 'Processing...' : 'Apply'}
                </button>
            </div>

            {/* Confirmation Dialog */}
            {showConfirm && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
                        <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Confirm {action.charAt(0).toUpperCase() + action.slice(1)}
                        </h3>
                        <p className="text-sm text-gray-600 dark:text-gray-300 mb-6">
                            Are you sure you want to {action} {selectedItems.length} {selectedItems.length === 1 ? 'asset' : 'assets'}? 
                            {action === 'delete' && ' This action cannot be undone.'}
                        </p>
                        <div className="flex justify-end space-x-3">
                            <button
                                type="button"
                                onClick={() => setShowConfirm(false)}
                                className="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                onClick={submitAction}
                                disabled={isLoading}
                                className={`inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white ${
                                    isLoading ? 'bg-red-400' : 'bg-red-600 hover:bg-red-700'
                                } focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500`}
                            >
                                {isLoading ? 'Processing...' : 'Confirm'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
