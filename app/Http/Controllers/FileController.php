<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file',
        ]);

        $data = [];

        foreach ($request->file('files') as $file) {
            $filename = $file->getClientOriginalName();
            $orgPath = $file->storeAs('files', $filename);
            $path = storage_path('app/private/' . $orgPath);

            $fileData = \App\OpenAI\File::create($filename, $path);
            $data[] = $fileData;

            Storage::delete($orgPath);
        }

        return response()->json($data, 201);
    }
}
