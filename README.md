# Vesti Hub

Aplicação Laravel para sincronizar produtos de múltiplos ERPs com a API da Vesti, sem banco de dados, sem Redis e sem filas.

## Objetivo

O projeto recebe produtos e variações de ERP(s) via HTTP, normaliza as estruturas diferentes para um modelo interno comum e envia os dados para a API da Vesti no formato exigido.

## Arquitetura

A solução foi estruturada com interfaces e abstrações para manter o núcleo de sincronização independente do ERP escolhido.

Fluxo principal:

```text
ERP Mock
  ↓ HTTP
ERP Client
  ↓
Product Mapper
  ↓
ProductData / VariationData
  ↓
ProductSyncService
  ↓
VestiPayloadMapper
  ↓
VestiClient
  ↓ HTTP
Vesti API
```

Os ERPs suportados inicialmente são:

- XPTO
- XYZ

A seleção do ERP é centralizada em um resolver/factory para evitar condicionais espalhadas pelo sistema.

## Requisitos

- PHP 8.4+
- Laravel 12
- Docker + Docker Compose
- Composer

## Instalação

1. Clone o repositório.
2. Copie o exemplo de ambiente:

```bash
cp .env.example .env
```

3. Ajuste as variáveis de ambiente conforme o ambiente local ou Docker.
4. Instale dependências do PHP:

```bash
composer install
```

5. Para executar com Docker:

```bash
docker compose up -d --build
```

## Configuração

Arquivo base de configuração:

```env
# URLs internas do Docker
ERP_XPTO_API_URL=http://erp-mock:8080
ERP_XYZ_API_URL=http://erp-mock:8080

# Credenciais da Vesti
VESTI_API_URL=
VESTI_API_KEY=
VESTI_COMPANY_ID=
```

Observações:

- `ERP_PROVIDER` suporta `xpto` e `xyz`.
- O projeto não usa banco de dados, Redis, filas ou persistência.
- As credenciais da Vesti não ficam versionadas no repositório.
- `VESTI_API_KEY` e `VESTI_COMPANY_ID` são obrigatórias para a integração real com a Vesti.

## Docker

O Compose inicia somente os serviços necessários para o projeto:

```bash
docker compose config --services
```

Serviços esperados:

- `app`
- `erp-mock`

## ERP Mock

O mock expõe os endpoints esperados pelos ERPs:

```text
GET /erp/xpto/produtos.json
GET /erp/xpto/variacoes.json
GET /erp/xyz/produtos.json
GET /erp/xyz/variacoes.json
```

O Hub consome esses endpoints exclusivamente via HTTP, sem ler arquivos do disco do projeto.

## Execução da sincronização

Comando principal:

```bash
php artisan products:sync
```

Esse comando apenas dispara o fluxo de sincronização; a regra de negócio fica em `ProductSyncService` e nas abstrações de ERP e Vesti.

## Testes

Para rodar a suíte completa:

```bash
php artisan test
```

Os testes usam `Http::fake()` para simular o ERP e a Vesti, evitando chamadas reais para a API externa.

## Limitação externa da Vesti

O fluxo real de envio para a Vesti depende de valores externos reais:

- `VESTI_API_KEY`
- `VESTI_COMPANY_ID`
- `VESTI_API_URL`

Esses valores não devem ser inventados, nem versionados no repositório. Se forem ausentes, a execução real da integração será bloqueada por configuração externa.

## Uso com IA

Este projeto foi organizado para facilitar a manutenção com ferramentas de IA e revisão automatizada:

- interfaces bem definidas;
- abstrações por ERP e por plataforma de venda;
- fluxo centralizado e testável;
- comando de sincronização enxuto;
- testes focados em comportamento real.

A IA pode auxiliar na leitura, revisão e manutenção do código sem alterar a arquitetura definida pelo SPEC.

## Observações finais

- Não há MySQL, PostgreSQL, Redis, filas ou persistência no fluxo principal.
- O projeto foi pensado para múltiplos ERPs sem espalhar condicionais pelo código.
- A sincronização usa modelos normais (`ProductData` e `VariationData`) e a associação de variações é feita via código extraído do SKU.
