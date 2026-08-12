<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SchoolSettingController extends Controller
{
    public function index()
    {

        return Inertia::render('Admin/Settings/Index', [
            'settings' => SchoolSetting::allAsArray(),
        ]);
    }

    public function update(Request $request)
    {

        $validated = $request->validate([
            'school_name'        => 'nullable|string|max:200',
            'school_code'        => 'nullable|string|max:60',
            'municipality'       => 'nullable|string|max:100',
            'state'              => 'nullable|string|max:100',
            'director_name'      => 'nullable|string|max:200',
            'control_study_name' => 'nullable|string|max:200',
        ]);

        foreach ($validated as $key => $value) {
            SchoolSetting::set($key, $value);
        }

        return redirect()->back()->with('success', 'Configuración guardada correctamente.');
    }

    public function uploadLogo(Request $request)
    {

        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        // Eliminar logo anterior si existe
        $oldPath = SchoolSetting::get('logo_path');
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $path = $request->file('logo')->store('logo', 'public');
        SchoolSetting::set('logo_path', $path);

        return redirect()->back()->with('success', 'Logo actualizado correctamente.');
    }
}
