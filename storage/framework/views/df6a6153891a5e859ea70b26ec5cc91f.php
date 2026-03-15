<?php $__env->startSection('content'); ?>
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900"><?php echo e($client->nome); ?></h1>
            <p class="text-sm text-slate-500">CPF/CNPJ: <?php echo e($client->cpf_cnpj ?: '-'); ?></p>
        </div>

        <div class="flex flex-wrap gap-2">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('clients.edit')): ?>
                <a href="<?php echo e(route('clients.edit', $client)); ?>"
                   class="inline-flex items-center rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600">
                    Editar cliente
                </a>
            <?php endif; ?>

            <a href="<?php echo e(route('clients.index')); ?>"
               class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Voltar
            </a>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <div class="font-semibold">Verifique os campos abaixo:</div>
            <ul class="mt-2 list-disc pl-5">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 pt-4">
            <nav class="-mb-px flex flex-wrap gap-2">
                <a href="<?php echo e(route('clients.show', ['client' => $client, 'tab' => 'geral'])); ?>"
                   class="<?php echo e($activeTab === 'geral' ? 'border-blue-600 bg-blue-50 text-blue-600' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-700'); ?> rounded-t-xl border-b-2 px-4 py-3 text-sm font-medium">
                    Geral
                </a>

                <a href="<?php echo e(route('clients.show', ['client' => $client, 'tab' => 'enderecos'])); ?>"
                   class="<?php echo e($activeTab === 'enderecos' ? 'border-blue-600 bg-blue-50 text-blue-600' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-700'); ?> rounded-t-xl border-b-2 px-4 py-3 text-sm font-medium">
                    Endereços
                </a>

                <a href="<?php echo e(route('clients.show', ['client' => $client, 'tab' => 'contatos'])); ?>"
                   class="<?php echo e($activeTab === 'contatos' ? 'border-blue-600 bg-blue-50 text-blue-600' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-700'); ?> rounded-t-xl border-b-2 px-4 py-3 text-sm font-medium">
                    Contatos
                </a>

                <a href="<?php echo e(route('clients.show', ['client' => $client, 'tab' => 'opcionais'])); ?>"
                   class="<?php echo e($activeTab === 'opcionais' ? 'border-blue-600 bg-blue-50 text-blue-600' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-700'); ?> rounded-t-xl border-b-2 px-4 py-3 text-sm font-medium">
                    Opcionais
                </a>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('documents.view')): ?>
                    <a href="<?php echo e(route('clients.show', ['client' => $client, 'tab' => 'ged'])); ?>"
                       class="<?php echo e($activeTab === 'ged' ? 'border-blue-600 bg-blue-50 text-blue-600' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-700'); ?> rounded-t-xl border-b-2 px-4 py-3 text-sm font-medium">
                        GED
                    </a>
                <?php endif; ?>
            </nav>
        </div>

        <div class="p-6">
            <?php if($activeTab === 'geral'): ?>
                <div class="space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Dados gerais</h2>
                        <p class="text-sm text-slate-500">Informações principais do cliente.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <?php
                            $fields = [
                                'Código Omie' => $client->cod_omie,
                                'Nome Fantasia' => $client->nome_fantasia,
                                'Nome / Razão Social' => $client->nome,
                                'Classificação' => $client->classificacao,
                                'Categoria' => $client->categoria,
                                'CPF/CNPJ' => $client->cpf_cnpj,
                                'Inscrição Estadual' => $client->inscri_estadual,
                                'Inscrição Municipal' => $client->inscri_municipal,
                                'Tipo Cliente' => $client->tipo_cliente,
                                'Status' => $client->status,
                                'Telefone' => $client->telefone,
                                'Celular Admin' => $client->celular_admin,
                                'E-mail Admin' => $client->email_admin,
                                'Contato Admin' => $client->contato_name_admin,
                                'Regional' => $client->regional,
                                'Associado' => $client->associado,
                                'Situação ABAC' => $client->situacao_abac,
                                'Data BACEN' => $client->dt_bacen ? \Carbon\Carbon::parse($client->dt_bacen)->format('d/m/Y') : null,
                                'Classificação Administradora' => $client->classificao_administradora,
                                'E-mail CONAC' => $client->email_conac,
                                'Segmentos' => $client->segmentos,
                                'Área de Atuação' => $client->area_atuacao,
                                'E-mail 2' => $client->email_2,
                                'E-mail 3' => $client->email_3,
                                'E-mail 4' => $client->email_4,
                                'E-mail 5' => $client->email_5,
                                'E-mail 6' => $client->email_6,
                                'E-mail 7' => $client->email_7,
                            ];
                        ?>

                        <?php $__currentLoopData = $fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="<?php echo e(in_array($label, ['Nome / Razão Social', 'E-mail Admin', 'Contato Admin', 'Segmentos', 'Área de Atuação']) ? 'md:col-span-2' : ''); ?>">
                                <label class="mb-1 block text-sm font-medium text-slate-700"><?php echo e($label); ?></label>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">
                                    <?php echo e($value ?: '-'); ?>

                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Observação 1</label>
                            <div class="min-h-[110px] rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">
                                <?php echo e($client->obs ?: '-'); ?>

                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Observação 2</label>
                            <div class="min-h-[110px] rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">
                                <?php echo e($client->obs_2 ?: '-'); ?>

                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if($activeTab === 'enderecos'): ?>
                <div x-data="{ createOpen: false, editOpen: null }" class="space-y-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Endereços</h2>
                            <p class="text-sm text-slate-500">Cadastros vinculados a este cliente.</p>
                        </div>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('clients.edit')): ?>
                            <button type="button"
                                    @click="createOpen = true"
                                    class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                Adicionar endereço
                            </button>
                        <?php endif; ?>
                    </div>

                    <form method="GET" action="<?php echo e(route('clients.show', $client)); ?>" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                        <input type="hidden" name="tab" value="enderecos">
                        <div class="md:col-span-2">
                            <input type="text" name="address_search" value="<?php echo e(request('address_search')); ?>"
                                   placeholder="Buscar por rua, bairro, município, estado, CEP..."
                                   class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                        </div>
                        <div>
                            <button class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white">Buscar</button>
                        </div>
                        <div>
                            <a href="<?php echo e(route('clients.show', ['client' => $client, 'tab' => 'enderecos'])); ?>"
                               class="block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-center text-sm font-medium text-slate-700">
                                Limpar
                            </a>
                        </div>
                    </form>

                    <div class="overflow-hidden rounded-2xl border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">CEP</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Rua</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Número</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Bairro</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Município/UF</th>
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('clients.edit')): ?>
                                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Ações</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <?php $__empty_1 = true; $__currentLoopData = $addresses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $address): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-700"><?php echo e($address->cep ?: '-'); ?></td>
                                            <td class="px-4 py-3 text-sm text-slate-700"><?php echo e($address->rua ?: '-'); ?></td>
                                            <td class="px-4 py-3 text-sm text-slate-700"><?php echo e($address->numero ?: '-'); ?></td>
                                            <td class="px-4 py-3 text-sm text-slate-700"><?php echo e($address->bairro ?: '-'); ?></td>
                                            <td class="px-4 py-3 text-sm text-slate-700"><?php echo e($address->municipio ?: '-'); ?> / <?php echo e($address->estado ?: '-'); ?></td>
                                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('clients.edit')): ?>
                                                <td class="px-4 py-3 text-right">
                                                    <div class="inline-flex gap-2">
                                                        <button type="button"
                                                                @click="editOpen = 'address-<?php echo e($address->id); ?>'"
                                                                class="rounded-lg border border-amber-300 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-50">
                                                            Editar
                                                        </button>

                                                        <form method="POST" action="<?php echo e(route('clients.addresses.destroy', [$client, $address])); ?>">
                                                            <?php echo csrf_field(); ?>
                                                            <?php echo method_field('DELETE'); ?>
                                                            <button type="submit"
                                                                    onclick="return confirm('Deseja excluir este endereço?')"
                                                                    class="rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50">
                                                                Excluir
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <tr>
                                            <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">Nenhum endereço encontrado.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php echo e($addresses->links()); ?>


                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('clients.edit')): ?>
                        <div x-show="createOpen" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50">
                            <div class="flex min-h-full items-center justify-center p-4">
                                <div @click.away="createOpen = false" class="relative my-8 w-full max-w-4xl rounded-2xl bg-white shadow-2xl max-h-[90vh] overflow-y-auto">
                                    <form method="POST" action="<?php echo e(route('clients.addresses.store', $client)); ?>">
                                        <?php echo csrf_field(); ?>
                                        <div class="border-b border-slate-200 px-6 py-4">
                                            <h3 class="text-lg font-semibold text-slate-900">Adicionar endereço</h3>
                                        </div>

                                        <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 xl:grid-cols-4">
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">CEP</label>
                                                <input type="text" name="cep" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div class="xl:col-span-2">
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Rua</label>
                                                <input type="text" name="rua" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Número</label>
                                                <input type="text" name="numero" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Complemento</label>
                                                <input type="text" name="complemento" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Bairro</label>
                                                <input type="text" name="bairro" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">País</label>
                                                <input type="text" name="pais" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Estado</label>
                                                <input type="text" name="estado" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Código IBGE</label>
                                                <input type="text" name="cod_ibge" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Município</label>
                                                <input type="text" name="municipio" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                        </div>

                                        <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4">
                                            <button type="button" @click="createOpen = false"
                                                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">
                                                Cancelar
                                            </button>
                                            <button type="submit"
                                                    class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                                Salvar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <?php $__currentLoopData = $addresses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $address): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div x-show="editOpen === 'address-<?php echo e($address->id); ?>'" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50">
                                <div class="flex min-h-full items-center justify-center p-4">
                                    <div @click.away="editOpen = null" class="relative my-8 w-full max-w-4xl rounded-2xl bg-white shadow-2xl max-h-[90vh] overflow-y-auto">
                                        <form method="POST" action="<?php echo e(route('clients.addresses.update', [$client, $address])); ?>">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PUT'); ?>

                                            <div class="border-b border-slate-200 px-6 py-4">
                                                <h3 class="text-lg font-semibold text-slate-900">Editar endereço</h3>
                                            </div>

                                            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 xl:grid-cols-4">
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">CEP</label>
                                                    <input type="text" name="cep" value="<?php echo e($address->cep); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div class="xl:col-span-2">
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Rua</label>
                                                    <input type="text" name="rua" value="<?php echo e($address->rua); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Número</label>
                                                    <input type="text" name="numero" value="<?php echo e($address->numero); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div class="md:col-span-2">
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Complemento</label>
                                                    <input type="text" name="complemento" value="<?php echo e($address->complemento); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Bairro</label>
                                                    <input type="text" name="bairro" value="<?php echo e($address->bairro); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">País</label>
                                                    <input type="text" name="pais" value="<?php echo e($address->pais); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Estado</label>
                                                    <input type="text" name="estado" value="<?php echo e($address->estado); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Código IBGE</label>
                                                    <input type="text" name="cod_ibge" value="<?php echo e($address->cod_ibge); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Município</label>
                                                    <input type="text" name="municipio" value="<?php echo e($address->municipio); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                            </div>

                                            <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4">
                                                <button type="button" @click="editOpen = null"
                                                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">
                                                    Cancelar
                                                </button>
                                                <button type="submit"
                                                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                                    Atualizar
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if($activeTab === 'contatos'): ?>
                <div x-data="{ createOpen: false, editOpen: null }" class="space-y-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Contatos</h2>
                            <p class="text-sm text-slate-500">Pessoas vinculadas a este cliente.</p>
                        </div>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('clients.edit')): ?>
                            <button type="button"
                                    @click="createOpen = true"
                                    class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                Adicionar contato
                            </button>
                        <?php endif; ?>
                    </div>

                    <form method="GET" action="<?php echo e(route('clients.show', $client)); ?>" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                        <input type="hidden" name="tab" value="contatos">
                        <div class="md:col-span-2">
                            <input type="text" name="contact_search" value="<?php echo e(request('contact_search')); ?>"
                                   placeholder="Buscar por nome, e-mail, telefone, função..."
                                   class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                        </div>
                        <div>
                            <button class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white">Buscar</button>
                        </div>
                        <div>
                            <a href="<?php echo e(route('clients.show', ['client' => $client, 'tab' => 'contatos'])); ?>"
                               class="block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-center text-sm font-medium text-slate-700">
                                Limpar
                            </a>
                        </div>
                    </form>

                    <div class="overflow-hidden rounded-2xl border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Nome</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Função</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">E-mail</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Telefone</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Departamento</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Flags</th>
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('clients.edit')): ?>
                                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Ações</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <?php $__empty_1 = true; $__currentLoopData = $contacts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contact): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-700"><?php echo e($contact->nome ?: '-'); ?></td>
                                            <td class="px-4 py-3 text-sm text-slate-700"><?php echo e($contact->funcao ?: '-'); ?></td>
                                            <td class="px-4 py-3 text-sm text-slate-700"><?php echo e($contact->email ?: '-'); ?></td>
                                            <td class="px-4 py-3 text-sm text-slate-700">
                                                <?php echo e($contact->telefone ?: '-'); ?>

                                                <?php if($contact->telefone_2): ?>
                                                    <div class="text-xs text-slate-500"><?php echo e($contact->telefone_2); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-700">
                                                <?php echo e($contact->departamento ?: '-'); ?>

                                                <?php if($contact->outro_departamento): ?>
                                                    <div class="text-xs text-slate-500"><?php echo e($contact->outro_departamento); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-700">
                                                <div class="flex flex-wrap gap-1">
                                                    <?php if($contact->representante_legal): ?>
                                                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">Rep. Legal</span>
                                                    <?php endif; ?>
                                                    <?php if($contact->comite): ?>
                                                        <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700">Comitê</span>
                                                    <?php endif; ?>
                                                    <?php if($contact->unlock_whatsApp): ?>
                                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">WhatsApp</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('clients.edit')): ?>
                                                <td class="px-4 py-3 text-right">
                                                    <div class="inline-flex gap-2">
                                                        <button type="button"
                                                                @click="editOpen = 'contact-<?php echo e($contact->id); ?>'"
                                                                class="rounded-lg border border-amber-300 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-50">
                                                            Editar
                                                        </button>

                                                        <form method="POST" action="<?php echo e(route('clients.contacts.destroy', [$client, $contact])); ?>">
                                                            <?php echo csrf_field(); ?>
                                                            <?php echo method_field('DELETE'); ?>
                                                            <button type="submit"
                                                                    onclick="return confirm('Deseja excluir este contato?')"
                                                                    class="rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50">
                                                                Excluir
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <tr>
                                            <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">Nenhum contato encontrado.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php echo e($contacts->links()); ?>


                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('clients.edit')): ?>
                        <div x-show="createOpen" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50">
                            <div class="flex min-h-full items-center justify-center p-4">
                                <div @click.away="createOpen = false" class="relative my-8 w-full max-w-5xl rounded-2xl bg-white shadow-2xl max-h-[90vh] overflow-y-auto">
                                    <form method="POST" action="<?php echo e(route('clients.contacts.store', $client)); ?>">
                                        <?php echo csrf_field(); ?>
                                        <div class="border-b border-slate-200 px-6 py-4">
                                            <h3 class="text-lg font-semibold text-slate-900">Adicionar contato</h3>
                                        </div>

                                        <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 xl:grid-cols-4">
                                            <div class="xl:col-span-2">
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Nome</label>
                                                <input type="text" name="nome" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Função</label>
                                                <input type="text" name="funcao" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Nascimento</label>
                                                <input type="date" name="dt_nascimento" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div class="xl:col-span-2">
                                                <label class="mb-1 block text-sm font-medium text-slate-700">E-mail</label>
                                                <input type="email" name="email" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Telefone</label>
                                                <input type="text" name="telefone" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Telefone 2</label>
                                                <input type="text" name="telefone_2" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Departamento</label>
                                                <input type="text" name="departamento" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Outro Departamento</label>
                                                <input type="text" name="outro_departamento" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div class="xl:col-span-4">
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Observação</label>
                                                <textarea name="obs" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm"></textarea>
                                            </div>
                                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                                <input type="checkbox" name="representante_legal" value="1" class="rounded border-slate-300">
                                                Representante legal
                                            </label>
                                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                                <input type="checkbox" name="comite" value="1" class="rounded border-slate-300">
                                                Comitê
                                            </label>
                                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                                <input type="checkbox" name="unlock_whatsApp" value="1" class="rounded border-slate-300">
                                                Liberar WhatsApp
                                            </label>
                                        </div>

                                        <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4">
                                            <button type="button" @click="createOpen = false"
                                                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">
                                                Cancelar
                                            </button>
                                            <button type="submit"
                                                    class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                                Salvar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <?php $__currentLoopData = $contacts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contact): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div x-show="editOpen === 'contact-<?php echo e($contact->id); ?>'" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50">
                                <div class="flex min-h-full items-center justify-center p-4">
                                    <div @click.away="editOpen = null" class="relative my-8 w-full max-w-5xl rounded-2xl bg-white shadow-2xl max-h-[90vh] overflow-y-auto">
                                        <form method="POST" action="<?php echo e(route('clients.contacts.update', [$client, $contact])); ?>">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PUT'); ?>

                                            <div class="border-b border-slate-200 px-6 py-4">
                                                <h3 class="text-lg font-semibold text-slate-900">Editar contato</h3>
                                            </div>

                                            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 xl:grid-cols-4">
                                                <div class="xl:col-span-2">
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Nome</label>
                                                    <input type="text" name="nome" value="<?php echo e($contact->nome); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Função</label>
                                                    <input type="text" name="funcao" value="<?php echo e($contact->funcao); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Nascimento</label>
                                                    <input type="date" name="dt_nascimento" value="<?php echo e($contact->dt_nascimento ? \Carbon\Carbon::parse($contact->dt_nascimento)->format('Y-m-d') : ''); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div class="xl:col-span-2">
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">E-mail</label>
                                                    <input type="email" name="email" value="<?php echo e($contact->email); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Telefone</label>
                                                    <input type="text" name="telefone" value="<?php echo e($contact->telefone); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Telefone 2</label>
                                                    <input type="text" name="telefone_2" value="<?php echo e($contact->telefone_2); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Departamento</label>
                                                    <input type="text" name="departamento" value="<?php echo e($contact->departamento); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Outro Departamento</label>
                                                    <input type="text" name="outro_departamento" value="<?php echo e($contact->outro_departamento); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div class="xl:col-span-4">
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Observação</label>
                                                    <textarea name="obs" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm"><?php echo e($contact->obs); ?></textarea>
                                                </div>
                                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                                    <input type="checkbox" name="representante_legal" value="1" <?php if($contact->representante_legal): echo 'checked'; endif; ?> class="rounded border-slate-300">
                                                    Representante legal
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                                    <input type="checkbox" name="comite" value="1" <?php if($contact->comite): echo 'checked'; endif; ?> class="rounded border-slate-300">
                                                    Comitê
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                                    <input type="checkbox" name="unlock_whatsApp" value="1" <?php if($contact->unlock_whatsApp): echo 'checked'; endif; ?> class="rounded border-slate-300">
                                                    Liberar WhatsApp
                                                </label>
                                            </div>

                                            <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4">
                                                <button type="button" @click="editOpen = null"
                                                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">
                                                    Cancelar
                                                </button>
                                                <button type="submit"
                                                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                                    Atualizar
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if($activeTab === 'opcionais'): ?>
                <div x-data="{ createOpen: false, editOpen: null }" class="space-y-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Opcionais</h2>
                            <p class="text-sm text-slate-500">Registros opcionais vinculados a este cliente.</p>
                        </div>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('clients.edit')): ?>
                            <button type="button"
                                    @click="createOpen = true"
                                    class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                Adicionar opcional
                            </button>
                        <?php endif; ?>
                    </div>

                    <form method="GET" action="<?php echo e(route('clients.show', $client)); ?>" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                        <input type="hidden" name="tab" value="opcionais">
                        <div class="md:col-span-2">
                            <input type="text" name="opcional_search" value="<?php echo e(request('opcional_search')); ?>"
                                   placeholder="Buscar por site, número ABAC ou SINAC..."
                                   class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                        </div>
                        <div>
                            <button class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white">Buscar</button>
                        </div>
                        <div>
                            <a href="<?php echo e(route('clients.show', ['client' => $client, 'tab' => 'opcionais'])); ?>"
                               class="block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-center text-sm font-medium text-slate-700">
                                Limpar
                            </a>
                        </div>
                    </form>

                    <div class="overflow-hidden rounded-2xl border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Site</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Início Atividade</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Nº ABAC</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Fim ABAC</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Nº SINAC</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Fim SINAC</th>
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('clients.edit')): ?>
                                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Ações</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <?php $__empty_1 = true; $__currentLoopData = $opcionais; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opcional): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-700"><?php echo e($opcional->site ?: '-'); ?></td>
                                            <td class="px-4 py-3 text-sm text-slate-700"><?php echo e($opcional->inicio_atv ? \Carbon\Carbon::parse($opcional->inicio_atv)->format('d/m/Y') : '-'); ?></td>
                                            <td class="px-4 py-3 text-sm text-slate-700"><?php echo e($opcional->num_abac ?: '-'); ?></td>
                                            <td class="px-4 py-3 text-sm text-slate-700"><?php echo e($opcional->dt_f_abac ? \Carbon\Carbon::parse($opcional->dt_f_abac)->format('d/m/Y') : '-'); ?></td>
                                            <td class="px-4 py-3 text-sm text-slate-700"><?php echo e($opcional->num_sinac ?: '-'); ?></td>
                                            <td class="px-4 py-3 text-sm text-slate-700"><?php echo e($opcional->dt_f_sinac ? \Carbon\Carbon::parse($opcional->dt_f_sinac)->format('d/m/Y') : '-'); ?></td>
                                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('clients.edit')): ?>
                                                <td class="px-4 py-3 text-right">
                                                    <div class="inline-flex gap-2">
                                                        <button type="button"
                                                                @click="editOpen = 'opcional-<?php echo e($opcional->id); ?>'"
                                                                class="rounded-lg border border-amber-300 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-50">
                                                            Editar
                                                        </button>

                                                        <form method="POST" action="<?php echo e(route('clients.opcionais.destroy', [$client, $opcional])); ?>">
                                                            <?php echo csrf_field(); ?>
                                                            <?php echo method_field('DELETE'); ?>
                                                            <button type="submit"
                                                                    onclick="return confirm('Deseja excluir este registro opcional?')"
                                                                    class="rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50">
                                                                Excluir
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <tr>
                                            <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">Nenhum registro opcional encontrado.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php echo e($opcionais->links()); ?>


                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('clients.edit')): ?>
                        <div x-show="createOpen" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50">
                            <div class="flex min-h-full items-center justify-center p-4">
                                <div @click.away="createOpen = false" class="relative my-8 w-full max-w-4xl rounded-2xl bg-white shadow-2xl max-h-[90vh] overflow-y-auto">
                                    <form method="POST" action="<?php echo e(route('clients.opcionais.store', $client)); ?>">
                                        <?php echo csrf_field(); ?>
                                        <div class="border-b border-slate-200 px-6 py-4">
                                            <h3 class="text-lg font-semibold text-slate-900">Adicionar opcional</h3>
                                        </div>

                                        <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 xl:grid-cols-3">
                                            <div class="xl:col-span-3">
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Site</label>
                                                <input type="text" name="site" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Início Atividade</label>
                                                <input type="date" name="inicio_atv" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Número ABAC</label>
                                                <input type="text" name="num_abac" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Fim ABAC</label>
                                                <input type="date" name="dt_f_abac" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Número SINAC</label>
                                                <input type="text" name="num_sinac" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Fim SINAC</label>
                                                <input type="date" name="dt_f_sinac" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                        </div>

                                        <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4">
                                            <button type="button" @click="createOpen = false"
                                                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">
                                                Cancelar
                                            </button>
                                            <button type="submit"
                                                    class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                                Salvar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <?php $__currentLoopData = $opcionais; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opcional): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div x-show="editOpen === 'opcional-<?php echo e($opcional->id); ?>'" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50">
                                <div class="flex min-h-full items-center justify-center p-4">
                                    <div @click.away="editOpen = null" class="relative my-8 w-full max-w-4xl rounded-2xl bg-white shadow-2xl max-h-[90vh] overflow-y-auto">
                                        <form method="POST" action="<?php echo e(route('clients.opcionais.update', [$client, $opcional])); ?>">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PUT'); ?>

                                            <div class="border-b border-slate-200 px-6 py-4">
                                                <h3 class="text-lg font-semibold text-slate-900">Editar opcional</h3>
                                            </div>

                                            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 xl:grid-cols-3">
                                                <div class="xl:col-span-3">
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Site</label>
                                                    <input type="text" name="site" value="<?php echo e($opcional->site); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Início Atividade</label>
                                                    <input type="date" name="inicio_atv" value="<?php echo e($opcional->inicio_atv ? \Carbon\Carbon::parse($opcional->inicio_atv)->format('Y-m-d') : ''); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Número ABAC</label>
                                                    <input type="text" name="num_abac" value="<?php echo e($opcional->num_abac); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Fim ABAC</label>
                                                    <input type="date" name="dt_f_abac" value="<?php echo e($opcional->dt_f_abac ? \Carbon\Carbon::parse($opcional->dt_f_abac)->format('Y-m-d') : ''); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Número SINAC</label>
                                                    <input type="text" name="num_sinac" value="<?php echo e($opcional->num_sinac); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Fim SINAC</label>
                                                    <input type="date" name="dt_f_sinac" value="<?php echo e($opcional->dt_f_sinac ? \Carbon\Carbon::parse($opcional->dt_f_sinac)->format('Y-m-d') : ''); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                            </div>

                                            <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4">
                                                <button type="button" @click="editOpen = null"
                                                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">
                                                    Cancelar
                                                </button>
                                                <button type="submit"
                                                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                                    Atualizar
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if($activeTab === 'ged' && auth()->user()->can('documents.view')): ?>
                <div x-data="{ createOpen: false }" class="space-y-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">GED</h2>
                            <p class="text-sm text-slate-500">Documentos vinculados a este cliente.</p>
                        </div>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('documents.create')): ?>
                            <button type="button"
                                    @click="createOpen = true"
                                    class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                Adicionar documentos
                            </button>
                        <?php endif; ?>
                    </div>

                    <form method="GET" action="<?php echo e(route('clients.show', $client)); ?>" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                        <input type="hidden" name="tab" value="ged">
                        <div class="md:col-span-2">
                            <input type="text" name="document_search" value="<?php echo e(request('document_search')); ?>"
                                   placeholder="Buscar por título, arquivo, tipo ou descrição..."
                                   class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                        </div>
                        <div>
                            <button class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white">Buscar</button>
                        </div>
                        <div>
                            <a href="<?php echo e(route('clients.show', ['client' => $client, 'tab' => 'ged'])); ?>"
                               class="block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-center text-sm font-medium text-slate-700">
                                Limpar
                            </a>
                        </div>
                    </form>

                    <div class="overflow-hidden rounded-2xl border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Título</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Arquivo</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Tipo</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Vencimento</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Enviado por</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <?php $__empty_1 = true; $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-700"><?php echo e($document->title ?: '-'); ?></td>
                                            <td class="px-4 py-3 text-sm text-slate-700"><?php echo e($document->original_name ?: '-'); ?></td>
                                            <td class="px-4 py-3 text-sm text-slate-700"><?php echo e($document->type ?: '-'); ?></td>
                                            <td class="px-4 py-3 text-sm text-slate-700"><?php echo e($document->expiration_date ? \Carbon\Carbon::parse($document->expiration_date)->format('d/m/Y') : '-'); ?></td>
                                            <td class="px-4 py-3 text-sm text-slate-700"><?php echo e($document->uploader?->name ?: '-'); ?></td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="inline-flex gap-2">
                                                    <a href="<?php echo e(route('clients.documents.download', [$client, $document])); ?>"
                                                       class="rounded-lg border border-blue-300 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-50">
                                                        Download
                                                    </a>

                                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('documents.delete')): ?>
                                                        <form method="POST" action="<?php echo e(route('clients.documents.destroy', [$client, $document])); ?>">
                                                            <?php echo csrf_field(); ?>
                                                            <?php echo method_field('DELETE'); ?>
                                                            <button type="submit"
                                                                    onclick="return confirm('Deseja excluir este documento?')"
                                                                    class="rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50">
                                                                Excluir
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <tr>
                                            <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">Nenhum documento encontrado.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php echo e($documents->links()); ?>


                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('documents.create')): ?>
                        <div x-show="createOpen" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50">
                            <div class="flex min-h-full items-center justify-center p-4">
                                <div @click.away="createOpen = false" class="relative my-8 w-full max-w-6xl rounded-2xl bg-white shadow-2xl max-h-[90vh] overflow-y-auto">
                                    <form method="POST" action="<?php echo e(route('clients.documents.store', $client)); ?>" enctype="multipart/form-data">
                                        <?php echo csrf_field(); ?>

                                        <div class="border-b border-slate-200 px-6 py-4">
                                            <h3 class="text-lg font-semibold text-slate-900">Adicionar documentos</h3>
                                            <p class="mt-1 text-sm text-slate-500">Você pode enviar até 5 arquivos por vez.</p>
                                        </div>

                                        <div class="space-y-4 p-6">
                                            <?php for($i = 0; $i < 5; $i++): ?>
                                                <div class="grid grid-cols-1 gap-4 rounded-2xl border border-slate-200 p-4 md:grid-cols-2 xl:grid-cols-5">
                                                    <div>
                                                        <label class="mb-1 block text-sm font-medium text-slate-700">Arquivo</label>
                                                        <input type="file" name="files[]" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-sm font-medium text-slate-700">Título</label>
                                                        <input type="text" name="title[]" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-sm font-medium text-slate-700">Tipo</label>
                                                        <input type="text" name="type[]" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                    </div>
                                                    <div class="xl:col-span-2">
                                                        <label class="mb-1 block text-sm font-medium text-slate-700">Descrição</label>
                                                        <input type="text" name="description[]" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-sm font-medium text-slate-700">Vencimento</label>
                                                        <input type="date" name="expiration_date[]" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                    </div>
                                                </div>
                                            <?php endfor; ?>
                                        </div>

                                        <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4">
                                            <button type="button" @click="createOpen = false"
                                                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">
                                                Cancelar
                                            </button>
                                            <button type="submit"
                                                    class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                                Salvar documentos
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/gabrielmendes/Downloads/mnt/data/laravel-ged-crud/resources/views/clients/show.blade.php ENDPATH**/ ?>