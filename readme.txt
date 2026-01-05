=== BR LGPD Consent (Cookies) ===
Contributors: BR ENIAC SOFTEC
Donate link: https://pluginswp.breniacsoftec.com/doacao
Tags: lgpd, cookies, consentimento, privacidade, gdpr, banner, modal, scripts condicionais
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 8.0
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin de consentimento de cookies LGPD com banner/modal, categorias personalizáveis e carregamento condicional de scripts conforme preferências do visitante.

== Description ==

O **BR LGPD Consent (Cookies)** adiciona um sistema completo de **consentimento de cookies** para sites WordPress, permitindo que o visitante escolha quais categorias deseja permitir e garantindo que scripts opcionais sejam executados **somente após o consentimento**.

O plugin foi pensado para ser simples de configurar e flexível para cenários reais: você pode manter cookies essenciais sempre ativos e condicionar ferramentas como analytics, pixels, chat, mídia incorporada e outros.

**Principais recursos**
* Banner/modal de cookies com opções de **Aceitar tudo**, **Rejeitar** e **Salvar preferências**.
* Categorias de cookies (ex.: Necessários, Estatísticas, Marketing, Mídia) com suporte a **categorias personalizadas**.
* Categorias marcadas como **sempre ativas** (essenciais/necessários) não dependem de consentimento.
* Carregamento **condicional** de scripts por categoria (executa apenas se o usuário permitir).
* Armazenamento de consentimento em cookie com serialização segura (compatível com formatos antigos e novos).
* Melhor compatibilidade com cache e diferentes paths do WordPress (evita duplicidade de cookie e perda de preferências).

**Observação importante**
Este plugin ajuda na gestão de consentimento e no carregamento condicional de scripts, mas **não substitui consultoria jurídica**. Adapte textos e categorias às necessidades e às políticas do seu site.

== Installation ==

1. No painel do WordPress, vá em **Plugins → Adicionar novo → Enviar plugin**.
2. Selecione o arquivo `.zip` do plugin e clique em **Instalar agora**.
3. Após instalar, clique em **Ativar**.
4. Acesse o menu do plugin (LGPD/Cookies) para configurar:
   - Categorias de cookies
   - Scripts por categoria
   - Textos e opções do modal/banner (se aplicável)

== Usage ==

**Fluxo recomendado**
1. Configure as **categorias** (mantenha “Necessários/Essenciais” como sempre ativa).
2. Para cada categoria opcional, cole os scripts correspondentes (ex.: Google Analytics, Meta Pixel, Chat, etc.).
3. Teste em aba anônima:
   - Abrir preferências
   - Marcar/desmarcar categorias
   - **Salvar preferências**
   - Reabrir o modal e validar persistência

**Boas práticas**
* Coloque scripts de rastreamento e marketing apenas nas categorias opcionais.
* Mantenha apenas o mínimo indispensável na categoria “Necessários”.
* Atualize sua **Política de Privacidade** e **Política de Cookies** descrevendo categorias e finalidade.

== Frequently Asked Questions ==

= 1) O plugin bloqueia scripts automaticamente? =
O plugin foi projetado para **carregar scripts opcionais somente após consentimento** (por categoria). Configure seus scripts nas categorias corretas para garantir o comportamento esperado.

= 2) Por que uma categoria nova aparece no modal, mas não fica marcada ao salvar? =
Isso normalmente ocorre quando existe **cookie duplicado com o mesmo nome em paths diferentes** ou quando algum cache mantém JS antigo. A versão 1.0.1 melhora esse cenário reforçando o path do cookie e evitando dependência de listas antigas no front-end.

= 3) Funciona com cache/CDN? =
Sim, mas recomenda-se limpar o cache após atualizar o plugin. Em alguns setups, manter arquivos JS antigos pode afetar botões como “Aceitar tudo”. A versão 1.0.1 reduz esse risco com fallback via DOM.

= 4) Posso personalizar textos/estilo? =
Sim. Você pode ajustar CSS e textos conforme o plugin disponibiliza no painel (quando aplicável). Se estiver usando um tema com cache agressivo, limpe o cache após mudanças.

= 5) O plugin é compatível com LGPD e GDPR? =
Ele oferece um mecanismo técnico de consentimento e controle de scripts. A conformidade total depende de como você categoriza cookies, descreve finalidade e mantém suas políticas atualizadas.

== Screenshots ==

1. Banner/modal de consentimento com opções rápidas.
2. Modal de preferências com categorias e toggles.
3. Área administrativa para gerenciamento de categorias.
4. Configuração de scripts por categoria.

== Changelog ==

= 1.0.1 =
* Correção: preferências não eram persistidas em alguns casos por duplicidade de cookie em paths diferentes.
* Correção: “Aceitar tudo”/ações do modal passaram a considerar categorias novas exibidas no DOM, evitando ignorar categorias adicionadas depois.
* Melhoria: aumento de versão para ajudar a quebrar cache de assets após atualização.

= 1.0.0 =
* Versão inicial do plugin.

== Upgrade Notice ==

= 1.0.1 =
Atualização recomendada. Corrige falhas em “Salvar preferências” e melhora a ativação de categorias novas em sites com cache e/ou cookie duplicado em paths diferentes.

== License ==

This plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or any later version.

== Credits ==

Desenvolvido por **BR Eniac SofTec**.
Se este plugin te ajuda, considere apoiar o desenvolvimento via doação (opcional).
