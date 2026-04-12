Sistema: SaaS Menu Interativo  
Tipo de documento: Arquitetura de software e organização técnica  
Objetivo: Definir a arquitetura MVC modular do sistema, estabelecendo camadas, responsabilidades, estrutura de diretórios, fluxo técnico, padrão de organização de módulos, papel de controllers, services, repositories, views e demais componentes da aplicação.

1. Finalidade do documento

Este documento define a arquitetura técnica base do SaaS Menu Interativo, com foco em organização profissional do código, separação de responsabilidades, escalabilidade, segurança, manutenção e preparação para operação SaaS multiempresa.

A adoção de arquitetura MVC em PHP puro é adequada ao projeto, desde que não seja implementada de forma simplista. Em muitos projetos, o problema não está em usar PHP puro, mas em misturar HTML, SQL, regras de negócio e controle de sessão no mesmo arquivo. Isso gera acoplamento excessivo, baixa rastreabilidade e dificuldade de evolução.

Por isso, a arquitetura aqui proposta não deve ser entendida como “apenas MVC”, mas como um MVC modular com camadas de serviço, repositórios, componentes compartilhados e organização orientada a domínio funcional.

2. Princípios arquiteturais do sistema

A arquitetura do sistema deverá seguir os seguintes princípios:

separação clara de responsabilidades;  
baixo acoplamento entre camadas;  
alta coesão por módulo;  
isolamento da regra de negócio;  
isolamento do acesso a dados;  
reutilização de componentes;  
facilidade de manutenção;  
suporte à escalabilidade funcional;  
preparação para ambiente multiempresa;  
segurança por contexto de acesso;  
compatibilidade com evolução futura sem reescrita total.

3. Modelo arquitetural adotado

O sistema será desenvolvido com uma arquitetura baseada em:

PHP puro profissional  
MVC modular  
camada de services  
camada de repositories  
camada de validação  
camada de apoio com helpers, middlewares e componentes compartilhados  
views organizadas por contexto funcional  
roteamento centralizado  
gestão de sessão e autenticação separadas por contexto  
integração com MySQL ou MariaDB

A arquitetura pode ser resumida assim:

Request  
→ Router  
→ Controller  
→ Service  
→ Repository  
→ Banco de dados

e no retorno:

Banco de dados  
→ Repository  
→ Service  
→ Controller  
→ View / Response

4. Estrutura conceitual das camadas

4.1. Camada de entrada da requisição

É responsável por receber a requisição HTTP, interpretar a URL, o método da requisição, os dados enviados e o contexto do usuário.

Componentes envolvidos:  
Front Controller  
Router  
Request  
Middlewares  
Session/Auth Context

Funções:  
receber todas as requisições pelo ponto central da aplicação;  
encaminhar a rota para o controller correto;  
executar validações preliminares de acesso;  
carregar contexto da empresa e do usuário autenticado;  
padronizar entrada de dados.

4.2. Camada de controle

É composta pelos controllers. Seu papel é orquestrar a requisição, chamar serviços apropriados e definir a resposta.

O controller não deve:  
conter SQL;  
implementar regra de negócio complexa;  
misturar lógica operacional com renderização extensa;  
manipular diretamente a persistência.

O controller deve:  
receber dados da requisição;  
acionar validações simples de entrada;  
invocar services;  
encaminhar dados para views ou respostas JSON;  
controlar fluxo da interface.

4.3. Camada de regra de negócio

É composta pelos services. Essa é a camada central da aplicação.

O service deve:  
conter regras operacionais e gerenciais;  
executar fluxos completos de negócio;  
coordenar chamadas a múltiplos repositories;  
validar coerência do processo;  
aplicar regras por perfil, empresa e contexto operacional;  
orquestrar transações quando necessário.

Exemplo: fechar uma comanda não é “apenas atualizar um status”. Pode envolver:  
validar se a comanda existe;  
validar se pertence à empresa correta;  
validar se não há itens pendentes;  
validar se há pagamento registrado ou pendência autorizada;  
atualizar status da comanda;  
atualizar mesa;  
registrar log;  
eventualmente gerar evento de impressão ou comprovante.

Esse fluxo pertence ao service, não ao controller.

4.4. Camada de persistência

É composta pelos repositories.

O repository deve:  
consultar dados;  
persistir dados;  
abstrair SQL do restante do sistema;  
isolar operações por tabela ou agregado lógico;  
fornecer métodos claros e reutilizáveis.

O repository não deve:  
tomar decisão de negócio;  
validar regra operacional complexa;  
controlar fluxo da interface.

4.5. Camada de apresentação

É composta pelas views e componentes visuais.

Essa camada deve:  
renderizar páginas HTML;  
organizar layouts;  
exibir dados;  
incluir componentes reutilizáveis;  
trabalhar com CSS e JS organizados por contexto.

Ela não deve:  
executar SQL;  
decidir regras de negócio;  
recalcular regras críticas que já foram validadas no backend.

5. Estrutura arquitetural recomendada

A estrutura recomendada para o projeto é a seguinte:

/public  
/app  
/config  
/routes  
/storage  
/bootstrap  
/vendor

6. Estrutura detalhada de diretórios

6.1. Diretório public

É a porta de entrada pública da aplicação e o único diretório que deve ficar exposto diretamente no ambiente web.

Estrutura sugerida:

/public  
index.php  
/assets  
/css  
/js  
/images  
/uploads  
/.htaccess

Responsabilidades:  
index.php como front controller;  
assets estáticos;  
arquivos públicos controlados;  
regras de reescrita via .htaccess.

Observação crítica: uploads precisam de tratamento cuidadoso. Nem tudo que for enviado pelo usuário deve ficar livremente acessível por URL pública.

6.2. Diretório app

É o núcleo da aplicação.

Estrutura sugerida:

/app  
/Core  
/Controllers  
/Services  
/Repositories  
/Models  
/Validators  
/Middlewares  
/Helpers  
/Traits  
/Policies  
/DTOs  
/Exceptions  
/View  
/Components

7. Descrição das pastas do núcleo

7.1. /app/Core

Contém a infraestrutura central do framework interno da aplicação.

Arquivos e componentes sugeridos:  
App.php  
Router.php  
Request.php  
Response.php  
Controller.php  
View.php  
Database.php  
Session.php  
Auth.php  
Env.php  
Container.php, se necessário no futuro

Responsabilidades:  
bootstrap interno;  
roteamento;  
injeção básica de dependências, se adotada;  
conexão com banco;  
resposta HTTP;  
sessão;  
autenticação;  
base abstrata para controllers e views.

7.2. /app/Controllers

Contém os controllers organizados por área e por módulo.

Estrutura sugerida:

/app/Controllers  
/Public  
/Operational  
/Admin  
/Saas

Exemplos:  
Public/MenuController.php  
Public/OrderController.php  
Operational/TableController.php  
Operational/KitchenController.php  
Operational/CashierController.php  
Admin/ProductController.php  
Admin/ReportController.php  
Admin/UserController.php  
Saas/CompanyController.php  
Saas/PlanController.php

Essa separação é superior a uma pasta única de controllers, porque respeita o contexto de acesso e reduz confusão entre módulos que têm nomes semelhantes, mas funções diferentes.

7.3. /app/Services

Contém a lógica de negócio.

Estrutura sugerida:

/app/Services  
/Public  
/Operational  
/Admin  
/Saas  
/Shared

Exemplos:  
Public/MenuService.php  
Public/CartService.php  
Operational/OrderFlowService.php  
Operational/TableService.php  
Operational/KitchenService.php  
Operational/CashierService.php  
Admin/ProductService.php  
Admin/StockService.php  
Admin/CustomizationService.php  
Saas/CompanyService.php  
Saas/SubscriptionService.php  
Shared/AuthService.php  
Shared/AuditService.php

7.4. /app/Repositories

Contém acesso a dados.

Estrutura sugerida:

/app/Repositories  
/MenuRepository.php  
/ProductRepository.php  
/CategoryRepository.php  
/AdditionalRepository.php  
/TableRepository.php  
/CommandRepository.php  
/OrderRepository.php  
/PaymentRepository.php  
/CustomerRepository.php  
/StockRepository.php  
/DeliveryRepository.php  
/UserRepository.php  
/CompanyRepository.php  
/SubscriptionRepository.php  
/AuditLogRepository.php

Critério de organização:  
um repository por agregado lógico ou por entidade principal;  
consultas complexas podem ser especializadas, mas sem virar repositório desorganizado.

7.5. /app/Models

Contém modelos de entidade ou estruturas de dados da aplicação.

Em PHP puro, os models podem ser leves, com foco em representação de dados, casts, apoio semântico e pequenas validações locais. Eles não devem virar classes gigantes que misturam persistência, regra de negócio e renderização.

Exemplos:  
Product.php  
Order.php  
Table.php  
Command.php  
Customer.php  
Company.php  
User.php

7.6. /app/Validators

Contém validadores específicos.

Exemplos:  
ProductValidator.php  
OrderValidator.php  
LoginValidator.php  
PaymentValidator.php  
CompanyValidator.php

Essa camada evita concentrar toda validação em controller ou service.  
Há dois tipos de validação que convém separar:

validação estrutural: campos obrigatórios, formato, tipo, comprimento;  
validação de negócio: coerência da operação, disponibilidade, permissões, estados válidos.

A validação estrutural pode ficar mais próxima do validator; a de negócio, no service.

7.7. /app/Middlewares

Contém filtros executados antes do controller.

Exemplos:  
AuthMiddleware.php  
GuestMiddleware.php  
CompanyContextMiddleware.php  
RoleMiddleware.php  
CsrfMiddleware.php  
SubscriptionActiveMiddleware.php

Esses middlewares serão decisivos para o SaaS. Por exemplo:  
um administrador de estabelecimento não deve acessar área SaaS global;  
um usuário de empresa inadimplente pode ter restrição de uso, conforme regra comercial;  
um operador deve acessar apenas rotas compatíveis com seu perfil.

7.8. /app/Helpers

Contém funções utilitárias compartilhadas.

Exemplos:  
format_currency.php  
format_datetime.php  
slug.php  
uuid.php  
sanitize.php  
qrcode_helper.php

Cuidado: helper não deve virar depósito genérico de lógica de negócio.

7.9. /app/Policies

Contém regras de autorização por módulo ou entidade.

Exemplos:  
OrderPolicy.php  
CommandPolicy.php  
UserPolicy.php  
SubscriptionPolicy.php

Essa camada é útil para checar permissões finas, além dos perfis fixos.

7.10. /app/DTOs

Contém objetos de transferência de dados.

Exemplos:  
CreateOrderDTO.php  
UpdateProductDTO.php  
FinalizePaymentDTO.php  
CreateCompanyDTO.php

Essa camada não é obrigatória no início, mas é muito recomendável em fluxos críticos para reduzir arrays soltos, aumentar clareza e melhorar manutenção.

7.11. /app/Exceptions

Contém exceções customizadas.

Exemplos:  
ValidationException.php  
UnauthorizedException.php  
BusinessRuleException.php  
NotFoundException.php  
SubscriptionInactiveException.php

7.12. /app/View e /app/Components

/app/View pode conter a infraestrutura de renderização.  
/app/Components pode conter componentes reutilizáveis de interface.

8. Organização das views

As views devem ser separadas por contexto de navegação.

Estrutura sugerida:

/resources/views  
/layouts  
/components  
/public  
/menu  
/order  
/account  
/operational  
/dashboard  
/tables  
/commands  
/orders  
/kitchen  
/cashier  
/delivery  
/admin  
/dashboard  
/products  
/categories  
/additionals  
/customers  
/promotions  
/stock  
/settings  
/users  
/reports  
/saas  
/dashboard  
/companies  
/plans  
/subscriptions  
/billing  
/support

9. Layouts recomendados

O sistema deve ter layouts distintos por área.

Exemplos:  
layout-public.php  
layout-operational.php  
layout-admin.php  
layout-saas.php  
layout-auth.php

Isso é importante porque:  
a área pública exige foco em experiência do cliente;  
a área operacional exige rapidez e legibilidade;  
a área administrativa exige densidade informacional;  
a área SaaS exige visão institucional e comercial.

10. Estrutura de rotas

O diretório /routes deve concentrar a definição de rotas por contexto.

Estrutura sugerida:

/routes  
web.php  
public.php  
operational.php  
admin.php  
saas.php  
api.php, se necessário futuramente

Exemplos de grupos de rota:

/menu  
/menu/produto/{slug}  
/conta  
/pedido/finalizar

/operacional/mesas  
/operacional/comandas  
/operacional/pedidos  
/operacional/producao  
/operacional/caixa  
/operacional/entregas

/admin/dashboard  
/admin/produtos  
/admin/categorias  
/admin/adicionais  
/admin/clientes  
/admin/promocoes  
/admin/estoque  
/admin/configuracoes  
/admin/usuarios  
/admin/relatorios

/saas/dashboard  
/saas/empresas  
/saas/planos  
/saas/assinaturas  
/saas/cobrancas  
/saas/suporte

11. Fluxo técnico padrão da requisição

11.1. Exemplo: cadastro de produto

1. Usuário autenticado acessa /admin/produtos/novo
2. Router resolve a rota
3. Middleware valida autenticação e perfil
4. ProductController recebe a requisição
5. ProductValidator valida estrutura básica
6. ProductService aplica regra de negócio
7. ProductRepository persiste os dados
8. AuditService registra ação
9. Controller retorna redirect com mensagem de sucesso

11.2. Exemplo: cliente envia pedido

1. Cliente monta pedido no cardápio
2. Request envia dados do pedido
3. Router resolve para OrderController
4. Validator valida formato dos dados
5. OrderService valida mesa, empresa, itens, adicionais, horário, disponibilidade e regras do pedido
6. OrderRepository grava pedido
7. CommandRepository atualiza comanda
8. PrintService ou QueueService dispara ação de impressão, se aplicável
9. Controller retorna resposta para acompanhamento do pedido
10. Organização por domínio funcional

A arquitetura deve refletir os domínios principais do sistema.

Domínios recomendados:  
Autenticação e acesso  
Empresas e assinaturas  
Cardápio e catálogo  
Mesas e comandas  
Pedidos  
Produção  
Caixa e pagamentos  
Clientes  
Estoque  
Entregas  
Relatórios  
Personalização visual  
Auditoria e suporte

Esse mapeamento ajuda a evitar controllers e services genéricos demais.

13. Controllers recomendados por domínio

Exemplos principais:

AuthController  
MenuController  
ProductController  
CategoryController  
AdditionalController  
TableController  
CommandController  
OrderController  
KitchenController  
CashierController  
PaymentController  
DeliveryController  
CustomerController  
PromotionController  
CouponController  
StockController  
SettingsController  
CustomizationController  
UserController  
ReportController  
CompanyController  
PlanController  
SubscriptionController  
BillingController  
SupportController

14. Services recomendados por domínio

Exemplos principais:

AuthService  
MenuService  
CatalogService  
ProductService  
CategoryService  
AdditionalService  
TableService  
CommandService  
OrderService  
OrderFlowService  
KitchenService  
CashierService  
PaymentService  
DeliveryService  
CustomerService  
PromotionService  
CouponService  
StockService  
SettingsService  
CustomizationService  
UserService  
ReportService  
CompanyService  
PlanService  
SubscriptionService  
BillingService  
SupportService  
AuditService  
NotificationService  
PrintService

Observação crítica: OrderService e OrderFlowService podem coexistir.  
Um cuida do CRUD e manutenção lógica do pedido;  
o outro cuida da orquestração do fluxo operacional entre estados do pedido.

15. Repositories recomendados por domínio

Exemplos:

UserRepository  
RoleRepository  
PermissionRepository  
CompanyRepository  
SubscriptionRepository  
ThemeRepository  
ProductRepository  
CategoryRepository  
AdditionalGroupRepository  
AdditionalItemRepository  
TableRepository  
CommandRepository  
CommandItemRepository  
OrderRepository  
OrderItemRepository  
PaymentRepository  
CashRegisterRepository  
CustomerRepository  
StockRepository  
StockMovementRepository  
DeliveryRepository  
CouponRepository  
PromotionRepository  
AuditLogRepository  
SupportTicketRepository

16. Separação entre módulos e camadas

Um erro comum é tratar “módulo” e “camada” como a mesma coisa. Não são.

Camadas:  
controller, service, repository, view, validator, middleware

Módulos:  
produtos, pedidos, comandas, caixa, relatórios, empresas

A organização correta é cruzar as duas dimensões.

Exemplo:  
Módulo Produtos  
→ ProductController  
→ ProductService  
→ ProductRepository  
→ views/admin/products

17. Estratégia para SaaS multiempresa

Como o sistema será SaaS, a arquitetura deve prever isolamento por empresa desde o início.

Cada entidade operacional relevante deverá carregar vínculo com company_id ou tenant_id, conforme nomenclatura adotada.

Exemplos:  
produtos  
categorias  
comandas  
mesas  
pedidos  
clientes  
usuários internos  
promoções  
configurações  
relatórios derivados

Regras essenciais:  
todo acesso deve considerar o contexto da empresa logada;  
queries devem filtrar company_id;  
services devem validar se o registro pertence à empresa ativa;  
administradores globais podem ter regras excepcionais, mas auditadas.

Esse ponto é crítico. Se for ignorado no início, a correção posterior será estruturalmente cara.

18. Gestão de autenticação e sessão

O sistema deve possuir contextos distintos de autenticação.

Contextos recomendados:  
cliente público contextual  
usuário interno do estabelecimento  
usuário institucional do SaaS

Isso evita misturar sessão de cliente com sessão administrativa.

Estrutura sugerida:  
AuthService  
SessionManager  
middlewares específicos por contexto  
guards lógicos por área

19. Estratégia de segurança arquitetural

A arquitetura deve prever:

CSRF em formulários internos  
sanitização de entrada  
hash seguro de senha  
controle de sessão  
validação de perfil  
isolamento por empresa  
logs de auditoria  
tratamento padronizado de exceções  
proteção contra acesso direto a arquivos sensíveis  
uploads validados por tipo, tamanho e destino

20. Estratégia de tratamento de erros

O sistema deve adotar tratamento centralizado de exceções.

Fluxo recomendado:  
camadas internas lançam exceções específicas;  
controller ou handler central interpreta;  
usuário recebe mensagem amigável;  
erro técnico detalhado fica em log.

Tipos de erro:  
validação  
autorização  
recurso não encontrado  
regra de negócio  
erro de persistência  
erro inesperado

21. Estratégia de transações

Operações que afetam múltiplas entidades devem usar transação de banco.

Exemplos:  
fechamento de comanda  
pagamento + baixa financeira  
cancelamento com reversão  
criação de pedido + itens + atualização de comanda  
ativação ou suspensão de assinatura

Essa responsabilidade deve ficar nos services, com apoio da camada de banco.

22. Estratégia de componentes compartilhados

O sistema deverá possuir componentes compartilhados para evitar repetição.

Exemplos:  
breadcrumbs  
alertas de sistema  
tabelas paginadas  
cards de status  
modais padrão  
formulários com validação visual  
cabeçalho por área  
sidebar por área  
rodapé institucional

Esses componentes devem ficar centralizados em views/components ou estrutura equivalente.

23. Organização dos assets

Estrutura sugerida:

/public/assets  
/css  
/js  
/images  
/icons  
/uploads  
/vendors, se necessário

Separação sugerida:  
css por área  
js por módulo  
scripts pequenos específicos por tela  
evitar um único arquivo JS gigante

Exemplo:  
public/assets/js/admin/products/form.js  
public/assets/js/operational/orders/status.js  
public/assets/css/public/menu.css  
public/assets/css/admin/dashboard.css

24. Padrão de nomenclatura

Recomendação:  
classes em PascalCase  
métodos em camelCase  
rotas em slug ou padrão estável  
pastas em nomes claros e previsíveis  
arquivos alinhados ao nome da classe

Exemplo:  
ProductController.php  
ProductService.php  
ProductRepository.php  
CreateProductDTO.php

25. Estrutura resumida de pastas proposta

Exemplo consolidado:

/public  
index.php  
/assets  
/css  
/js  
/images  
/uploads

/app  
/Core  
/Controllers  
/Public  
/Operational  
/Admin  
/Saas  
/Services  
/Public  
/Operational  
/Admin  
/Saas  
/Shared  
/Repositories  
/Models  
/Validators  
/Middlewares  
/Helpers  
/Policies  
/DTOs  
/Exceptions  
/View  
/Components

/config  
app.php  
database.php  
auth.php  
saas.php

/routes  
public.php  
operational.php  
admin.php  
saas.php  
web.php

/resources  
/views  
/layouts  
/components  
/public  
/operational  
/admin  
/saas

/storage  
/logs  
/cache  
/temp  
/exports

/bootstrap  
app.php

/vendor

26. Benefícios da arquitetura proposta

Essa arquitetura entrega as seguintes vantagens:

facilidade de expansão por módulos;  
organização compatível com projeto SaaS;  
separação clara entre interface e negócio;  
redução de código duplicado;  
maior segurança na manutenção;  
melhor testabilidade futura;  
maior previsibilidade para novos desenvolvedores;  
melhor aderência ao deploy em Hostinger.

27. Limites e cuidados da arquitetura

Apesar de sólida, essa arquitetura exige disciplina. Há riscos práticos a evitar:

colocar regra de negócio no controller;  
usar repository como service disfarçado;  
transformar helper em depósito de tudo;  
misturar área pública e área administrativa nas mesmas views;  
não aplicar company_id em toda consulta relevante;  
acoplar JavaScript demais ao HTML sem padrão;  
criar services gigantes e genéricos demais.

O ideal é manter foco em coesão por domínio.

28. Conclusão

A arquitetura MVC proposta para o SaaS Menu Interativo é adequada ao porte e à complexidade do projeto, desde que implementada com modularidade real, separação de camadas e visão de produto SaaS multiempresa desde o início.

A estrutura apresentada prepara o sistema para operar com segurança, manutenção controlada e crescimento progressivo, sem cair na armadilha de um “PHP puro improvisado”. O ponto mais importante é que controllers, services e repositories cumpram papéis distintos e consistentes, e que o contexto da empresa assinante esteja presente em toda a espinha dorsal do sistema.