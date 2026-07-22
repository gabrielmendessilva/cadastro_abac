<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sincronização do WordPress dos associados (associados:sync)
    |--------------------------------------------------------------------------
    | O portal dos associados é um WordPress: o CNPJ da empresa fica em
    | wp_usermeta (meta_key casando com cnpj_meta_like) e cada usuário
    | vinculado ao CNPJ vira um contato do cliente.
    */

    'connection' => env('ASSOCIADOS_DB_CONNECTION', 'pgsql-associado'),

    'sync' => [
        // Quantidade de CNPJs processados por chunk (progress/preload de contatos).
        'chunk' => (int) env('ASSOCIADOS_SYNC_CHUNK', 200),

        'max_warning_samples' => 200,

        // user_id fixo gravado nos contatos criados/atualizados (mesmo do legado).
        'contact_user_id' => 1,
    ],

    // Padrão LIKE usado para achar as metas de CNPJ na wp_usermeta.
    'cnpj_meta_like' => '%cnpj_associada%',

    // De-para meta_key (WordPress) => coluna de clients no banco do app.
    // Chaves confirmadas no phpMyAdmin do WordPress (prefixo _associada_).
    // Rode `php artisan associados:sync --discover` para achar as demais.
    'meta_map' => [
        '_associada_telefone' => 'phone',
        '_associada_razao_social' => 'name',
        '_associada_nome_fantasia_comercial' => 'fantasy_name',
    ],

    // De-para meta_key (WordPress) => campo de client_enderecos. Quando qualquer
    // um vier preenchido, o sync cria/atualiza o endereço tipo "principal" do
    // cliente (mesmo modelo do rm:import; vazio nunca sobrescreve).
    'endereco_meta_map' => [
        '_associada_cep' => 'cep',
        '_associada_endereco' => 'rua',
        '_associada_numero' => 'numero',
        '_associada_complemento' => 'complemento',
        '_associada_bairro' => 'bairro',
        '_associada_cidade' => 'municipio',
        '_associada_uf' => 'estado',
    ],

    // Metas internas do WordPress que nunca interessam — ficam fora do aviso de
    // "meta não mapeada" para não poluir o relatório e o --discover.
    'meta_ignore' => [
        'wp_capabilities',
        'wp_user_level',
        'wp_user-settings',
        'wp_user-settings-time',
        'wp_dashboard_quick_press_last_post_id',
        'session_tokens',
        'nickname',
        'first_name',
        'last_name',
        'description',
        'rich_editing',
        'syntax_highlighting',
        'comment_shortcuts',
        'admin_color',
        'use_ssl',
        'show_admin_bar_front',
        'locale',
        'dismissed_wp_pointers',
        'default_password_nag',
        '_associada_logo',
        // Lixo interno do WP visto no censo da primeira execução real.
        'wp_elementor_enable_ai',
        'community-events-location',
        'closedpostboxes_dashboard',
        'meta-box-order_dashboard',
        'metaboxhidden_dashboard',
        '_gform-update-entry-id',
    ],

];
