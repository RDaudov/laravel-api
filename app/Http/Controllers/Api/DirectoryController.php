<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Directory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class DirectoryController extends Controller
{

    public function index()
    {
        $directories = Auth::user()->directories()->select('id', 'name')->get();
        
        if ($directories->isEmpty()) {
            return response()->json(['message' => 'У вас пока нет директорий'], 200);
        }
        
        return response()->json($directories);
    }
    
    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $userId = auth()->id();
        $directoryName = $validatedData['name'];
        $path = $userId . '/' . $directoryName;

        $existingDirectory = Directory::where('user_id', $userId)
            ->where('name', $directoryName)
            ->first();

        if ($existingDirectory) {
            throw ValidationException::withMessages([
                'name' => ['Директория с таким именем уже существует.'],
            ]);
        }

        if (Storage::exists($path)) {
            throw ValidationException::withMessages([
                'name' => ['Физическая директория с таким именем уже существует.'],
            ]);
        }

        $directory = Directory::create([
            'user_id' => $userId,
            'name' => $directoryName,
            'path' => $path,
        ]);

        Storage::makeDirectory($path);

        return response()->json($directory, 201);
    }

    public function delete(Directory $directory)
    {
        $this->authorize('delete', $directory);

        DB::beginTransaction();

        try {
            $directory->files()->delete();

            if (!Storage::deleteDirectory($directory->path)) {
                throw new \Exception('Ошибка при удалении директории');
            }

            $directory->delete();

            DB::commit();

            return response()->json(['message' => 'Директория и все её содержимое удалены успешно'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Не удалось удалить директорию: ' . $e->getMessage()], 500);
        }
    }


    public function rename(Request $request, Directory $directory)
    {
        $this->authorize('update', $directory);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $oldPath = $directory->path;
        $newPath = auth()->user()->id . '/' . $validatedData['name'];

        $directory->update([
            'name' => $validatedData['name'],
            'path' => $newPath,
        ]);

        Storage::move($oldPath, $newPath);

        return response()->json($directory);
    }
}
