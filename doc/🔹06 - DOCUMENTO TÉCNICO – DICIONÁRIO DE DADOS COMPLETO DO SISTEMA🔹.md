Sistema: SaaS Menu Interativo  
Tipo de documento: Dicionário de dados  
Objetivo: Descrever, de forma estruturada, as tabelas e colunas do banco de dados do sistema, com tipo sugerido, obrigatoriedade, finalidade funcional e regra de preenchimento.

1. Finalidade do documento

Este documento formaliza o dicionário de dados do SaaS Menu Interativo, detalhando a estrutura lógica das tabelas principais do sistema. Seu objetivo é padronizar a implementação do banco de dados, apoiar a construção do `schema.sql`, orientar controllers, services e repositories, e reduzir ambiguidades entre regra de negócio e persistência.

O foco aqui não é apenas listar campos. O foco é deixar claro o papel de cada coluna no funcionamento do produto.

2. Convenções adotadas

Padrões gerais adotados:

`BIGINT UNSIGNED AUTO_INCREMENT` para chaves primárias principais.  
`DATETIME` para controle temporal.  
`DECIMAL(10,2)` para valores monetários, salvo necessidade futura de escala maior.  
`TINYINT(1)` para booleanos.  
`VARCHAR` para textos curtos.  
`TEXT` para descrições livres.  
`JSON` apenas quando a estrutura exigir flexibilidade real.  
`created_at`, `updated_at` e `deleted_at` nas tabelas em que fizer sentido histórico.

Obrigatoriedade:  
Sim = preenchimento obrigatório.  
Não = campo opcional.  
Condicional = obrigatório apenas em determinados contextos de negócio.

3. BLOCO SAAS INSTITUCIONAL

3.1. Tabela `plans`

Finalidade: armazenar os planos comerciais do SaaS.

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador do plano|Gerado automaticamente|
|name|VARCHAR(120)|Sim|Nome comercial do plano|Ex.: Básico, Profissional, Premium|
|slug|VARCHAR(120)|Sim|Identificador textual único|Deve ser único e normalizado|
|description|TEXT|Não|Descrição do plano|Texto explicativo comercial|
|price_monthly|DECIMAL(10,2)|Sim|Valor mensal do plano|Maior ou igual a zero|
|price_yearly|DECIMAL(10,2)|Não|Valor anual do plano|Opcional|
|max_users|INT UNSIGNED|Não|Limite de usuários por plano|Nulo pode significar ilimitado|
|max_products|INT UNSIGNED|Não|Limite de produtos|Nulo pode significar ilimitado|
|max_tables|INT UNSIGNED|Não|Limite de mesas|Nulo pode significar ilimitado|
|features_json|JSON|Não|Recursos habilitados no plano|Estrutura controlada pelo sistema|
|status|VARCHAR(20)|Sim|Situação do plano|ativo, inativo|
|created_at|DATETIME|Sim|Data de criação|Automático|
|updated_at|DATETIME|Não|Data da última atualização|Atualizado pelo sistema|

3.2. Tabela `companies`

Finalidade: armazenar os estabelecimentos assinantes.

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador da empresa|Automático|
|name|VARCHAR(150)|Sim|Nome fantasia do estabelecimento|Texto principal da empresa|
|legal_name|VARCHAR(180)|Não|Razão social|Opcional|
|document_number|VARCHAR(20)|Não|CPF/CNPJ|Validar formato quando informado|
|email|VARCHAR(150)|Sim|E-mail principal|Deve ser válido|
|phone|VARCHAR(25)|Não|Telefone principal|Opcional|
|whatsapp|VARCHAR(25)|Não|WhatsApp comercial|Opcional|
|slug|VARCHAR(150)|Sim|Identificador amigável|Único por empresa|
|status|VARCHAR(20)|Sim|Situação geral da empresa|ativa, teste, suspensa, cancelada|
|plan_id|BIGINT UNSIGNED FK|Não|Plano atual vinculado|Relacionado a `plans.id`|
|subscription_status|VARCHAR(30)|Sim|Situação da assinatura|ativa, trial, inadimplente, suspensa, cancelada|
|trial_ends_at|DATETIME|Não|Término do período de teste|Opcional|
|subscription_starts_at|DATETIME|Não|Início da assinatura atual|Opcional|
|subscription_ends_at|DATETIME|Não|Fim da assinatura atual|Opcional|
|created_at|DATETIME|Sim|Criação do cadastro|Automático|
|updated_at|DATETIME|Não|Última atualização|Automático|

3.3. Tabela `subscriptions`

Finalidade: registrar histórico de assinaturas.

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador da assinatura|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa assinante|Referência a `companies.id`|
|plan_id|BIGINT UNSIGNED FK|Sim|Plano contratado|Referência a `plans.id`|
|status|VARCHAR(20)|Sim|Estado da assinatura|ativa, vencida, cancelada, trial|
|billing_cycle|VARCHAR(20)|Sim|Periodicidade de cobrança|mensal, anual|
|amount|DECIMAL(10,2)|Sim|Valor contratado|Maior ou igual a zero|
|starts_at|DATETIME|Sim|Data inicial da assinatura|Obrigatório|
|ends_at|DATETIME|Não|Data final prevista|Opcional|
|canceled_at|DATETIME|Não|Data de cancelamento|Preencher apenas se cancelada|
|created_at|DATETIME|Sim|Data de criação|Automático|
|updated_at|DATETIME|Não|Data de atualização|Automático|

3.4. Tabela `subscription_payments`

Finalidade: registrar cobranças e pagamentos das assinaturas.

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador do pagamento|Automático|
|subscription_id|BIGINT UNSIGNED FK|Sim|Assinatura relacionada|FK obrigatória|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa cobrada|FK obrigatória|
|reference_month|TINYINT UNSIGNED|Sim|Mês de referência|1 a 12|
|reference_year|SMALLINT UNSIGNED|Sim|Ano de referência|Ano válido|
|amount|DECIMAL(10,2)|Sim|Valor da cobrança|Maior ou igual a zero|
|status|VARCHAR(20)|Sim|Situação da cobrança|pendente, pago, vencido, cancelado|
|payment_method|VARCHAR(30)|Não|Meio de pagamento|pix, cartão, boleto etc.|
|paid_at|DATETIME|Não|Data do pagamento|Só se pago|
|due_date|DATE|Sim|Vencimento da cobrança|Obrigatório|
|transaction_reference|VARCHAR(120)|Não|Código externo|Opcional|
|created_at|DATETIME|Sim|Criação do registro|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|

4. BLOCO DE USUÁRIOS, PERFIS E PERMISSÕES

4.1. Tabela `roles`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador do perfil|Automático|
|name|VARCHAR(100)|Sim|Nome do perfil|Ex.: Garçom|
|slug|VARCHAR(100)|Sim|Chave única do perfil|Ex.: waiter|
|context|VARCHAR(30)|Sim|Contexto do perfil|company, saas, public|
|description|TEXT|Não|Descrição do papel|Opcional|
|created_at|DATETIME|Sim|Criação|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|

4.2. Tabela `permissions`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador da permissão|Automático|
|module|VARCHAR(80)|Sim|Módulo funcional|Ex.: products|
|action|VARCHAR(80)|Sim|Ação autorizada|Ex.: edit|
|slug|VARCHAR(150)|Sim|Chave única|Ex.: products.edit|
|description|TEXT|Não|Explicação da permissão|Opcional|
|created_at|DATETIME|Sim|Criação|Automático|

4.3. Tabela `role_permissions`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador da relação|Automático|
|role_id|BIGINT UNSIGNED FK|Sim|Perfil|Referência a `roles.id`|
|permission_id|BIGINT UNSIGNED FK|Sim|Permissão|Referência a `permissions.id`|

4.4. Tabela `users`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador do usuário|Automático|
|company_id|BIGINT UNSIGNED FK|Condicional|Empresa do usuário|Obrigatório para usuários da empresa; nulo para usuários globais SaaS|
|role_id|BIGINT UNSIGNED FK|Sim|Perfil do usuário|Obrigatório|
|name|VARCHAR(150)|Sim|Nome completo|Obrigatório|
|email|VARCHAR(150)|Sim|E-mail de login|Único|
|phone|VARCHAR(25)|Não|Telefone|Opcional|
|password_hash|VARCHAR(255)|Sim|Senha criptografada|Nunca armazenar senha em texto puro|
|status|VARCHAR(20)|Sim|Situação do usuário|ativo, inativo, bloqueado|
|is_saas_user|TINYINT(1)|Sim|Indica se é usuário institucional|0 ou 1|
|last_login_at|DATETIME|Não|Último acesso|Atualizado após login|
|created_at|DATETIME|Sim|Criação|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|
|deleted_at|DATETIME|Não|Exclusão lógica|Opcional|

5. BLOCO DE CONFIGURAÇÕES E PERSONALIZAÇÃO

5.1. Tabela `company_settings`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Único por empresa|
|opening_time|TIME|Não|Hora de abertura|Opcional|
|closing_time|TIME|Não|Hora de fechamento|Opcional|
|allow_orders_outside_business_hours|TINYINT(1)|Sim|Permite pedidos fora do horário|0 ou 1|
|minimum_order_amount|DECIMAL(10,2)|Não|Pedido mínimo|Nulo ou valor >= 0|
|accept_pix|TINYINT(1)|Sim|Aceita Pix|0 ou 1|
|accept_online_payment|TINYINT(1)|Sim|Aceita pagamento online|0 ou 1|
|accept_cash|TINYINT(1)|Sim|Aceita dinheiro|0 ou 1|
|accept_credit_card|TINYINT(1)|Sim|Aceita crédito|0 ou 1|
|accept_debit_card|TINYINT(1)|Sim|Aceita débito|0 ou 1|
|allow_table_service|TINYINT(1)|Sim|Usa atendimento por mesa|0 ou 1|
|allow_delivery|TINYINT(1)|Sim|Aceita delivery|0 ou 1|
|allow_pickup|TINYINT(1)|Sim|Aceita retirada|0 ou 1|
|allow_counter_order|TINYINT(1)|Sim|Aceita balcão|0 ou 1|
|default_order_status|VARCHAR(30)|Sim|Status inicial dos pedidos|Ex.: pending|
|auto_print_orders|TINYINT(1)|Sim|Impressão automática|0 ou 1|
|currency_code|VARCHAR(10)|Sim|Moeda do sistema|Ex.: BRL|
|timezone|VARCHAR(60)|Sim|Fuso da empresa|Ex.: America/Belem|
|created_at|DATETIME|Sim|Criação|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|

5.2. Tabela `company_themes`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Único por empresa|
|primary_color|VARCHAR(20)|Não|Cor principal|Ex.: #FF6600|
|secondary_color|VARCHAR(20)|Não|Cor secundária|Opcional|
|accent_color|VARCHAR(20)|Não|Cor de destaque|Opcional|
|logo_path|VARCHAR(255)|Não|Caminho da logo|Upload validado|
|banner_path|VARCHAR(255)|Não|Caminho do banner|Upload validado|
|title|VARCHAR(150)|Não|Título público|Opcional|
|description|TEXT|Não|Descrição pública|Opcional|
|footer_text|VARCHAR(255)|Não|Texto de rodapé|Opcional|
|created_at|DATETIME|Sim|Criação|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|

6. BLOCO DE CATÁLOGO E CARDÁPIO

6.1. Tabela `categories`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador da categoria|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa proprietária|Obrigatório|
|name|VARCHAR(120)|Sim|Nome da categoria|Ex.: Pizzas|
|slug|VARCHAR(120)|Sim|Identificador amigável|Único por empresa|
|description|TEXT|Não|Descrição da categoria|Opcional|
|display_order|INT UNSIGNED|Sim|Ordem de exibição|Inteiro >= 0|
|status|VARCHAR(20)|Sim|Situação|ativo, inativo|
|created_at|DATETIME|Sim|Criação|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|
|deleted_at|DATETIME|Não|Exclusão lógica|Opcional|

6.2. Tabela `products`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador do produto|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|category_id|BIGINT UNSIGNED FK|Sim|Categoria|Obrigatório|
|name|VARCHAR(150)|Sim|Nome do produto|Obrigatório|
|slug|VARCHAR(150)|Sim|Identificador amigável|Único por empresa|
|description|TEXT|Não|Descrição do produto|Opcional|
|sku|VARCHAR(60)|Não|Código interno|Opcional|
|image_path|VARCHAR(255)|Não|Foto do produto|Upload validado|
|price|DECIMAL(10,2)|Sim|Preço base|>= 0|
|promotional_price|DECIMAL(10,2)|Não|Preço promocional|Menor ou igual ao preço base, quando usado|
|is_featured|TINYINT(1)|Sim|Produto em destaque|0 ou 1|
|is_active|TINYINT(1)|Sim|Produto habilitado|0 ou 1|
|is_paused|TINYINT(1)|Sim|Produto pausado temporariamente|0 ou 1|
|allows_notes|TINYINT(1)|Sim|Permite observação|0 ou 1|
|has_additionals|TINYINT(1)|Sim|Possui adicionais|0 ou 1|
|display_order|INT UNSIGNED|Sim|Ordem visual|>= 0|
|created_at|DATETIME|Sim|Criação|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|
|deleted_at|DATETIME|Não|Exclusão lógica|Opcional|

6.3. Tabela `additional_groups`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador do grupo|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|name|VARCHAR(120)|Sim|Nome do grupo|Ex.: Adicionais|
|description|TEXT|Não|Explicação do grupo|Opcional|
|is_required|TINYINT(1)|Sim|Seleção obrigatória|0 ou 1|
|min_selection|SMALLINT UNSIGNED|Não|Mínimo de escolhas|Nulo ou >= 0|
|max_selection|SMALLINT UNSIGNED|Não|Máximo de escolhas|Nulo ou >= min_selection|
|status|VARCHAR(20)|Sim|Situação|ativo, inativo|
|created_at|DATETIME|Sim|Criação|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|

6.4. Tabela `additional_items`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador do adicional|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|additional_group_id|BIGINT UNSIGNED FK|Sim|Grupo pai|Obrigatório|
|name|VARCHAR(120)|Sim|Nome do adicional|Obrigatório|
|description|TEXT|Não|Descrição|Opcional|
|price|DECIMAL(10,2)|Sim|Valor adicional|>= 0|
|status|VARCHAR(20)|Sim|Situação|ativo, inativo|
|display_order|INT UNSIGNED|Sim|Ordem de exibição|>= 0|
|created_at|DATETIME|Sim|Criação|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|

6.5. Tabela `product_additional_groups`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador da relação|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|product_id|BIGINT UNSIGNED FK|Sim|Produto|Obrigatório|
|additional_group_id|BIGINT UNSIGNED FK|Sim|Grupo adicional|Obrigatório|

7. BLOCO DE MESAS, COMANDAS E CLIENTES

7.1. Tabela `tables`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador da mesa|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|name|VARCHAR(100)|Não|Nome da mesa|Ex.: Varanda 01|
|number|INT UNSIGNED|Sim|Número da mesa|Deve ser único no contexto da empresa|
|capacity|SMALLINT UNSIGNED|Não|Capacidade de pessoas|Opcional|
|qr_code_token|VARCHAR(120)|Sim|Token do QR Code|Único|
|status|VARCHAR(30)|Sim|Estado atual da mesa|livre, ocupada, aguardando_fechamento, bloqueada|
|created_at|DATETIME|Sim|Criação|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|

7.2. Tabela `customers`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador do cliente|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|name|VARCHAR(150)|Sim|Nome do cliente|Obrigatório|
|phone|VARCHAR(25)|Não|Telefone|Opcional|
|email|VARCHAR(150)|Não|E-mail|Opcional|
|document_number|VARCHAR(20)|Não|Documento|Opcional|
|birth_date|DATE|Não|Data de nascimento|Opcional|
|notes|TEXT|Não|Observações internas|Opcional|
|status|VARCHAR(20)|Sim|Situação|ativo, inativo|
|created_at|DATETIME|Sim|Criação|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|

7.3. Tabela `commands`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador da comanda|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|table_id|BIGINT UNSIGNED FK|Não|Mesa vinculada|Obrigatório para consumo em mesa|
|customer_id|BIGINT UNSIGNED FK|Não|Cliente cadastrado|Opcional|
|opened_by_user_id|BIGINT UNSIGNED FK|Não|Usuário que abriu|Opcional|
|customer_name|VARCHAR(150)|Não|Nome digitado no momento|Usado quando não há cliente formal|
|status|VARCHAR(20)|Sim|Situação da comanda|aberta, fechada, cancelada|
|opened_at|DATETIME|Sim|Data/hora de abertura|Obrigatório|
|closed_at|DATETIME|Não|Data/hora de fechamento|Só ao encerrar|
|notes|TEXT|Não|Observações gerais|Opcional|
|created_at|DATETIME|Sim|Criação|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|

8. BLOCO DE PEDIDOS E ITENS

8.1. Tabela `orders`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador do pedido|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|command_id|BIGINT UNSIGNED FK|Não|Comanda vinculada|Opcional|
|table_id|BIGINT UNSIGNED FK|Não|Mesa vinculada|Opcional|
|customer_id|BIGINT UNSIGNED FK|Não|Cliente|Opcional|
|order_number|VARCHAR(40)|Sim|Número operacional do pedido|Único por estratégia definida|
|channel|VARCHAR(20)|Sim|Canal do pedido|table, delivery, pickup, counter|
|status|VARCHAR(30)|Sim|Status operacional|pending, received, preparing, ready, delivered, paid, finished, canceled|
|payment_status|VARCHAR(20)|Sim|Situação financeira|pending, partial, paid, canceled|
|customer_name|VARCHAR(150)|Não|Nome do cliente no ato|Snapshot opcional|
|subtotal_amount|DECIMAL(10,2)|Sim|Soma dos itens|>= 0|
|discount_amount|DECIMAL(10,2)|Sim|Desconto aplicado|>= 0|
|delivery_fee|DECIMAL(10,2)|Sim|Taxa de entrega|>= 0|
|total_amount|DECIMAL(10,2)|Sim|Total final|>= 0|
|notes|TEXT|Não|Observações gerais|Opcional|
|placed_by|VARCHAR(20)|Sim|Origem do lançamento|customer, waiter, cashier|
|placed_by_user_id|BIGINT UNSIGNED FK|Não|Usuário que lançou|Obrigatório quando não for cliente|
|created_at|DATETIME|Sim|Data do pedido|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|
|canceled_at|DATETIME|Não|Cancelamento|Só se cancelado|

8.2. Tabela `order_items`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador do item|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|order_id|BIGINT UNSIGNED FK|Sim|Pedido pai|Obrigatório|
|product_id|BIGINT UNSIGNED FK|Sim|Produto original|Obrigatório|
|product_name_snapshot|VARCHAR(150)|Sim|Nome do produto no momento da venda|Gravar no ato do pedido|
|unit_price|DECIMAL(10,2)|Sim|Preço unitário no momento|Snapshot obrigatório|
|quantity|INT UNSIGNED|Sim|Quantidade|>= 1|
|notes|TEXT|Não|Observação do item|Opcional|
|line_subtotal|DECIMAL(10,2)|Sim|Subtotal da linha|Calculado|
|status|VARCHAR(20)|Sim|Situação do item|active, canceled|
|created_at|DATETIME|Sim|Criação|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|

8.3. Tabela `order_item_additionals`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador do adicional do item|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|order_item_id|BIGINT UNSIGNED FK|Sim|Item do pedido|Obrigatório|
|additional_item_id|BIGINT UNSIGNED FK|Sim|Adicional original|Obrigatório|
|additional_name_snapshot|VARCHAR(150)|Sim|Nome do adicional na venda|Snapshot obrigatório|
|unit_price|DECIMAL(10,2)|Sim|Valor unitário do adicional|Snapshot obrigatório|
|quantity|INT UNSIGNED|Sim|Quantidade|>= 1|
|line_subtotal|DECIMAL(10,2)|Sim|Subtotal do adicional|Calculado|
|created_at|DATETIME|Sim|Criação|Automático|

9. BLOCO DE PRODUÇÃO E HISTÓRICO OPERACIONAL

9.1. Tabela `order_status_history`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador do histórico|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|order_id|BIGINT UNSIGNED FK|Sim|Pedido|Obrigatório|
|old_status|VARCHAR(30)|Não|Status anterior|Opcional na primeira entrada|
|new_status|VARCHAR(30)|Sim|Novo status|Obrigatório|
|changed_by_user_id|BIGINT UNSIGNED FK|Não|Usuário responsável|Opcional|
|changed_at|DATETIME|Sim|Momento da mudança|Obrigatório|
|notes|TEXT|Não|Observações|Opcional|

9.2. Tabela `kitchen_print_logs`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador do log|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|order_id|BIGINT UNSIGNED FK|Sim|Pedido impresso|Obrigatório|
|print_type|VARCHAR(30)|Sim|Tipo de impressão|kitchen_ticket, cashier_receipt etc.|
|printed_by_user_id|BIGINT UNSIGNED FK|Não|Usuário que imprimiu|Opcional|
|printed_at|DATETIME|Sim|Data/hora da impressão|Obrigatório|
|status|VARCHAR(20)|Sim|Situação do processo|success, failed|
|notes|TEXT|Não|Observações técnicas|Opcional|

10. BLOCO DE PAGAMENTOS E CAIXA

10.1. Tabela `payment_methods`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|name|VARCHAR(100)|Sim|Nome exibido|Ex.: Pix|
|code|VARCHAR(40)|Sim|Código interno|Ex.: pix|
|status|VARCHAR(20)|Sim|Situação|ativo, inativo|
|created_at|DATETIME|Sim|Criação|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|

10.2. Tabela `payments`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador do pagamento|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|order_id|BIGINT UNSIGNED FK|Condicional|Pedido pago|Obrigatório quando pagamento for por pedido|
|command_id|BIGINT UNSIGNED FK|Condicional|Comanda paga|Obrigatório quando pagamento for por comanda|
|payment_method_id|BIGINT UNSIGNED FK|Sim|Forma de pagamento|Obrigatório|
|amount|DECIMAL(10,2)|Sim|Valor pago|> 0|
|status|VARCHAR(20)|Sim|Situação do pagamento|pending, paid, failed, refunded, canceled|
|transaction_reference|VARCHAR(120)|Não|Referência externa|Opcional|
|paid_at|DATETIME|Não|Data/hora do pagamento|Só ao confirmar|
|received_by_user_id|BIGINT UNSIGNED FK|Não|Caixa/usuário responsável|Opcional|
|created_at|DATETIME|Sim|Criação|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|

10.3. Tabela `cash_registers`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador do caixa|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|opened_by_user_id|BIGINT UNSIGNED FK|Sim|Usuário que abriu|Obrigatório|
|closed_by_user_id|BIGINT UNSIGNED FK|Não|Usuário que fechou|Só no fechamento|
|opened_at|DATETIME|Sim|Abertura|Obrigatório|
|closed_at|DATETIME|Não|Fechamento|Só ao encerrar|
|opening_amount|DECIMAL(10,2)|Sim|Valor inicial|>= 0|
|closing_amount_reported|DECIMAL(10,2)|Não|Valor informado no fechamento|Opcional|
|closing_amount_calculated|DECIMAL(10,2)|Não|Valor calculado pelo sistema|Opcional|
|status|VARCHAR(20)|Sim|Situação do caixa|open, closed|
|notes|TEXT|Não|Observações|Opcional|
|created_at|DATETIME|Sim|Criação|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|

10.4. Tabela `cash_movements`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador do movimento|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|cash_register_id|BIGINT UNSIGNED FK|Sim|Caixa relacionado|Obrigatório|
|payment_id|BIGINT UNSIGNED FK|Não|Pagamento relacionado|Opcional|
|type|VARCHAR(20)|Sim|Tipo do movimento|income, expense, adjustment|
|description|VARCHAR(255)|Sim|Descrição resumida|Obrigatório|
|amount|DECIMAL(10,2)|Sim|Valor|Pode exigir regra positiva por tipo|
|movement_at|DATETIME|Sim|Momento da movimentação|Obrigatório|
|created_by_user_id|BIGINT UNSIGNED FK|Sim|Usuário responsável|Obrigatório|

11. BLOCO DE ESTOQUE

11.1. Tabela `stock_items`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador do item de estoque|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|product_id|BIGINT UNSIGNED FK|Não|Produto vinculado|Opcional|
|name|VARCHAR(150)|Sim|Nome do item de estoque|Obrigatório|
|sku|VARCHAR(60)|Não|Código interno|Opcional|
|current_quantity|DECIMAL(10,3)|Sim|Quantidade atual|>= 0|
|minimum_quantity|DECIMAL(10,3)|Não|Quantidade mínima|Opcional|
|unit_of_measure|VARCHAR(20)|Sim|Unidade|un, kg, l etc.|
|status|VARCHAR(20)|Sim|Situação|ativo, inativo|
|created_at|DATETIME|Sim|Criação|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|

11.2. Tabela `stock_movements`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador do movimento|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|stock_item_id|BIGINT UNSIGNED FK|Sim|Item movimentado|Obrigatório|
|type|VARCHAR(20)|Sim|Tipo|entry, exit, adjustment|
|quantity|DECIMAL(10,3)|Sim|Quantidade movimentada|> 0|
|reason|VARCHAR(255)|Não|Motivo|Opcional|
|reference_type|VARCHAR(40)|Não|Tipo de origem|order, manual, purchase|
|reference_id|BIGINT UNSIGNED|Não|ID da origem|Opcional|
|moved_by_user_id|BIGINT UNSIGNED FK|Não|Usuário responsável|Opcional|
|moved_at|DATETIME|Sim|Momento da movimentação|Obrigatório|
|created_at|DATETIME|Sim|Criação|Automático|

12. BLOCO DE ENTREGA

12.1. Tabela `delivery_zones`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador da zona|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|name|VARCHAR(120)|Sim|Nome da zona|Ex.: Centro|
|description|TEXT|Não|Descrição|Opcional|
|fee_amount|DECIMAL(10,2)|Sim|Taxa de entrega|>= 0|
|minimum_order_amount|DECIMAL(10,2)|Não|Pedido mínimo na zona|Opcional|
|status|VARCHAR(20)|Sim|Situação|ativo, inativo|
|created_at|DATETIME|Sim|Criação|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|

12.2. Tabela `delivery_addresses`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador do endereço|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|customer_id|BIGINT UNSIGNED FK|Sim|Cliente|Obrigatório|
|label|VARCHAR(60)|Não|Rótulo do endereço|casa, trabalho|
|street|VARCHAR(150)|Sim|Logradouro|Obrigatório|
|number|VARCHAR(20)|Sim|Número|Obrigatório|
|complement|VARCHAR(120)|Não|Complemento|Opcional|
|neighborhood|VARCHAR(120)|Sim|Bairro|Obrigatório|
|city|VARCHAR(120)|Sim|Cidade|Obrigatório|
|state|CHAR(2)|Sim|UF|Ex.: PA|
|zip_code|VARCHAR(15)|Não|CEP|Opcional|
|reference|VARCHAR(255)|Não|Referência|Opcional|
|delivery_zone_id|BIGINT UNSIGNED FK|Não|Zona de entrega|Opcional|
|created_at|DATETIME|Sim|Criação|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|

12.3. Tabela `deliveries`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador da entrega|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|order_id|BIGINT UNSIGNED FK|Sim|Pedido entregue|Obrigatório|
|delivery_address_id|BIGINT UNSIGNED FK|Sim|Endereço da entrega|Obrigatório|
|delivery_user_id|BIGINT UNSIGNED FK|Não|Entregador|Opcional|
|status|VARCHAR(20)|Sim|Situação|pending, assigned, in_route, delivered, failed, canceled|
|delivery_fee|DECIMAL(10,2)|Sim|Taxa aplicada|>= 0|
|assigned_at|DATETIME|Não|Momento da atribuição|Opcional|
|left_at|DATETIME|Não|Saída para entrega|Opcional|
|delivered_at|DATETIME|Não|Entrega concluída|Opcional|
|notes|TEXT|Não|Observações|Opcional|
|created_at|DATETIME|Sim|Criação|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|

13. BLOCO DE PROMOÇÕES E CUPONS

13.1. Tabela `promotions`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador da promoção|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|name|VARCHAR(150)|Sim|Nome da promoção|Obrigatório|
|description|TEXT|Não|Descrição|Opcional|
|discount_type|VARCHAR(20)|Sim|Tipo de desconto|fixed, percent|
|discount_value|DECIMAL(10,2)|Sim|Valor do desconto|>= 0|
|starts_at|DATETIME|Sim|Início da vigência|Obrigatório|
|ends_at|DATETIME|Não|Fim da vigência|Opcional|
|minimum_order_amount|DECIMAL(10,2)|Não|Pedido mínimo|Opcional|
|status|VARCHAR(20)|Sim|Situação|ativo, inativo|
|created_at|DATETIME|Sim|Criação|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|

13.2. Tabela `promotion_products`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador|Automático|
|promotion_id|BIGINT UNSIGNED FK|Sim|Promoção|Obrigatório|
|product_id|BIGINT UNSIGNED FK|Sim|Produto abrangido|Obrigatório|

13.3. Tabela `coupons`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador do cupom|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|code|VARCHAR(60)|Sim|Código do cupom|Único por empresa|
|description|TEXT|Não|Descrição|Opcional|
|discount_type|VARCHAR(20)|Sim|Tipo de desconto|fixed, percent|
|discount_value|DECIMAL(10,2)|Sim|Valor do desconto|>= 0|
|minimum_order_amount|DECIMAL(10,2)|Não|Pedido mínimo|Opcional|
|usage_limit|INT UNSIGNED|Não|Limite total de uso|Opcional|
|used_count|INT UNSIGNED|Sim|Quantidade usada|Inicialmente 0|
|starts_at|DATETIME|Sim|Início da vigência|Obrigatório|
|ends_at|DATETIME|Não|Fim da vigência|Opcional|
|status|VARCHAR(20)|Sim|Situação|ativo, inativo|
|created_at|DATETIME|Sim|Criação|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|

13.4. Tabela `coupon_usages`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador do uso|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa|Obrigatório|
|coupon_id|BIGINT UNSIGNED FK|Sim|Cupom utilizado|Obrigatório|
|customer_id|BIGINT UNSIGNED FK|Não|Cliente|Opcional|
|order_id|BIGINT UNSIGNED FK|Sim|Pedido em que foi usado|Obrigatório|
|used_at|DATETIME|Sim|Momento do uso|Obrigatório|

14. BLOCO DE AUDITORIA E SUPORTE

14.1. Tabela `audit_logs`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador do log|Automático|
|company_id|BIGINT UNSIGNED FK|Não|Empresa impactada|Opcional em ações globais|
|user_id|BIGINT UNSIGNED FK|Não|Usuário responsável|Opcional se ação automatizada|
|module|VARCHAR(80)|Sim|Módulo afetado|Ex.: orders|
|action|VARCHAR(80)|Sim|Ação executada|Ex.: cancel|
|entity_type|VARCHAR(80)|Sim|Tipo da entidade|Ex.: order|
|entity_id|BIGINT UNSIGNED|Sim|ID da entidade|Obrigatório|
|old_values_json|JSON|Não|Valores anteriores|Opcional|
|new_values_json|JSON|Não|Novos valores|Opcional|
|ip_address|VARCHAR(45)|Não|IP da ação|Opcional|
|user_agent|VARCHAR(255)|Não|Navegador/origem|Opcional|
|created_at|DATETIME|Sim|Momento do evento|Automático|

14.2. Tabela `support_tickets`

|Coluna|Tipo sugerido|Obrigatório|Descrição funcional|Regra de preenchimento|
|---|---|---|---|---|
|id|BIGINT UNSIGNED AI PK|Sim|Identificador do chamado|Automático|
|company_id|BIGINT UNSIGNED FK|Sim|Empresa solicitante|Obrigatório|
|opened_by_user_id|BIGINT UNSIGNED FK|Sim|Usuário que abriu|Obrigatório|
|assigned_to_user_id|BIGINT UNSIGNED FK|Não|Responsável interno|Opcional|
|subject|VARCHAR(180)|Sim|Assunto|Obrigatório|
|description|TEXT|Sim|Descrição do problema|Obrigatório|
|status|VARCHAR(20)|Sim|Situação do chamado|open, in_progress, resolved, closed|
|priority|VARCHAR(20)|Sim|Prioridade|low, medium, high, urgent|
|created_at|DATETIME|Sim|Abertura|Automático|
|updated_at|DATETIME|Não|Atualização|Automático|
|closed_at|DATETIME|Não|Encerramento|Só ao fechar|

15. Regras gerais de preenchimento

Algumas regras devem valer transversalmente no sistema.

Campos monetários nunca devem aceitar valores negativos, salvo quando a operação exigir sinal controlado em camada de serviço.  
Campos de status devem trabalhar com conjuntos fixos e controlados.  
Toda tabela transacional relevante deve carregar `company_id`.  
Campos snapshot em pedidos e itens devem ser preenchidos no momento da venda e não recalculados a partir do cadastro atual.  
Campos de data de encerramento, cancelamento ou pagamento devem permanecer nulos enquanto o evento não ocorrer.  
Uploads devem salvar caminho validado, nunca conteúdo binário diretamente em colunas comuns.  
Senhas devem ser armazenadas apenas em hash seguro.

16. Campos críticos para validação forte

Os campos que exigem maior rigor de validação são:

`email`  
`document_number`  
`slug`  
`price`, `amount`, `subtotal_amount`, `total_amount`  
`status`  
`password_hash`  
`order_number`  
`qr_code_token`  
`code` de cupom  
`payment_method.code`

17. Observações de modelagem importantes

A tabela `orders` não substitui `commands`. Pedido e comanda continuam entidades distintas.  
A tabela `payments` pode apontar para pedido ou comanda, mas a regra de negócio deve impedir inconsistência.  
A tabela `products` precisa manter `is_active` e `is_paused` separadamente.  
A tabela `users` precisa suportar tanto usuários de empresa quanto usuários globais SaaS.  
A tabela `stock_items` foi mantida simples para viabilizar MVP e crescimento posterior.

18. Conclusão

O dicionário de dados acima fornece uma base completa e coerente para implementação do banco do SaaS Menu Interativo. Ele está alinhado à arquitetura MVC proposta, à lógica multiempresa do SaaS e à separação correta entre catálogo, operação, financeiro, entrega e governança institucional.

A próxima etapa tecnicamente mais útil é transformar este dicionário em um `schema.sql` profissional, já com `CREATE TABLE`, chaves estrangeiras, índices e comentários organizados por blocos.