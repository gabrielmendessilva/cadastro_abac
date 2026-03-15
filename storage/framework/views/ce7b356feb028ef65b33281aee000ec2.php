<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title', 'Sistema GED'); ?></title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">
    <div class="min-h-screen flex">
        <aside class="w-72 bg-slate-900 text-white p-6 hidden lg:block">
            <div class="mb-8">
                <h1 class="text-2xl font-bold">Sistema GED</h1>
                <p class="text-slate-400 text-sm mt-2">Cadastro online + documentos por cliente</p>
            </div>

            <nav class="space-y-2">
                <a href="<?php echo e(route('dashboard')); ?>" class="block rounded-xl px-4 py-3 hover:bg-slate-800 <?php echo e(request()->routeIs('dashboard') ? 'bg-slate-800' : ''); ?>">Dashboard</a>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('clients.view')): ?>
                    <a href="<?php echo e(route('clients.index')); ?>" class="block rounded-xl px-4 py-3 hover:bg-slate-800 <?php echo e(request()->routeIs('clients.*') ? 'bg-slate-800' : ''); ?>">Clientes</a>
                <?php endif; ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('documents.view')): ?>
                    <a href="<?php echo e(route('documents.index')); ?>" class="block rounded-xl px-4 py-3 hover:bg-slate-800 <?php echo e(request()->routeIs('documents.*') ? 'bg-slate-800' : ''); ?>">GED</a>
                <?php endif; ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('users.view')): ?>
                    <a href="<?php echo e(route('users.index')); ?>" class="block rounded-xl px-4 py-3 hover:bg-slate-800 <?php echo e(request()->routeIs('users.*') ? 'bg-slate-800' : ''); ?>">Usuários</a>
                <?php endif; ?>
            </nav>
        </aside>

        <div class="flex-1">
            <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between shadow-sm">
                <div>
                    <h2 class="text-xl font-semibold"><?php echo $__env->yieldContent('page-title', 'Painel'); ?></h2>
                    <p class="text-sm text-slate-500">Bem-vindo, <?php echo e(auth()->user()->name); ?></p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex rounded-full bg-emerald-100 text-emerald-700 px-3 py-1 text-sm font-medium">
                        <?php echo e(auth()->user()->getRoleNames()->first() ?? 'Sem perfil'); ?>

                    </span>
                    <form method="POST" action="<?php echo e(route('logout')); ?>">
                        <?php echo csrf_field(); ?>
                        <button class="rounded-xl bg-slate-900 px-4 py-2 text-white hover:bg-slate-700">Sair</button>
                    </form>
                </div>
            </header>

            <main class="p-6">
                <?php if(session('success')): ?>
                    <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-700">
                        <?php echo e(session('success')); ?>

                    </div>
                <?php endif; ?>

                <?php if(session('error')): ?>
                    <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                        <?php echo e(session('error')); ?>

                    </div>
                <?php endif; ?>

                <?php echo $__env->yieldContent('content'); ?>
            </main>
        </div>
    </div>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>

</body>
</html>
<?php /**PATH /Users/gabrielmendes/Downloads/mnt/data/laravel-ged-crud/resources/views/layouts/app.blade.php ENDPATH**/ ?>