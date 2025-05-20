<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SparePart;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display a listing of spare parts inventory.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Check permission
        if (! auth()->user()->can('inventory.view')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view inventory.');
        }

        $query = SparePart::with(['vendor']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('part_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('vendor')) {
            $query->where('vendor_id', $request->input('vendor'));
        }

        if ($request->filled('stock_status')) {
            switch ($request->input('stock_status')) {
                case 'in_stock':
                    $query->where('quantity_on_hand', '>', 0);
                    break;
                case 'low_stock':
                    $query->whereColumn('quantity_on_hand', '<=', 'reorder_level')
                        ->where('quantity_on_hand', '>', 0);
                    break;
                case 'out_of_stock':
                    $query->where('quantity_on_hand', 0);
                    break;
            }
        }

        // Sort results
        $sortField = $request->input('sort', 'name');
        $sortDirection = $request->input('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        // Paginate the results
        $spareParts = $query->paginate(15);

        // Get data for filters
        $vendors = Vendor::orderBy('name')->get();
        $stockStatuses = [
            'all' => 'All Items',
            'in_stock' => 'In Stock',
            'low_stock' => 'Low Stock',
            'out_of_stock' => 'Out of Stock',
        ];

        // Calculate inventory statistics
        $stats = $this->calculateInventoryStats();

        return view('inventory.index', compact(
            'spareParts',
            'vendors',
            'stockStatuses',
            'stats'
        ));
    }

    /**
     * Calculate inventory statistics.
     *
     * @return array
     */
    private function calculateInventoryStats()
    {
        $totalItems = SparePart::count();
        $totalValue = SparePart::selectRaw('SUM(quantity_on_hand * unit_price) as total_value')->first()->total_value ?? 0;
        $lowStockItems = SparePart::whereColumn('quantity_on_hand', '<=', 'reorder_level')
            ->where('quantity_on_hand', '>', 0)
            ->count();
        $outOfStockItems = SparePart::where('quantity_on_hand', 0)->count();

        return [
            'totalItems' => $totalItems,
            'totalValue' => $totalValue,
            'lowStockItems' => $lowStockItems,
            'outOfStockItems' => $outOfStockItems,
        ];
    }

    /**
     * Show the form for creating a new spare part.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Check permission
        if (! auth()->user()->can('inventory.create')) {
            return redirect()->route('inventory.index')->with('error', 'You do not have permission to create inventory items.');
        }

        $vendors = Vendor::orderBy('name')->get();
        $assetTypes = AssetCategory::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();

        return view('inventory.create', compact('vendors', 'assetTypes', 'locations'));
    }

    /**
     * Store a newly created spare part in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Check permission
        if (! auth()->user()->can('inventory.create')) {
            return redirect()->route('inventory.index')->with('error', 'You do not have permission to create inventory items.');
        }

        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'part_number' => 'required|string|max:100|unique:spare_parts',
            'description' => 'nullable|string',
            'asset_type_id' => 'nullable|exists:asset_categories,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'manufacturer' => 'nullable|string|max:255',
            'quantity_on_hand' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'location_in_store' => 'nullable|string|max:255',
        ]);

        // Create the spare part
        $sparePart = new SparePart;
        $sparePart->name = $request->name;
        $sparePart->part_number = $request->part_number;
        $sparePart->description = $request->description;
        $sparePart->asset_type_id = $request->asset_type_id;
        $sparePart->vendor_id = $request->vendor_id;
        $sparePart->manufacturer = $request->manufacturer;
        $sparePart->quantity_on_hand = $request->quantity_on_hand;
        $sparePart->reorder_level = $request->reorder_level;
        $sparePart->unit_price = $request->unit_price;
        $sparePart->location_in_store = $request->location_in_store;
        $sparePart->save();

        return redirect()->route('inventory.show', $sparePart)
            ->with('success', 'Spare part created successfully.');
    }

    /**
     * Display the specified spare part.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(SparePart $sparePart)
    {
        // Check permission
        if (! auth()->user()->can('inventory.view')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view inventory items.');
        }

        // Load related data
        $sparePart->load(['vendor']);

        // Get transaction history for this part
        $transactions = $this->getPartTransactions($sparePart->id);

        // Get compatible assets
        $compatibleAssets = Asset::where('category_id', $sparePart->asset_type_id)
            ->orderBy('name')
            ->get();

        return view('inventory.show', compact('sparePart', 'transactions', 'compatibleAssets'));
    }

    /**
     * Get transaction history for a specific part.
     *
     * @param  int  $partId
     * @return array
     */
    private function getPartTransactions($partId)
    {
        // This is a simplified example
        // In a real application, you would have a proper transactions model

        // Get purchase order items involving this part
        $poItems = PurchaseOrderItem::with(['purchaseOrder', 'purchaseOrder.vendor'])
            ->where('item_type', 'SparePart')
            ->where('spare_part_id', $partId)
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->purchaseOrder->order_date,
                    'type' => 'Purchase',
                    'reference' => 'PO #'.$item->purchaseOrder->po_number,
                    'quantity' => $item->quantity,
                    'value' => $item->total_price,
                    'notes' => 'Vendor: '.($item->purchaseOrder->vendor->name ?? 'Unknown'),
                ];
            });

        // Get maintenance usages involving this part
        $maintenanceUsages = DB::table('maintenance_log_spare_parts')
            ->join('maintenance_logs', 'maintenance_log_spare_parts.maintenance_log_id', '=', 'maintenance_logs.id')
            ->join('assets', 'maintenance_logs.asset_id', '=', 'assets.id')
            ->where('maintenance_log_spare_parts.spare_part_id', $partId)
            ->select(
                'maintenance_logs.completion_datetime as date',
                'maintenance_log_spare_parts.quantity_used as quantity',
                'maintenance_log_spare_parts.cost_at_time_of_use as value',
                'maintenance_logs.title as reference',
                'assets.name as asset_name'
            )
            ->get()
            ->map(function ($usage) {
                return [
                    'date' => $usage->date,
                    'type' => 'Usage',
                    'reference' => $usage->reference,
                    'quantity' => -1 * $usage->quantity,
                    'value' => $usage->value,
                    'notes' => 'Used for: '.$usage->asset_name,
                ];
            });

        // Combine and sort by date (descending)
        $transactions = $poItems->concat($maintenanceUsages)
            ->sortByDesc('date')
            ->values()
            ->all();

        return $transactions;
    }

    /**
     * Show the form for editing the specified spare part.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(SparePart $sparePart)
    {
        // Check permission
        if (! auth()->user()->can('inventory.edit')) {
            return redirect()->route('inventory.show', $sparePart)->with('error', 'You do not have permission to edit inventory items.');
        }

        $vendors = Vendor::orderBy('name')->get();
        $assetTypes = AssetCategory::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();

        return view('inventory.edit', compact('sparePart', 'vendors', 'assetTypes', 'locations'));
    }

    /**
     * Update the specified spare part in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SparePart $sparePart)
    {
        // Check permission
        if (! auth()->user()->can('inventory.edit')) {
            return redirect()->route('inventory.show', $sparePart)->with('error', 'You do not have permission to edit inventory items.');
        }

        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'part_number' => 'required|string|max:100|unique:spare_parts,part_number,'.$sparePart->id,
            'description' => 'nullable|string',
            'asset_type_id' => 'nullable|exists:asset_categories,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'manufacturer' => 'nullable|string|max:255',
            'quantity_on_hand' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'location_in_store' => 'nullable|string|max:255',
        ]);

        // Update the spare part
        $sparePart->name = $request->name;
        $sparePart->part_number = $request->part_number;
        $sparePart->description = $request->description;
        $sparePart->asset_type_id = $request->asset_type_id;
        $sparePart->vendor_id = $request->vendor_id;
        $sparePart->manufacturer = $request->manufacturer;
        $sparePart->quantity_on_hand = $request->quantity_on_hand;
        $sparePart->reorder_level = $request->reorder_level;
        $sparePart->unit_price = $request->unit_price;
        $sparePart->location_in_store = $request->location_in_store;
        $sparePart->save();

        return redirect()->route('inventory.show', $sparePart)
            ->with('success', 'Spare part updated successfully.');
    }

    /**
     * Remove the specified spare part from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(SparePart $sparePart)
    {
        // Check permission
        if (! auth()->user()->can('inventory.delete')) {
            return redirect()->route('inventory.index')->with('error', 'You do not have permission to delete inventory items.');
        }

        // Check if part is used in any maintenance logs
        $usageCount = DB::table('maintenance_log_spare_parts')
            ->where('spare_part_id', $sparePart->id)
            ->count();

        if ($usageCount > 0) {
            return redirect()->route('inventory.show', $sparePart)
                ->with('error', 'Cannot delete spare part that has been used in maintenance logs.');
        }

        // Check if part is in any purchase order items
        $poItemCount = PurchaseOrderItem::where('spare_part_id', $sparePart->id)->count();

        if ($poItemCount > 0) {
            return redirect()->route('inventory.show', $sparePart)
                ->with('error', 'Cannot delete spare part that is referenced in purchase orders.');
        }

        // Delete the spare part
        $sparePart->delete();

        return redirect()->route('inventory.index')
            ->with('success', 'Spare part deleted successfully.');
    }

    /**
     * Adjust inventory quantity.
     *
     * @return \Illuminate\Http\Response
     */
    public function adjustQuantity(Request $request, SparePart $sparePart)
    {
        // Check permission
        if (! auth()->user()->can('inventory.adjust')) {
            return redirect()->route('inventory.show', $sparePart)->with('error', 'You do not have permission to adjust inventory.');
        }

        // Validate the request
        $validated = $request->validate([
            'adjustment_type' => 'required|string|in:add,subtract,set',
            'quantity' => 'required|integer|min:0',
            'reason' => 'required|string|max:255',
        ]);

        // Calculate new quantity
        $oldQuantity = $sparePart->quantity_on_hand;
        $newQuantity = $oldQuantity;

        switch ($request->adjustment_type) {
            case 'add':
                $newQuantity = $oldQuantity + $request->quantity;
                break;
            case 'subtract':
                $newQuantity = max(0, $oldQuantity - $request->quantity);
                break;
            case 'set':
                $newQuantity = $request->quantity;
                break;
        }

        // Begin transaction
        DB::beginTransaction();

        try {
            // Update the quantity
            $sparePart->quantity_on_hand = $newQuantity;
            $sparePart->save();

            // Log the adjustment in inventory_adjustments table
            // This is a placeholder - you would need to create this table
            DB::table('inventory_adjustments')->insert([
                'spare_part_id' => $sparePart->id,
                'previous_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'adjustment_quantity' => $newQuantity - $oldQuantity,
                'adjustment_type' => $request->adjustment_type,
                'reason' => $request->reason,
                'adjusted_by' => Auth::id(),
                'created_at' => Carbon::now(),
            ]);

            DB::commit();

            return redirect()->route('inventory.show', $sparePart)
                ->with('success', 'Inventory quantity adjusted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error adjusting inventory: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show low stock report.
     *
     * @return \Illuminate\Http\Response
     */
    public function lowStockReport()
    {
        // Check permission
        if (! auth()->user()->can('inventory.report.view')) {
            return redirect()->route('inventory.index')->with('error', 'You do not have permission to view inventory reports.');
        }

        // Get items where quantity is at or below reorder level
        $lowStockItems = SparePart::with(['vendor'])
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
            ->orderBy('quantity_on_hand')
            ->get();

        // Calculate value of items that need to be ordered
        $reorderValue = 0;
        foreach ($lowStockItems as $item) {
            $quantityToOrder = $item->reorder_level - $item->quantity_on_hand;
            if ($quantityToOrder > 0) {
                $reorderValue += $quantityToOrder * $item->unit_price;
            }
        }

        return view('inventory.low-stock-report', compact('lowStockItems', 'reorderValue'));
    }

    /**
     * Generate purchase order for low stock items.
     *
     * @return \Illuminate\Http\Response
     */
    public function generatePurchaseOrders()
    {
        // Check permission
        if (! auth()->user()->can('purchase_order.create')) {
            return redirect()->route('inventory.index')->with('error', 'You do not have permission to create purchase orders.');
        }

        // Get low stock items grouped by vendor
        $lowStockItems = SparePart::with(['vendor'])
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
            ->where('quantity_on_hand', '<', 'reorder_level')
            ->where('vendor_id', '!=', null)
            ->get()
            ->groupBy('vendor_id');

        // For each vendor, create a purchase order
        $purchaseOrders = [];

        foreach ($lowStockItems as $vendorId => $items) {
            $vendor = Vendor::find($vendorId);

            if ($vendor) {
                // Generate PO number
                $poNumber = 'PO-'.date('Ymd').'-'.str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

                // Create purchase order
                $purchaseOrder = new PurchaseOrder;
                $purchaseOrder->po_number = $poNumber;
                $purchaseOrder->vendor_id = $vendorId;
                $purchaseOrder->order_date = Carbon::now();
                $purchaseOrder->expected_delivery_date = Carbon::now()->addDays(14);
                $purchaseOrder->order_status = 'Draft';
                $purchaseOrder->created_by_user_id = Auth::id();
                $purchaseOrder->save();

                // Add items to purchase order
                $totalAmount = 0;
                foreach ($items as $item) {
                    $quantityToOrder = $item->reorder_level - $item->quantity_on_hand;

                    if ($quantityToOrder > 0) {
                        $poItem = new PurchaseOrderItem;
                        $poItem->purchase_order_id = $purchaseOrder->id;
                        $poItem->item_type = 'SparePart';
                        $poItem->spare_part_id = $item->id;
                        $poItem->item_description = $item->name.' ('.$item->part_number.')';
                        $poItem->quantity = $quantityToOrder;
                        $poItem->unit_price = $item->unit_price;
                        $poItem->total_price = $quantityToOrder * $item->unit_price;
                        $poItem->received_quantity = 0;
                        $poItem->save();

                        $totalAmount += $poItem->total_price;
                    }
                }

                // Update purchase order total
                $purchaseOrder->total_amount = $totalAmount;
                $purchaseOrder->save();

                $purchaseOrders[] = $purchaseOrder;
            }
        }

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase orders generated successfully for low stock items.');
    }

    /**
     * Export inventory to CSV/Excel.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        // Check permission
        if (! auth()->user()->can('inventory.export')) {
            return redirect()->route('inventory.index')->with('error', 'You do not have permission to export inventory data.');
        }

        return Excel::download(new SparePartsExport($request), 'inventory.xlsx');
    }
}
