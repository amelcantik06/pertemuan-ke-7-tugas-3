<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    // Menampilkan semua book[cite: 1]
    public function index()
    {
        return response()->json(Book::all(), 200);
    }

    // Menyimpan book baru[cite: 2]
   public function store(Request $request)
{
    $validated = $request->validate([
        'title' => 'required|string',
        'description' => 'required|string',
        'status' => 'required',
    ]);

    $book = Book::create($validated);
    return response()->json($book, 201);
}

    // Menampilkan satu book berdasarkan ID[cite: 2]
    public function show($id)
    {
        $book = Book::find($id);
        if (!$book) {
            return response()->json(['message' => 'Book not found'], 404);
        }
        return response()->json($book, 200);
    }

    // Memperbarui book[cite: 2]
    public function update(Request $request, $id)
    {
        $book = Book::find($id);
        if (!$book) {
            return response()->json(['message' => 'Book not found'], 404);
        }

        $book->update($request->all());
        return response()->json($book, 200);
    }

    // Menghapus book[cite: 2]
    public function destroy($id)
    {
        $book = Book::find($id);
        if (!$book) {
            return response()->json(['message' => 'Book not found'], 404);
        }

        $book->delete();
        return response()->noContent();
    }
}