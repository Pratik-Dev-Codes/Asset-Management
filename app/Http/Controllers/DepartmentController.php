<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Location;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    /**
     * Display the departments landing page.
     *
     * @return \Illuminate\Http\Response
     */
    public function landing()
    {
        return view('departments.landing');
    }
    /**
     * Display a listing of the departments.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Check permission
        if (!auth()->user()->can('department.view')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view departments.');
        }

        $departments = Department::with('location')
            ->withCount('assets')
            ->orderBy('name')
            ->get();
        
        return view('departments.index', compact('departments'));
    }

    /**
     * Show the form for creating a new department.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Check permission
        if (!auth()->user()->can('department.create')) {
            return redirect()->route('departments.index')->with('error', 'You do not have permission to create departments.');
        }

        $locations = Location::orderBy('name')->get();
        
        return view('departments.create', compact('locations'));
    }

    /**
     * Store a newly created department in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('department.create')) {
            return redirect()->route('departments.index')->with('error', 'You do not have permission to create departments.');
        }

        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:departments',
            'description' => 'nullable|string',
            'location_id' => 'nullable|exists:locations,id',
        ]);

        // Create the department
        $department = new Department();
        $department->name = $request->name;
        $department->code = $request->code;
        $department->description = $request->description;
        $department->location_id = $request->location_id;
        $department->save();
        
        return redirect()->route('departments.index')
            ->with('success', 'Department created successfully.');
    }

    /**
     * Display the specified department.
     *
     * @param  \App\Models\Department  $department
     * @return \Illuminate\Http\Response
     */
    public function show(Department $department)
    {
        // Check permission
        if (!auth()->user()->can('department.view')) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view departments.');
        }

        // Load the department details
        $department->load('location');
        
        // Get users belonging to the department
        $users = $department->users()->paginate(10);
        
        // Get assets assigned to the department
        $assets = $department->assets()->paginate(10);
        
        return view('departments.show', compact('department', 'users', 'assets'));
    }

    /**
     * Show the form for editing the specified department.
     *
     * @param  \App\Models\Department  $department
     * @return \Illuminate\Http\Response
     */
    public function edit(Department $department)
    {
        // Check permission
        if (!auth()->user()->can('department.edit')) {
            return redirect()->route('departments.show', $department)->with('error', 'You do not have permission to edit departments.');
        }

        $locations = Location::orderBy('name')->get();
        
        return view('departments.edit', compact('department', 'locations'));
    }

    /**
     * Update the specified department in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Department  $department
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Department $department)
    {
        // Check permission
        if (!auth()->user()->can('department.edit')) {
            return redirect()->route('departments.show', $department)->with('error', 'You do not have permission to edit departments.');
        }

        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:departments,code,' . $department->id,
            'description' => 'nullable|string',
            'location_id' => 'nullable|exists:locations,id',
        ]);

        // Update the department
        $department->name = $request->name;
        $department->code = $request->code;
        $department->description = $request->description;
        $department->location_id = $request->location_id;
        $department->save();
        
        return redirect()->route('departments.show', $department)
            ->with('success', 'Department updated successfully.');
    }

    /**
     * Remove the specified department from storage.
     *
     * @param  \App\Models\Department  $department
     * @return \Illuminate\Http\Response
     */
    public function destroy(Department $department)
    {
        // Check permission
        if (!auth()->user()->can('department.delete')) {
            return redirect()->route('departments.index')->with('error', 'You do not have permission to delete departments.');
        }

        // Check if department has assets
        if ($department->assets()->count() > 0) {
            return redirect()->route('departments.index')
                ->with('error', 'Cannot delete department that has assets assigned to it.');
        }

        // Check if department has users
        if ($department->users()->count() > 0) {
            return redirect()->route('departments.index')
                ->with('error', 'Cannot delete department that has users assigned to it.');
        }
        
        // Delete the department
        $department->delete();
        
        return redirect()->route('departments.index')
            ->with('success', 'Department deleted successfully.');
    }

    /**
     * Get all departments as JSON for API or AJAX requests.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDepartments()
    {
        $departments = Department::select('id', 'name', 'code')->orderBy('name')->get();
        
        return response()->json($departments);
    }

    /**
     * Get departments for a specific location.
     *
     * @param  int  $locationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDepartmentsByLocation($locationId)
    {
        $departments = Department::where('location_id', $locationId)
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();
        
        return response()->json($departments);
    }
}