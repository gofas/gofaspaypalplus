# Módulo PayPal Plus para WHMCS

[![versão](https://img.shields.io/github/v/release/gofas/gofaspaypalplus?label=vers%C3%A3o&color=005071&style=flat-square)](https://github.com/gofas/gofaspaypalplus/releases/latest)
[![downloads](https://img.shields.io/endpoint?url=https%3A%2F%2Fgofas.net%2Fwp-json%2Fgofas%2Fv1%2Fbadge%2Fgofaspaypalplus&style=flat-square)](https://github.com/gofas/gofaspaypalplus/releases/latest)
[![abrir issue](https://img.shields.io/badge/suporte-abrir%20issue-ff8700?style=flat-square)](https://gofas.net/?p=13814/#new-post)

Integra o checkout transparente do PayPal diretamente na fatura do WHMCS, sem redirecionar o cliente para fora do seu site. Desenvolvido pela Gofas Software, é 100% gratuito e de código aberto.

## Sumário

- [Download](#download)
- [Funcionalidades](#funcionalidades)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Configuração](#configuração)
- [Informações importantes](#informações-importantes)
- [Suporte](#suporte)
- [Licença](#licença)

## Download

**[Baixar a versão mais recente](https://github.com/gofas/gofaspaypalplus/releases/latest/download/gofaspaypalplus.zip)**

## Funcionalidades

- **Checkout transparente** do PayPal embutido na fatura, sem redirecionamento externo
- **Cartões salvos** pelo cliente no PayPal
- **Confirmação automática de pagamento** via notificação (callback) do PayPal
- **Imagem personalizada** para o botão "Finalizar Pagamento"
- **Ordem dos campos CPF e CNPJ** configurável
- **Suporte a produção e a testes (sandbox)**
- **Modo diagnóstico** e log de callbacks
- **Aviso de atualização** e verificação de versão na própria tela de configuração do módulo

## Requisitos

- PHP >= 7.1
- Conta PayPal com aplicativo REST API criado (Client ID e Client Secret, produção e sandbox)

## Instalação

1. Baixe o arquivo pelo link de download e descompacte. Será criada a pasta `gofaspaypalplus`.
2. Copie a pasta `modules` de dentro de `gofaspaypalplus` para a raiz da instalação do WHMCS, mesclando com as pastas existentes.
3. Ative o módulo em `Opções > Pagamentos > Portais para Pagamentos > aba All Payment Gateways`, clicando em "Gofas PayPal Plus".
4. Informe o Client ID e o Client Secret.

## Configuração

### Pré configuração no PayPal

Crie um aplicativo REST API no painel de desenvolvedor do PayPal (https://developer.paypal.com/developer/applications/) e copie o Client ID e o Client Secret de produção e de sandbox.

### Opções do módulo

<img src="https://raw.githubusercontent.com/gofas/gofaspaypalplus/master/docs/img/tela-configuracoes-modulo-2.2.0.png" alt="Tela de configuracoes do modulo" width="640">

- **Live Client ID** e **Live Client Secret**: credenciais de produção do aplicativo.
- **Sandbox Client ID** e **Sandbox Client Secret**: credenciais do ambiente de testes.
- **Sandbox**: alterna entre o ambiente de testes e produção.
- **Debug**: exibe resultados e erros retornados pela API. Não use em produção.
- **Log callback**: grava as notificações recebidas do PayPal.
- **Administrador atribuído**: administrador com permissão para usar a API interna do WHMCS.
- **Ordem do campo CPF ou CNPJ** e **Ordem do campo CNPJ**: posição dos campos personalizados de cliente usados pelo módulo.
- **Imagem do botão Finalizar Pagamento**: URL da imagem usada no botão de pagamento.
- **Enviar estatísticas de uso (opcional)**: controla o envio identificado das estatísticas de confirmação de pagamento. Desmarcado, as confirmações continuam sendo contabilizadas de forma anônima.

## Informações importantes

- As tarifas do PayPal são pagas separadamente, conforme o plano da sua conta.
- Sempre faça backup antes de mudar algo no seu sistema.

## Suporte

[Abrir issue](https://gofas.net/?p=13814/#new-post) no fórum do módulo.

## Licença

[Contrato de licença de uso](https://gofas.net/contrato-de-venda-de-licenca-de-uso-de-software/)
