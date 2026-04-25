<?php

namespace App\Http\Controllers;

use App\Models\CalonSantri;

class AdminController extends Controller
{
    public function index() {
        $pendaftar = CalonSantri::latest()->paginate(20);
        return view('admin.dashboard', compact('pendaftar'));
    }

    public function show($id) {
        $santri = CalonSantri::findOrFail($id);
        return view('admin.show', compact('santri'));
    }
}
