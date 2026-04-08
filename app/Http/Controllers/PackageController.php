<?php

namespace App\Http\Controllers;

use App\Http\Requests\PackageRequest;
use App\Models\Package;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::latest()->paginate(10);

        return view('packages.index', compact('packages'));
    }

    public function create()
    {
        return view('packages.create');
    }

    public function store(PackageRequest $request)
    {
        Package::create($request->validated());

        return redirect()->route('packages.index')->with('status', 'Package created successfully.');
    }

    public function edit(Package $package)
    {
        return view('packages.edit', compact('package'));
    }

    public function update(PackageRequest $request, Package $package)
    {
        $package->update($request->validated());

        return redirect()->route('packages.index')->with('status', 'Package updated successfully.');
    }

    public function destroy(Package $package)
    {
        $package->delete();

        return redirect()->route('packages.index')->with('status', 'Package deleted successfully.');
    }
}
