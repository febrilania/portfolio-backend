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
        // Validasi input
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'tech_stack' => 'required|string',
            'link' => 'nullable|url',
        ]);

        // Periksa file gambar
        if (!$request->hasFile('image')) {
            return response()->json([
                'success' => false,
                'message' => 'File gambar tidak ditemukan',
            ], 400);
        }

        $image = $request->file('image');
        if (!$image->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'File gambar tidak valid',
            ], 400);
        }

        // Upload gambar ke Cloudinary
        try {
            $uploadedImage = Cloudinary::upload($image->getRealPath());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupload gambar ke Cloudinary: ' . $e->getMessage(),
            ], 500);
        }

        if (!$uploadedImage) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupload gambar ke Cloudinary',
            ], 500);
        }

        // Ambil URL gambar setelah upload
        $imageUrl = $uploadedImage->getSecurePath();
        if (!$imageUrl) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendapatkan URL gambar dari Cloudinary',
            ], 500);
        }

        // Simpan data project ke database
        $project = Project::create([
            'title' => $request->title,
            'description' => $request->description,
            'image' => $imageUrl,
            'tech_stack' => $request->tech_stack,
            'link' => $request->link,
        ]);

        // Kembalikan respons JSON
        return response()->json([
            'success' => true,
            'message' => 'Project berhasil ditambahkan',
            'data' => $project,
        ], 201);  // HTTP status 201 (Created)
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
