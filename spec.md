# Vesti Hub — Specification

## 1. Objetivo

Construir um Hub de Integração em Laravel capaz de:

1. Consumir APIs de diferentes sistemas ERP;
2. Obter produtos e suas variações;
3. Normalizar estruturas diferentes de ERP para um modelo interno comum;
4. Enviar os produtos normalizados para a API da Vesti.

O sistema deve permitir adicionar novos ERPs sem modificar a lógica principal de sincronização.

O projeto será desenvolvido como um desafio técnico, priorizando:

* Responsabilidade única;
* Baixo acoplamento;
* Legibilidade;
* Reutilização;
* Testabilidade;
* Performance;
* Escalabilidade;
* Extensibilidade.

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

O projeto será composto por dois componentes:

```text
ERP Mock
    ↓ HTTP
Vesti Hub
    ↓ HTTP
Vesti API
```

Arquitetura:

```text
┌──────────────────────────────┐
│          ERP MOCK            │
│                              │
│ GET /erp/produtos.json       │
│ GET /erp/variacoes.json      │
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

Ele deverá expor:

```text
GET /erp/produtos.json
GET /erp/variacoes.json
```

O ERP Mock deverá retornar os dados fornecidos nos arquivos JSON do desafio.

O Hub deverá consumir essas URLs através de HTTP.

O Hub não deve acessar diretamente os arquivos JSON.

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
code              →         referencia
name              →         nome
description       →         descricao
price             →         preco
price_promotional →         promocao
composition       →         composicao
brand              →         marca
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
sku               →         variacao
size              →         tamanho
color             →         cor
quantity          →         quantidade
unit_measurement  →         unidade
ordering          →         ordem
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

Exemplo conceitual:

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

Exemplo conceitual:

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

Os tipos definitivos devem ser ajustados conforme os dados reais e o contrato da API Vesti.

O domínio não deve conhecer `code`, `referencia`, `name`, `nome`, etc. como conceitos diferentes.

Após a normalização, deve existir somente o modelo interno.

---

# 10. Relacionamento entre produto e variação

O relacionamento deve utilizar o identificador contido no SKU/variação.

Exemplo:

```text
Produto:

code = 8750014
```

Variação:

```text
sku = 8750014_G_PRETA
```

O código do produto corresponde ao primeiro segmento do SKU.

No XYZ:

```text
referencia = 8750014

variacao = 8750014_G_PRETA
```

O Mapper deve normalizar ambos para:

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

Criar contratos semelhantes a:

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
│
├── XptoErpClient
└── XyzErpClient
```

E:

```text
ProductMapperInterface
│
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
* Retornar os dados recebidos.

Não deve:

* Normalizar dados;
* Conhecer a Vesti;
* Criar produtos;
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
* Controlar o processo de sincronização.

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
VestiClient
        │
        ▼
Vesti API
```

O serviço não deve implementar diretamente detalhes de HTTP.

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
    public function createProduct(ProductData $product): array;
}
```

O cliente deverá utilizar o HTTP Client do Laravel.

---

# 18. API Vesti

Utilizar a documentação oficial fornecida no desafio:

```text
POST /products
```

Documentação:

https://integracao.meuvesti.com/doc/api/index.html#api-Produtos-post_products

Antes da implementação do cliente, analisar a documentação para determinar:

* URL;
* Método;
* Autenticação;
* Headers;
* Payload;
* Campos obrigatórios;
* Campos opcionais;
* Estrutura das variações;
* Respostas de sucesso;
* Respostas de erro.

Não inventar campos que não estejam presentes no contrato da API.

---

# 19. Separação do payload da Vesti

O modelo interno não deve necessariamente ser igual ao payload da Vesti.

A aplicação deverá separar:

```text
ERP
 ↓
ERP Mapper
 ↓
ProductData
 ↓
Vesti Payload Mapper
 ↓
Vesti API
```

Caso o contrato da Vesti exija uma estrutura diferente, criar um componente específico para transformar `ProductData` no payload da Vesti.

Isso evita acoplar o domínio ao formato externo da API.

---

# 20. Command

Criar um Artisan Command:

```bash
php artisan products:sync
```

O Command deve apenas iniciar o processo de sincronização.

Não colocar regras de negócio no Command.

---

# 21. Configuração

As URLs e credenciais devem ser configuráveis através do `.env`.

Exemplo:

```env
ERP_API_URL=http://erp-mock
VESTI_API_URL=
VESTI_API_KEY=
```

Não armazenar credenciais no código.

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
ERP_API_URL=http://erp-mock
```

Não utilizar:

```env
ERP_API_URL=http://localhost
```

para comunicação entre containers.

O ERP Mock deverá disponibilizar:

```text
GET /erp/produtos.json
GET /erp/variacoes.json
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

O ERP Mock poderá selecionar qual ERP está sendo simulado através de configuração, rota ou outra abordagem simples.

A implementação deve deixar clara a separação entre XPTO e XYZ.

---

# 25. Banco de dados

Não utilizar banco de dados.

O projeto não deverá criar persistência para:

* Produtos;
* Variações;
* Sincronizações;
* Jobs;
* Histórico.

Os dados serão obtidos das APIs do ERP e enviados à Vesti.

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

* Timeout do ERP;
* Falha de conexão;
* HTTP 4xx;
* HTTP 5xx;
* JSON inválido;
* Campos obrigatórios ausentes;
* Produto inválido;
* Variação inválida;
* Falha de transformação;
* Timeout da Vesti;
* Erros HTTP da Vesti.

Configurar timeout para chamadas HTTP.

Retry poderá ser utilizado para falhas temporárias, como HTTP 5xx ou erros de conexão, desde que não gere duplicidade de cadastro.

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

Testar produtos sem variações.

Testar variações sem produto correspondente.

---

# 32. Testes do ProductSyncService

Testar:

* Busca de produtos;
* Busca de variações;
* Indexação;
* Relacionamento;
* Uso correto do Mapper;
* Criação de `ProductData`;
* Envio para Vesti;
* Tratamento de erros.

Utilizar mocks das interfaces.

---

# 33. Testes do VestiClient

Utilizar HTTP fake/mock.

Testar:

* Método POST;
* URL;
* Headers;
* Autenticação;
* Payload;
* Resposta de sucesso;
* HTTP 4xx;
* HTTP 5xx;
* Timeout.

Nunca utilizar credenciais reais nos testes.

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

Simular APIs do ERP.

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
       produtos         variações
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
              VestiClient
                    │
                    ▼
             POST /products
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

A estrutura acima é uma sugestão. Alterações são permitidas quando houver justificativa arquitetural.

---

# 39. Configuração do ERP

A arquitetura deve permitir selecionar qual ERP será utilizado.

Exemplo conceitual:

```env
ERP_PROVIDER=xpto
```

Valores esperados inicialmente:

```text
xpto
xyz
```

A resolução do cliente e Mapper deve ser feita através de Dependency Injection/Factory/Strategy, evitando condicionais espalhados pela aplicação.

Evitar código como:

```php
if ($erp === 'xpto') {
    ...
} elseif ($erp === 'xyz') {
    ...
}
```

espalhado pelo sistema.

A escolha do ERP deve ficar centralizada.

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

---

# 41. Idempotência

A aplicação deve considerar o comportamento de execuções repetidas.

A estratégia definitiva deverá depender das capacidades da API Vesti.

Não criar banco de dados apenas para implementar idempotência.

Caso a API Vesti não disponibilize mecanismo suficiente para identificar produtos previamente cadastrados, documentar a limitação.

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

Explicar `.env`.

## ERP Mock

Explicar os endpoints:

```text
GET /erp/produtos.json
GET /erp/variacoes.json
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

## Arquitetura

Explicar as responsabilidades de cada componente.

## Uso de IA

Descrever como ferramentas de IA foram utilizadas durante o desenvolvimento.

---

# 43. Uso de IA

A IA poderá ser utilizada como ferramenta de apoio.

Seu uso deve ser documentado no README.

Exemplos:

* Análise dos requisitos;
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
4. Disponibilizar `/erp/produtos.json`;
5. Disponibilizar `/erp/variacoes.json`;
6. Permitir consumo dessas APIs pelo Hub;
7. Suportar XPTO;
8. Suportar XYZ;
9. Normalizar ambos para o mesmo modelo interno;
10. Relacionar corretamente produtos e variações;
11. Gerar o payload esperado pela Vesti;
12. Enviar produtos através da API Vesti;
13. Tratar erros de integração;
14. Possuir testes automatizados;
15. Possuir documentação;
16. Possuir instruções de execução;
17. Demonstrar uso de IA;
18. Permitir inclusão de novos ERPs com baixo impacto no núcleo da aplicação.

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
       VestiPayload
               │
               ▼
          Vesti API
```

A arquitetura deve favorecer extensibilidade sem introduzir complexidade desnecessária.
