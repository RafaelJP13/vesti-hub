# Vesti Hub — Specification

## 1. Objetivo

Construir um Hub de Integração em Laravel capaz de:

1. Consumir APIs de diferentes sistemas ERP;
2. Obter produtos e suas variações;
3. Normalizar estruturas diferentes de ERP para um modelo interno comum;
4. Transformar o modelo interno para o formato esperado pela API da Vesti;
5. Enviar os produtos normalizados para a API da Vesti.

O sistema deve permitir adicionar novos ERPs sem modificar a lógica principal de sincronização.

---

# 2. Stack

* PHP 8.4+
* Laravel 12
* Docker
* Docker Compose
* PHPUnit/Pest
* Git
* GitHub

Não utilizar banco de dados para o fluxo principal da aplicação.

Não utilizar Redis.

Não utilizar Jobs/Queues na implementação inicial.

A aplicação deverá funcionar sem depender de um serviço externo de banco de dados.

---

# 3. Arquitetura

O projeto será composto por dois componentes principais:

```text
ERP Mock
    ↓ HTTP
Vesti Hub
    ↓ HTTP
Vesti API
```

O ERP Mock deverá representar os dois ERPs disponíveis no desafio:

```text
ERP XPTO ──┐
           ├──→ ERP Mock ──HTTP──→ Vesti Hub ──HTTP──→ Vesti API
ERP XYZ ───┘
```

Arquitetura do Hub:

```text
┌──────────────────────────────┐
│          ERP MOCK            │
│                              │
│ ERP XPTO                     │
│ GET /erp/xpto/produtos.json  │
│ GET /erp/xpto/variacoes.json │
│                              │
│ ERP XYZ                      │
│ GET /erp/xyz/produtos.json   │
│ GET /erp/xyz/variacoes.json  │
└──────────────┬───────────────┘
               │
               │ HTTP GET
               ▼
┌──────────────────────────────┐
│          VESTI HUB           │
│           Laravel            │
│                              │
│ ERP Client                   │
│      ↓                       │
│ ERP Mapper                   │
│      ↓                       │
│ ProductData                  │
│      ↓                       │
│ ProductSyncService           │
│      ↓                       │
│ VestiPayloadMapper            │
│      ↓                       │
│ VestiClient                  │
└──────────────┬───────────────┘
               │
               │ HTTP POST
               ▼
┌──────────────────────────────┐
│          VESTI API           │
└──────────────────────────────┘
```

---

# 4. ERP Mock

O ERP Mock será utilizado exclusivamente para simular as APIs dos ERPs durante o desenvolvimento.

O Mock deverá representar separadamente os dois ERPs disponíveis.

## ERP XPTO

```text
GET /erp/xpto/produtos.json
GET /erp/xpto/variacoes.json
```

## ERP XYZ

```text
GET /erp/xyz/produtos.json
GET /erp/xyz/variacoes.json
```

O ERP Mock deverá retornar os dados fornecidos nos arquivos JSON correspondentes ao ERP.

O Hub deverá consumir essas URLs através de HTTP.

O Hub não deve acessar diretamente os arquivos JSON.

O ERP Mock não deverá realizar nenhuma normalização dos dados.

Cada endpoint deverá retornar a estrutura original do respectivo ERP.

---

# 5. ERPs disponíveis

Existem dois ERPs:

```text
ERP XPTO

ERP XYZ
```

Ambos fornecem produtos e variações.

Os dados possuem a mesma finalidade de negócio, mas estruturas diferentes.

O objetivo da arquitetura é transformar ambas as estruturas em um modelo interno comum.

---

# 6. Estrutura dos dados do ERP XPTO

## 6.1 Produtos XPTO

Exemplo:

```json
{
    "code": 1761095,
    "name": "SHORT ANTI FIT",
    "description": null,
    "price": "109,90",
    "price_promotional": 66,
    "composition": "100% Algodão",
    "brand": "Joana Modas"
}
```

Campos:

```text
code
name
description
price
price_promotional
composition
brand
```

---

## 6.2 Variações XPTO

Exemplo:

```json
{
    "sku": "8750014_G_PRETA",
    "size": "G",
    "color": "PRETA",
    "quantity": 370,
    "unit_measurement": "UN",
    "ordering": 3
}
```

Campos:

```text
sku
size
color
quantity
unit_measurement
ordering
```

---

# 7. Estrutura dos dados do ERP XYZ

## 7.1 Produtos XYZ

Exemplo:

```json
{
    "referencia": 1761095,
    "nome": "SHORT ANTI FIT",
    "descricao": null,
    "preco": "109,90",
    "promocao": 66,
    "composicao": "100% Algodão",
    "marca": "Joana Modas"
}
```

Campos:

```text
referencia
nome
descricao
preco
promocao
composicao
marca
```

---

## 7.2 Variações XYZ

Exemplo:

```json
{
    "variacao": "8750014_G_PRETA",
    "tamanho": "G",
    "cor": "PRETA",
    "quantidade": 370,
    "unidade": "UN",
    "ordem": 3
}
```

Campos:

```text
variacao
tamanho
cor
quantidade
unidade
ordem
```

---

# 8. Mapeamento entre ERPs

Os dados dos dois ERPs deverão resultar no mesmo modelo interno.

## Produto

```text
XPTO                         XYZ
------------------------------------------------
code                  →      referencia
name                  →      nome
description           →      descricao
price                 →      preco
price_promotional     →      promocao
composition           →      composicao
brand                 →      marca
```

Modelo interno:

```text
code
name
description
price
promotionalPrice
composition
brand
```

---

## Variação

```text
XPTO                         XYZ
------------------------------------------------
sku                   →      variacao
size                  →      tamanho
color                 →      cor
quantity              →      quantidade
unit_measurement      →      unidade
ordering              →      ordem
```

Modelo interno:

```text
sku
size
color
quantity
unitMeasurement
ordering
```

---

# 9. Modelo interno

Criar DTOs independentes dos ERPs.

## ProductData

```php
final readonly class ProductData
{
    public function __construct(
        public int $code,
        public string $name,
        public ?string $description,
        public float $price,
        public ?float $promotionalPrice,
        public ?string $composition,
        public ?string $brand,
        public array $variations,
    ) {}
}
```

## VariationData

```php
final readonly class VariationData
{
    public function __construct(
        public string $sku,
        public string|int $size,
        public string $color,
        public int $quantity,
        public string $unitMeasurement,
        public int $ordering,
    ) {}
}
```

Os tipos definitivos devem ser ajustados conforme os dados reais.

O modelo interno não deve depender da estrutura específica de nenhum ERP.

O domínio não deve conhecer:

```text
code
referencia
name
nome
price
preco
```

como conceitos diferentes.

Após a normalização, deve existir somente o modelo interno.

Não adicionar ao modelo interno campos específicos da Vesti que não existam nos dados dos ERPs.

Por exemplo, não adicionar:

```text
weight
height
width
length
location
categories
barcode
color_code
color_integration_id
```

somente para reproduzir o payload da Vesti.

Esses campos pertencem à camada de integração com a Vesti quando aplicável.

---

# 10. Relacionamento entre produto e variação

O relacionamento deve utilizar o identificador contido no SKU/variação.

Exemplo:

Produto:

```text
code = 8750014
```

Variações:

```text
8750014_G_PRETA
8750014_GG_PRETA
```

O código do produto corresponde ao primeiro segmento do SKU.

No XYZ:

```text
referencia = 8750014
```

Variação:

```text
variacao = 8750014_G_PRETA
```

O Mapper deverá conseguir extrair:

```text
8750014
```

da variação.

O resultado deverá ser:

```text
ProductData

code = 8750014

variations = [
    VariationData(
        sku = "8750014_G_PRETA",
        size = "G",
        color = "PRETA",
        quantity = 370,
        unitMeasurement = "UN",
        ordering = 3
    )
]
```

O relacionamento não deve depender do nome do produto.

---

# 11. Performance no relacionamento

Não procurar as variações percorrendo toda a lista para cada produto.

Evitar:

```text
para cada produto:
    percorrer todas as variações
```

Preferir indexação:

```text
variationsByProductCode

8750014 => [
    variation,
    variation
]

1761349 => [
    variation,
    variation
]
```

O fluxo deverá ser:

```text
1. Obter variações;

2. Extrair o código do produto através do SKU;

3. Indexar as variações;

4. Obter produtos;

5. Associar as variações utilizando o índice.
```

Isso evita buscas repetitivas e melhora o desempenho para grandes volumes.

---

# 12. Abstração para múltiplos ERPs

A aplicação deverá utilizar abstrações para permitir N ERPs.

Criar:

```php
interface ErpClientInterface
{
    public function getProducts(): array;

    public function getVariations(): array;
}
```

E:

```php
interface ProductMapperInterface
{
    public function mapProduct(array $product): ProductData;

    public function mapVariation(array $variation): VariationData;

    public function getProductCodeFromVariation(array $variation): int;
}
```

As interfaces são conceituais e podem ser ajustadas caso outra abstração produza melhor separação de responsabilidades.

---

# 13. Implementações dos ERPs

Criar implementações específicas:

```text
ErpClientInterface

├── XptoErpClient
└── XyzErpClient
```

E:

```text
ProductMapperInterface

├── XptoProductMapper
└── XyzProductMapper
```

Cada implementação deve conhecer somente a estrutura do seu ERP.

Exemplo:

```text
XPTO

code
name
price
price_promotional

       ↓

XptoProductMapper

       ↓

ProductData
```

```text
XYZ

referencia
nome
preco
promocao

       ↓

XyzProductMapper

       ↓

ProductData
```

---

# 14. Cliente ERP

O cliente ERP deve ser responsável exclusivamente pela comunicação HTTP.

Responsabilidades:

* Fazer GET dos produtos;
* Fazer GET das variações;
* Configurar timeout;
* Tratar erros HTTP;
* Tratar falhas de conexão;
* Validar resposta JSON;
* Retornar os dados recebidos.

Não deve:

* Normalizar dados;
* Conhecer a Vesti;
* Criar produtos;
* Implementar regras de relacionamento;
* Implementar regras de negócio.

---

# 15. Mapper

O Mapper deve ser responsável exclusivamente pela transformação da estrutura do ERP para o modelo interno.

Responsabilidades:

* Mapear produto;
* Mapear variação;
* Extrair o código do produto;
* Converter tipos necessários;
* Validar estrutura mínima necessária.

Não deve:

* Fazer requisições HTTP;
* Enviar produtos para Vesti;
* Controlar o processo de sincronização;
* Conhecer detalhes da API Vesti.

---

# 16. Serviço de sincronização

Criar:

```text
ProductSyncService
```

Esse serviço será responsável por orquestrar a sincronização.

Fluxo:

```text
ProductSyncService
        │
        ▼
   ErpClient
        │
        ├── produtos
        │
        └── variações
        │
        ▼
Indexação das variações
        │
        ▼
      Mapper
        │
        ▼
   ProductData
        │
        ▼
VestiPayloadMapper
        │
        ▼
   VestiClient
        │
        ▼
    Vesti API
```

O serviço não deve implementar diretamente detalhes de HTTP.

O serviço deve trabalhar com interfaces sempre que houver necessidade de substituição ou extensão.

---

# 17. Cliente Vesti

Criar:

```text
VestiClient
```

Implementando:

```php
interface SalesPlatformInterface
{
    public function createProducts(array $products): array;
}
```

A operação deverá aceitar uma coleção de produtos, pois o endpoint da Vesti recebe:

```json
{
    "products": [
        {}
    ]
}
```

O cliente deverá utilizar o HTTP Client do Laravel.

O cliente será responsável exclusivamente pela comunicação com a API Vesti.

Não deve conhecer estruturas específicas de XPTO ou XYZ.

---

# 18. API Vesti

A integração deverá utilizar o endpoint real fornecido pela documentação da Vesti:

```text
POST /v1/products/company/{company_id}
```

A URL será composta por:

```text
VESTI_API_URL
+
/v1/products/company/{company_id}
```

Autenticação:

```http
apikey: {token}
```

Header:

```http
Content-Type: application/json
```

Payload:

```json
{
    "products": [
        {
            "integration_id": "123",
            "code": "123",
            "name": "Produto teste",
            "active": true,
            "description": "Produto X",
            "full_description": "",
            "composition": "100% algodão",
            "brand": "Marca 01",
            "release_at": "2025-10-15 10:30:00",
            "price": 120.5,
            "promotion": true,
            "price_promotional": 115.8,
            "weight": 400,
            "height": 10,
            "width": 20,
            "length": 30,
            "location": "Pratileira",
            "categories": [
                "0001_1_1"
            ],
            "order_colors": [
                {
                    "color": "BRANCO",
                    "order": 2
                }
            ],
            "variations": [
                {
                    "sku": "123_P_BRANCO",
                    "size": "P",
                    "color": "BRANCO",
                    "quantity": 10,
                    "order": 1,
                    "unit_type": "PÇ",
                    "barcode": "7891000000019"
                }
            ]
        }
    ]
}
```

A implementação deverá utilizar somente os campos necessários e suportados pelo contrato da Vesti.

Não inventar valores para campos que não estão disponíveis nos dados dos ERPs.

---

# 19. Separação do payload da Vesti

O modelo interno não deve ser igual ao payload da Vesti.

O fluxo será:

```text
ERP
 ↓
ERP Client
 ↓
ERP Mapper
 ↓
ProductData
 ↓
VestiPayloadMapper
 ↓
Vesti Payload
 ↓
VestiClient
 ↓
Vesti API
```

Criar:

```text
VestiPayloadMapper
```

Responsável por transformar `ProductData` no formato esperado pela API Vesti.

Exemplo:

```text
ProductData
     ↓
VestiPayloadMapper
     ↓
Vesti Product Payload
```

Mapeamento conceitual:

```text
ProductData.code
        ↓
Vesti.integration_id / code

ProductData.name
        ↓
Vesti.name

ProductData.description
        ↓
Vesti.description

ProductData.price
        ↓
Vesti.price

ProductData.promotionalPrice
        ↓
Vesti.price_promotional
```

A aplicação poderá derivar:

```text
promotionalPrice != null
        ↓
promotion = true
```

Caso não exista preço promocional:

```text
promotionalPrice == null
        ↓
promotion = false
```

A variação deverá ser transformada:

```text
VariationData
        ↓
Vesti Variation Payload
```

Mapeamento:

```text
sku
 ↓
sku

size
 ↓
size

color
 ↓
color

quantity
 ↓
quantity

unitMeasurement
 ↓
unit_type

ordering
 ↓
order
```

Campos da Vesti que não possuem correspondência nos dados dos ERPs não devem receber valores fictícios.

Exemplos:

```text
barcode
color_code
color_integration_id
weight
height
width
length
location
categories
```

Esses campos somente deverão ser enviados se houver uma fonte definida para seus valores.

---

# 20. Command

Criar um Artisan Command:

```bash
php artisan products:sync
```

O Command deve apenas iniciar o processo de sincronização.

Não colocar regras de negócio no Command.

Fluxo:

```text
Command
   ↓
ProductSyncService
```

---

# 21. Configuração

As URLs e credenciais devem ser configuráveis através do `.env`.

```env
ERP_PROVIDER=xpto

ERP_XPTO_API_URL=http://erp-mock
ERP_XYZ_API_URL=http://erp-mock

VESTI_API_URL=
VESTI_API_KEY=
VESTI_COMPANY_ID=
```

As URLs finais dos endpoints deverão ser compostas pelo respectivo cliente ERP.

Exemplo:

```text
ERP_XPTO_API_URL
    +
/erp/xpto/produtos.json

ERP_XPTO_API_URL
    +
/erp/xpto/variacoes.json
```

E:

```text
ERP_XYZ_API_URL
    +
/erp/xyz/produtos.json

ERP_XYZ_API_URL
    +
/erp/xyz/variacoes.json
```

A API Key da Vesti nunca deverá ser armazenada diretamente no código.

Não versionar o `.env`.

Criar `.env.example`.

---

# 22. Docker

Utilizar Docker Compose.

Serviços iniciais:

```yaml
services:

  app:
    # Laravel / PHP

  erp-mock:
    # API Mock dos ERPs
```

Não criar:

```text
mysql
postgres
redis
```

Não utilizar banco para armazenar produtos ou variações.

---

# 23. ERP Mock e Docker

O Hub deve consumir o ERP Mock através da rede interna do Docker.

Exemplo:

```env
ERP_XPTO_API_URL=http://erp-mock
ERP_XYZ_API_URL=http://erp-mock
```

Não utilizar:

```env
ERP_XPTO_API_URL=http://localhost
```

para comunicação entre containers.

O ERP Mock deverá disponibilizar:

```text
GET /erp/xpto/produtos.json
GET /erp/xpto/variacoes.json

GET /erp/xyz/produtos.json
GET /erp/xyz/variacoes.json
```

---

# 24. Dados do ERP Mock

Os arquivos fornecidos deverão ser organizados de forma semelhante a:

```text
erp-mock/

└── data/

    ├── erpxpto/

    │   ├── produtos-erp.json
    │   └── variacoes-erp.json

    │
    └── erpxyz/

        ├── produtos-erp.json
        └── variacoes-erp.json
```

O ERP Mock deverá manter a separação entre XPTO e XYZ.

Cada endpoint deverá carregar exclusivamente os arquivos correspondentes ao ERP solicitado.

O ERP Mock não deverá modificar ou normalizar os dados antes de retorná-los.

---

# 25. Banco de dados

Não utilizar banco de dados.

O projeto não deverá criar persistência para:

* Produtos;
* Variações;
* Sincronizações;
* Jobs;
* Histórico.

Os dados serão obtidos das APIs dos ERPs e enviados à Vesti.

Logs deverão utilizar o mecanismo de logs do Laravel.

---

# 26. Jobs e filas

Não implementar Jobs/Queues na primeira versão.

A sincronização será síncrona:

```text
Command
  ↓
Sync
  ↓
ERP
  ↓
Mapper
  ↓
VestiPayloadMapper
  ↓
Vesti
```

A arquitetura deverá permitir uma futura evolução para processamento assíncrono caso o volume ou os requisitos reais exijam.

Não adicionar Redis ou banco apenas para demonstrar escalabilidade.

---

# 27. Escalabilidade

A solução deverá ser capaz de lidar com aproximadamente 50.000 produtos.

A implementação deve:

* Evitar buscas O(n²) desnecessárias;
* Indexar variações;
* Evitar chamadas HTTP redundantes;
* Evitar transformações repetidas;
* Reutilizar clientes HTTP;
* Manter responsabilidades separadas.

O envio para a Vesti deverá permitir processamento em lotes.

Exemplo conceitual:

```text
50.000 produtos

        ↓

Lote 1
Lote 2
Lote 3
...
```

Cada lote poderá ser enviado através do payload:

```json
{
    "products": [
        {},
        {},
        {}
    ]
}
```

O tamanho do lote deverá ser configurável posteriormente caso necessário.

Essa estratégia não implica utilização de Jobs ou Queues.

O sistema deve ser projetado para possibilitar evolução futura para:

* Processamento em lotes;
* Streaming;
* Jobs;
* Workers;
* Filas;
* Retry distribuído.

Essas funcionalidades não fazem parte da primeira versão.

---

# 28. Tratamento de erros

Tratar:

## ERP

* Timeout;
* Falha de conexão;
* HTTP 4xx;
* HTTP 5xx;
* JSON inválido;
* Campos obrigatórios ausentes;
* Produto inválido;
* Variação inválida;
* Falha de transformação.

## Vesti

* Timeout;
* Falha de conexão;
* HTTP 4xx;
* HTTP 5xx;
* JSON inválido;
* Resposta indicando falha da operação.

A resposta esperada da Vesti possui estrutura semelhante a:

```json
{
    "result": {
        "success": true,
        "message": "Ok",
        "messages": ""
    },
    "statusCode": 200
}
```

O sistema não deverá considerar uma operação bem-sucedida apenas porque o HTTP status foi `200`.

Também deverá verificar o campo:

```text
result.success
```

Retry poderá ser utilizado para falhas temporárias, como:

* HTTP 5xx;
* Erros de conexão;
* Timeout.

Entretanto, retry não deverá provocar duplicidade de cadastro.

---

# 29. Testes

O projeto deverá possuir testes automatizados.

## ERP Client

Testar:

* Endpoint correto;
* Método HTTP;
* Resposta válida;
* HTTP 4xx;
* HTTP 5xx;
* Timeout;
* Falha de conexão;
* JSON inválido.

Utilizar HTTP fake/mock.

Não realizar chamadas reais durante os testes.

---

# 30. Testes dos Mappers

## XPTO

Testar:

```text
code → code

name → name

description → description

price → price

price_promotional → promotionalPrice

composition → composition

brand → brand
```

E:

```text
sku → sku

size → size

color → color

quantity → quantity

unit_measurement → unitMeasurement

ordering → ordering
```

## XYZ

Testar:

```text
referencia → code

nome → name

descricao → description

preco → price

promocao → promotionalPrice

composicao → composition

marca → brand
```

E:

```text
variacao → sku

tamanho → size

cor → color

quantidade → quantity

unidade → unitMeasurement

ordem → ordering
```

Também testar conversões de tipos, especialmente preços.

Exemplo:

```text
"109,90"
```

deverá resultar em:

```text
109.90
```

---

# 31. Testes de relacionamento

Testar que:

```text
8750014
```

seja relacionado a:

```text
8750014_G_PRETA
8750014_GG_PRETA
```

e que as variações de outros produtos não sejam associadas incorretamente.

Testar:

* Produtos sem variações;
* Variações sem produto correspondente;
* SKU inválido;
* SKU sem código de produto;
* Variações duplicadas, caso existam.

---

# 32. Testes do ProductSyncService

Testar:

* Busca de produtos;
* Busca de variações;
* Indexação;
* Relacionamento;
* Uso correto do Mapper;
* Criação de `ProductData`;
* Uso do `VestiPayloadMapper`;
* Envio para Vesti;
* Tratamento de erros.

Utilizar mocks das interfaces.

Não realizar chamadas reais para ERP ou Vesti nos testes unitários.

---

# 33. Testes do VestiClient

Utilizar HTTP fake/mock.

Testar:

* Método `POST`;
* URL;
* `company_id`;
* Header `apikey`;
* Header `Content-Type`;
* Payload;
* Envio de múltiplos produtos;
* Resposta HTTP 200;
* Resposta de sucesso;
* Resposta indicando falha;
* HTTP 4xx;
* HTTP 5xx;
* Timeout;
* Falha de conexão.

Nunca utilizar credenciais reais nos testes.

Exemplo esperado da URL:

```text
/v1/products/company/{company_id}
```

Exemplo esperado do header:

```text
apikey: {VESTI_API_KEY}
```

---

# 34. TDD

Para as principais regras de negócio, utilizar:

```text
RED
 ↓
Criar teste que falha
 ↓
GREEN
 ↓
Implementar solução mínima
 ↓
REFACTOR
```

Os testes devem orientar a implementação.

Priorizar TDD para:

* Mappers;
* Relacionamento entre produtos e variações;
* Conversão de preços;
* `VestiPayloadMapper`;
* Regras de sincronização.

---

# 35. Legibilidade

O código deve:

* Utilizar nomes descritivos;
* Manter métodos pequenos;
* Evitar métodos com múltiplas responsabilidades;
* Evitar duplicação;
* Evitar condicionais complexos;
* Utilizar tipagem adequada;
* Utilizar Dependency Injection;
* Utilizar interfaces quando houver necessidade de substituição ou extensão.

Não criar abstrações sem justificativa.

---

# 36. Responsabilidades

## ERP Mock

Simular APIs dos ERPs XPTO e XYZ.

## ErpClient

Comunicar-se com o ERP.

## Mapper

Normalizar dados do ERP.

## ProductData

Representar o produto normalizado.

## VariationData

Representar a variação normalizada.

## VestiPayloadMapper

Converter o domínio para o formato da Vesti.

## VestiClient

Comunicar-se com a API Vesti.

## ProductSyncService

Orquestrar a sincronização.

## Command

Iniciar a sincronização.

---

# 37. Fluxo completo

```text
php artisan products:sync
            │
            ▼
   ProductSyncService
            │
            ▼
        ErpClient
            │
            ├───────────────┐
            ▼               ▼
        produtos        variações
            │               │
            └───────┬───────┘
                    ▼
                Indexação
                    │
                    ▼
                  Mapper
                    │
                    ▼
              ProductData
                    │
                    ▼
          VestiPayloadMapper
                    │
                    ▼
             Vesti Payload
                    │
                    ▼
              VestiClient
                    │
                    ▼
       POST /v1/products/company/{id}
                    │
                    ▼
                  Vesti
```

---

# 38. Estrutura sugerida

```text
vesti-hub/

│
├── app/
│   ├── Contracts/
│   │   ├── ErpClientInterface.php
│   │   ├── ProductMapperInterface.php
│   │   └── SalesPlatformInterface.php
│   │
│   ├── DTOs/
│   │   ├── ProductData.php
│   │   └── VariationData.php
│   │
│   ├── Mappers/
│   │   ├── XptoProductMapper.php
│   │   ├── XyzProductMapper.php
│   │   └── VestiPayloadMapper.php
│   │
│   ├── Services/
│   │   ├── Erp/
│   │   │   ├── XptoErpClient.php
│   │   │   └── XyzErpClient.php
│   │   │
│   │   ├── Vesti/
│   │   │   └── VestiClient.php
│   │   │
│   │   └── ProductSyncService.php
│   │
│   └── Console/
│       └── Commands/
│           └── SyncProductsCommand.php
│
├── erp-mock/
│   ├── data/
│   │   ├── erpxpto/
│   │   │   ├── produtos-erp.json
│   │   │   └── variacoes-erp.json
│   │   │
│   │   └── erpxyz/
│   │       ├── produtos-erp.json
│   │       └── variacoes-erp.json
│   │
│   └── ...
│
├── tests/
│   ├── Unit/
│   └── Feature/
│
├── Dockerfile
├── docker-compose.yml
├── .env.example
├── .gitignore
├── README.md
├── SPEC.md
└── composer.json
```

A estrutura acima é uma sugestão.

Alterações são permitidas quando houver justificativa arquitetural.

---

# 39. Configuração do ERP

A arquitetura deve permitir selecionar qual ERP será utilizado.

Exemplo:

```env
ERP_PROVIDER=xpto
```

Valores esperados inicialmente:

```text
xpto
xyz
```

A resolução do cliente e Mapper deve ser feita através de Dependency Injection, Factory ou Strategy.

Evitar condicionais espalhados pela aplicação.

Não utilizar:

```php
if ($erp === 'xpto') {
    ...
} elseif ($erp === 'xyz') {
    ...
}
```

espalhado pelo sistema.

A escolha do ERP deve ficar centralizada.

Exemplo conceitual:

```text
ERP_PROVIDER
      │
      ▼
ERP Factory
      │
      ├── xpto → XptoErpClient + XptoProductMapper
      │
      └── xyz  → XyzErpClient + XyzProductMapper
```

---

# 40. Extensibilidade

Adicionar um novo ERP deve exigir principalmente:

```text
NovoErpClient

NovoErpMapper
```

sem modificar:

```text
ProductSyncService

ProductData

VariationData

VestiClient
```

Exemplo:

```text
XPTO ───────┐
            │
XYZ ────────┤
            │
ABC ────────┤
            ▼
       ProductData
            │
            ▼
   VestiPayloadMapper
            │
            ▼
        VestiClient
```

O núcleo da aplicação não deve conhecer os detalhes de cada ERP.

---

# 41. Idempotência

A aplicação deve considerar o comportamento de execuções repetidas.

A API Vesti disponibiliza o campo:

```text
integration_id
```

Esse campo deverá ser utilizado como identificador de integração conforme o contrato da API e a estratégia definida para o ERP.

A estratégia definitiva de idempotência deverá depender do comportamento efetivamente disponibilizado pela API Vesti.

Não criar banco de dados apenas para implementar idempotência.

Caso a API Vesti não disponibilize mecanismo suficiente para garantir idempotência ou identificar produtos previamente enviados, essa limitação deverá ser documentada no README.

Retry não deverá ser implementado de forma que uma falha de comunicação possa gerar cadastros duplicados sem uma estratégia definida.

---

# 42. README

O README deverá conter:

## Objetivo

Explicar o problema resolvido.

## Arquitetura

Explicar:

```text
ERP → Hub → Vesti
```

## Requisitos

* Docker;
* Docker Compose.

## Instalação

Exemplo:

```bash
git clone <repository>

cd vesti-hub

docker compose up -d --build
```

## Configuração

Explicar:

```text
ERP_PROVIDER

ERP_XPTO_API_URL

ERP_XYZ_API_URL

VESTI_API_URL

VESTI_API_KEY

VESTI_COMPANY_ID
```

## ERP Mock

Explicar os endpoints:

```text
GET /erp/xpto/produtos.json
GET /erp/xpto/variacoes.json

GET /erp/xyz/produtos.json
GET /erp/xyz/variacoes.json
```

## Execução

```bash
docker compose exec app php artisan products:sync
```

## Testes

```bash
docker compose exec app php artisan test
```

## Múltiplos ERPs

Explicar como XPTO e XYZ são normalizados para o mesmo modelo interno.

## Integração Vesti

Explicar:

```text
POST /v1/products/company/{company_id}
```

e a utilização do header:

```text
apikey
```

## Arquitetura

Explicar as responsabilidades de cada componente.

## Tratamento de erros

Explicar como erros dos ERPs e da Vesti são tratados.

## Escalabilidade

Explicar a estratégia de indexação de variações e envio em lotes.

## Idempotência

Documentar a estratégia adotada e eventuais limitações da API.

## Uso de IA

Descrever como ferramentas de IA foram utilizadas durante o desenvolvimento.

---

# 43. Uso de IA

A IA poderá ser utilizada como ferramenta de apoio.

Seu uso deve ser documentado no README.

Exemplos:

* Análise dos requisitos;
* Análise da documentação da API Vesti;
* Proposta de arquitetura;
* Geração de código inicial;
* Geração de testes;
* Revisão de código;
* Identificação de problemas;
* Refatoração;
* Documentação.

O código gerado por IA deverá ser revisado, testado e compreendido pelo desenvolvedor.

---

# 44. Critérios de aceitação

O projeto deverá:

1. Iniciar através do Docker Compose;
2. Executar sem banco de dados;
3. Disponibilizar o ERP Mock;
4. Disponibilizar os endpoints do ERP XPTO;
5. Disponibilizar os endpoints do ERP XYZ;
6. Permitir consumo dessas APIs pelo Hub;
7. Suportar XPTO;
8. Suportar XYZ;
9. Normalizar ambos para o mesmo modelo interno;
10. Relacionar corretamente produtos e variações;
11. Gerar o payload esperado pela Vesti;
12. Utilizar `POST /v1/products/company/{company_id}`;
13. Utilizar autenticação através do header `apikey`;
14. Enviar produtos através da API Vesti;
15. Tratar erros de integração;
16. Possuir testes automatizados;
17. Possuir documentação;
18. Possuir instruções de execução;
19. Demonstrar uso de IA;
20. Permitir inclusão de novos ERPs com baixo impacto no núcleo da aplicação;
21. Evitar buscas O(n²) no relacionamento de produtos e variações;
22. Permitir envio de múltiplos produtos em uma requisição;
23. Não armazenar credenciais no código;
24. Não depender de banco de dados, Redis ou filas na primeira versão.

---

# 45. Princípio arquitetural

O princípio central da aplicação é:

```text
SOURCE

  ↓

ERP API

  ↓

ERP ADAPTER

  ↓

NORMALIZATION

  ↓

DOMAIN MODEL

  ↓

VESTI PAYLOAD MAPPER

  ↓

VESTI ADAPTER

  ↓

DESTINATION
```

O núcleo do sistema não deve depender da estrutura específica de nenhum ERP.

XPTO e XYZ são apenas fontes diferentes de dados que precisam produzir a mesma saída.

O objetivo final é:

```text
ERP XPTO ──────┐
               │
ERP XYZ ───────┤
               │
ERP N ─────────┘
               │
               ▼
          ProductData
               │
               ▼
      VestiPayloadMapper
               │
               ▼
        Vesti Payload
               │
               ▼
          VestiClient
               │
               ▼
           Vesti API
```

A arquitetura deve favorecer extensibilidade sem introduzir complexidade desnecessária.

O modelo interno deve permanecer independente tanto dos ERPs quanto do formato específico da API Vesti.

A responsabilidade de adaptar o domínio para a Vesti deve permanecer isolada no `VestiPayloadMapper`, enquanto a comunicação HTTP deve permanecer isolada no `VestiClient`.
