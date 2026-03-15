<?php $__env->startSection('title', 'Clientes'); ?>
<?php $__env->startSection('page-title', 'Clientes'); ?>

<?php $__env->startSection('content'); ?>
<div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h3 class="text-xl font-semibold">Gestão de clientes</h3>
            <p class="text-slate-500 text-sm">Cadastro online completo dos clientes.</p>
        </div>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('clients.create')): ?>
            <a href="<?php echo e(route('clients.create')); ?>" class="rounded-2xl bg-indigo-600 px-5 py-3 text-white">Novo cliente</a>
        <?php endif; ?>
    </div>

    <form method="GET" class="mb-6 grid gap-3 md:grid-cols-5">
        <input type="text" name="search" value="<?php echo e(request('search')); ?>" class="rounded-2xl border border-slate-300 px-4 py-3" placeholder="Nome, CPF/CNPJ ou e-mail">
        <input type="text" name="city" value="<?php echo e(request('city')); ?>" class="rounded-2xl border border-slate-300 px-4 py-3" placeholder="Cidade">
        <input type="text" name="state" value="<?php echo e(request('state')); ?>" maxlength="2" class="rounded-2xl border border-slate-300 px-4 py-3 uppercase" placeholder="UF">
        <select name="status" class="rounded-2xl border border-slate-300 px-4 py-3">
            <option value="">Status</option>
            <option value="1" <?php if(request('status') === '1'): echo 'selected'; endif; ?>>Ativo</option>
            <option value="0" <?php if(request('status') === '0'): echo 'selected'; endif; ?>>Inativo</option>
        </select>
        <div class="flex gap-3">
            <button class="w-full rounded-2xl bg-slate-900 px-5 py-3 text-white">Buscar</button>
            <a href="<?php echo e(route('clients.index')); ?>" class="rounded-2xl border px-5 py-3">Limpar</a>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-left text-slate-500">
                    <th class="py-3 pr-4">Nome</th>
                    <th class="py-3 pr-4">Documento</th>
                    <th class="py-3 pr-4">Cidade/UF</th>
                    <th class="py-3 pr-4">Docs</th>
                    <th class="py-3 pr-4">Status</th>
                    <th class="py-3 pr-4">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $clients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="border-b border-slate-100">
                        <td class="py-4 pr-4">
                            <div class="font-medium"><?php echo e($client->name); ?></div>
                            <div class="text-xs text-slate-500"><?php echo e($client->email ?: '-'); ?></div>
                        </td>
                        <td class="py-4 pr-4"><?php echo e($client->document); ?></td>
                        <td class="py-4 pr-4"><?php echo e(trim(($client->city ?: '-') . ' / ' . ($client->state ?: '-'))); ?></td>
                        <td class="py-4 pr-4"><?php echo e($client->documents_count); ?></td>
                        <td class="py-4 pr-4"><?php echo e($client->status ? 'Ativo' : 'Inativo'); ?></td>
                        <td class="py-4 pr-4 flex gap-2 flex-wrap">
                            <a href="<?php echo e(route('clients.show', ['client' => $client, 'tab' => 'geral'])); ?>" class="rounded-xl border px-3 py-2">Ver</a>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('documents.create')): ?>
                                <a href="<?php echo e(route('clients.show', ['client' => $client, 'tab' => 'ged'])); ?>" class="rounded-xl border px-3 py-2">GED</a>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('clients.edit')): ?>
                                <a href="<?php echo e(route('clients.edit', $client)); ?>" class="rounded-xl border px-3 py-2">Editar</a>
                            <?php endif; ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('clients.delete')): ?>
                                <form method="POST" action="<?php echo e(route('clients.destroy', $client)); ?>" onsubmit="return confirm('Excluir cliente?')">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-red-700">Excluir</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="py-6 text-center text-slate-500">Nenhum cliente encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4"><?php echo e($clients->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/gabrielmendes/Downloads/mnt/data/laravel-ged-crud/resources/views/clients/index.blade.php ENDPATH**/ ?>