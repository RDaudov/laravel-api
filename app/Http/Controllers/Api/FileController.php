<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Directory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    public function index(Request $request)
    {
        $query = File::query();

        if ($request->filled('directory_id')) {
            $directoryId = (int) $request->directory_id;
            $query->where('directory_id', $directoryId);
        } else {
            $query->whereNull('directory_id');
        }

        $query->where('user_id', auth()->id());

        $perPage = $request->input('per_page', 15); 
        $files = $query->paginate($perPage);

        return response()->json($files);
    }
    
    public function upload(Request $request)
    {
        $request->validate([
            'files' => 'required',
            'files.*' => 'file|max:10240', 
            'directory_id' => 'nullable|exists:directories,id'
        ]);

        $uploadedFiles = [];

        foreach ($request->file('files') as $file) {
            $directory = Directory::find($request->directory_id);
            $path = $directory ? $directory->path : auth()->user()->id;
            
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $baseFileName = pathinfo($originalName, PATHINFO_FILENAME);
            
            $uniqueFileName = $baseFileName . '_' . Str::uuid() . '.' . $extension;
            
            $filePath = $file->storeAs($path, $uniqueFileName);

            $uploadedFile = File::create([
                'user_id' => auth()->id(),
                'directory_id' => $request->directory_id,
                'name' => $originalName, 
                'path' => $filePath,
                'size' => $file->getSize(),
                'unique_link' => Str::random(40),
            ]);

            $uploadedFiles[] = $uploadedFile;
        }

        return response()->json($uploadedFiles, 201);
    }

    public function delete(File $file)
    {
        $this->authorize('delete', $file);

        try {
            $filePath = $file->path;
            $fileName = $file->name;
            $fileSize = $file->size;

            if (!Storage::delete($filePath)) {
                throw new \Exception("Не у��алось удалить физический файл: {$filePath}");
            }

            $file->delete();

            $response = [
                'message' => 'Файл успешно удален',
                'details' => [
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'deleted_at' => now()->toDateTimeString(),
                ]
            ];

            Log::info('Файл успешно удален', $response['details']);

            return response()->json($response, 200);
        } catch (\Exception $e) {
            Log::error('Ошибка при удалении файла', [
                'file' => $file->toArray(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Не удалось удалить файл',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function rename(Request $request, File $file)
    {
        $this->authorize('update', $file);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $oldPath = $file->path;
        $oldName = $file->name;

        $extension = pathinfo($oldPath, PATHINFO_EXTENSION);
        $uuid = Str::afterLast(pathinfo($oldPath, PATHINFO_FILENAME), '_');

        $newFileName = $validatedData['name'] . '_' . $uuid . '.' . $extension;
        $newPath = dirname($oldPath) . '/' . $newFileName;

        $file->update([
            'name' => $validatedData['name'], 
            'path' => $newPath,
        ]);


        if (Storage::move($oldPath, $newPath)) {
            return response()->json($file);
        } else {
            return response()->json(['error' => 'Не удалось переименовать файл'], 500);
        }
    }

    public function info(File $file)
    {
        $this->authorize('view', $file);

        return response()->json([
            'name' => $file->name,
            'size' => $file->size,
            'created_at' => $file->created_at,
        ]);
    }

    public function togglePublic(File $file)
    {
        $this->authorize('update', $file);

        $file->update(['is_public' => !$file->is_public]);

        return response()->json($file);
    }

    public function download($uniqueLink)
    {
        $file = File::where('unique_link', $uniqueLink)->firstOrFail();

        if (!$file->is_public && !auth()->check()) {
            return response()->json(['message' => 'Нет доступа'], 403);
        }
        if (!Storage::exists($file->path)) {
            return response()->json(['message' => 'Файл не найден'], 404);
        }

        return Storage::download($file->path, $file->name);
    }

    public function diskUsage()
    {
        $totalSize = File::where('user_id', auth()->id())->sum('size');

        return response()->json([
            'used_space' => $totalSize/ 1000 . ' KB',
        ]);
    }

}