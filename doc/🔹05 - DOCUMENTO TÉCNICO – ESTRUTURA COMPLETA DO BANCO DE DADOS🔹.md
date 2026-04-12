Sistema: SaaS Menu Interativo  
Tipo de documento: Arquitetura do banco de dados  
Objetivo: Definir a estrutura completa do banco de dados do sistema, incluindo entidades principais, relacionamentos, tabelas, colunas estratégicas, chaves primárias, chaves estrangeiras, índices e organização por módulos funcionais.

1. Finalidade do documento

Este documento apresenta a modelagem lógica e estrutural do banco de dados do SaaS Menu Interativo, com foco em sustentação da operação comercial, atendimento, produção, pagamento, entrega, gestão administrativa e administração global da plataforma SaaS.

A modelagem aqui proposta foi concebida para atender três exigências simultâneas:

primeiro, suportar a operação diária do estabelecimento;  
segundo, permitir personalização e gestão por empresa assinante;  
terceiro, manter a plataforma preparada para crescimento como produto SaaS multiempresa.

Essa modelagem não deve ser tratada como um banco “apenas de cardápio”. Ela é uma base transacional operacional, comercial e gerencial.

2. Diretriz central da modelagem

A regra estrutural mais importante do banco é esta:

toda informação operacional ou administrativa pertencente a um estabelecimento deve estar vinculada à empresa assinante correspondente.

Isso significa que a maioria das tabelas de negócio deverá possuir uma referência obrigatória à empresa, normalmente por meio do campo `company_id`.

Sem isso, o sistema não se sustenta como SaaS multiempresa.

3. Organização macro do banco por domínio

O banco será organizado em grandes blocos funcionais:

bloco SaaS institucional;  
bloco de usuários e permissões;  
bloco de personalização e configuração;  
bloco de catálogo e cardápio;  
bloco de mesas, comandas e atendimento;  
bloco de pedidos e itens;  
bloco de produção;  
bloco de pagamentos e caixa;  
bloco de estoque;  
bloco de entrega;  
bloco de clientes;  
bloco de promoções e cupons;  
bloco de relatórios e auditoria.

4. Convenções gerais de modelagem

4.1. Padrão de chaves primárias

Todas as tabelas principais utilizarão:

`id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`

Esse padrão é adequado ao crescimento do sistema e evita limitações prematuras de volume.

4.2. Padrão de chaves estrangeiras

As chaves estrangeiras seguirão o padrão:

`{entidade}_id`

Exemplos:  
`company_id`  
`user_id`  
`product_id`  
`order_id`

4.3. Padrão de datas

Campos recomendados:  
`created_at DATETIME`  
`updated_at DATETIME NULL`  
`deleted_at DATETIME NULL` quando houver exclusão lógica

4.4. Exclusão lógica

Tabelas operacionais e administrativas importantes devem adotar exclusão lógica sempre que a integridade histórica for relevante.

Exemplos:  
produtos  
categorias  
usuários  
promoções  
cupons

Já tabelas transacionais como itens de pedido não devem ser “apagadas” livremente; nelas a lógica ideal é cancelamento, inativação ou histórico.

5. Bloco SaaS institucional

5.1. Tabela `companies`

Finalidade: armazenar os estabelecimentos assinantes da plataforma.

Campos estratégicos:  
`id`  
`name` – nome comercial do estabelecimento  
`legal_name` – razão social, se houver  
`document_number` – CPF/CNPJ  
`email`  
`phone`  
`whatsapp`  
`slug` – identificador amigável  
`status` – ativo, teste, suspenso, cancelado  
`plan_id` – plano atual  
`subscription_status` – ativa, trial, inadimplente, suspensa, cancelada  
`trial_ends_at`  
`subscription_starts_at`  
`subscription_ends_at`  
`created_at`  
`updated_at`

Índices:  
PK em `id`  
índice único em `slug`  
índice em `status`  
índice em `plan_id`  
índice em `subscription_status`

Relacionamentos:  
1 company pertence a 1 plano atual  
1 company possui muitos usuários internos  
1 company possui muitos produtos, pedidos, clientes, mesas, relatórios etc.

5.2. Tabela `plans`

Finalidade: armazenar os planos comerciais do SaaS.

Campos:  
`id`  
`name`  
`slug`  
`description`  
`price_monthly`  
`price_yearly`  
`max_users`  
`max_products`  
`max_tables`  
`features_json`  
`status`  
`created_at`  
`updated_at`

Índices:  
único em `slug`  
índice em `status`

5.3. Tabela `subscriptions`

Finalidade: registrar o histórico de assinaturas por empresa.

Campos:  
`id`  
`company_id`  
`plan_id`  
`status`  
`billing_cycle` – mensal, anual  
`amount`  
`starts_at`  
`ends_at`  
`canceled_at`  
`created_at`  
`updated_at`

Índices:  
índice em `company_id`  
índice em `plan_id`  
índice em `status`  
índice composto em (`company_id`, `status`)

5.4. Tabela `subscription_payments`

Finalidade: registrar cobranças e pagamentos das assinaturas.

Campos:  
`id`  
`subscription_id`  
`company_id`  
`reference_month`  
`reference_year`  
`amount`  
`status` – pendente, pago, vencido, cancelado  
`payment_method`  
`paid_at`  
`due_date`  
`transaction_reference`  
`created_at`  
`updated_at`

Índices:  
índice em `subscription_id`  
índice em `company_id`  
índice em `status`  
índice em `due_date`

6. Bloco de usuários, perfis e permissões

6.1. Tabela `roles`

Finalidade: definir perfis de acesso.

Campos:  
`id`  
`name`  
`slug`  
`context` – company, saas, public  
`description`  
`created_at`  
`updated_at`

Exemplos de valores:  
admin_establishment  
manager  
cashier  
waiter  
kitchen  
delivery  
saas_admin  
saas_support  
saas_financial

6.2. Tabela `permissions`

Finalidade: cadastro das permissões do sistema.

Campos:  
`id`  
`module`  
`action`  
`slug`  
`description`  
`created_at`

Exemplo:  
module = products  
action = edit  
slug = products.edit

6.3. Tabela `role_permissions`

Finalidade: relacionar perfis e permissões.

Campos:  
`id`  
`role_id`  
`permission_id`

Índices:  
único em (`role_id`, `permission_id`)

6.4. Tabela `users`

Finalidade: armazenar usuários internos do sistema.

Campos:  
`id`  
`company_id` NULL quando for usuário global SaaS  
`role_id`  
`name`  
`email`  
`phone`  
`password_hash`  
`status` – ativo, inativo, bloqueado  
`is_saas_user` – sim/não  
`last_login_at`  
`created_at`  
`updated_at`  
`deleted_at`

Índices:  
único em `email`  
índice em `company_id`  
índice em `role_id`  
índice composto em (`company_id`, `status`)

Observação importante: usuários SaaS podem ter `company_id` nulo, enquanto usuários do estabelecimento devem obrigatoriamente ter `company_id`.

7. Bloco de personalização e configuração da empresa

7.1. Tabela `company_settings`

Finalidade: armazenar parâmetros operacionais e comerciais do estabelecimento.

Campos:  
`id`  
`company_id`  
`opening_time`  
`closing_time`  
`allow_orders_outside_business_hours`  
`minimum_order_amount`  
`accept_pix`  
`accept_online_payment`  
`accept_cash`  
`accept_credit_card`  
`accept_debit_card`  
`allow_table_service`  
`allow_delivery`  
`allow_pickup`  
`allow_counter_order`  
`default_order_status`  
`auto_print_orders`  
`currency_code`  
`timezone`  
`created_at`  
`updated_at`

Índices:  
único em `company_id`

7.2. Tabela `company_themes`

Finalidade: armazenar personalização visual.

Campos:  
`id`  
`company_id`  
`primary_color`  
`secondary_color`  
`accent_color`  
`logo_path`  
`banner_path`  
`title`  
`description`  
`footer_text`  
`created_at`  
`updated_at`

Índices:  
único em `company_id`

8. Bloco de catálogo e cardápio

8.1. Tabela `categories`

Finalidade: armazenar categorias de produtos.

Campos:  
`id`  
`company_id`  
`name`  
`slug`  
`description`  
`display_order`  
`status` – ativo, inativo  
`created_at`  
`updated_at`  
`deleted_at`

Índices:  
índice em `company_id`  
índice composto em (`company_id`, `status`)  
índice composto em (`company_id`, `display_order`)

8.2. Tabela `products`

Finalidade: armazenar os produtos comercializados.

Campos:  
`id`  
`company_id`  
`category_id`  
`name`  
`slug`  
`description`  
`sku` NULL  
`image_path`  
`price`  
`promotional_price` NULL  
`is_featured`  
`is_active`  
`is_paused`  
`allows_notes`  
`has_additionals`  
`display_order`  
`created_at`  
`updated_at`  
`deleted_at`

Índices:  
índice em `company_id`  
índice em `category_id`  
índice composto em (`company_id`, `is_active`, `is_paused`)  
índice composto em (`company_id`, `display_order`)  
índice composto em (`company_id`, `slug`)

Observação crítica: `is_active` e `is_paused` não são redundantes.  
Um produto pode estar ativo, porém pausado temporariamente por indisponibilidade.

8.3. Tabela `additional_groups`

Finalidade: grupos de adicionais vinculáveis aos produtos.

Campos:  
`id`  
`company_id`  
`name`  
`description`  
`is_required`  
`min_selection`  
`max_selection`  
`status`  
`created_at`  
`updated_at`

Índices:  
índice em `company_id`  
índice em `status`

8.4. Tabela `additional_items`

Finalidade: itens adicionais dentro de um grupo.

Campos:  
`id`  
`company_id`  
`additional_group_id`  
`name`  
`description`  
`price`  
`status`  
`display_order`  
`created_at`  
`updated_at`

Índices:  
índice em `company_id`  
índice em `additional_group_id`

8.5. Tabela `product_additional_groups`

Finalidade: tabela pivô entre produtos e grupos de adicionais.

Campos:  
`id`  
`company_id`  
`product_id`  
`additional_group_id`

Índices:  
único em (`product_id`, `additional_group_id`)  
índice em `company_id`

9. Bloco de mesas e atendimento

9.1. Tabela `tables`

Finalidade: mesas do estabelecimento.

Campos:  
`id`  
`company_id`  
`name`  
`number`  
`capacity`  
`qr_code_token`  
`status` – livre, ocupada, aguardando_fechamento, bloqueada  
`created_at`  
`updated_at`

Índices:  
índice em `company_id`  
índice composto em (`company_id`, `number`)  
índice único em `qr_code_token`

9.2. Tabela `commands`

Finalidade: comandas abertas por mesa e/ou cliente.

Campos:  
`id`  
`company_id`  
`table_id` NULL para outros canais  
`customer_id` NULL  
`opened_by_user_id` NULL  
`customer_name`  
`status` – aberta, fechada, cancelada  
`opened_at`  
`closed_at` NULL  
`notes`  
`created_at`  
`updated_at`

Índices:  
índice em `company_id`  
índice em `table_id`  
índice em `customer_id`  
índice composto em (`company_id`, `status`)  
índice composto em (`table_id`, `status`)

Observação estratégica: a comanda é uma entidade diferente do pedido.  
Ela consolida consumo; o pedido representa uma submissão operacional.

10. Bloco de clientes

10.1. Tabela `customers`

Finalidade: cadastro de clientes.

Campos:  
`id`  
`company_id`  
`name`  
`phone`  
`email` NULL  
`document_number` NULL  
`birth_date` NULL  
`notes` NULL  
`status`  
`created_at`  
`updated_at`

Índices:  
índice em `company_id`  
índice em `phone`  
índice composto em (`company_id`, `phone`)

Observação: em SaaS multiempresa, o cliente pertence ao contexto do estabelecimento.  
No futuro, pode haver unificação por identidade global, mas isso não é prioridade do MVP.

11. Bloco de pedidos

11.1. Tabela `orders`

Finalidade: registrar cada pedido operacional.

Campos:  
`id`  
`company_id`  
`command_id` NULL  
`table_id` NULL  
`customer_id` NULL  
`order_number`  
`channel` – table, delivery, pickup, counter  
`status` – pending, received, preparing, ready, delivered, paid, finished, canceled  
`payment_status` – pending, partial, paid, canceled  
`customer_name` NULL  
`subtotal_amount`  
`discount_amount`  
`delivery_fee`  
`total_amount`  
`notes`  
`placed_by` – customer, waiter, cashier  
`placed_by_user_id` NULL  
`created_at`  
`updated_at`  
`canceled_at` NULL

Índices:  
índice em `company_id`  
índice em `command_id`  
índice em `table_id`  
índice em `customer_id`  
índice composto em (`company_id`, `status`)  
índice composto em (`company_id`, `payment_status`)  
índice composto em (`company_id`, `created_at`)  
índice composto em (`company_id`, `order_number`)

11.2. Tabela `order_items`

Finalidade: armazenar os itens do pedido.

Campos:  
`id`  
`company_id`  
`order_id`  
`product_id`  
`product_name_snapshot`  
`unit_price`  
`quantity`  
`notes`  
`line_subtotal`  
`status` – active, canceled  
`created_at`  
`updated_at`

Índices:  
índice em `company_id`  
índice em `order_id`  
índice em `product_id`

Observação importante: `product_name_snapshot` e `unit_price` devem ser gravados no item para preservar histórico transacional, mesmo que o produto mude depois.

11.3. Tabela `order_item_additionals`

Finalidade: adicionais selecionados por item.

Campos:  
`id`  
`company_id`  
`order_item_id`  
`additional_item_id`  
`additional_name_snapshot`  
`unit_price`  
`quantity`  
`line_subtotal`  
`created_at`

Índices:  
índice em `company_id`  
índice em `order_item_id`  
índice em `additional_item_id`

12. Bloco de produção

12.1. Tabela `order_status_history`

Finalidade: histórico de movimentação dos pedidos.

Campos:  
`id`  
`company_id`  
`order_id`  
`old_status`  
`new_status`  
`changed_by_user_id` NULL  
`changed_at`  
`notes` NULL

Índices:  
índice em `company_id`  
índice em `order_id`  
índice em `changed_at`

Essa tabela é essencial para rastreabilidade operacional e relatórios de tempo de preparo.

12.2. Tabela `kitchen_print_logs`

Finalidade: registrar impressões operacionais.

Campos:  
`id`  
`company_id`  
`order_id`  
`print_type` – kitchen_ticket, cashier_receipt etc.  
`printed_by_user_id` NULL  
`printed_at`  
`status`  
`notes`

Índices:  
índice em `company_id`  
índice em `order_id`

13. Bloco de pagamentos e caixa

13.1. Tabela `payment_methods`

Finalidade: formas de pagamento disponíveis por empresa.

Campos:  
`id`  
`company_id`  
`name`  
`code` – pix, cash, credit_card, debit_card, online  
`status`  
`created_at`  
`updated_at`

Índices:  
índice em `company_id`  
índice composto em (`company_id`, `code`)

13.2. Tabela `payments`

Finalidade: pagamentos realizados para pedidos ou comandas.

Campos:  
`id`  
`company_id`  
`order_id` NULL  
`command_id` NULL  
`payment_method_id`  
`amount`  
`status` – pending, paid, failed, refunded, canceled  
`transaction_reference` NULL  
`paid_at` NULL  
`received_by_user_id` NULL  
`created_at`  
`updated_at`

Índices:  
índice em `company_id`  
índice em `order_id`  
índice em `command_id`  
índice em `payment_method_id`  
índice composto em (`company_id`, `status`)  
índice composto em (`company_id`, `paid_at`)

Observação: permitir `order_id` e `command_id` oferece flexibilidade.  
Na prática, uma política deve impedir inconsistência, como ambos nulos sem justificativa.

13.3. Tabela `cash_registers`

Finalidade: controle de abertura e fechamento de caixa.

Campos:  
`id`  
`company_id`  
`opened_by_user_id`  
`closed_by_user_id` NULL  
`opened_at`  
`closed_at` NULL  
`opening_amount`  
`closing_amount_reported` NULL  
`closing_amount_calculated` NULL  
`status` – open, closed  
`notes` NULL  
`created_at`  
`updated_at`

Índices:  
índice em `company_id`  
índice em `status`  
índice composto em (`company_id`, `opened_at`)

13.4. Tabela `cash_movements`

Finalidade: movimentações do caixa.

Campos:  
`id`  
`company_id`  
`cash_register_id`  
`payment_id` NULL  
`type` – income, expense, adjustment  
`description`  
`amount`  
`movement_at`  
`created_by_user_id`

Índices:  
índice em `company_id`  
índice em `cash_register_id`  
índice em `payment_id`  
índice em `movement_at`

14. Bloco de estoque

14.1. Tabela `stock_items`

Finalidade: itens controlados em estoque.

Campos:  
`id`  
`company_id`  
`product_id` NULL  
`name`  
`sku` NULL  
`current_quantity`  
`minimum_quantity`  
`unit_of_measure`  
`status`  
`created_at`  
`updated_at`

Índices:  
índice em `company_id`  
índice em `product_id`  
índice composto em (`company_id`, `status`)

14.2. Tabela `stock_movements`

Finalidade: registrar entradas e saídas de estoque.

Campos:  
`id`  
`company_id`  
`stock_item_id`  
`type` – entry, exit, adjustment  
`quantity`  
`reason`  
`reference_type` NULL – order, manual, purchase  
`reference_id` NULL  
`moved_by_user_id` NULL  
`moved_at`  
`created_at`

Índices:  
índice em `company_id`  
índice em `stock_item_id`  
índice em `reference_type`  
índice em `reference_id`

Observação crítica: para MVP, esse estoque pode ser simplificado.  
Se houver baixa automática real por insumo, será necessário evoluir para ficha técnica.

15. Bloco de entrega

15.1. Tabela `delivery_zones`

Finalidade: faixas ou zonas de entrega.

Campos:  
`id`  
`company_id`  
`name`  
`description` NULL  
`fee_amount`  
`minimum_order_amount` NULL  
`status`  
`created_at`  
`updated_at`

Índices:  
índice em `company_id`  
índice em `status`

15.2. Tabela `delivery_addresses`

Finalidade: endereços do cliente para entrega.

Campos:  
`id`  
`company_id`  
`customer_id`  
`label` – casa, trabalho  
`street`  
`number`  
`complement` NULL  
`neighborhood`  
`city`  
`state`  
`zip_code`  
`reference` NULL  
`delivery_zone_id` NULL  
`created_at`  
`updated_at`

Índices:  
índice em `company_id`  
índice em `customer_id`  
índice em `delivery_zone_id`

15.3. Tabela `deliveries`

Finalidade: controle operacional das entregas.

Campos:  
`id`  
`company_id`  
`order_id`  
`delivery_address_id`  
`delivery_user_id` NULL  
`status` – pending, assigned, in_route, delivered, failed, canceled  
`delivery_fee`  
`assigned_at` NULL  
`left_at` NULL  
`delivered_at` NULL  
`notes` NULL  
`created_at`  
`updated_at`

Índices:  
índice em `company_id`  
índice em `order_id`  
índice em `delivery_user_id`  
índice composto em (`company_id`, `status`)

16. Bloco de promoções e cupons

16.1. Tabela `promotions`

Finalidade: promoções ativas do estabelecimento.

Campos:  
`id`  
`company_id`  
`name`  
`description`  
`discount_type` – fixed, percent  
`discount_value`  
`starts_at`  
`ends_at`  
`minimum_order_amount` NULL  
`status`  
`created_at`  
`updated_at`

Índices:  
índice em `company_id`  
índice em `status`  
índice composto em (`company_id`, `starts_at`, `ends_at`)

16.2. Tabela `promotion_products`

Finalidade: vincular promoção a produtos específicos.

Campos:  
`id`  
`promotion_id`  
`product_id`

Índices:  
único em (`promotion_id`, `product_id`)

16.3. Tabela `coupons`

Finalidade: cupons promocionais.

Campos:  
`id`  
`company_id`  
`code`  
`description`  
`discount_type`  
`discount_value`  
`minimum_order_amount` NULL  
`usage_limit` NULL  
`used_count` DEFAULT 0  
`starts_at`  
`ends_at`  
`status`  
`created_at`  
`updated_at`

Índices:  
índice em `company_id`  
índice único composto em (`company_id`, `code`)  
índice em `status`

16.4. Tabela `coupon_usages`

Finalidade: histórico de uso de cupons.

Campos:  
`id`  
`company_id`  
`coupon_id`  
`customer_id` NULL  
`order_id`  
`used_at`

Índices:  
índice em `company_id`  
índice em `coupon_id`  
índice em `order_id`  
índice em `customer_id`

17. Bloco de auditoria e suporte

17.1. Tabela `audit_logs`

Finalidade: registrar ações críticas.

Campos:  
`id`  
`company_id` NULL  
`user_id` NULL  
`module`  
`action`  
`entity_type`  
`entity_id`  
`old_values_json` NULL  
`new_values_json` NULL  
`ip_address` NULL  
`user_agent` NULL  
`created_at`

Índices:  
índice em `company_id`  
índice em `user_id`  
índice em `module`  
índice em `entity_type`  
índice em `entity_id`

17.2. Tabela `support_tickets`

Finalidade: chamados de suporte da plataforma.

Campos:  
`id`  
`company_id`  
`opened_by_user_id`  
`assigned_to_user_id` NULL  
`subject`  
`description`  
`status` – open, in_progress, resolved, closed  
`priority`  
`created_at`  
`updated_at`  
`closed_at` NULL

Índices:  
índice em `company_id`  
índice em `status`  
índice em `priority`

18. Relacionamentos principais do sistema

18.1. Relacionamentos SaaS

`plans` 1:N `companies`  
`companies` 1:N `subscriptions`  
`subscriptions` 1:N `subscription_payments`

18.2. Relacionamentos de acesso

`roles` 1:N `users`  
`roles` N:N `permissions` via `role_permissions`  
`companies` 1:N `users`

18.3. Relacionamentos de catálogo

`companies` 1:N `categories`  
`categories` 1:N `products`  
`companies` 1:N `additional_groups`  
`additional_groups` 1:N `additional_items`  
`products` N:N `additional_groups` via `product_additional_groups`

18.4. Relacionamentos operacionais

`companies` 1:N `tables`  
`tables` 1:N `commands`  
`customers` 1:N `commands`  
`commands` 1:N `orders`  
`orders` 1:N `order_items`  
`order_items` 1:N `order_item_additionals`

18.5. Relacionamentos financeiros

`payment_methods` 1:N `payments`  
`cash_registers` 1:N `cash_movements`  
`payments` 1:N `cash_movements`

18.6. Relacionamentos logísticos

`customers` 1:N `delivery_addresses`  
`delivery_zones` 1:N `delivery_addresses`  
`orders` 1:1 ou 1:N lógico com `deliveries`, conforme política futura

19. Tabelas mais críticas para índices de performance

As tabelas que mais exigirão índices adequados são:

`products`  
`orders`  
`order_items`  
`commands`  
`payments`  
`cash_movements`  
`customers`  
`deliveries`  
`audit_logs`

Justificativa:  
essas tabelas serão consultadas com frequência para listagens, filtros, dashboards, relatórios e operação ao vivo.

20. Índices compostos recomendados

Além dos índices já citados, os mais importantes são:

em `orders`:  
(`company_id`, `status`, `created_at`)  
(`company_id`, `payment_status`, `created_at`)

em `products`:  
(`company_id`, `category_id`, `is_active`, `is_paused`)

em `commands`:  
(`company_id`, `table_id`, `status`)

em `payments`:  
(`company_id`, `status`, `paid_at`)

em `deliveries`:  
(`company_id`, `status`, `delivery_user_id`)

Esses índices favorecem filtros operacionais e relatórios por período.

21. Normalização e equilíbrio prático

A modelagem proposta busca equilíbrio entre normalização e desempenho transacional.

Ela é relativamente normalizada, mas preserva campos de snapshot em tabelas transacionais. Isso é proposital.

Exemplo:  
em `order_items`, guardar nome do produto e preço do momento da venda é melhor do que depender apenas do cadastro atual do produto.  
Sem isso, relatórios históricos podem ser corrompidos por alterações futuras no catálogo.

22. Campos de snapshot recomendados

Devem ser mantidos snapshots em:

`order_items.product_name_snapshot`  
`order_items.unit_price`  
`order_item_additionals.additional_name_snapshot`  
`order_item_additionals.unit_price`  
eventualmente `orders.customer_name`

Essa decisão fortalece consistência histórica.

23. Regras de integridade importantes

Algumas regras não devem ficar só no banco; devem ser reforçadas também na camada de service.

Exemplos:  
pedido não pode ser criado para empresa diferente da mesa/comanda;  
produto não pode ser adicionado se estiver inativo ou pausado;  
pagamento não pode exceder valor devido sem política explícita;  
comanda fechada não deve receber novo pedido;  
empresa suspensa não deve operar normalmente;  
cupom vencido não deve ser aplicado;  
entrega não pode ser concluída sem vínculo com pedido válido.

24. Tabelas opcionais para fase posterior

Para não inflar o MVP, algumas tabelas podem ser adiadas:

`notifications`  
`favorites`  
`customer_loyalty_points`  
`product_variations`  
`recipe_items`  
`inventory_purchases`  
`refunds`  
`online_payment_webhooks`

Essas evoluções são úteis, mas não essenciais na fase inicial.

25. Blocos priorizados para o MVP

No MVP, as tabelas indispensáveis são:

`companies`  
`plans`  
`users`  
`roles`  
`company_settings`  
`company_themes`  
`categories`  
`products`  
`additional_groups`  
`additional_items`  
`product_additional_groups`  
`tables`  
`commands`  
`customers`  
`orders`  
`order_items`  
`order_item_additionals`  
`order_status_history`  
`payment_methods`  
`payments`  
`cash_registers`  
`cash_movements`  
`delivery_zones`  
`deliveries`  
`coupons`  
`audit_logs`

26. Avaliação crítica da modelagem

A modelagem está adequada para um SaaS de operação alimentícia, mas há três pontos que merecem vigilância.

O primeiro é o risco de tentar detalhar estoque avançado cedo demais. Isso pode atrasar o projeto sem trazer valor imediato ao MVP.

O segundo é o risco de não separar bem pedido, comanda, pagamento e caixa. Esses quatro blocos precisam continuar independentes, embora integrados.

O terceiro é o risco de negligenciar snapshots históricos em nome de uma normalização excessiva. Em sistema comercial, histórico confiável vale mais que pureza acadêmica de modelagem.

27. Conclusão

A estrutura de banco de dados proposta fornece base sólida para o SaaS Menu Interativo operar como plataforma multiempresa, cobrindo catálogo, atendimento, operação, produção, financeiro, entrega, personalização e governança institucional.

A espinha dorsal do modelo está corretamente apoiada em `company_id`, na separação entre catálogo e transação, na distinção entre comanda e pedido, e na organização dos dados por domínios funcionais. Isso cria uma base consistente para a próxima etapa de detalhamento técnico.