
Sistema: SaaS Menu Interativo  
Tipo de documento: Estrutura funcional e navegação do sistema  
Objetivo: Definir os módulos do sistema, a organização das páginas, a hierarquia de navegação, a separação entre áreas pública, operacional, administrativa e SaaS, bem como a lógica macro de circulação dos usuários.

1. Finalidade deste documento

Este documento estabelece a estrutura macro de navegação do SaaS Menu Interativo, definindo como o sistema será organizado em módulos, áreas, páginas e subpáginas. Seu objetivo é criar uma base lógica estável para o desenvolvimento posterior da arquitetura MVC, do banco de dados, das rotas, das permissões e da interface.

Este documento também corrige um ponto importante: em sistemas desse tipo, muitos projetos falham por não separar corretamente o que pertence ao cliente final, ao operador do estabelecimento, ao gestor do negócio e ao administrador da plataforma SaaS. Aqui essa separação passa a ser formal.

2. Princípio geral de organização

O sistema será dividido em quatro grandes áreas de navegação:

Área pública do cliente  
Área operacional do estabelecimento  
Área administrativa do estabelecimento  
Área administrativa global do SaaS

Essa divisão é a mais adequada porque separa claramente os contextos de uso, reduz mistura de responsabilidades e melhora segurança, escalabilidade e usabilidade.

3. Estrutura macro do sistema

3.1. Área pública do cliente

É o ambiente de interação do consumidor final com o estabelecimento. Essa área deve ser leve, rápida, responsiva e intuitiva. Seu foco é consulta de cardápio, montagem do pedido, acompanhamento da conta e interação básica com o atendimento.

Módulos da área pública:  
Cardápio digital  
Comanda do cliente  
Minha conta  
Finalização do pedido  
Acompanhamento de status  
Página institucional pública do estabelecimento

3.2. Área operacional do estabelecimento

É o ambiente interno de uso diário da equipe operacional. Nessa área ficam os fluxos de atendimento, comandas, produção, caixa e entrega.

Módulos da área operacional:  
Painel operacional  
Mesas e comandas  
Pedidos  
Atendimento do garçom  
Produção/cozinha  
Caixa/PDV  
Entregas e motoboys  
Impressões operacionais

3.3. Área administrativa do estabelecimento

É o ambiente gerencial e de configuração da empresa assinante. Aqui são definidos produtos, categorias, adicionais, horários, promoções, taxas, usuários internos, personalização visual e relatórios.

Módulos da área administrativa:  
Dashboard gerencial  
Catálogo de produtos  
Categorias  
Adicionais  
Clientes  
Promoções e cupons  
Estoque  
Taxas e entregas  
Configurações comerciais  
Personalização visual  
Usuários e permissões  
Relatórios e financeiro

3.4. Área administrativa global do SaaS

É o ambiente do proprietário da plataforma, responsável pela governança do produto como serviço. Essa área administra empresas assinantes, planos, cobranças, suporte, métricas globais e parâmetros institucionais do sistema.

Módulos da área SaaS:  
Painel global  
Gestão de empresas  
Planos e assinaturas  
Cobranças  
Usuários administradores da plataforma  
Suporte  
Parâmetros globais  
Monitoramento institucional

4. Regra central de hierarquia

A navegação do sistema seguirá uma hierarquia de quatro níveis:

Nível 1: Área  
Nível 2: Módulo  
Nível 3: Página principal do módulo  
Nível 4: Ações e subpáginas

Exemplo conceitual:

Área administrativa do estabelecimento  
→ Módulo de produtos  
→ Página de listagem de produtos  
→ Ações: cadastrar, editar, pausar, excluir, visualizar histórico

Essa estrutura deve orientar rotas, menus laterais, breadcrumbs, permissões e organização dos controllers.

5. Mapa da área pública do cliente

5.1. Página de entrada do estabelecimento

Função: ser a porta principal de acesso ao ambiente público do estabelecimento.

Elementos previstos:  
Banner principal  
Logo  
Título do estabelecimento  
Descrição curta  
Informações operacionais  
Botão de iniciar pedido  
Link para cardápio  
Avisos de horário de funcionamento  
Informação sobre pedido mínimo  
Informação sobre formas de pagamento

Essa página pode ser acessada por link público direto ou redirecionada após leitura do QR Code.

5.2. Página do cardápio digital

Função: exibir o catálogo de produtos para navegação e seleção.

Elementos previstos:  
Banner  
Logo  
Título  
Descrição  
Número da mesa, quando houver  
Nome do cliente, quando identificado  
Botão “chamar garçom”  
Botão “minha conta”  
Campo de pesquisa  
Categorias em abas  
Cards de produtos com nome, descrição, foto e valor  
Selo de promoção, indisponibilidade ou destaque  
Botão de adicionar produto

Subfluxos:  
Abrir detalhes do produto  
Adicionar item à comanda  
Ir para adicionais  
Pesquisar produto  
Filtrar por categoria

5.3. Página/modal de abertura da comanda

Função: registrar o contexto do cliente antes dos pedidos.

Campos previstos:  
Mesa, automática via QR Code quando aplicável  
Nome do cliente  
Identificação opcional adicional, se o negócio desejar

Observação técnica importante: essa etapa pode ser modal ou página. Em termos de experiência, modal é adequado quando o fluxo é curto. Em termos de robustez, página dedicada pode ser melhor se houver mais regras. Para o MVP, modal é aceitável.

5.4. Página de detalhes e adicionais do produto

Função: permitir a montagem detalhada do item antes da inclusão no pedido.

Elementos previstos:  
Nome do produto  
Imagem  
Descrição  
Preço base  
Grupos de adicionais  
Escolha de quantidade permitida por grupo  
Itens adicionais com valor  
Observações  
Total parcial do item  
Botão de confirmar

Essa página é crítica. Ela deve respeitar regras variáveis por estabelecimento, como opcionais, obrigatórios, limite mínimo e máximo de escolhas.

5.5. Página “Minha conta”

Função: permitir ao cliente consultar sua situação de consumo.

Elementos previstos:  
Resumo da mesa  
Resumo por comanda/pessoa  
Itens consumidos  
Quantidade por item  
Valores por pessoa  
Total da mesa  
Situação do pagamento  
Ações permitidas, como solicitar fechamento

Esse módulo deve existir de forma clara, porque em ambiente de mesa ele reduz atrito com a equipe.

5.6. Página de finalização do pedido

Função: consolidar o pedido antes do envio.

Elementos previstos:  
Lista de produtos  
Observações  
Valor total  
Forma de entrega ou consumo  
Botão de confirmar pedido

Em pedidos por mesa, essa etapa finaliza o envio para o fluxo operacional. Em delivery ou retirada, futuramente pode incluir endereço e forma de pagamento.

5.7. Página de acompanhamento de status

Função: informar o progresso do pedido ao cliente.

Status recomendados:  
Recebido  
Em preparo  
Pronto  
Saiu para entrega, quando aplicável  
Finalizado

Esse recurso melhora a experiência do usuário e reduz perguntas operacionais.

6. Mapa da área operacional do estabelecimento

6.1. Painel operacional

Função: concentrar a visão resumida da operação em tempo real.

Elementos previstos:  
Pedidos em aberto  
Pedidos em preparo  
Mesas ocupadas  
Chamados de garçom  
Pagamentos pendentes  
Entregas em andamento  
Alertas operacionais

Esse painel deve ser objetivo. Não é dashboard executivo; é painel de operação viva.

6.2. Módulo de mesas

Página principal: listagem visual das mesas

Função: mostrar status de cada mesa e permitir acesso rápido às comandas vinculadas.

Status possíveis:  
Livre  
Ocupada  
Aguardando pedido  
Aguardando fechamento  
Fechada  
Bloqueada

Ações:  
Abrir mesa  
Visualizar mesa  
Acessar comandas  
Transferir comanda  
Solicitar fechamento  
Encerrar mesa

6.3. Módulo de comandas

Página principal: listagem de comandas abertas

Função: gerenciar pedidos associados a uma mesa e/ou cliente.

Ações:  
Criar comanda  
Visualizar comanda  
Adicionar item  
Editar item  
Cancelar item  
Fechar comanda  
Transferir comanda  
Unificar comandas, se permitido  
Separar conta, se previsto em fase futura

Aqui há um ponto estrutural importante: mesa e comanda não são a mesma coisa. Uma mesa pode conter uma ou mais comandas. Essa distinção deve permanecer em todo o projeto.

6.4. Módulo de pedidos

Página principal: fila de pedidos

Função: organizar o fluxo dos pedidos enviados pelo cliente ou pela equipe.

Filtros sugeridos:  
Todos  
Novos  
Em preparo  
Prontos  
Entregues  
Finalizados  
Cancelados

Ações:  
Visualizar pedido  
Alterar status  
Enviar para produção  
Marcar como pronto  
Finalizar pedido  
Cancelar pedido

6.5. Módulo do garçom/atendimento

Função: oferecer interface operacional rápida para atendimento em salão.

Recursos:  
Localizar mesa  
Abrir comanda  
Lançar pedido manualmente  
Consultar itens da mesa  
Responder chamado do cliente  
Solicitar fechamento  
Ver histórico recente da mesa

Essa interface precisa ser extremamente enxuta. Não deve reproduzir toda a administração.

6.6. Módulo de produção/cozinha

Função: controlar execução dos pedidos.

Página principal:  
Painel de produção por status

Elementos:  
Número do pedido  
Mesa/comanda  
Itens  
Observações  
Hora de entrada  
Tempo de preparo  
Prioridade

Ações:  
Aceitar pedido  
Iniciar preparo  
Marcar como pronto  
Sinalizar problema  
Reimprimir ticket

6.7. Módulo de caixa/PDV

Função: controlar pagamentos e fechamento das operações.

Página principal:  
Painel de pagamentos e comandas pendentes

Recursos:  
Consultar pedido ou comanda  
Visualizar total  
Aplicar desconto autorizado  
Aplicar cupom  
Registrar forma de pagamento  
Confirmar pagamento  
Marcar pagamento pendente  
Emitir comprovante  
Fechamento de caixa

Aqui haverá, futuramente, distinção entre pagamento de pedido individual, pagamento por comanda e fechamento da mesa.

6.8. Módulo de entregas e motoboys

Função: acompanhar a logística de entrega.

Recursos:  
Fila de pedidos para entrega  
Motoboy responsável  
Taxa de entrega aplicada  
Endereço do pedido  
Status da saída  
Status da entrega  
Histórico de entregas

Para o MVP, o controle pode ser operacional simples, sem geolocalização em tempo real.

6.9. Módulo de impressão operacional

Função: consolidar todas as ações de impressão automática e manual.

Tipos de impressão:  
Ticket de produção  
Ticket de balcão  
Comprovante de pagamento  
Resumo da comanda  
Fechamento de caixa

7. Mapa da área administrativa do estabelecimento

7.1. Dashboard gerencial

Função: dar visão resumida da performance do negócio.

Indicadores sugeridos:  
Total de pedidos do dia  
Faturamento do dia  
Ticket médio  
Produtos mais vendidos  
Pedidos por canal  
Pagamentos pendentes  
Clientes recorrentes  
Status operacional atual

Essa página é gerencial, não operacional. Não deve ficar poluída com detalhes de execução.

7.2. Módulo de produtos

Página principal:  
Listagem de produtos

Ações:  
Cadastrar  
Editar  
Visualizar  
Pausar  
Ativar  
Excluir logicamente  
Ordenar exibição  
Vincular categoria  
Vincular adicionais  
Definir preço  
Definir imagem  
Definir descrição  
Definir destaque

7.3. Módulo de categorias

Função: organizar o catálogo.

Ações:  
Cadastrar categoria  
Editar  
Ordenar  
Ativar/desativar  
Definir exibição no menu

7.4. Módulo de adicionais

Função: configurar grupos de complementos e escolhas.

Ações:  
Cadastrar grupo  
Definir obrigatoriedade  
Definir mínimo e máximo de seleção  
Cadastrar item adicional  
Definir valor  
Vincular grupo a produtos

Esse módulo precisa ficar destacado, pois ele impacta diretamente a lógica do carrinho e do pedido.

7.5. Módulo de clientes

Função: registrar histórico e perfil básico de clientes.

Dados previstos:  
Nome  
Contato  
Histórico de pedidos  
Valor acumulado  
Preferências futuras, se desejado

7.6. Módulo de promoções e cupons

Função: administrar incentivos comerciais.

Ações:  
Cadastrar promoção  
Cadastrar cupom  
Definir vigência  
Definir critérios  
Definir desconto fixo ou percentual  
Restringir por produto, categoria ou pedido mínimo

7.7. Módulo de estoque

Função: controlar disponibilidade de itens.

Ações:  
Registrar quantidade  
Baixa manual  
Pausar produto  
Definir limite mínimo  
Visualizar alertas de indisponibilidade  
Consultar movimentações

7.8. Módulo de taxas e entregas

Função: configurar regras de entrega.

Ações:  
Cadastrar taxa por bairro, faixa ou regra  
Definir valor mínimo para delivery  
Definir raio ou área atendida  
Ativar/desativar entregas

7.9. Módulo de configurações comerciais

Função: reunir parâmetros centrais do negócio.

Itens:  
Horário de funcionamento  
Abertura e fechamento de pedidos  
Pedido mínimo  
Formas de pagamento  
Pix  
Pagamento online  
Fechamento fora de horário  
Mensagens automáticas  
Canal de atendimento

7.10. Módulo de personalização visual

Função: adaptar a identidade pública do estabelecimento.

Ações:  
Alterar cores  
Logo  
Banner  
Título  
Descrição  
Imagem institucional  
Ordem de blocos visuais

Esse módulo é essencial para diferenciação entre empresas do SaaS.

7.11. Módulo de usuários e permissões

Função: gerenciar equipe interna.

Perfis possíveis:  
Administrador do estabelecimento  
Gerente  
Caixa  
Garçom  
Produção  
Entrega

Ações:  
Cadastrar usuário  
Editar  
Ativar/desativar  
Definir perfil  
Redefinir senha  
Controlar permissões

7.12. Módulo de relatórios

Função: consolidar inteligência operacional e gerencial.

Relatórios previstos:  
Pedidos por período  
Vendas por período  
Produtos mais vendidos  
Clientes recorrentes  
Pedidos cancelados  
Descontos aplicados  
Formas de pagamento  
Entregas  
Movimento financeiro  
Desempenho por horário

8. Mapa da área administrativa global do SaaS

8.1. Dashboard global

Função: acompanhar a operação da plataforma como produto.

Indicadores:  
Empresas ativas  
Empresas em teste  
Assinaturas vencidas  
Receita recorrente  
Chamados de suporte  
Uso por módulo

8.2. Módulo de empresas

Função: administrar estabelecimentos cadastrados na plataforma.

Ações:  
Cadastrar empresa  
Editar dados  
Ativar/desativar acesso  
Ver plano contratado  
Acessar status da conta  
Gerenciar ambiente da empresa

8.3. Módulo de planos e assinaturas

Função: controlar estrutura comercial do SaaS.

Ações:  
Criar plano  
Editar plano  
Definir limite de recursos  
Associar empresa ao plano  
Controlar ciclo de cobrança  
Definir status da assinatura

8.4. Módulo de cobranças

Função: controlar adimplência da plataforma.

Ações:  
Visualizar cobranças  
Registrar pagamento  
Suspender acesso por inadimplência  
Emitir histórico financeiro da assinatura

8.5. Módulo de suporte

Função: acompanhar atendimento aos clientes do SaaS.

Ações:  
Abrir chamado  
Responder chamado  
Classificar prioridade  
Vincular empresa  
Registrar solução

8.6. Módulo de parâmetros globais

Função: definir regras institucionais do sistema.

Exemplos:  
Configurações de segurança  
Parâmetros padrões  
Textos institucionais  
Recursos habilitados por plano  
Regras globais de notificações

9. Estrutura recomendada de menus

9.1. Menu da área pública

Início  
Cardápio  
Minha conta  
Acompanhar pedido  
Chamar garçom

9.2. Menu da área operacional

Painel  
Mesas  
Comandas  
Pedidos  
Produção  
Caixa  
Entregas  
Impressões

9.3. Menu da área administrativa do estabelecimento

Dashboard  
Produtos  
Categorias  
Adicionais  
Clientes  
Promoções  
Estoque  
Taxas e entregas  
Configurações  
Personalização  
Usuários  
Relatórios

9.4. Menu da área administrativa global do SaaS

Painel global  
Empresas  
Planos  
Assinaturas  
Cobranças  
Suporte  
Parâmetros  
Administradores

10. Lógica de circulação dos usuários

10.1. Cliente final

Entra no cardápio  
Identifica mesa ou contexto do pedido  
Consulta categorias  
Seleciona produto  
Configura adicionais  
Envia pedido  
Acompanha status  
Consulta conta  
Solicita fechamento ou paga

10.2. Garçom

Entra no painel operacional  
Consulta mesa  
Abre ou acessa comanda  
Lança itens  
Acompanha pedidos  
Atende chamado  
Solicita fechamento

10.3. Produção

Entra no painel de pedidos  
Visualiza fila  
Inicia preparo  
Marca como pronto  
Finaliza etapa de produção

10.4. Caixa

Consulta comandas pendentes  
Confere itens e totais  
Registra pagamento  
Imprime comprovante  
Fecha caixa

10.5. Gestor do estabelecimento

Acessa dashboard  
Configura produtos e parâmetros  
Consulta relatórios  
Acompanha desempenho  
Administra equipe

10.6. Administrador SaaS

Gerencia empresas  
Administra planos  
Controla assinaturas  
Acompanha suporte  
Monitora operação da plataforma

11. Relação entre páginas principais e modais

Nem toda ação deve virar página. Algumas devem ser tratadas como modais operacionais para reduzir atrito.

Modais recomendados:  
Abrir comanda  
Adicionar produto rápido  
Configurar adicionais simples  
Chamar garçom  
Aplicar desconto  
Confirmar pagamento  
Confirmar cancelamento  
Pausar produto

Páginas completas recomendadas:  
Cardápio  
Minha conta  
Pedidos  
Produção  
Caixa  
Produtos  
Relatórios  
Configurações  
Usuários  
Assinaturas

12. Diretriz para breadcrumbs e navegação contextual

Todas as áreas administrativas e operacionais deverão utilizar breadcrumb para indicar posição do usuário no sistema.

Exemplo:  
Administração > Produtos > Editar produto  
Operacional > Mesas > Mesa 08 > Comanda João  
SaaS > Empresas > Empresa X > Assinatura

Na área pública, a navegação deve ser mais direta e visual, com menos texto estrutural.

13. Critérios para organização de rotas

As rotas devem seguir a divisão por área e módulo.

Exemplo conceitual:  
Área pública: /menu, /conta, /pedido/finalizar  
Área operacional: /operacional/mesas, /operacional/pedidos, /operacional/caixa  
Área administrativa: /admin/produtos, /admin/categorias, /admin/relatorios  
Área SaaS: /saas/empresas, /saas/planos, /saas/assinaturas

Esse padrão facilitará controllers, middleware, autenticação e manutenção.

14. Pontos críticos identificados nesta etapa

Alguns pontos exigem atenção desde agora.

O sistema não deve misturar “pedido” com “comanda”. Pedido é evento operacional; comanda é unidade de consumo vinculada a mesa e cliente.

O sistema não deve misturar “área operacional” com “administração”. Quem opera atendimento não precisa navegar pela estrutura gerencial inteira.

O módulo de adicionais não deve ser tratado como detalhe secundário. Ele é estrutural.

O módulo de caixa precisa ficar separado do módulo de pedidos, embora se relacionem diretamente.

A personalização visual do estabelecimento deve estar isolada da configuração comercial.

O painel SaaS global não pode compartilhar a mesma lógica da administração da empresa assinante.

15. Conclusão

A hierarquia proposta organiza o SaaS Menu Interativo de forma profissional, separando claramente experiência do cliente, operação do estabelecimento, gestão da empresa e administração global da plataforma.

Essa estrutura cria base sólida para a próxima etapa, que deverá formalizar os perfis de usuário e a matriz de permissões, pois a navegação definida aqui só se torna segura e coerente quando associada a regras explícitas de acesso.