<?php

namespace App\Http\Controllers;

use App\Models\SavedMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdminController extends Controller
{
    private string $adminEmail = 'alex.g.alcivar49@gmail.com';

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::check() || Auth::user()->email !== $this->adminEmail) {
                abort(403, 'Unauthorized action.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        // Fetch all maps marked as public by users, regardless of current approval status
        $publicMapsForApproval = SavedMap::where('is_public', true)
            ->with('user:id,name') // Eager load user for display
            ->orderBy('is_featured', 'desc') // Show featured ones first within their approval status
            ->orderBy('is_approved', 'asc') // Then show unapproved ones
            ->orderBy('updated_at', 'desc')
            ->get();

        return Inertia::render('Admin/Index', [
            'mapsForApproval' => $publicMapsForApproval,
        ]);
    }

    public function approve(SavedMap $savedMap)
    {
        if (!$savedMap->is_public) {
            return redirect()->back()->with('error', 'This map is not marked as public by the user.');
        }
        $savedMap->update(['is_approved' => true]);
        return redirect()->route('admin.index')->with('success', 'Map approved successfully.');
    }

    public function unapprove(SavedMap $savedMap)
    {
        // When unapproving, also unfeature it.
        $savedMap->update(['is_approved' => false, 'is_featured' => false]);
        return redirect()->route('admin.index')->with('success', 'Map unapproved and unfeatured successfully.');
    }

    public function feature(SavedMap $savedMap)
    {
        if (!$savedMap->is_public || !$savedMap->is_approved) {
            return redirect()->back()->with('error', 'Only public and approved maps can be featured.');
        }
        $savedMap->update(['is_featured' => true]);
        return redirect()->route('admin.index')->with('success', 'Map featured successfully.');
    }

    public function unfeature(SavedMap $savedMap)
    {
        $savedMap->update(['is_featured' => false]);
        return redirect()->route('admin.index')->with('success', 'Map unfeatured successfully.');
    }
}
