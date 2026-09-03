<?php

/*
|--------------------------------------------------------------------------
| Mensagens de validação
|--------------------------------------------------------------------------
|
| Sem este arquivo o Laravel devolve a própria chave da regra na tela — foi
| assim que um CPF duplicado apareceu para o usuário como "validation.unique".
| O framework traz um en/validation.php embutido, mas ele não entra em cena
| aqui: APP_LOCALE e APP_FALLBACK_LOCALE são os dois pt_BR, então não existe
| idioma para onde cair.
|
| O bloco `attributes` no fim é o que faz a mensagem citar o rótulo que está
| no formulário ("CNPJ / CPF") em vez do nome da coluna ("document").
|
*/

return [

    'accepted' => 'O campo :attribute deve ser aceito.',
    'accepted_if' => 'O campo :attribute deve ser aceito quando :other for :value.',
    'active_url' => 'O campo :attribute deve conter uma URL válida.',
    'after' => 'O campo :attribute deve conter uma data posterior a :date.',
    'after_or_equal' => 'O campo :attribute deve conter uma data posterior ou igual a :date.',
    'alpha' => 'O campo :attribute deve conter apenas letras.',
    'alpha_dash' => 'O campo :attribute deve conter apenas letras, números, hífens e sublinhados.',
    'alpha_num' => 'O campo :attribute deve conter apenas letras e números.',
    'any_of' => 'O campo :attribute é inválido.',
    'array' => 'O campo :attribute deve ser uma lista.',
    'ascii' => 'O campo :attribute deve conter apenas caracteres alfanuméricos e símbolos de um byte.',
    'before' => 'O campo :attribute deve conter uma data anterior a :date.',
    'before_or_equal' => 'O campo :attribute deve conter uma data anterior ou igual a :date.',
    'between' => [
        'array' => 'O campo :attribute deve ter entre :min e :max itens.',
        'file' => 'O arquivo :attribute deve ter entre :min e :max kilobytes.',
        'numeric' => 'O campo :attribute deve estar entre :min e :max.',
        'string' => 'O campo :attribute deve ter entre :min e :max caracteres.',
    ],
    'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
    'can' => 'O campo :attribute contém um valor não autorizado.',
    'confirmed' => 'A confirmação do campo :attribute não confere.',
    'contains' => 'O campo :attribute está sem um valor obrigatório.',
    'current_password' => 'A senha informada está incorreta.',
    'date' => 'O campo :attribute deve conter uma data válida.',
    'date_equals' => 'O campo :attribute deve conter uma data igual a :date.',
    'date_format' => 'O campo :attribute deve corresponder ao formato :format.',
    'decimal' => 'O campo :attribute deve ter :decimal casas decimais.',
    'declined' => 'O campo :attribute deve ser recusado.',
    'declined_if' => 'O campo :attribute deve ser recusado quando :other for :value.',
    'different' => 'Os campos :attribute e :other devem ser diferentes.',
    'digits' => 'O campo :attribute deve ter :digits dígitos.',
    'digits_between' => 'O campo :attribute deve ter entre :min e :max dígitos.',
    'dimensions' => 'O campo :attribute tem dimensões de imagem inválidas.',
    'distinct' => 'O campo :attribute tem um valor duplicado.',
    'doesnt_contain' => 'O campo :attribute não pode conter nenhum destes valores: :values.',
    'doesnt_end_with' => 'O campo :attribute não pode terminar com: :values.',
    'doesnt_start_with' => 'O campo :attribute não pode começar com: :values.',
    'email' => 'O campo :attribute deve conter um e-mail válido.',
    'encoding' => 'O campo :attribute deve usar a codificação :encoding.',
    'ends_with' => 'O campo :attribute deve terminar com: :values.',
    'enum' => 'O valor selecionado em :attribute é inválido.',
    'exists' => 'O valor selecionado em :attribute é inválido.',
    'extensions' => 'O campo :attribute deve ter uma destas extensões: :values.',
    'file' => 'O campo :attribute deve conter um arquivo.',
    'filled' => 'O campo :attribute deve ser preenchido.',
    'gt' => [
        'array' => 'O campo :attribute deve ter mais de :value itens.',
        'file' => 'O arquivo :attribute deve ser maior que :value kilobytes.',
        'numeric' => 'O campo :attribute deve ser maior que :value.',
        'string' => 'O campo :attribute deve ter mais de :value caracteres.',
    ],
    'gte' => [
        'array' => 'O campo :attribute deve ter :value itens ou mais.',
        'file' => 'O arquivo :attribute deve ter :value kilobytes ou mais.',
        'numeric' => 'O campo :attribute deve ser maior ou igual a :value.',
        'string' => 'O campo :attribute deve ter :value caracteres ou mais.',
    ],
    'hex_color' => 'O campo :attribute deve conter uma cor hexadecimal válida.',
    'image' => 'O campo :attribute deve conter uma imagem.',
    'in' => 'O valor selecionado em :attribute é inválido.',
    'in_array' => 'O campo :attribute deve existir em :other.',
    'in_array_keys' => 'O campo :attribute deve conter ao menos uma destas chaves: :values.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'ip' => 'O campo :attribute deve conter um IP válido.',
    'ipv4' => 'O campo :attribute deve conter um IPv4 válido.',
    'ipv6' => 'O campo :attribute deve conter um IPv6 válido.',
    'json' => 'O campo :attribute deve conter um JSON válido.',
    'list' => 'O campo :attribute deve ser uma lista.',
    'lowercase' => 'O campo :attribute deve estar em minúsculas.',
    'lt' => [
        'array' => 'O campo :attribute deve ter menos de :value itens.',
        'file' => 'O arquivo :attribute deve ser menor que :value kilobytes.',
        'numeric' => 'O campo :attribute deve ser menor que :value.',
        'string' => 'O campo :attribute deve ter menos de :value caracteres.',
    ],
    'lte' => [
        'array' => 'O campo :attribute não pode ter mais que :value itens.',
        'file' => 'O arquivo :attribute deve ter :value kilobytes ou menos.',
        'numeric' => 'O campo :attribute deve ser menor ou igual a :value.',
        'string' => 'O campo :attribute deve ter :value caracteres ou menos.',
    ],
    'mac_address' => 'O campo :attribute deve conter um endereço MAC válido.',
    'max' => [
        'array' => 'O campo :attribute não pode ter mais que :max itens.',
        'file' => 'O arquivo :attribute não pode ter mais que :max kilobytes.',
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'string' => 'O campo :attribute não pode ter mais que :max caracteres.',
    ],
    'max_digits' => 'O campo :attribute não pode ter mais que :max dígitos.',
    'mimes' => 'O campo :attribute deve conter um arquivo do tipo: :values.',
    'mimetypes' => 'O campo :attribute deve conter um arquivo do tipo: :values.',
    'min' => [
        'array' => 'O campo :attribute deve ter no mínimo :min itens.',
        'file' => 'O arquivo :attribute deve ter no mínimo :min kilobytes.',
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
        'string' => 'O campo :attribute deve ter no mínimo :min caracteres.',
    ],
    'min_digits' => 'O campo :attribute deve ter no mínimo :min dígitos.',
    'missing' => 'O campo :attribute deve estar ausente.',
    'missing_if' => 'O campo :attribute deve estar ausente quando :other for :value.',
    'missing_unless' => 'O campo :attribute deve estar ausente a menos que :other seja :value.',
    'missing_with' => 'O campo :attribute deve estar ausente quando :values estiver presente.',
    'missing_with_all' => 'O campo :attribute deve estar ausente quando :values estiverem presentes.',
    'multiple_of' => 'O campo :attribute deve ser múltiplo de :value.',
    'not_in' => 'O valor selecionado em :attribute é inválido.',
    'not_regex' => 'O formato do campo :attribute é inválido.',
    'numeric' => 'O campo :attribute deve ser um número.',
    'password' => [
        'letters' => 'A senha deve conter ao menos uma letra.',
        'mixed' => 'A senha deve conter ao menos uma letra maiúscula e uma minúscula.',
        'numbers' => 'A senha deve conter ao menos um número.',
        'symbols' => 'A senha deve conter ao menos um símbolo.',
        'uncompromised' => 'A senha informada apareceu em um vazamento de dados. Escolha outra.',
    ],
    'present' => 'O campo :attribute deve estar presente.',
    'present_if' => 'O campo :attribute deve estar presente quando :other for :value.',
    'present_unless' => 'O campo :attribute deve estar presente a menos que :other seja :value.',
    'present_with' => 'O campo :attribute deve estar presente quando :values estiver presente.',
    'present_with_all' => 'O campo :attribute deve estar presente quando :values estiverem presentes.',
    'prohibited' => 'O campo :attribute não é permitido.',
    'prohibited_if' => 'O campo :attribute não é permitido quando :other for :value.',
    'prohibited_if_accepted' => 'O campo :attribute não é permitido quando :other for aceito.',
    'prohibited_if_declined' => 'O campo :attribute não é permitido quando :other for recusado.',
    'prohibited_unless' => 'O campo :attribute não é permitido a menos que :other esteja em :values.',
    'prohibits' => 'O campo :attribute impede que :other esteja presente.',
    'regex' => 'O formato do campo :attribute é inválido.',
    'required' => 'O campo :attribute é obrigatório.',
    'required_array_keys' => 'O campo :attribute deve conter as chaves: :values.',
    'required_if' => 'O campo :attribute é obrigatório quando :other for :value.',
    'required_if_accepted' => 'O campo :attribute é obrigatório quando :other for aceito.',
    'required_if_declined' => 'O campo :attribute é obrigatório quando :other for recusado.',
    'required_unless' => 'O campo :attribute é obrigatório a menos que :other esteja em :values.',
    'required_with' => 'O campo :attribute é obrigatório quando :values está presente.',
    'required_with_all' => 'O campo :attribute é obrigatório quando :values estão presentes.',
    'required_without' => 'O campo :attribute é obrigatório quando :values não está presente.',
    'required_without_all' => 'O campo :attribute é obrigatório quando nenhum de :values está presente.',
    'same' => 'Os campos :attribute e :other devem ser iguais.',
    'size' => [
        'array' => 'O campo :attribute deve conter :size itens.',
        'file' => 'O arquivo :attribute deve ter :size kilobytes.',
        'numeric' => 'O campo :attribute deve ser :size.',
        'string' => 'O campo :attribute deve ter :size caracteres.',
    ],
    'starts_with' => 'O campo :attribute deve começar com: :values.',
    'string' => 'O campo :attribute deve ser um texto.',
    'timezone' => 'O campo :attribute deve conter um fuso horário válido.',
    'ulid' => 'O campo :attribute deve conter um ULID válido.',
    'unique' => 'Este :attribute já está cadastrado.',
    'uploaded' => 'Falha no envio do arquivo :attribute.',
    'uppercase' => 'O campo :attribute deve estar em maiúsculas.',
    'url' => 'O campo :attribute deve conter uma URL válida.',
    'uuid' => 'O campo :attribute deve conter um UUID válido.',

    /*
    |--------------------------------------------------------------------------
    | Mensagens por campo
    |--------------------------------------------------------------------------
    |
    | Regra específica de um campo: 'campo.regra' => 'mensagem'.
    |
    */

    'custom' => [
        'file' => [
            'max' => 'O arquivo não pode passar de 10 MB.',
            'mimes' => 'Formato não aceito. Envie PDF, imagem (JPG/PNG), Word ou Excel.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Nomes dos campos na tela
    |--------------------------------------------------------------------------
    |
    | O que aparece no lugar de :attribute. Sem isso a mensagem cita o nome da
    | coluna do banco, que não é o rótulo que o usuário está vendo.
    |
    */

    'attributes' => [
        // Cliente — identificação
        'name' => 'Nome / Razão Social',
        'document' => 'CNPJ / CPF',
        'cpf' => 'CPF',
        'rg' => 'RG',
        'fantasy_name' => 'Nome Fantasia',
        'nome_comercial' => 'Nome Comercial',
        'outros_nomes' => 'Outros Nomes',
        'possui_outro_nome' => 'Possui outro nome',
        'classificacao' => 'Classificação',
        'categoria' => 'Categoria',
        'regional_id' => 'Regional',
        'inscri_estadual' => 'Inscrição Estadual',
        'inscri_municipal' => 'Inscrição Municipal',
        'tipo_cliente' => 'Tipo de Cliente',
        'cod_omie' => 'Código Omie',
        'omie_id' => 'Código Omie',
        'dt_nascimento' => 'Data de Nascimento',
        'autenticacao_whatsapp' => 'Autenticação por WhatsApp',
        'status' => 'Status',
        'ativo' => 'Ativo',

        // Cliente — ABAC / SINAC
        'associado_abac' => 'Associado ABAC',
        'dt_filiacao_abac' => 'Data de Filiação ABAC',
        'num_filiacao_abac' => 'Número de Filiação ABAC',
        'dt_desfiliacao_abac' => 'Data de Desfiliação ABAC',
        'motivo_desfiliacao_abac' => 'Motivo da Desfiliação ABAC',
        'obs_abac' => 'Observações ABAC',
        'associado_sinac' => 'Associado SINAC',
        'dt_filiacao_sinac' => 'Data de Filiação SINAC',
        'num_filiacao_sinac' => 'Número de Filiação SINAC',
        'dt_desfiliacao_sinac' => 'Data de Desfiliação SINAC',
        'motivo_desfiliacao_sinac' => 'Motivo da Desfiliação SINAC',
        'obs_sinac' => 'Observações SINAC',
        'dt_filiacao' => 'Data de Filiação',
        'dt_desfiliacao' => 'Data de Desfiliação',
        'motivo_desfiliacao' => 'Motivo da Desfiliação',
        'num_filiacao' => 'Número de Filiação',
        'dt_f_abac' => 'Data de Filiação ABAC',
        'dt_f_sinac' => 'Data de Filiação SINAC',
        'num_abac' => 'Número ABAC',
        'num_sinac' => 'Número SINAC',
        'situacao_abac' => 'Situação ABAC',

        // Cliente — datas e situação
        'dt_abertura_empresa' => 'Data de Abertura da Empresa',
        'dt_aniversario_empresa' => 'Aniversário da Empresa',
        'dt_autorizacao_consorcio' => 'Data de Autorização do Consórcio',
        'dt_pedido_consorcio' => 'Data do Pedido de Consórcio',
        'dt_bacen' => 'Data BACEN',
        'status_empresa' => 'Status da Empresa',
        'classificao_administradora' => 'Classificação da Administradora',
        'inicio_atv' => 'Início das Atividades',
        'aniversario' => 'Aniversário',

        // Contatos
        'email' => 'E-mail',
        'email_2' => 'E-mail 2',
        'email_3' => 'E-mail 3',
        'email_4' => 'E-mail 4',
        'email_5' => 'E-mail 5',
        'email_6' => 'E-mail 6',
        'email_7' => 'E-mail 7',
        'email_conac' => 'E-mail CONAC',
        'email_ouvidoria' => 'E-mail da Ouvidoria',
        'email_presidente' => 'E-mail do Presidente',
        'email_secretaria' => 'E-mail da Secretaria',
        'emails_boletos' => 'E-mails para Boletos',
        'telefone_ouvidoria' => 'Telefone da Ouvidoria',
        'mobile' => 'Celular',
        'phone' => 'Telefone',
        'celular' => 'Celular',
        'telefone' => 'Telefone',
        'telefone_2' => 'Telefone 2',
        'ramal' => 'Ramal',
        'site' => 'Site',
        'url' => 'URL',
        'responsavel_empresa' => 'Responsável pela Empresa',
        'contato_name_admin' => 'Contato Administrativo',
        'contato_id' => 'Contato',
        'nome' => 'Nome',
        'funcao' => 'Função',
        'departamento' => 'Departamento',
        'outro_departamento' => 'Outro Departamento',
        'papel' => 'Papel',
        'responsavel' => 'Responsável',
        'representante_legal' => 'Representante Legal',

        // Endereço
        'cep' => 'CEP',
        'rua' => 'Rua',
        'numero' => 'Número',
        'complemento' => 'Complemento',
        'bairro' => 'Bairro',
        'municipio' => 'Município',
        'estado' => 'Estado',
        'pais' => 'País',
        'cod_ibge' => 'Código IBGE',
        'tipo' => 'Tipo',

        // Secretaria / comitês / sócios
        'presidente_atual' => 'Presidente Atual',
        'mandato_inicio' => 'Início do Mandato',
        'mandato_termino' => 'Término do Mandato',
        'mandato_alerta' => 'Alerta de Mandato',
        'comite' => 'Comitê',
        'comite_nome' => 'Nome do Comitê',
        'quota_participacao' => 'Quota de Participação',
        'area' => 'Área',
        'area_atuacao' => 'Área de Atuação',
        'segmento' => 'Segmento',

        // Observações
        'notes' => 'Observações',
        'obs' => 'Observações',
        'obs_2' => 'Observações 2',
        'obs_cadastro' => 'Observações do Cadastro',
        'obs_juridico' => 'Observações do Jurídico',
        'obs_sinac_juridico' => 'Observações SINAC (Jurídico)',
        'observacoes' => 'Observações',
        'observacao' => 'Observação',
        'descricao' => 'Descrição',
        'description' => 'Descrição',

        // Documentos (GED)
        'client_id' => 'Cliente',
        'title' => 'Título',
        'type' => 'Tipo',
        'category' => 'Categoria',
        'file' => 'Arquivo',
        'files' => 'Arquivos',
        'expiration_date' => 'Data de Validade',
        'dt_vencimento' => 'Data de Vencimento',
        'rotulo' => 'Rótulo',
        'tag_ids' => 'Tags',
        'new_tag_nome' => 'Nome da nova tag',
        'new_tag_cor' => 'Cor da nova tag',

        // Usuários e acesso
        'role' => 'Perfil',
        'password' => 'Senha',
        'current_password' => 'Senha atual',

        // Financeiro / Omie
        'possui_contrato_ativo' => 'Possui Contrato Ativo',
        'valor' => 'Valor',
        'desconto' => 'Desconto',
        'vencimento' => 'Vencimento',
        'data_recebimento' => 'Data de Recebimento',
        'numero_parcela' => 'Número da Parcela',
        'cc_id' => 'Conta Corrente',
        'codigo_lancamento' => 'Código do Lançamento',
        'codigo_lancamento_omie' => 'Código do Lançamento (Omie)',
        'codigo_lancamento_integracao' => 'Código de Integração',
        'tipo_receber' => 'Tipo de Recebimento',
        'projeto' => 'Projeto',
        'idCompra' => 'ID da Compra',
        'codRm' => 'Código RM',
        'user_id' => 'Usuário',
    ],

];
