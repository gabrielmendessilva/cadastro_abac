<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Models\Client;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{

    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('documents.view'), 403);

        $documents = Document::with(['client', 'uploader'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhereHas('client', fn($clientQuery) => $clientQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('documents.index', compact('documents'));
    }

    public function create()
    {
        abort_unless(auth()->user()->can('documents.create'), 403);

        $clients = Client::orderBy('name')->get();
        return view('documents.create', compact('clients'));
    }

    public function store(StoreDocumentRequest $request)
    {
        abort_unless(auth()->user()->can('documents.create'), 403);

        $file = $request->file('file');
        $path = $file->store('documents', 'public');

        Document::create([
            'client_id' => $request->client_id,
            'title' => $request->title,
            'type' => $request->type,
            'description' => $request->description,
            'expiration_date' => $request->expiration_date,
            'status' => $request->boolean('status', true),
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()->route('documents.index')->with('success', 'Documento enviado com sucesso.');
    }

    public function show(Document $document)
    {
        abort_unless(auth()->user()->can('documents.view'), 403);

        $document->load(['client', 'uploader']);

        return view('documents.show', compact('document'));
    }

    public function edit(Document $document)
    {
        abort_unless(auth()->user()->can('documents.edit'), 403);

        $clients = Client::orderBy('name')->get();
        return view('documents.edit', compact('document', 'clients'));
    }

    public function update(UpdateDocumentRequest $request, Document $document)
    {
        abort_unless(auth()->user()->can('documents.edit'), 403);

        if ($request->hasFile('file')) {
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $file = $request->file('file');
            $document->file_path = $file->store('documents', 'public');
            $document->original_name = $file->getClientOriginalName();
        }

        $document->update([
            'client_id' => $request->client_id,
            'title' => $request->title,
            'type' => $request->type,
            'description' => $request->description,
            'expiration_date' => $request->expiration_date,
            'status' => $request->boolean('status', true),
            'file_path' => $document->file_path,
            'original_name' => $document->original_name,
        ]);

        return redirect()->route('documents.index')->with('success', 'Documento atualizado com sucesso.');
    }

    public function destroy(Document $document)
    {
        abort_unless(auth()->user()->can('documents.delete'), 403);

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Documento removido com sucesso.');
    }

    public function download(Document $document)
    {
        abort_unless(auth()->user()->can('documents.view'), 403);

        return Storage::disk('public')->download($document->file_path, $document->original_name);
    }

    public function preview(Document $document)
    {
        abort_unless(auth()->user()->can('documents.view'), 403);

        return response()->file(storage_path('app/public/' . $document->file_path));
    }
}
