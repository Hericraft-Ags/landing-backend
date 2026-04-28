<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AuthorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Author::query();

        // Búsqueda
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%")
                ->orWhere('role', 'LIKE', "%{$search}%");
            });
        }

        // Paginación
        $authors = $query->orderBy('name')
            ->paginate(10);

        // Agregar la URL completa de la imagen y conteo de artículos
        $authors->getCollection()->transform(function ($author) {
            if ($author->avatar_url) {
                $author->avatar_url = Storage::url($author->avatar_url);
            }
            $author->articles_count = $author->articles()->count();
            return $author;
        });

        return response()->json([
            'success' => true,
            'data' => $authors
        ]);
    }

     /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'nullable|email|unique:authors',
            'bio' => 'nullable|string',
            'avatar_url' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'role' => 'nullable|string|max:100',
            'social_links' => 'nullable|string'
        ]);

        // Manejar avatar
        if ($request->hasFile('avatar_url')) {
            $path = $request->file('avatar_url')->store('authors', 'public');
            $validated['avatar_url'] = Storage::url($path);
        }

        // Procesar social_links
        if ($request->has('social_links')) {
            $socialLinks = json_decode($request->social_links, true);
            if (is_array($socialLinks)) {
                // Limpiar valores vacíos
                $socialLinks = array_filter($socialLinks, function($value) {
                    return !empty($value);
                });
                $validated['social_links'] = $socialLinks;
            }
        }

        $author = Author::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Autor creado exitosamente',
            'data' => $author
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $author = Author::findOrFail($id);
    
        // Asegurar que social_links sea un array
        if ($author->social_links && is_string($author->social_links)) {
            $author->social_links = json_decode($author->social_links, true);
        }
    
        if ($author->avatar_url) {
            $author->avatar_url = Storage::url($author->avatar_url);
        }

        return response()->json([
            'success' => true,
            'data' => $author
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $author = Author::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'nullable|email|unique:authors,email,' . $id,
            'bio' => 'nullable|string',
            'avatar_url' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'role' => 'nullable|string|max:100',
            'social_links' => 'nullable|string'
        ]);

        // Manejar avatar
        if ($request->hasFile('avatar_url')) {
            // Eliminar avatar anterior
            if ($author->avatar_url) {
                $oldPath = str_replace('/storage/', '', $author->avatar_url);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('avatar_url')->store('authors', 'public');
            $validated['avatar_url'] = Storage::url($path);
        }

        // Procesar social_links
        if ($request->has('social_links')) {
            $socialLinks = json_decode($request->social_links, true);
            if (is_array($socialLinks)) {
                // Limpiar valores vacíos
                $socialLinks = array_filter($socialLinks, function($value) {
                    return !empty($value);
                });
                $validated['social_links'] = $socialLinks;
            }
        }

        $author->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Autor actualizado exitosamente',
            'data' => $author
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    // app/Http/Controllers/Api/Admin/AuthorController.php

    public function destroy($id)
    {
        $author = Author::findOrFail($id);
        
        // Verificar si tiene artículos asociados
        if ($author->articles()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el autor porque tiene artículos asociados'
            ], 422);
        }

        // Eliminar avatar si existe
        if ($author->avatar_url) {
            $path = str_replace('/storage/', '', $author->avatar_url);
            Storage::disk('public')->delete($path);
        }

        $author->delete();

        return response()->json([
            'success' => true,
            'message' => 'Autor eliminado exitosamente'
        ]);
    }
}
