<?php

namespace Tests\Feature;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_can_be_created_listed_shown_updated_and_deleted(): void
    {
        $createResponse = $this->postJson('/api/books', [
            'title' => 'Laravel Basics',
            'description' => 'An introductory book.',
            'status' => 'available',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('title', 'Laravel Basics')
            ->assertJsonPath('status', 'available');

        $book = Book::firstOrFail();

        $this->getJson('/api/books')
            ->assertOk()
            ->assertJsonFragment(['id' => $book->id, 'title' => 'Laravel Basics']);

        $this->getJson("/api/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('description', 'An introductory book.');

        $this->putJson("/api/books/{$book->id}", [
            'title' => 'Laravel Guide',
            'status' => 'unavailable',
        ])
            ->assertOk()
            ->assertJsonPath('title', 'Laravel Guide')
            ->assertJsonPath('status', 'unavailable');

        $this->deleteJson("/api/books/{$book->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    public function test_book_creation_validates_required_fields_and_unknown_books_return_not_found(): void
    {
        $this->postJson('/api/books', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'status']);

        $this->getJson('/api/books/999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Book not found');
    }
}