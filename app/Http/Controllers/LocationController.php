<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LocationController extends Controller
{
    /**
     * Display a listing of the locations.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Check permission using Gate facade
        if (Gate::denies('location.view')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view locations.');
        }

        // Get root locations (those with no parent) with their nested children
        $locations = Location::with('children')
            ->whereNull('parent_id')
            ->withCount('assets')
            ->orderBy('name')
            ->get();
            
        // Get all locations for the flat list view (optional)
        $allLocations = Location::orderBy('name')->get();
        
        return view('locations.index', compact('locations', 'allLocations'));
    }

    /**
     * Show the form for creating a new location.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Check permission using Gate facade
        if (Gate::denies('location.create')) {
            return redirect()->route('locations.index')->with('error', 'You do not have permission to create locations.');
        }

        // Get locations for parent selection
        $locations = Location::getNestedList();
        
        return view('locations.create', compact('locations'));
    }

    /**
     * Store a newly created location in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Check permission using Gate facade
        if (Gate::denies('location.create')) {
            return redirect()->route('locations.index')->with('error', 'You do not have permission to create locations.');
        }

        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:locations,code',
            'parent_id' => 'nullable|exists:locations,id',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'type' => 'required|string|in:facility,plant,office,warehouse',
            'is_active' => 'boolean',
        ]);

        // Create the location
        $location = new Location();
        $location->name = $request->name;
        $location->code = $request->code;
        $location->parent_id = $request->parent_id ?: null;
        $location->address = $request->address;
        $location->city = $request->city;
        $location->state = $request->state;
        $location->postal_code = $request->postal_code;
        $location->country = $request->country;
        $location->latitude = $request->latitude;
        $location->longitude = $request->longitude;
        $location->contact_person = $request->contact_person;
        $location->contact_email = $request->contact_email;
        $location->contact_phone = $request->contact_phone;
        $location->type = $request->type;
        $location->is_active = $request->has('is_active');
        $location->save();
        
        return redirect()->route('locations.index')
            ->with('success', 'Location created successfully.');
    }

    /**
     * Display the specified location.
     *
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Http\Response
     */
    public function show(Location $location)
    {
        // Check permission using Gate facade
        if (Gate::denies('location.view')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view locations.');
        }

        // Get paginated assets for this location
        $assets = $location->assets()->paginate(10);
        
        // Get departments for this location
        $departments = $location->departments;
        
        return view('locations.show', compact('location', 'assets', 'departments'));
    }

    /**
     * Show the form for editing the specified location.
     *
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Http\Response
     */
    public function edit(Location $location)
    {
        // Check permission using Gate facade
        if (Gate::denies('location.edit')) {
            return redirect()->route('locations.show', $location)->with('error', 'You do not have permission to edit locations.');
        }

        // Get locations for parent selection (exclude current location and its descendants)
        $locations = Location::getNestedList($location->id);
        
        return view('locations.edit', compact('location', 'locations'));
    }

    /**
     * Update the specified location in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Location $location)
    {
        // Check permission using Gate facade
        if (Gate::denies('location.edit')) {
            return redirect()->route('locations.show', $location)->with('error', 'You do not have permission to edit locations.');
        }

        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:locations,code,' . $location->id,
            'parent_id' => [
                'nullable',
                'exists:locations,id',
                function ($attribute, $value, $fail) use ($location) {
                    if ($value == $location->id) {
                        $fail('A location cannot be its own parent.');
                    }
                    // Check if the selected parent is a descendant of the current location
                    if ($value && $location->isDescendantOf($value)) {
                        $fail('A location cannot be a descendant of itself.');
                    }
                },
            ],
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'type' => 'required|string|in:facility,plant,office,warehouse',
            'is_active' => 'boolean',
        ]);

        // Update the location
        $location->name = $request->name;
        $location->code = $request->code;
        $location->parent_id = $request->parent_id ?: null;
        $location->address = $request->address;
        $location->city = $request->city;
        $location->state = $request->state;
        $location->postal_code = $request->postal_code;
        $location->country = $request->country;
        $location->latitude = $request->latitude;
        $location->longitude = $request->longitude;
        $location->contact_person = $request->contact_person;
        $location->contact_email = $request->contact_email;
        $location->contact_phone = $request->contact_phone;
        $location->type = $request->type;
        $location->is_active = $request->has('is_active');
        $location->save();
        
        return redirect()->route('locations.show', $location)
            ->with('success', 'Location updated successfully.');
    }

    /**
     * Remove the specified location from storage.
     *
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Http\Response
     */
    public function destroy(Location $location)
    {
        // Check permission using Gate facade
        if (Gate::denies('location.delete')) {
            return redirect()->route('locations.show', $location)->with('error', 'You do not have permission to delete locations.');
        }

        // Check if location has any assets
        if ($location->assets()->exists()) {
            return redirect()->route('locations.show', $location)
                ->with('error', 'Cannot delete location that has assets assigned to it.');
        }

        // Check if location has any child locations
        if ($location->children()->exists()) {
            return redirect()->route('locations.show', $location)
                ->with('error', 'Cannot delete location that has child locations.');
        }

        // Delete the location
        $location->delete();
        
        return redirect()->route('locations.index')
            ->with('success', 'Location deleted successfully.');
    }
}
