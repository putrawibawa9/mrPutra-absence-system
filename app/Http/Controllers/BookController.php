<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookRequest;
use App\Models\Book;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::query()->withCount('classrooms')->orderBy('title')->paginate(15);

        return view('books.index', compact('books'));
    }

    public function create()
    {
        return view('books.create');
    }

    public function store(BookRequest $request)
    {
        Book::create([
            'title' => $request->string('title')->toString(),
            'notes' => $request->string('notes')->toString() ?: null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('books.index')->with('status', 'Buku berhasil ditambahkan.');
    }

    public function edit(Book $book)
    {
        return view('books.edit', compact('book'));
    }

    public function update(BookRequest $request, Book $book)
    {
        $book->update([
            'title' => $request->string('title')->toString(),
            'notes' => $request->string('notes')->toString() ?: null,
            'is_active' => $request->boolean('is_active', false),
        ]);

        return redirect()->route('books.index')->with('status', 'Buku berhasil diperbarui.');
    }

    public function destroy(Book $book)
    {
        // Buku yang masih dipakai kelas tidak dihapus — cukup nonaktifkan agar
        // tidak muncul di dropdown tapi kelas lama tetap menampilkan judulnya.
        if ($book->classrooms()->exists()) {
            $book->update(['is_active' => false]);

            return redirect()->route('books.index')->with('status', 'Buku masih dipakai kelas, jadi dinonaktifkan (bukan dihapus).');
        }

        $book->delete();

        return redirect()->route('books.index')->with('status', 'Buku berhasil dihapus.');
    }
}
