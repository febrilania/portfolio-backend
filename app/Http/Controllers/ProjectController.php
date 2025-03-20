<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Cloudinary\Uploader;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Project::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'title' => 'required|string|max:255',
    //         'description' => 'nullable|string',
    //         'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
    //         'tech_stack' => 'required|string',
    //         'link' => 'nullable|url',
    //     ]);

    //     if ($request->hasFile('image')) {
    //         $imagePath = $request->file('image')->store('projects', 'public');
    //     } else {
    //         $imagePath = null;
    //     }

    //     $project = Project::create([
    //         'title' => $request->title,
    //         'description' => $request->description,
    //         'image' => $imagePath ? asset("storage/$imagePath") : null,
    //         'tech_stack' => $request->tech_stack,
    //         'link' => $request->link,
    //     ]);
    //     return response()->json($project, 200);
    // }

    public function store(Request $request)
    {
        // Validasi input dari form
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'tech_stack' => 'required|string',
            'link' => 'nullable|url',
        ]);

        $imageUrl = ''; // Set default value to empty string

        // Jika ada file gambar, upload ke Cloudinary
        if ($request->hasFile('image')) {
            $image = $request->file('image');

            try {
                // Upload gambar ke Cloudinary menggunakan Facade
                $uploadedImage = Cloudinary::upload($image->getRealPath());

                // Ambil URL gambar yang aman dari Cloudinary
                $imageUrl = $uploadedImage->getSecureUrl();  // Menggunakan method getSecureUrl()
            } catch (\Exception $e) {
                // Handle jika terjadi error saat upload
                return response()->json(['error' => 'Image upload failed: ' . $e->getMessage()], 500);
            }
        }

        // Simpan data project ke database
        $project = Project::create([
            'title' => $request->title,
            'description' => $request->description,
            'image' => $imageUrl, // Simpan URL gambar dari Cloudinary (kosong jika tidak ada gambar)
            'tech_stack' => $request->tech_stack,
            'link' => $request->link,
        ]);

        // Return response sukses dengan data project
        return response()->json($project, 201);
    }






    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        return response()->json($project);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'tech_stack' => 'required|string',
            'link' => 'nullable|url',
        ]);

        if ($request->hasFile('image')) {
            // Hapus gambar lama jika ada
            if ($project->image) {
                $oldImagePath = str_replace(asset('storage/'), '', $project->image);
                Storage::disk('public')->delete($oldImagePath);
            }

            $imagePath = $request->file('image')->store('projects', 'public');
            $project->image = asset("storage/$imagePath");
        }

        $project->update($request->except(['image']));

        return response()->json($project);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $project->delete();

        return response()->json(['message' => 'Project deleted'], 204);
    }
}
