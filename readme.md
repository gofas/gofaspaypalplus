# Módulo PayPal Plus para WHMCS

[![versão](https://img.shields.io/github/v/release/gofas/gofaspaypalplus?label=vers%C3%A3o&color=005071&style=flat-square)](https://github.com/gofas/gofaspaypalplus/releases/latest)
[![downloads](https://img.shields.io/github/downloads/gofas/gofaspaypalplus/total?label=downloads&color=005071&style=flat-square)](https://github.com/gofas/gofaspaypalplus/releases/latest)
[![licença](https://img.shields.io/badge/licen%C3%A7a-propriet%C3%A1ria-005071?style=flat-square)](https://gofas.net/contrato-de-venda-de-licenca-de-uso-de-software/)
[![suporte](https://img.shields.io/badge/suporte-f%C3%B3rum%20gratuito-ff8700?style=flat-square)](https://gofas.net/foruns/)

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

O download é contabilizado no site pelo contador de instalações do módulo.

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

Fórum de suporte gratuito: https://gofas.net/foruns/

## Licença

Software proprietário da Gofas Software. O código é público apenas para transparência e consulta; isso não concede licença de uso, modificação ou redistribuição. É vedado modificar, redistribuir, sublicenciar ou realizar engenharia reversa sem autorização prévia por escrito. Veja [LICENSE](LICENSE) e o contrato completo em https://gofas.net/contrato-de-venda-de-licenca-de-uso-de-software/.
