import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';

export default function AssetFilters({ filters }) {
    const { data, setData, get } = useForm({
        search: filters.search || '',
        status: filters.status || '',
        category_id: filters.category_id || '',
        location_id: filters.location_id || '',
        department_id: filters.department_id || '',
        date_from: filters.date_from || '',
        date_to: filters.date_to || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        get(route('assets.index', data), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        get(route('assets.index'));
    };

    return (
        <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-4 mb-6">
            <form onSubmit={handleSubmit} className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    {/* Search Input */}
                    <div>
                        <label htmlFor="search" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Search
                        </label>
                        <input
                            type="text"
                            id="search"
                            value={data.search}
                            onChange={(e) => setData('search', e.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="Search assets..."
                        />
                    </div>

                    {/* Status Dropdown */}
                    <div>
                        <label htmlFor="status" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Status
                        </label>
                        <select
                            id="status"
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                            <option value="">All Statuses</option>
                            <option value="available">Available</option>
                            <option value="assigned">Assigned</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="retired">Retired</option>
                        </select>
                    </div>

                    {/* Category Dropdown */}
                    <div>
                        <label htmlFor="category_id" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Category
                        </label>
                        <select
                            id="category_id"
                            value={data.category_id}
                            onChange={(e) => setData('category_id', e.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                            <option value="">All Categories</option>
                            {/* Categories will be populated via props */}
                            {filters.categories?.map((category) => (
                                <option key={category.id} value={category.id}>
                                    {category.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* Location Dropdown */}
                    <div>
                        <label htmlFor="location_id" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Location
                        </label>
                        <select
                            id="location_id"
                            value={data.location_id}
                            onChange={(e) => setData('location_id', e.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                            <option value="">All Locations</option>
                            {filters.locations?.map((location) => (
                                <option key={location.id} value={location.id}>
                                    {location.name}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {/* Date From */}
                    <div>
                        <label htmlFor="date_from" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            From Date
                        </label>
                        <input
                            type="date"
                            id="date_from"
                            value={data.date_from}
                            onChange={(e) => setData('date_from', e.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        />
                    </div>

                    {/* Date To */}
                    <div>
                        <label htmlFor="date_to" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            To Date
                        </label>
                        <input
                            type="date"
                            id="date_to"
                            value={data.date_to}
                            onChange={(e) => setData('date_to', e.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        />
                    </div>

                    {/* Department Dropdown */}
                    <div>
                        <label htmlFor="department_id" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Department
                        </label>
                        <select
                            id="department_id"
                            value={data.department_id}
                            onChange={(e) => setData('department_id', e.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                            <option value="">All Departments</option>
                            {filters.departments?.map((dept) => (
                                <option key={dept.id} value={dept.id}>
                                    {dept.name}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                <div className="flex justify-end space-x-3">
                    <button
                        type="button"
                        onClick={resetFilters}
                        className="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600"
                    >
                        Reset Filters
                    </button>
                    <button
                        type="submit"
                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>
    );
}
