{{-- Aba USO INTERNO (auditoria) --}}
<div class="space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Uso interno · Auditoria</h2>
        <p class="text-sm text-slate-500">Registro automático de criação e alterações por aba.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <p class="text-xs uppercase text-slate-500">Criado em</p>
            <p class="mt-1 text-sm font-semibold text-slate-800">{{ $client->created_at?->format('d/m/Y H:i') ?: '-' }}</p>
            <p class="text-xs text-slate-500">por {{ $client->creator?->name ?? '—' }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <p class="text-xs uppercase text-slate-500">Última alteração</p>
            <p class="mt-1 text-sm font-semibold text-slate-800">{{ $client->updated_at?->format('d/m/Y H:i') ?: '-' }}</p>
            <p class="text-xs text-slate-500">por {{ $client->updater?->name ?? '—' }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <p class="text-xs uppercase text-slate-500">Total de alterações</p>
            <p class="mt-1 text-sm font-semibold text-slate-800">{{ $auditLogs->total() }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-xs">
                <tr>
                    <th class="px-3 py-2 text-left font-semibold text-slate-500">Quando</th>
                    <th class="px-3 py-2 text-left font-semibold text-slate-500">Usuário</th>
                    <th class="px-3 py-2 text-left font-semibold text-slate-500">Aba</th>
                    <th class="px-3 py-2 text-left font-semibold text-slate-500">Campo</th>
                    <th class="px-3 py-2 text-left font-semibold text-slate-500">Antes</th>
                    <th class="px-3 py-2 text-left font-semibold text-slate-500">Depois</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($auditLogs as $log)
                    <tr>
                        <td class="px-3 py-2 text-xs text-slate-600">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                        <td class="px-3 py-2 text-xs">{{ $log->user?->name ?: '—' }}</td>
                        <td class="px-3 py-2">
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700">{{ $log->aba }}</span>
                        </td>
                        <td class="px-3 py-2 text-xs font-mono">{{ $log->campo ?: ($log->acao === 'created' ? '— cadastro inicial —' : '-') }}</td>
                        <td class="px-3 py-2 text-xs text-slate-500"><code class="rounded bg-slate-50 px-1">{{ \Illuminate\Support\Str::limit($log->valor_anterior ?? '-', 60) }}</code></td>
                        <td class="px-3 py-2 text-xs text-slate-700"><code class="rounded bg-slate-50 px-1">{{ \Illuminate\Support\Str::limit($log->valor_novo ?? '-', 60) }}</code></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-8 text-center text-sm text-slate-500">Sem alterações registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (method_exists($auditLogs, 'links'))
        {{ $auditLogs->links() }}
    @endif
</div>
