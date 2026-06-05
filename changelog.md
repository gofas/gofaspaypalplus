# Gofas PayPal Plus

Módulo de gateway de pagamento para WHMCS que integra cobranças via PayPal Plus (solução de checkout transparente PayPal para o Brasil). Desenvolvido pela Gofas Software.

## Funcionalidades

- Checkout transparente PayPal Plus (cartão de crédito sem sair do site)
- Parcelamento em até 12x
- Webhook para baixa automática de faturas

## Requisitos

- WHMCS 7.x ou superior
- PHP 8.x
- Conta PayPal Business com PayPal Plus habilitado (Client ID e Client Secret)

## Instalação

1. Copiar `modules/gateways/` para o `modules/gateways/` do WHMCS
2. Ativar em **Configurações > Formas de Pagamento**
3. Informar Client ID e Client Secret

## Changelog

Ver [changelog.md](changelog.md).
