<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    /**
     * Menampilkan form upload file.
     */
    public function create()
    {
        return view('upload');
    }

    /**
     * Memproses file yang di-upload.
     */
    public function upload(Request $request)
    {
        // 1. Validasi File (Sesuai ketentuan poin 7 modul)
        $request->validate([
            'file' => 'required|file|mimes:jpg,png,pdf|max:2048', // Maksimal ukuran 2MB
        ]);

        // 2. Ambil file dari request
        $file = $request->file('file');

        // 3. Mengubah nama file agar unik sebelum disimpan (Sesuai poin 6 modul)
        $filename = time() . '_' . $file->getClientOriginalName();

        // 4. Simpan file ke disk 'public' di dalam folder 'documents'
        $path = $file->storeAs('documents', $filename, 'public');

        return "File berhasil di-upload! Path penyimpanan: " . $path;
    }
}