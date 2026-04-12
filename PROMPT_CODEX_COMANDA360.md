Atue como Desenvolvedor PHP Sênior e continue o projeto **Comanda360** a partir do estado atual real do código.

## Regra principal
Não reinicie o projeto. Não troque a arquitetura. Não use framework. Continue exatamente a partir da base atual.

## Raiz real do projeto
Considere **comanda360/** como a raiz do código PHP.

## O que já existe
- MVC em PHP puro
- Front controller
- Router
- Request / Response
- Database PDO
- Session
- Auth
- ExceptionHandler
- AuthMiddleware
- módulo Auth
- módulo Produtos
- módulo Mesas
- módulo Comandas

## O que foi revisado
A base está funcional, mas há alguns ajustes obrigatórios de continuidade:
1. trocar `SaaS Menu` para `Comanda360` em `resources/views/layouts/app.php`
2. corrigir README para usar `seed_demo_comanda360.sql`
3. padronizar links/forms para `base_url()` quando possível
4. preservar `company_id` em toda nova regra de negócio
5. não quebrar a estrutura existente

## Próximo objetivo
Implementar o núcleo de **Pedidos**, ligando:
Mesa -> Comanda -> Pedido -> Itens do Pedido

## Escopo exato da próxima entrega
Crie:
- `app/Repositories/OrderRepository.php`
- `app/Repositories/OrderItemRepository.php`
- `app/Services/Admin/OrderService.php`
- `app/Controllers/Admin/OrderController.php`
- views em `resources/views/admin/orders/`

Implemente:
- listagem de pedidos por empresa
- criação de pedido a partir de comanda aberta
- seleção de produtos da empresa autenticada
- quantidade
- observação do item
- gravação em `orders`
- gravação em `order_items`
- geração consistente de `order_number`
- cálculo de `subtotal_amount`, `discount_amount`, `delivery_fee`, `total_amount`
- `status` inicial do pedido
- `payment_status` inicial do pedido
- snapshot de nome e preço do produto no item

## Regras obrigatórias
- só criar pedido para comanda aberta
- comanda deve pertencer à empresa autenticada
- produtos devem pertencer à empresa autenticada
- item deve ter quantidade >= 1
- controllers sem SQL
- services com regra de negócio
- repositories com persistência
- views apenas apresentação

## Forma da resposta
Na sua resposta:
1. explique rapidamente o que será implementado
2. mostre a árvore dos arquivos novos/alterados
3. entregue o código completo dos arquivos novos/alterados
4. não entregue pseudocódigo
5. não resuma demais
6. preserve compatibilidade com a base atual
