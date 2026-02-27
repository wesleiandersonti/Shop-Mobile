# Installers (Padrão Proxmox Community-Style)

Este projeto adota o padrão de instaladores em shell no estilo `community-scripts` (tteck):

- Script único
- Fluxo interativo (`whiptail`)
- Modo **Default** e **Advanced**
- Validações de ambiente (root, versão, arquitetura)
- Mensageria visual (`msg_info`, `msg_ok`, `msg_error`)

## Objetivo

Padronizar a instalação de todos os sistemas do ecossistema para provisionamento rápido, repetível e seguro.

## Próximos scripts previstos

- `gitea-vm.sh`
- `shop-mobile-vm.sh`
- `evolution-api-vm.sh`
- `template-vm.sh` (base para novos instaladores)

## Convenções

- Arquivos `.sh` devem ser idempotentes sempre que possível.
- Toda instalação deve terminar com resumo final + próximos passos.
- Sempre documentar parâmetros padrão e avançados.
