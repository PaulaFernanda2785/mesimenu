Sistema: SaaS Menu Interativo  
Tipo de documento: Controle de acesso, perfis operacionais e governança de permissões  
Objetivo: Definir os perfis de usuário do sistema, seus níveis de responsabilidade, escopos de atuação e permissões de acesso nas áreas pública, operacional, administrativa e institucional do SaaS.

1. Finalidade do documento

Este documento estabelece a estrutura de perfis de acesso do SaaS Menu Interativo, definindo quem pode acessar cada área do sistema, quais ações cada perfil pode executar e quais limites operacionais devem ser respeitados.

A finalidade prática desse documento é evitar dois problemas clássicos em sistemas operacionais e SaaS:

o primeiro é conceder acesso excessivo a usuários que deveriam apenas operar tarefas específicas;  
o segundo é restringir demais a operação e gerar gargalos administrativos desnecessários.

A matriz de permissões, portanto, não é apenas um componente de segurança. Ela é também um componente de organização operacional, rastreabilidade e governança.

2. Princípio geral de controle de acesso

O sistema deverá adotar controle de acesso baseado em perfis, com possibilidade futura de evolução para permissões granulares por ação.

Na fase inicial, a estrutura recomendada é híbrida:

nível 1: perfil principal do usuário;  
nível 2: permissões por módulo e por ação crítica.

Essa abordagem é a mais adequada porque acelera o desenvolvimento, mantém a operação simples e evita a complexidade prematura de um RBAC totalmente granular logo no início. Ainda assim, preserva espaço para expansão.

3. Estrutura geral dos tipos de usuário

O sistema terá dois grandes grupos de usuários:

usuários externos, representados pelo cliente final do estabelecimento;  
usuários internos, representados pelos operadores, gestores e administradores do sistema.

Esses grupos se subdividem da seguinte forma:

3.1. Usuário externo  
Cliente final

3.2. Usuários internos do estabelecimento  
Administrador do estabelecimento  
Gerente operacional  
Operador de caixa  
Garçom/Atendente  
Operador de produção/cozinha  
Operador de entrega/motoboy

3.3. Usuários institucionais do SaaS  
Administrador global da plataforma  
Suporte institucional da plataforma  
Financeiro/comercial da plataforma

4. Regra central de segregação de acesso

Os acessos devem obedecer a quatro fronteiras de segurança:

área pública do cliente;  
área operacional do estabelecimento;  
área administrativa do estabelecimento;  
área global do SaaS.

Nenhum perfil deve atravessar essas fronteiras sem autorização explícita.

Exemplo:  
o garçom não deve acessar relatórios financeiros;  
o operador de produção não deve editar produtos;  
o administrador de uma empresa assinante não deve acessar dados de outra empresa;  
o suporte SaaS não deve operar o caixa do estabelecimento, salvo por mecanismos controlados de suporte assistido, se houver futuramente.

5. Perfis de usuário do sistema

5.1. Cliente final

Descrição:  
É o usuário consumidor, que acessa o sistema por QR Code, link direto ou outro canal público, com o objetivo de consultar o cardápio, montar pedidos, visualizar sua conta e interagir com o atendimento.

Responsabilidade operacional:  
Consumir o menu, selecionar produtos, enviar pedidos e acompanhar sua própria jornada de consumo.

Escopo:  
Área pública do estabelecimento.

Permissões esperadas:  
visualizar cardápio;  
pesquisar produtos;  
selecionar categoria;  
abrir comanda, se permitido;  
informar nome;  
adicionar produtos;  
configurar adicionais;  
inserir observações;  
visualizar resumo da conta;  
acompanhar status do pedido;  
chamar garçom;  
finalizar pedido;  
visualizar formas de pagamento disponíveis.

Restrições:  
não acessa área administrativa;  
não acessa pedidos de outras mesas;  
não acessa configurações do sistema;  
não acessa relatórios;  
não pode editar catálogo ou preços.

5.2. Garçom/Atendente

Descrição:  
Perfil operacional voltado ao atendimento em salão ou balcão. Atua na abertura de comandas, lançamento manual de itens, consulta de mesas e suporte ao cliente.

Responsabilidade operacional:  
Executar o atendimento diário e garantir o correto registro dos pedidos.

Escopo:  
Área operacional.

Permissões esperadas:  
acessar painel operacional resumido;  
visualizar mesas;  
abrir mesa;  
acessar comandas;  
criar comanda;  
editar comanda;  
adicionar itens;  
registrar observações;  
consultar status do pedido;  
responder chamados de clientes;  
solicitar fechamento;  
visualizar resumo da conta da mesa.

Restrições:  
não pode alterar configurações do sistema;  
não pode editar produtos ou preços;  
não pode acessar relatórios financeiros completos;  
não pode gerenciar usuários;  
não pode alterar planos ou dados SaaS;  
não deve cancelar pedidos finalizados sem autorização superior.

5.3. Operador de produção/cozinha

Descrição:  
Perfil responsável pela execução dos pedidos recebidos, com foco em preparação, status e fluxo de produção.

Responsabilidade operacional:  
Receber, organizar, preparar e atualizar o status dos pedidos em produção.

Escopo:  
Área operacional.

Permissões esperadas:  
acessar fila de pedidos;  
visualizar detalhes do pedido;  
visualizar observações do cliente;  
marcar pedido como recebido;  
marcar pedido como em preparo;  
marcar pedido como pronto;  
sinalizar indisponibilidade operacional;  
reimprimir ticket de produção, se autorizado.

Restrições:  
não pode alterar preços;  
não pode acessar relatórios financeiros;  
não pode cadastrar produtos;  
não pode registrar pagamentos;  
não pode gerenciar usuários;  
não acessa administração SaaS.

5.4. Operador de caixa

Descrição:  
Perfil responsável pelo recebimento financeiro dos pedidos, conferência de totais, aplicação controlada de descontos e encerramento financeiro operacional.

Responsabilidade operacional:  
Processar pagamentos e registrar fechamento de caixa.

Escopo:  
Área operacional, com acesso parcial a dados administrativos financeiros operacionais.

Permissões esperadas:  
acessar painel de comandas pendentes;  
consultar pedidos e comandas;  
visualizar valores totais;  
registrar forma de pagamento;  
confirmar pagamento;  
imprimir comprovantes;  
registrar pagamento pendente;  
aplicar desconto, se autorizado;  
fechar caixa;  
consultar movimentação de caixa do período.

Restrições:  
não pode alterar catálogo de produtos;  
não pode editar configurações comerciais gerais;  
não pode cadastrar usuários;  
não pode administrar assinatura SaaS;  
não pode alterar relatórios históricos consolidados sem permissão superior.

5.5. Operador de entrega / motoboy

Descrição:  
Perfil voltado ao acompanhamento e execução da entrega dos pedidos externos.

Responsabilidade operacional:  
Receber o pedido destinado à entrega, acompanhar sua saída e registrar conclusão.

Escopo:  
Área operacional, limitada ao módulo de entregas.

Permissões esperadas:  
visualizar pedidos atribuídos;  
visualizar dados da entrega;  
visualizar endereço e referência;  
marcar pedido como em rota;  
marcar pedido como entregue;  
registrar ocorrência básica de entrega.

Restrições:  
não acessa caixa completo;  
não acessa relatórios;  
não acessa catálogo;  
não acessa administração;  
não acessa outras áreas internas além do módulo logístico.

5.6. Gerente operacional

Descrição:  
Perfil intermediário entre administração total e operação diária. Atua supervisionando fluxo, autorizando certas ações sensíveis e acompanhando desempenho da unidade.

Responsabilidade operacional:  
Coordenar a operação do estabelecimento, apoiar a equipe e resolver situações excepcionais.

Escopo:  
Área operacional completa e parte relevante da área administrativa do estabelecimento.

Permissões esperadas:  
visualizar painel operacional completo;  
acompanhar mesas, comandas e pedidos;  
acompanhar produção;  
acompanhar caixa;  
autorizar cancelamentos;  
autorizar descontos;  
consultar relatórios operacionais;  
consultar relatórios financeiros resumidos;  
acompanhar clientes;  
acompanhar produtos pausados;  
supervisionar entregas.

Restrições:  
não deve alterar plano do SaaS;  
não deve administrar empresas da plataforma;  
não deve ter acesso institucional global;  
pode ter restrição à criação de usuários administrativos, conforme política do negócio.

5.7. Administrador do estabelecimento

Descrição:  
É o principal responsável pela conta operacional da empresa assinante. Administra catálogo, configurações, usuários internos, promoções, relatórios e parâmetros do negócio.

Responsabilidade operacional:  
Gerir o ambiente da empresa dentro da plataforma.

Escopo:  
Área administrativa do estabelecimento e supervisão operacional integral.

Permissões esperadas:  
acesso total à administração da empresa;  
gerenciar produtos;  
gerenciar categorias;  
gerenciar adicionais;  
gerenciar promoções;  
gerenciar cupons;  
gerenciar estoque;  
gerenciar taxas e entregas;  
gerenciar horários;  
gerenciar formas de pagamento;  
gerenciar personalização visual;  
gerenciar usuários internos;  
visualizar relatórios completos;  
acompanhar caixa;  
acompanhar pedidos e mesas;  
parametrizar operação da unidade.

Restrições:  
não acessa dados de outras empresas;  
não acessa gestão global da plataforma;  
não administra planos institucionais além da própria assinatura, se permitido pelo SaaS;  
não altera parâmetros globais do produto.

5.8. Suporte institucional da plataforma

Descrição:  
Perfil interno do proprietário do SaaS, voltado ao atendimento técnico e acompanhamento de problemas de uso da plataforma.

Responsabilidade operacional:  
Prestar suporte institucional às empresas assinantes.

Escopo:  
Área global do SaaS, com acesso técnico limitado e controlado.

Permissões esperadas:  
visualizar cadastro de empresas;  
visualizar status da assinatura;  
consultar erros e logs operacionais, se implementado;  
acompanhar chamados;  
orientar uso do sistema;  
visualizar configurações da empresa para suporte.

Restrições:  
não deve alterar dados financeiros sem autorização;  
não deve realizar operações críticas em nome da empresa sem rastreabilidade;  
não deve acessar informações sigilosas além do necessário;  
não deve operar caixa, pedidos ou produção como rotina.

5.9. Financeiro/comercial da plataforma

Descrição:  
Perfil voltado ao controle comercial e financeiro do próprio SaaS.

Responsabilidade operacional:  
Gerenciar cobranças, assinaturas, planos e status comercial das empresas clientes.

Escopo:  
Área global do SaaS, com foco em gestão comercial.

Permissões esperadas:  
visualizar empresas;  
visualizar planos;  
gerenciar cobranças;  
registrar pagamentos de assinatura;  
acompanhar inadimplência;  
ativar ou suspender acesso conforme regra comercial;  
emitir histórico comercial.

Restrições:  
não administra operação interna do estabelecimento;  
não altera pedidos, mesas ou produtos das empresas;  
não gerencia módulos técnicos profundos do sistema.

5.10. Administrador global da plataforma

Descrição:  
É o perfil de maior autoridade no sistema. Controla o produto SaaS em nível institucional, empresas, planos, parâmetros, suporte e governança da plataforma.

Responsabilidade operacional:  
Administrar integralmente a plataforma como produto e ambiente multiempresa.

Escopo:  
Área global do SaaS com controle total.

Permissões esperadas:  
gerenciar empresas;  
gerenciar planos;  
gerenciar assinaturas;  
gerenciar parâmetros globais;  
gerenciar usuários institucionais;  
acompanhar métricas globais;  
habilitar ou desabilitar recursos;  
acompanhar suporte;  
intervir administrativamente em contas quando necessário.

Restrições:  
não deve executar operações de rotina do estabelecimento como prática normal;  
o uso desse perfil deve ser restrito, auditado e excepcional.

6. Matriz conceitual de áreas por perfil

6.1. Acesso às grandes áreas

|Perfil|Área Pública|Área Operacional|Área Administrativa do Estabelecimento|Área Global SaaS|
|---|---|---|---|---|
|Cliente final|Sim|Não|Não|Não|
|Garçom/Atendente|Não|Sim|Não|Não|
|Produção/Cozinha|Não|Sim|Não|Não|
|Caixa|Não|Sim|Parcial|Não|
|Motoboy/Entrega|Não|Sim limitada|Não|Não|
|Gerente operacional|Não|Sim|Parcial ampla|Não|
|Administrador do estabelecimento|Não|Sim|Sim|Não|
|Suporte SaaS|Não|Não direto|Não direto|Sim parcial|
|Financeiro/comercial SaaS|Não|Não|Não|Sim parcial|
|Administrador global SaaS|Não|Não direto|Não direto|Sim total|

Observação crítica: “não direto” significa que eventual acesso assistido deve depender de política específica, auditoria e justificativa.

7. Matriz funcional de permissões por módulo

7.1. Cardápio digital

|Ação|Cliente|Garçom|Produção|Caixa|Motoboy|Gerente|Admin Estab.|Suporte SaaS|Financeiro SaaS|Admin Global|
|---|---|---|---|---|---|---|---|---|---|---|
|Visualizar cardápio|Sim|Sim|Não|Sim|Não|Sim|Sim|Sim|Não|Sim|
|Pesquisar produtos|Sim|Sim|Não|Sim|Não|Sim|Sim|Sim|Não|Sim|
|Adicionar item ao pedido|Sim|Sim|Não|Não|Não|Sim|Sim|Não|Não|Não|
|Visualizar detalhes do produto|Sim|Sim|Sim|Sim|Não|Sim|Sim|Sim|Não|Sim|

7.2. Comandas e mesas

|Ação|Cliente|Garçom|Produção|Caixa|Motoboy|Gerente|Admin Estab.|Suporte SaaS|Financeiro SaaS|Admin Global|
|---|---|---|---|---|---|---|---|---|---|---|
|Abrir comanda|Sim limitada|Sim|Não|Não|Não|Sim|Sim|Não|Não|Não|
|Visualizar comanda|Sim própria|Sim|Sim parcial|Sim|Não|Sim|Sim|Não|Não|Não|
|Editar comanda|Não|Sim|Não|Não|Não|Sim|Sim|Não|Não|Não|
|Fechar comanda|Não|Solicita|Não|Sim|Não|Sim|Sim|Não|Não|Não|
|Visualizar mesas|Não|Sim|Não|Sim|Não|Sim|Sim|Não|Não|Não|
|Alterar status da mesa|Não|Parcial|Não|Não|Não|Sim|Sim|Não|Não|Não|

7.3. Pedidos e produção

|Ação|Cliente|Garçom|Produção|Caixa|Motoboy|Gerente|Admin Estab.|Suporte SaaS|Financeiro SaaS|Admin Global|
|---|---|---|---|---|---|---|---|---|---|---|
|Enviar pedido|Sim|Sim|Não|Não|Não|Sim|Sim|Não|Não|Não|
|Visualizar fila de pedidos|Não|Sim parcial|Sim|Sim|Sim entrega|Sim|Sim|Não|Não|Não|
|Alterar status do pedido|Não|Não|Sim|Parcial|Sim entrega|Sim|Sim|Não|Não|Não|
|Cancelar pedido|Não|Não|Não|Parcial autorizada|Não|Sim|Sim|Não|Não|Não|
|Reimprimir ticket|Não|Parcial|Sim|Sim|Não|Sim|Sim|Não|Não|Não|

7.4. Caixa e pagamentos

|Ação|Cliente|Garçom|Produção|Caixa|Motoboy|Gerente|Admin Estab.|Suporte SaaS|Financeiro SaaS|Admin Global|
|---|---|---|---|---|---|---|---|---|---|---|
|Visualizar total da conta|Sim própria|Sim|Não|Sim|Não|Sim|Sim|Não|Não|Não|
|Registrar pagamento|Não|Não|Não|Sim|Não|Sim|Sim|Não|Não|Não|
|Aplicar desconto|Não|Não|Não|Parcial|Não|Sim|Sim|Não|Não|Não|
|Fechar caixa|Não|Não|Não|Sim|Não|Sim|Sim|Não|Não|Não|
|Visualizar movimento do caixa|Não|Não|Não|Sim|Não|Sim|Sim|Não|Não|Não|

7.5. Catálogo e configuração comercial

|Ação|Cliente|Garçom|Produção|Caixa|Motoboy|Gerente|Admin Estab.|Suporte SaaS|Financeiro SaaS|Admin Global|
|---|---|---|---|---|---|---|---|---|---|---|
|Cadastrar produto|Não|Não|Não|Não|Não|Não|Sim|Não|Não|Não|
|Editar produto|Não|Não|Não|Não|Não|Parcial opcional|Sim|Não|Não|Não|
|Pausar produto|Não|Não|Não|Não|Não|Sim|Sim|Não|Não|Não|
|Gerenciar categorias|Não|Não|Não|Não|Não|Não|Sim|Não|Não|Não|
|Gerenciar adicionais|Não|Não|Não|Não|Não|Não|Sim|Não|Não|Não|
|Gerenciar promoções/cupons|Não|Não|Não|Não|Não|Parcial|Sim|Não|Não|Não|
|Gerenciar horários|Não|Não|Não|Não|Não|Parcial|Sim|Não|Não|Não|
|Gerenciar formas de pagamento|Não|Não|Não|Não|Não|Não|Sim|Não|Não|Não|
|Gerenciar taxas de entrega|Não|Não|Não|Não|Não|Parcial|Sim|Não|Não|Não|

7.6. Usuários e permissões

|Ação|Cliente|Garçom|Produção|Caixa|Motoboy|Gerente|Admin Estab.|Suporte SaaS|Financeiro SaaS|Admin Global|
|---|---|---|---|---|---|---|---|---|---|---|
|Visualizar usuários internos|Não|Não|Não|Não|Não|Parcial|Sim|Não|Não|Não|
|Cadastrar usuário interno|Não|Não|Não|Não|Não|Parcial opcional|Sim|Não|Não|Não|
|Alterar perfil de usuário|Não|Não|Não|Não|Não|Não|Sim|Não|Não|Não|
|Ativar/desativar usuário|Não|Não|Não|Não|Não|Não|Sim|Não|Não|Não|

7.7. Relatórios

|Ação|Cliente|Garçom|Produção|Caixa|Motoboy|Gerente|Admin Estab.|Suporte SaaS|Financeiro SaaS|Admin Global|
|---|---|---|---|---|---|---|---|---|---|---|
|Visualizar relatórios operacionais|Não|Não|Não|Parcial|Não|Sim|Sim|Não|Não|Não|
|Visualizar relatórios financeiros|Não|Não|Não|Parcial|Não|Sim resumido|Sim|Não|Não|Não|
|Visualizar produtos mais vendidos|Não|Não|Não|Não|Não|Sim|Sim|Não|Não|Não|
|Visualizar clientes recorrentes|Não|Não|Não|Não|Não|Sim|Sim|Não|Não|Não|

7.8. Área global do SaaS

|Ação|Cliente|Garçom|Produção|Caixa|Motoboy|Gerente|Admin Estab.|Suporte SaaS|Financeiro SaaS|Admin Global|
|---|---|---|---|---|---|---|---|---|---|---|
|Visualizar empresas assinantes|Não|Não|Não|Não|Não|Não|Não|Sim|Sim|Sim|
|Gerenciar planos|Não|Não|Não|Não|Não|Não|Não|Não|Sim parcial|Sim|
|Gerenciar assinaturas|Não|Não|Não|Não|Não|Não|Não|Não|Sim|Sim|
|Gerenciar parâmetros globais|Não|Não|Não|Não|Não|Não|Não|Não|Não|Sim|
|Gerenciar usuários institucionais|Não|Não|Não|Não|Não|Não|Não|Não|Não|Sim|
|Ativar/suspender empresa|Não|Não|Não|Não|Não|Não|Não|Não|Sim parcial|Sim|

8. Níveis recomendados de permissão por ação

Para o desenvolvimento profissional do sistema, cada ação relevante deve ser categorizada em um dos seguintes níveis:

visualizar;  
criar;  
editar;  
alterar status;  
cancelar;  
aprovar;  
excluir lógico;  
administrar.

Essa classificação é importante porque dois perfis podem acessar o mesmo módulo, mas com poderes distintos.

Exemplo:  
o caixa pode visualizar uma comanda e registrar pagamento;  
o gerente pode visualizar a mesma comanda, autorizar desconto e aprovar cancelamento;  
o administrador do estabelecimento pode, além disso, alterar parâmetros que afetam o fluxo dessa comanda.

9. Permissões críticas que exigem tratamento especial

Algumas ações não devem depender apenas de perfil amplo. Elas devem possuir validação adicional, confirmação ou registro de auditoria.

Essas ações incluem:

cancelamento de pedido;  
cancelamento de item já enviado à produção;  
aplicação de desconto manual;  
fechamento de caixa;  
reativação de produto pausado por indisponibilidade;  
troca de perfil de usuário;  
suspensão de empresa assinante;  
alteração de parâmetros financeiros;  
intervenção institucional do SaaS em conta de cliente.

10. Diretrizes de segurança para implementação

10.1. Isolamento por empresa

Como se trata de SaaS institucional, todo usuário interno do estabelecimento deve estar vinculado a uma empresa específica. Nenhum usuário da empresa A poderá visualizar ou manipular dados da empresa B.

Esse é um requisito obrigatório, não opcional.

10.2. Sessão e autenticação

Toda área interna do sistema deverá exigir autenticação. Perfis públicos não compartilham a mesma autenticação de usuários internos.

Recomendação:  
cliente final acessa fluxo público contextual;  
usuários internos acessam login administrativo/operacional;  
usuários globais do SaaS acessam ambiente institucional separado.

10.3. Auditoria mínima

O sistema deverá registrar, no mínimo, ações críticas com:  
usuário;  
empresa;  
ação executada;  
módulo;  
data e hora;  
identificador do registro afetado.

10.4. Privilégio mínimo

Cada perfil deve receber apenas o acesso necessário para seu trabalho. Esse princípio reduz erro humano, fraudes e danos operacionais.

11. Estrutura recomendada para modelagem de permissões

Na implementação, a estrutura pode começar com:

tabela de perfis;  
tabela de usuários;  
tabela de permissões por perfil;  
mapeamento por módulo e ação;  
vínculo obrigatório com empresa.

Estruturalmente, isso é mais sólido do que embutir verificações diretamente no código sem padronização.

12. Regras estratégicas para o MVP

Para evitar excesso de complexidade no início, o MVP deve trabalhar com perfis fixos pré-definidos:

cliente final;  
garçom;  
produção;  
caixa;  
gerente;  
administrador do estabelecimento;  
administrador global SaaS.

Perfis como suporte institucional, financeiro/comercial SaaS e motoboy podem entrar de forma simplificada na primeira versão, caso o cronograma exija redução de escopo.

13. Conclusão

A definição de perfis e permissões do SaaS Menu Interativo precisa ser tratada como parte central da arquitetura e não como detalhe secundário. O sistema lida com operação comercial, fluxo financeiro, dados de clientes, controle interno e ambiente multiempresa. Sem uma matriz de acesso clara, a solução perde segurança, rastreabilidade e consistência operacional.

A estrutura proposta neste documento separa corretamente os perfis por responsabilidade, reduz sobreposição de funções e cria base sólida para a implementação de autenticação, middleware, rotas protegidas, menus condicionais e governança do SaaS.