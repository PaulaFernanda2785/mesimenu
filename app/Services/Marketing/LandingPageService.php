<?php
declare(strict_types=1);

namespace App\Services\Marketing;

use App\Repositories\CompanyRepository;
use App\Repositories\PlanRepository;
use App\Repositories\SubscriptionRepository;
use App\Services\Shared\PlanFeatureCatalogService;
use Throwable;

final class LandingPageService
{
    public function __construct(
        private readonly PlanRepository $plans = new PlanRepository(),
        private readonly CompanyRepository $companies = new CompanyRepository(),
        private readonly SubscriptionRepository $subscriptions = new SubscriptionRepository(),
        private readonly PlanFeatureCatalogService $featureCatalog = new PlanFeatureCatalogService(),
        private readonly \App\Repositories\PublicInteractionRepository $publicInteractions = new \App\Repositories\PublicInteractionRepository()
    ) {}

    public function build(): array
    {
        try {
            $companySummary = $this->companies->summary();
            $subscriptionSummary = $this->subscriptions->summary();
            $plans = $this->normalizePlans($this->plans->listActiveForPublicCatalog());
            $publishedInteractions = $this->publicInteractions->listPublished(6);
        } catch (Throwable) {
            $companySummary = [];
            $subscriptionSummary = [];
            $plans = [];
            $publishedInteractions = [];
        }
        $featuredPlans = array_values(array_filter(
            $plans,
            static fn (array $plan): bool => !empty($plan['is_featured'])
        ));
        $recommendedPlans = array_values(array_filter(
            $plans,
            static fn (array $plan): bool => !empty($plan['is_recommended'])
        ));

        return [
            'seo' => $this->buildSeo(),
            'navigation' => $this->navigation(),
            'hero_metrics' => [
                [
                    'value' => (string) ((int) ($companySummary['active_companies'] ?? 0) + (int) ($companySummary['testing_companies'] ?? 0)),
                    'label' => 'operacoes em andamento',
                ],
                [
                    'value' => (string) ((int) ($subscriptionSummary['active_subscriptions'] ?? 0) + (int) ($subscriptionSummary['trial_subscriptions'] ?? 0)),
                    'label' => 'assinaturas monitoradas',
                ],
                [
                    'value' => 'PIX + cartao',
                    'label' => 'cobranca mensal e anual',
                ],
                [
                    'value' => 'SEO ready',
                    'label' => 'estrutura pronta para indexacao',
                ],
            ],
            'about_highlights' => [
                [
                    'value' => '4 areas',
                    'label' => 'publico, operacional, administrativo e SaaS',
                ],
                [
                    'value' => 'Multiempresa',
                    'label' => 'cada cliente opera com identidade, catalogo e regras proprias',
                ],
                [
                    'value' => 'PIX + cartao',
                    'label' => 'pagamentos do negocio e cobranca recorrente em um mesmo ecossistema',
                ],
            ],
            'about_capabilities' => [
                'Cardapio digital responsivo',
                'QR Code por mesa',
                'Comandas, pedidos e cozinha',
                'Caixa, pagamentos e fechamento',
                'Delivery com zonas de entrega',
                'Estoque, produtos e adicionais',
                'Usuarios, suporte e permissoes',
                'Planos, assinaturas e cobranca',
            ],
            'about_modules' => [
                [
                    'eyebrow' => 'Cliente final',
                    'title' => 'Cardapio, QR Code e pedido guiado',
                    'description' => 'O consumidor entra por link ou QR da mesa, navega por categorias, escolhe adicionais e envia pedidos com menos dependencia do atendimento manual.',
                ],
                [
                    'eyebrow' => 'Operacao',
                    'title' => 'Comandas, cozinha, caixa e entrega em sincronia',
                    'description' => 'A equipe acompanha mesas, comandas, status de preparo, tickets, pagamentos e entregas sem quebrar o fluxo operacional do estabelecimento.',
                ],
                [
                    'eyebrow' => 'Gestao da empresa',
                    'title' => 'Catalogo, estoque, usuarios e leitura gerencial',
                    'description' => 'O negocio administra produtos, adicionais, identidade visual, estoque, usuarios internos, relatorios e configuracoes comerciais em um unico ambiente.',
                ],
                [
                    'eyebrow' => 'Governanca SaaS',
                    'title' => 'Empresas, planos, assinaturas e suporte',
                    'description' => 'A Comanda360 tambem opera como produto recorrente, com gestao de empresas assinantes, planos de assinatura, cobranca e acompanhamento institucional.',
                ],
            ],
            'problem_points' => [
                [
                    'title' => 'Vendas sem rastreabilidade',
                    'description' => 'Pedidos anotados manualmente geram retrabalho, erro operacional e nenhuma base real para decisao comercial.',
                ],
                [
                    'title' => 'Cobranca improvisada',
                    'description' => 'Sem um fluxo claro de assinatura, a empresa perde previsibilidade, atrasa recebimentos e mistura operacao com financeiro.',
                ],
                [
                    'title' => 'Marketing sem conversao',
                    'description' => 'Trafego pago ou organico nao sustenta crescimento quando a pagina publica nao explica valor, nao prova confianca e nao capta leads.',
                ],
                [
                    'title' => 'Equipe dependente de memoria',
                    'description' => 'Quando processos vivem em conversas, a escala trava. O negocio fica lento, vulneravel e pouco replicavel.',
                ],
                [
                    'title' => 'Fechamento de mesa com conflito',
                    'description' => 'Sem controle de comanda, consumo e status de pagamento, o fechamento vira discussao, demora no caixa e risco de cobrar errado ou deixar item para tras.',
                ],
                [
                    'title' => 'Financeiro sem conciliacao real',
                    'description' => 'Quando recebimentos, comandas e formas de pagamento nao conversam entre si, sobra caixa sem conferencia, falha de registro e pouca clareza sobre o que realmente entrou.',
                ],
            ],
            'solutions' => [
                [
                    'eyebrow' => 'Operacao',
                    'title' => 'Atendimento mais rapido e operacao mais organizada',
                    'description' => 'A Comanda360 conecta mesas, comandas, pedidos e cozinha em um fluxo mais claro para reduzir falhas, acelerar o atendimento e melhorar a experiencia do cliente.',
                ],
                [
                    'eyebrow' => 'Pagamento',
                    'title' => 'Fechamento de conta com mais controle e menos atrito',
                    'description' => 'Pagamentos, consumo e status da comanda ficam integrados para diminuir erro de cobranca, reduzir divergencias no caixa e dar mais seguranca no fechamento.',
                ],
                [
                    'eyebrow' => 'Gestao',
                    'title' => 'Mais padrao para a equipe e mais controle para a gestao',
                    'description' => 'Com processos registrados no sistema, a empresa deixa de depender de memoria e improviso, ganha visibilidade da operacao e cria base para crescer com mais consistencia.',
                ],
                [
                    'eyebrow' => 'Crescimento',
                    'title' => 'Mais forca comercial para atrair e converter novas empresas',
                    'description' => 'A pagina da Comanda360, os planos e o fluxo de contato ajudam a apresentar melhor a solucao, fortalecer a proposta de valor e transformar interesse em oportunidade real de assinatura.',
                ],
            ],
            'feature_groups' => $this->featureGroups(),
            'public_interactions' => $publishedInteractions,
            'plans' => $plans,
            'featured_plans' => $featuredPlans,
            'recommended_plans' => $recommendedPlans,
            'plans_stats' => [
                'total_active' => count($plans),
                'featured' => count($featuredPlans),
                'recommended' => count($recommendedPlans),
            ],
            'workflow' => [
                [
                    'step' => '01',
                    'title' => 'A empresa encontra uma pagina pensada para converter interesse em assinatura',
                    'description' => 'A landing apresenta dores reais, proposta de valor, planos disponiveis e chamadas de acao para transformar visita em contato comercial.',
                ],
                [
                    'step' => '02',
                    'title' => 'O plano certo fica mais claro no momento da decisao',
                    'description' => 'O catalogo destaca os planos disponiveis, recomendados e mais estrategicos para facilitar a comparacao e reduzir friccao na contratacao.',
                ],
                [
                    'step' => '03',
                    'title' => 'A assinatura avanca com pagamento simples e recorrente',
                    'description' => 'PIX e cartao entram como caminhos diretos para acelerar a contratacao, sustentar a recorrencia e dar mais previsibilidade de receita.',
                ],
                [
                    'step' => '04',
                    'title' => 'A operacao ganha visibilidade, controle e continuidade',
                    'description' => 'Depois da entrada, a empresa acompanha a operacao com mais leitura gerencial, acesso organizado, suporte e modulos alinhados ao plano contratado.',
                ],
            ],
            'blog_articles' => [
                [
                    'category' => 'SEO local',
                    'title' => 'Como atrair clientes no Google quando o processo de venda ainda e manual',
                    'excerpt' => 'Entenda por que pagina bonita nao basta: sem oferta clara, prova e captura de lead, o trafego nao vira receita.',
                ],
                [
                    'category' => 'Gestao',
                    'title' => 'Os gargalos invisiveis de operar pedidos e cobrancas fora de um fluxo centralizado',
                    'excerpt' => 'Erros de pedido, perda de historico e inadimplencia normalmente nascem no mesmo problema: falta de sistema e padrao.',
                ],
                [
                    'category' => 'Assinatura',
                    'title' => 'Mensal ou anual: como posicionar seu plano para vender mais sem desvalorizar o produto',
                    'excerpt' => 'Precificacao nao e tabela isolada. Ela precisa conversar com onboarding, capacidade operacional e percepcao de valor.',
                ],
            ],
            'faq' => $this->faqItems(),
        ];
    }

    private function buildSeo(): array
    {
        $canonical = app_url('/');
        $logoUrl = asset_url('/img/logo-comanda360.png');
        $faq = [];
        foreach ($this->faqItems() as $item) {
            if (!is_array($item)) {
                continue;
            }

            $question = trim((string) ($item['question'] ?? ''));
            $answer = trim((string) ($item['answer'] ?? ''));
            if ($question === '' || $answer === '') {
                continue;
            }

            $faq[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        return [
            'title' => 'Comanda360 | Plataforma para empresas que querem organizar atendimento, pedidos e cobranca',
            'description' => 'Comanda360 para empresas que querem organizar atendimento, comandas, pagamentos e contratacao recorrente com planos mensais ou anuais.',
            'keywords' => 'sistema para restaurante, comanda360, comanda digital, qr code para mesas, sistema de pedidos, cobranca recorrente pix',
            'canonical' => $canonical,
            'robots' => 'index,follow,max-image-preview:large',
            'og_image' => $logoUrl,
            'structured_data' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Organization',
                    'name' => 'Comanda360',
                    'url' => $canonical,
                    'logo' => $logoUrl,
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => 'Comanda360',
                    'url' => $canonical,
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'SoftwareApplication',
                    'name' => 'Comanda360',
                    'applicationCategory' => 'BusinessApplication',
                    'operatingSystem' => 'Web',
                    'url' => $canonical,
                    'description' => 'Comanda360 para operacao comercial, assinaturas, pagamentos via PIX e cartao e digitalizacao de vendas.',
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'FAQPage',
                    'mainEntity' => $faq,
                ],
            ],
        ];
    }

    private function navigation(): array
    {
        return [
            ['href' => '#sobre', 'label' => 'Sobre'],
            ['href' => '#problemas', 'label' => 'Problemas'],
            ['href' => '#solucoes', 'label' => 'Solucoes'],
            ['href' => '#funcionalidades', 'label' => 'Funcionalidades'],
            ['href' => '#planos', 'label' => 'Planos'],
            ['href' => '#blog', 'label' => 'Blog'],
            ['href' => '#contato', 'label' => 'Contato'],
        ];
    }

    private function faqItems(): array
    {
        return [
            [
                'question' => 'A Comanda360 atende empresas com mesas, comandas e pedidos no salao?',
                'answer' => 'Sim. A Comanda360 foi pensada para organizar mesas, comandas, pedidos, atendimento e fechamento em um fluxo mais claro para a empresa assinante.',
            ],
            [
                'question' => 'Depois da contratacao, meus clientes conseguem acessar o cardapio pelo QR Code da mesa?',
                'answer' => 'Sim. Depois que a empresa entra na plataforma, os clientes do estabelecimento podem acessar o cardapio pelo QR Code e seguir a jornada com mais autonomia.',
            ],
            [
                'question' => 'A Comanda360 ajuda no fechamento da conta e no controle de pagamento da empresa?',
                'answer' => 'Sim. Consumo, comanda, formas de pagamento e fechamento ficam mais conectados para reduzir erro operacional e dar mais seguranca no caixa da empresa.',
            ],
            [
                'question' => 'A plataforma trabalha com pagamento via PIX e cartao?',
                'answer' => 'Sim. A plataforma contempla PIX, cartao e cobranca recorrente conforme o ciclo escolhido pela empresa no momento da assinatura.',
            ],
            [
                'question' => 'Posso contratar a Comanda360 no plano mensal ou anual?',
                'answer' => 'Sim. A Comanda360 pode apresentar contratacao mensal e anual para facilitar a escolha do formato mais adequado para a empresa.',
            ],
            [
                'question' => 'Como eu escolho o plano ideal para a minha empresa?',
                'answer' => 'A comparacao entre os planos ajuda a entender qual estrutura faz mais sentido para o momento da sua operacao. Se houver duvida, o canal de contato comercial existe justamente para orientar essa decisao.',
            ],
            [
                'question' => 'A Comanda360 serve apenas para operacao ou tambem ajuda a empresa a vender melhor?',
                'answer' => 'Os dois. A plataforma melhora atendimento, organizacao da operacao, fechamento e a capacidade da empresa de atender melhor e vender com menos erro.',
            ],
            [
                'question' => 'Esta pagina publica e da minha empresa ou da propria Comanda360?',
                'answer' => 'Esta pagina publica e da propria Comanda360 e foi feita para atrair novas empresas interessadas em contratar a plataforma. Ela nao e uma pagina promocional das empresas assinantes.',
            ],
            [
                'question' => 'Se eu quiser entender melhor o plano ideal, posso falar com o comercial antes de assinar?',
                'answer' => 'Sim. A secao de contato existe exatamente para isso: abrir uma conversa comercial com mais contexto sobre operacao, momento da empresa e plano de interesse antes da contratacao.',
            ],
        ];
    }

    private function featureGroups(): array
    {
        return [
            [
                'step' => '01',
                'eyebrow' => 'QR Code na mesa',
                'title' => 'O cliente entra no fluxo certo sem depender do atendimento manual',
                'description' => 'A leitura do QR Code identifica a mesa e reduz o atrito logo no primeiro contato, deixando o inicio do atendimento mais rapido e mais confiavel.',
                'image' => 'img/qrcode-celular.png',
                'image_alt' => 'Acesso por QR Code no celular com a Comanda360',
                'result' => 'Menos erro na identificacao da mesa e mais agilidade no inicio do pedido.',
                'items' => [
                    'Entrada direta pelo celular do cliente',
                    'Identificacao da mesa com menos atrito',
                    'Jornada inicial mais rapida e organizada',
                ],
            ],
            [
                'step' => '02',
                'eyebrow' => 'Cardapio digital',
                'title' => 'A empresa apresenta melhor o cardapio e vende com mais clareza',
                'description' => 'Produtos, categorias e jornada de escolha ficam mais intuitivos no celular, ajudando o cliente a navegar melhor e aumentando a chance de conversao.',
                'image' => 'img/menu-celular.png',
                'image_alt' => 'Cardapio digital da Comanda360 no celular',
                'result' => 'Mais autonomia para o cliente e mais capacidade de venda no salao.',
                'items' => [
                    'Categorias mais organizadas',
                    'Navegacao pensada para uso mobile',
                    'Apresentacao comercial mais forte do cardapio',
                ],
            ],
            [
                'step' => '03',
                'eyebrow' => 'Produtos e adicionais',
                'title' => 'Itens, adicionais e observacoes ficam mais precisos no pedido',
                'description' => 'A escolha de produtos e complementos passa a seguir um fluxo guiado, reduzindo ruído entre cliente, atendimento e cozinha.',
                'image' => 'img/produtos-celular.png',
                'image_alt' => 'Tela de produtos e adicionais da Comanda360',
                'result' => 'Menos retrabalho operacional e mais precisao no que vai para producao.',
                'items' => [
                    'Escolha de itens com melhor orientacao visual',
                    'Adicionais e observacoes no mesmo fluxo',
                    'Pedido mais completo antes do envio',
                ],
            ],
            [
                'step' => '04',
                'eyebrow' => 'Caixa e comanda',
                'title' => 'A equipe acompanha consumo, caixa e fechamento com mais controle',
                'description' => 'Mesa, comanda e movimentacao financeira deixam de ficar soltas e passam a seguir um fluxo operacional mais legivel para quem atende e para quem gere.',
                'image' => 'img/caixa-celular.png',
                'image_alt' => 'Controle de caixa e comandas da Comanda360',
                'result' => 'Mais seguranca na operacao diaria e menos dependencia de memoria da equipe.',
                'items' => [
                    'Abertura e leitura mais clara das comandas',
                    'Acompanhamento do consumo e do caixa',
                    'Rotina de fechamento com menos conflito',
                ],
            ],
            [
                'step' => '05',
                'eyebrow' => 'Pagamento integrado',
                'title' => 'O recebimento acontece com mais seguranca e menos divergencia',
                'description' => 'Pagamentos, status da comanda e fechamento passam a conversar entre si, diminuindo erro de cobranca e melhorando a leitura do que realmente foi recebido.',
                'image' => 'img/pagamento-celular.png',
                'image_alt' => 'Fluxo de pagamentos da Comanda360',
                'result' => 'Menos atrito no fechamento e mais confianca no controle financeiro.',
                'items' => [
                    'PIX e cartao no mesmo ecossistema',
                    'Leitura mais clara do status de pagamento',
                    'Fechamento com menos erro e mais confiabilidade',
                ],
            ],
            [
                'step' => '06',
                'eyebrow' => 'Leitura gerencial',
                'title' => 'A gestao acompanha indicadores e decide com mais visibilidade',
                'description' => 'A empresa sai do improviso e passa a ter uma camada visual de acompanhamento para entender desempenho, gargalos e oportunidades de crescimento.',
                'image' => 'img/estatistica-celular.png',
                'image_alt' => 'Indicadores e estatisticas da Comanda360',
                'result' => 'Mais visibilidade para ajustar operacao, venda e crescimento com base real.',
                'items' => [
                    'Indicadores para leitura mais rapida da operacao',
                    'Acompanhamento gerencial em ambiente visual',
                    'Base mais forte para decidir e evoluir',
                ],
            ],
        ];
    }

    private function normalizePlans(array $plans): array
    {
        $normalized = [];
        foreach ($plans as $plan) {
            if (!is_array($plan)) {
                continue;
            }

            $featuresJson = $plan['features_json'] ?? null;
            $pricing = $this->featureCatalog->pricingConfigFromJson($featuresJson);
            $featureLabels = $this->featureCatalog->enabledLabelsFromJson($featuresJson);
            $priceMonthly = $pricing['mensal'] !== null
                ? (float) $pricing['mensal']
                : (float) ($plan['price_monthly'] ?? 0);
            $priceYearly = $pricing['anual'] !== null
                ? (float) $pricing['anual']
                : ($plan['price_yearly'] !== null ? (float) $plan['price_yearly'] : null);
            $yearlyBasePrice = round($priceMonthly * 12, 2);
            $yearlyDiscountPercent = round((float) ($pricing['desconto_anual_percentual'] ?? 0), 2);
            $publicLanding = $this->featureCatalog->publicLandingConfigFromJson($featuresJson);
            $normalized[] = [
                'id' => (int) ($plan['id'] ?? 0),
                'name' => trim((string) ($plan['name'] ?? 'Plano')),
                'slug' => trim((string) ($plan['slug'] ?? '')),
                'description' => trim((string) ($plan['description'] ?? '')),
                'price_monthly' => $priceMonthly,
                'price_yearly' => $priceYearly,
                'price_yearly_base' => $yearlyBasePrice,
                'price_yearly_discount_percent' => $yearlyDiscountPercent,
                'max_users' => $plan['max_users'] !== null ? (int) $plan['max_users'] : null,
                'max_products' => $plan['max_products'] !== null ? (int) $plan['max_products'] : null,
                'max_tables' => $plan['max_tables'] !== null ? (int) $plan['max_tables'] : null,
                'feature_labels' => $featureLabels,
                'public_display_order' => $publicLanding['ordem_exibicao'] ?? null,
                'is_featured' => !empty($publicLanding['destaque']),
                'is_recommended' => !empty($publicLanding['recomendado']),
            ];
        }

        usort($normalized, static function (array $left, array $right): int {
            $leftOrder = isset($left['public_display_order']) ? (int) $left['public_display_order'] : PHP_INT_MAX;
            $rightOrder = isset($right['public_display_order']) ? (int) $right['public_display_order'] : PHP_INT_MAX;
            if ($leftOrder !== $rightOrder) {
                return $leftOrder <=> $rightOrder;
            }

            $leftRecommended = !empty($left['is_recommended']) ? 0 : 1;
            $rightRecommended = !empty($right['is_recommended']) ? 0 : 1;
            if ($leftRecommended !== $rightRecommended) {
                return $leftRecommended <=> $rightRecommended;
            }

            $leftFeatured = !empty($left['is_featured']) ? 0 : 1;
            $rightFeatured = !empty($right['is_featured']) ? 0 : 1;

            if ($leftFeatured !== $rightFeatured) {
                return $leftFeatured <=> $rightFeatured;
            }

            $priceCompare = ((float) $left['price_monthly']) <=> ((float) $right['price_monthly']);
            if ($priceCompare !== 0) {
                return $priceCompare;
            }

            return ((int) $left['id']) <=> ((int) $right['id']);
        });

        return $normalized;
    }
}
