<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('page-title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<div class="grid md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
    <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
        <p class="text-slate-500 text-sm">Usuários</p>
        <h3 class="text-4xl font-bold mt-2"><?php echo e($stats['users']); ?></h3>
    </div>
    <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
        <p class="text-slate-500 text-sm">Clientes</p>
        <h3 class="text-4xl font-bold mt-2"><?php echo e($stats['clients']); ?></h3>
    </div>
    <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
        <p class="text-slate-500 text-sm">Documentos</p>
        <h3 class="text-4xl font-bold mt-2"><?php echo e($stats['documents']); ?></h3>
    </div>
    <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
        <p class="text-slate-500 text-sm">Clientes ativos</p>
        <h3 class="text-4xl font-bold mt-2"><?php echo e($stats['active_clients']); ?></h3>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Últimos clientes</h3>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('clients.create')): ?>
                <a href="<?php echo e(route('clients.create')); ?>" class="rounded-xl bg-slate-900 px-4 py-2 text-white text-sm">Novo cliente</a>
            <?php endif; ?>
        </div>
        <div class="space-y-3">
            <?php $__empty_1 = true; $__currentLoopData = $latestClients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $client): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="rounded-2xl border border-slate-200 p-4 flex justify-between items-center">
                    <div>
                        <p class="font-medium"><?php echo e($client->name); ?></p>
                        <p class="text-sm text-slate-500"><?php echo e($client->document); ?></p>
                    </div>
                    <span class="text-sm <?php echo e($client->status ? 'text-emerald-600' : 'text-slate-500'); ?>"><?php echo e($client->status ? 'Ativo' : 'Inativo'); ?></span>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-slate-500">Nenhum cliente cadastrado.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Últimos documentos</h3>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('documents.create')): ?>
                <a href="<?php echo e(route('documents.create')); ?>" class="rounded-xl bg-slate-900 px-4 py-2 text-white text-sm">Novo documento</a>
            <?php endif; ?>
        </div>
        <div class="space-y-3">
            <?php $__empty_1 = true; $__currentLoopData = $latestDocuments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="rounded-2xl border border-slate-200 p-4">
                    <p class="font-medium"><?php echo e($document->title); ?></p>
                    <p class="text-sm text-slate-500">Cliente: <?php echo e($document->client->name ?? '-'); ?></p>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-slate-500">Nenhum documento enviado.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/gabrielmendes/Downloads/mnt/data/laravel-ged-crud/resources/views/dashboard/index.blade.php ENDPATH**/ ?>