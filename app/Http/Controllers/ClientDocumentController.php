<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientDocumentController extends Controller
{
    public function store(Request $request, Client $client)
    {
        abort_unless(auth()->user()->can('documents.create'), 403);

        $allowedCategories = array_keys(Document::CATEGORIES);

        $request->validate([
            'title' => ['nullable', 'array'],
            'title.*' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'array'],
            'type.*' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'array'],
            'category.*' => ['nullable', 'string', 'in:' . implode(',', $allowedCategories)],
            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string'],
            'expiration_date' => ['nullable', 'array'],
            'expiration_date.*' => ['nullable', 'date'],
            'files' => ['required', 'array', 'max:5'],
            'files.*' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
        ]);

        $rawFiles = $request->file('files', []);
        $hasAnyFile = collect($rawFiles)->filter()->isNotEmpty();

        if (! $hasAnyFile) {
            return redirect()
                ->route('clients.show', ['client' => $client, 'tab' => 'ged'])
                ->withErrors(['files' => 'Selecione pelo menos um arquivo.'])
                ->withInput();
        }

        $titles = $request->input('title', []);
        $types = $request->input('type', []);
        $categories = $request->input('category', []);
        $descriptions = $request->input('description', []);
        $expirationDates = $request->input('expiration_date', []);

        foreach ($rawFiles as $index => $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store('documents', 'public');
            $originalName = $file->getClientOriginalName();

            Document::create([
                'client_id' => $client->id,
                'title' => $titles[$index] ?? pathinfo($originalName, PATHINFO_FILENAME),
                'type' => $types[$index] ?? null,
                'category' => in_array($categories[$index] ?? null, $allowedCategories, true)
                    ? $categories[$index]
                    : 'demais',
                'description' => $descriptions[$index] ?? null,
                'expiration_date' => $expirationDates[$index] ?? null,
                'status' => true,
                'file_path' => $path,
                'original_name' => $originalName,
                'uploaded_by' => auth()->id(),
            ]);
        }

        return redirect()
            ->route('clients.show', ['client' => $client, 'tab' => 'ged'])
            ->with('success', 'Documento(s) enviado(s) com sucesso.');
    }

    public function download(Client $client, Document $document)
    {
        abort_unless(auth()->user()->can('documents.view'), 403);
        abort_if($document->client_id !== $client->id, 404);

        return Storage::disk('public')->download($document->file_path, $document->original_name);
    }

    public function destroy(Client $client, Document $document)
    {
        abort_unless(auth()->user()->can('documents.delete'), 403);
        abort_if($document->client_id !== $client->id, 404);

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()
            ->route('clients.show', ['client' => $client, 'tab' => 'ged'])
            ->with('success', 'Documento removido com sucesso.');
    }

    public function preview(Client $client, Document $document)
    {
        abort_unless(auth()->user()->can('documents.view'), 403);
        abort_if($document->client_id !== $client->id, 404);

        return response()->file(storage_path('app/public/' . $document->file_path));
    }
}
