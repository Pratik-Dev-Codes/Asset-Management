import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import AssetFilters from '@/Components/Assets/AssetFilters';
import BulkActions from '@/Components/Assets/BulkActions';
import { Checkbox } from '@/Components/ui/checkbox';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { Search, Filter, PlusCircle, RefreshCw } from 'lucide-react';
import DashboardWidgets from '@/Components/Dashboard/DashboardWidgets';

const getStatusVariant = (status) => {
    if (!status) return 'outline';
    switch (status.toLowerCase()) {
        case 'available':
            return 'success';
        case 'assigned':
            return 'secondary';
        case 'maintenance':
            return 'warning';
        case 'retired':
            return 'destructive';
        default:
            return 'outline';
    }
};

export default function AssetIndex({ 
    assets, 
    stats,
    filters: initialFilters = {}, 
    categories = [], 
    locations = [], 
    departments = [] 
}) {
    const [selectedItems, setSelectedItems] = useState([]);
    const [isFilterOpen, setIsFilterOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState(initialFilters.search || '');
    const [filters, setFilters] = useState({
        search: initialFilters.search || '',
        status: initialFilters.status || '',
        category_id: initialFilters.category_id || '',
        location_id: initialFilters.location_id || '',
        department_id: initialFilters.department_id || '',
        date_from: initialFilters.date_from || '',
        date_to: initialFilters.date_to || '',
    });

    // Update selected items when assets change
    useEffect(() => {
        // Deselect items that are no longer in the current page
        setSelectedItems(prev => 
            prev.filter(id => assets.data.some(asset => asset.id === id))
        );
    }, [assets.data]);

    const handleFilterChange = (newFilters) => {
        const updatedFilters = { ...filters, ...newFilters, page: 1 };
        setFilters(updatedFilters);
        
        // Remove empty filters
        const cleanFilters = Object.fromEntries(
            Object.entries(updatedFilters).filter(([_, v]) => v !== '' && v !== null)
        );
        
        router.get(route('assets.index'), cleanFilters, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleSearch = (e) => {
        if (e) e.preventDefault();
        handleFilterChange({ search: searchTerm });
    };

    const handleResetFilters = () => {
        setSearchTerm('');
        setFilters({
            search: '',
            status: '',
            category_id: '',
            location_id: '',
            department_id: '',
            date_from: '',
            date_to: '',
        });
        router.get(route('assets.index'));
    };

    const toggleSelectAll = (checked) => {
        if (checked) {
            const pageIds = assets.data.map(asset => asset.id);
            setSelectedItems(prev => [...new Set([...prev, ...pageIds])]);
        } else {
            const pageIds = new Set(assets.data.map(asset => asset.id));
            setSelectedItems(prev => prev.filter(id => !pageIds.has(id)));
        }
    };

    const toggleSelectItem = (id) => {
        setSelectedItems(prev => 
            prev.includes(id) 
                ? prev.filter(itemId => itemId !== id)
                : [...prev, id]
        );
    };

    const handleDeselectAll = () => {
        setSelectedItems([]);
    };

    const handleBulkAction = (action) => {
        if (selectedItems.length === 0) return;

        if (action === 'delete' && !confirm(`Are you sure you want to delete ${selectedItems.length} selected items?`)) {
            return;
        }

        router.post(route('assets.bulk-action'), {
            ids: selectedItems,
            action: action,
        }, {
            onSuccess: () => {
                setSelectedItems([]);
                router.reload({ only: ['assets'] });
            },
        });
    };

    const handlePageChange = (url) => {
        if (url) {
            const page = new URL(url).searchParams.get('page');
            router.get(route('assets.index', { ...filters, page }), {}, {
                preserveState: true,
                preserveScroll: true,
            });
        }
    };
    const allSelectedOnPage = assets.data.length > 0 && 
        assets.data.every(asset => selectedItems.includes(asset.id));
    const someSelectedOnPage = assets.data.some(asset => 
        selectedItems.includes(asset.id)
    ) && !allSelectedOnPage;

    return (
        <AppLayout>
            <Head title="Asset Management" />
            
            <div className="space-y-6">
                {/* Dashboard Widgets */}
                {stats && <DashboardWidgets stats={stats} />}

                {/* Header */}
                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Asset Management</h1>
                        <p className="text-muted-foreground">
                            Manage and track your organization's assets
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button asChild>
                            <Link href={route('assets.create')}>
                                <PlusCircle className="mr-2 h-4 w-4" />
                                Add Asset
                            </Link>
                        </Button>
                        <Button 
                            variant="outline" 
                            size="icon" 
                            onClick={() => router.reload({ only: ['assets'] })}
                            title="Refresh"
                        >
                            <RefreshCw className="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                {/* Search and Filters */}
                <div className="bg-card p-4 rounded-lg border">
                    <form onSubmit={handleSearch} className="space-y-4">
                        <div className="flex flex-col md:flex-row gap-4">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    type="search"
                                    placeholder="Search assets..."
                                    className="pl-9 w-full"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                />
                            </div>
                            <Button 
                                variant="outline" 
                                type="button"
                                onClick={() => setIsFilterOpen(!isFilterOpen)}
                                className="gap-2"
                            >
                                <Filter className="h-4 w-4" />
                                Filters
                                {Object.values(filters).filter(Boolean).length > 0 && (
                                    <span className="ml-1 flex h-4 w-4 items-center justify-center rounded-full bg-primary text-xs text-primary-foreground">
                                        {Object.values(filters).filter(Boolean).length}
                                    </span>
                                )}
                            </Button>
                        </div>

                        {/* Advanced Filters */}
                        {isFilterOpen && (
                            <AssetFilters 
                                filters={filters} 
                                categories={categories}
                                locations={locations}
                                departments={departments}
                                onFilterChange={handleFilterChange}
                                onReset={handleResetFilters}
                            />
                        )}
                    </form>
                </div>

                {/* Bulk Actions */}
                {selectedItems.length > 0 && (
                    <BulkActions 
                        selectedItems={selectedItems}
                        onDeselectAll={handleDeselectAll}
                        onBulkAction={handleBulkAction}
                    />
                )}

                {/* Assets Table */}
                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-12">
                                    <Checkbox 
                                        checked={allSelectedOnPage}
                                        onCheckedChange={toggleSelectAll}
                                        aria-label="Select all"
                                        indeterminate={someSelectedOnPage}
                                    />
                                </TableHead>
                                <TableHead>Asset Tag</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Category</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Location</TableHead>
                                <TableHead>Assigned To</TableHead>
                                <TableHead>Purchase Date</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {assets.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={9} className="h-24 text-center">
                                        <div className="flex flex-col items-center justify-center py-6">
                                            <p className="text-muted-foreground">No assets found.</p>
                                            <Button 
                                                variant="link" 
                                                className="mt-2"
                                                onClick={handleResetFilters}
                                            >
                                                Clear all filters
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ) : (
                                assets.data.map((asset) => (
                                    <TableRow key={asset.id}>
                                        <TableCell>
                                            <Checkbox 
                                                checked={selectedItems.includes(asset.id)}
                                                onCheckedChange={() => toggleSelectItem(asset.id)}
                                                aria-label={`Select ${asset.name}`}
                                            />
                                        </TableCell>
                                        <TableCell className="font-medium">{asset.asset_tag || 'N/A'}</TableCell>
                                        <TableCell>
                                            <Link 
                                                href={route('assets.show', asset.id)}
                                                className="font-medium hover:underline"
                                            >
                                                {asset.name}
                                            </Link>
                                        </TableCell>
                                        <TableCell>{asset.category?.name || 'N/A'}</TableCell>
                                        <TableCell>
                                            <Badge variant={getStatusVariant(asset.status)}>
                                                {asset.status ? asset.status.charAt(0).toUpperCase() + asset.status.slice(1) : 'N/A'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{asset.location?.name || 'N/A'}</TableCell>
                                        <TableCell>
                                            {asset.assigned_to 
                                                ? `${asset.assigned_to.first_name} ${asset.assigned_to.last_name}`
                                                : 'Unassigned'}
                                        </TableCell>
                                        <TableCell>
                                            {asset.purchase_date ? new Date(asset.purchase_date).toLocaleDateString() : 'N/A'}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button 
                                                    variant="ghost" 
                                                    size="sm" 
                                                    asChild
                                                >
                                                    <Link href={route('assets.show', asset.id)}>View</Link>
                                                </Button>
                                                <Button 
                                                    variant="outline" 
                                                    size="sm" 
                                                    asChild
                                                >
                                                    <Link href={route('assets.edit', asset.id)}>Edit</Link>
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>

                {/* Pagination */}
                {assets.meta && assets.meta.total > assets.meta.per_page && (
                    <div className="flex items-center justify-end space-x-2 py-4">
                        <div className="text-sm text-muted-foreground">
                            Showing <span className="font-medium">{assets.meta.from}</span> to{' '}
                            <span className="font-medium">{assets.meta.to}</span> of{' '}
                            <span className="font-medium">{assets.meta.total}</span> assets
                        </div>
                        <div className="space-x-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => handlePageChange(assets.links.prev)}
                                disabled={!assets.links.prev}
                            >
                                Previous
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => handlePageChange(assets.links.next)}
                                disabled={!assets.links.next}
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

AssetIndex.layout = page => <AppLayout children={page} />;
