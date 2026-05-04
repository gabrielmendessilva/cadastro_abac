<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Tag;
use Illuminate\Http\Request;

class ClientTagController extends Controller
{
    public function sync(Request $request, Client $client)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);

        $data = $request->validate([
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'new_tag_nome' => ['nullable', 'string', 'max:100'],
            'new_tag_cor' => ['nullable', 'string', 'max:20'],
        ]);

        $tagIds = $data['tag_ids'] ?? [];

        if (!empty($data['new_tag_nome'])) {
            $newTag = Tag::firstOrCreate(
                ['nome' => trim($data['new_tag_nome'])],
                ['cor' => $data['new_tag_cor'] ?? 'slate'],
            );
            $tagIds[] = $newTag->id;
        }

        $client->tags()->sync(array_unique($tagIds));

        return back()->with('success', 'Tags atualizadas.');
    }
}
