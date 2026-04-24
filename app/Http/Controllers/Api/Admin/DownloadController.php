<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Download::with(['category']);

        if ($request->has('status')) {
            if ($request->status === 'published') {
                $query->where('is_published', true);
            } elseif ($request->status === 'draft') {
                $query->where('is_published', false);
            }
        }

        if ($request->has('file_type')) {
            $query->where('file_type', $request->file_type);
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'LIKE', "%{$request->search}%")
                  ->orWhere('description', 'LIKE', "%{$request->search}%");
            });
        }

        $downloads = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $downloads
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|mimes:pdf,pptx,xlsx,docx,zip|max:10240', // 10MB max
            'file_size' => 'nullable|string|max:50',
            'file_type' => 'nullable|string|max:50',
            'icon_class' => 'nullable|string|max:50',
            'category_id' => 'nullable|exists:categories,id',
            'is_published' => 'nullable|boolean',
        ]);

        // Subir el archivo
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            
            // Crear nombre único
            $fileName = Str::slug($request->title) . '_' . time() . '.' . $extension;
            
            // Guardar en storage/app/public/downloads
            $path = $file->storeAs('downloads', $fileName, 'public');
            
            // Generar URL pública
            $validated['file_url'] = Storage::url($path);
            
            // Calcular tamaño si no viene
            if (empty($request->file_size)) {
                $sizeInMB = round($file->getSize() / 1048576, 2);
                $validated['file_size'] = $sizeInMB . ' MB';
            }
            
            // Detectar tipo de archivo si no viene
            if (empty($request->file_type)) {
                $validated['file_type'] = strtoupper($extension);
            }
            
            // Asignar icono si no viene
            if (empty($request->icon_class)) {
                $validated['icon_class'] = $this->getIconClass($extension);
            }
        }

        $validated['is_published'] = filter_var($request->is_published ?? false, FILTER_VALIDATE_BOOLEAN);

        $download = Download::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Descarga creada exitosamente',
            'data' => $download
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $download = Download::with(['category'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $download
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $download = Download::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,pptx,xlsx,docx,zip|max:10240',
            'file_size' => 'nullable|string|max:50',
            'file_type' => 'nullable|string|max:50',
            'icon_class' => 'nullable|string|max:50',
            'category_id' => 'nullable|exists:categories,id',
            'is_published' => 'nullable|boolean',
        ]);

        // Si suben nuevo archivo
        if ($request->hasFile('file')) {
            // Eliminar archivo anterior
            if ($download->file_url) {
                $oldPath = str_replace('/storage/', '', $download->file_url);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::slug($request->title) . '_' . time() . '.' . $extension;
            $path = $file->storeAs('downloads', $fileName, 'public');
            
            $validated['file_url'] = Storage::url($path);
            
            // Actualizar tamaño
            $sizeInMB = round($file->getSize() / 1048576, 2);
            $validated['file_size'] = $sizeInMB . ' MB';
            
            // Actualizar tipo
            $validated['file_type'] = strtoupper($extension);
            
            // Actualizar icono
            $validated['icon_class'] = $this->getIconClass($extension);
        }

        $validated['is_published'] = filter_var($request->is_published ?? $download->is_published, FILTER_VALIDATE_BOOLEAN);

        $download->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Descarga actualizada exitosamente',
            'data' => $download
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $download = Download::findOrFail($id);
        
        // Eliminar archivo físico
        if ($download->file_url) {
            $path = str_replace('/storage/', '', $download->file_url);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
        
        $download->delete();

        return response()->json([
            'success' => true,
            'message' => 'Descarga eliminada exitosamente'
        ]);
    }

    public function togglePublish($id)
    {
        $download = Download::findOrFail($id);
        $download->is_published = !$download->is_published;
        $download->save();

        return response()->json([
            'success' => true,
            'message' => $download->is_published ? 'Descarga publicada' : 'Descarga despublicada',
            'data' => $download
        ]);
    }

    public function getCategories()
    {
        $categories = Category::where('type', 'downloadable')
            ->orWhereNull('type')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function incrementDownloads($id)
    {
        $download = Download::findOrFail($id);
        $download->increment('download_count');

        return response()->json([
            'success' => true,
            'message' => 'Contador actualizado',
            'download_count' => $download->download_count
        ]);
    }

    private function getIconClass($extension)
    {
        return match(strtolower($extension)) {
            'pdf' => 'fas fa-file-pdf',
            'pptx', 'ppt' => 'fas fa-file-powerpoint',
            'xlsx', 'xls' => 'fas fa-file-excel',
            'docx', 'doc' => 'fas fa-file-word',
            'zip', 'rar' => 'fas fa-file-archive',
            default => 'fas fa-file',
        };
    }
}
