# Vesti Integration Hub — Specification

## 1. Objetivo

Desenvolver um Hub de Integração em PHP/Laravel capaz de receber dados de produtos provenientes de diferentes sistemas ERP, normalizar essas informações e cadastrá-las na plataforma de vendas Vesti através da API disponibilizada.

O sistema deverá ser projetado para suportar múltiplos ERPs com estruturas de dados diferentes, mantendo uma saída padronizada para a integração com a Vesti.

O projeto deve priorizar:

* Separação de responsabilidades;
* Legibilidade;
* Reutilização de código;
* Baixo acoplamento;
* Testabilidade;
* Performance;
* Escalabilidade;
* Facilidade de manutenção;
* Facilidade para adicionar novos ERPs.

---

# 2. Stack

Utilizar:

* PHP 8.4+;
* Laravel 8+;
* Docker;
* Docker Compose;
* PHPUnit ou Pest;
* Git;
* GitHub.

Não utilizar banco de dados nesta primeira versão.

O fluxo principal da aplicação é uma integração entre fontes externas e a API da Vesti e não exige persistência local.

---

# 3. Estrutura das fontes ERP

O desafio fornece dois ERPs:

```text
erpxpto/
├── produtos-erp.json
└── variacoes-erp.json

erpxyz/
├── produtos-erp.json
└── variacoes-erp.json
```

Os arquivos JSON devem ser considerados as fontes de dados disponibilizadas pelo desafio.

A aplicação deve ser estruturada de forma que a fonte dos dados possa posteriormente ser substituída por uma API HTTP sem necessidade de alterar a lógica de negócio.

---

# 4. ERP XPTO

## 4.1 Produtos

O ERP XPTO fornece produtos com uma estrutura semelhante a:

```json
{
    "code": 1759710,
    "name": "CALÇA SKINNY",
    "description": null,
    "price": "94,90",
    "price_promotional": 0,
    "composition": "100% Algodão",
    "brand": "Joana Modas"
}
```

Os campos observados são:

* `code`
* `name`
* `description`
* `price`
* `price_promotional`
* `composition`
* `brand`

Os dados reais do arquivo fornecido pelo desafio devem ser considerados a fonte de verdade.

Não assumir campos que não existam no arquivo.

---

# 5. Variações do ERP XPTO

As variações possuem uma estrutura semelhante a:

```json
{
    "sku": "1759710_46_MEDIA",
    "size": 46,
    "color": "MEDIA",
    "quantity": 1,
    "unit_measurement": "UN",
    "ordering": 0
}
```

Os campos observados são:

* `sku`
* `size`
* `color`
* `quantity`
* `unit_measurement`
* `ordering`

---

# 6. Relacionamento entre produto e variação

No ERP XPTO, o relacionamento entre uma variação e o produto pai está representado no SKU.

Exemplo:

```text
Produto:
code = 1759710

Variação:
sku = 1759710_46_MEDIA
```

O código do produto pai corresponde ao primeiro segmento do SKU:

```text
1759710_46_MEDIA
│
└── product code = 1759710
```

A estrutura deve ser interpretada de forma que:

```text
1759710_46_MEDIA

1759710 → produto pai
46      → tamanho
MEDIA   → cor
```

O código utilizado para relacionar produto e variação não deve ser baseado no nome do produto.

O campo `name` não é um identificador único.

Por exemplo, existem diversos produtos chamados:

```text
CALÇA SKINNY
BLUSA CROPPED
BLUSA BABY LOOK
CALÇA CIGARRETE
```

mas com códigos diferentes.

Portanto, o relacionamento deve utilizar o identificador apropriado fornecido pelo ERP.

---

# 7. Indexação das variações

Para evitar processamento desnecessário, as variações devem ser indexadas por produto pai antes do processamento dos produtos.

Evitar uma implementação equivalente a:

```text
para cada produto:
    procurar todas as variações
```

Preferir:

```text
carregar todas as variações
        ↓
indexar por produto
        ↓
processar produtos
```

Exemplo conceitual:

```text
variationsByProduct

1759710 → [
    variation 1,
    variation 2,
    variation 3
]

1759841 → [
    variation 4,
    variation 5
]
```

A implementação deve buscar eficiência para grandes volumes de dados.

---

# 8. ERP XYZ

O ERP XYZ também possui:

```text
erpxyz/produtos-erp.json
erpxyz/variacoes-erp.json
```

A estrutura do ERP XYZ deverá ser analisada diretamente a partir dos arquivos fornecidos.

Não assumir que os campos possuem os mesmos nomes utilizados pelo ERP XPTO.

O ERP XYZ deve possuir sua própria camada de transformação caso sua estrutura seja diferente.

Exemplo conceitual:

```text
ERP XPTO
   ↓
XptoProductMapper
   ↓
ProductData


ERP XYZ
   ↓
XyzProductMapper
   ↓
ProductData
```

Ambos devem produzir o mesmo modelo interno.

---

# 9. Abstração para múltiplos ERPs

A arquitetura deve permitir a inclusão de novos ERPs sem alterar o núcleo da aplicação.

Criar uma abstração equivalente a:

```php
interface ErpClientInterface
{
    public function getProducts(): array;

    public function getVariations(): array;
}
```

Cada ERP deverá possuir sua implementação específica quando necessário.

Exemplo:

```text
ErpClientInterface
        │
        ├── XptoErpClient
        │
        └── XyzErpClient
```

A implementação responsável por obter os dados não deve conter regras específicas da Vesti.

---

# 10. Fonte de dados

Inicialmente, os dados poderão ser obtidos dos arquivos JSON fornecidos pelo desafio.

A arquitetura deve evitar acoplar o domínio diretamente ao sistema de arquivos.

O objetivo é permitir posteriormente uma implementação HTTP:

```text
JsonErpClient
```

ou:

```text
HttpErpClient
```

sem alterar o restante do fluxo.

Conceitualmente:

```text
                 ErpClientInterface
                        │
              ┌─────────┴─────────┐
              │                   │
         JSON Source         HTTP Source
              │                   │
              └─────────┬─────────┘
                        ▼
                 Modelo da aplicação
```

---

# 11. Modelo interno

Criar um modelo interno independente do ERP.

Esse modelo representa um produto normalizado que será utilizado pelo restante da aplicação.

Exemplo conceitual:

```php
final readonly class ProductData
{
    public function __construct(
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

Os campos definitivos devem ser definidos após analisar:

1. Todos os dados relevantes dos dois ERPs;
2. A documentação da API da Vesti.

Não criar campos sem necessidade.

---

# 12. Mapper

Criar uma abstração para converter os dados de cada ERP para o modelo interno.

Exemplo:

```php
interface ProductMapperInterface
{
    public function map(
        array $product,
        array $variations
    ): ProductData;
}
```

Implementações esperadas:

```text
ProductMapperInterface
        │
        ├── XptoProductMapper
        │
        └── XyzProductMapper
```

O Mapper deve ser responsável exclusivamente pela transformação dos dados.

Não deve:

* Fazer requisições HTTP;
* Cadastrar produtos na Vesti;
* Controlar o processo de sincronização;
* Acessar banco de dados;
* Conter lógica específica de infraestrutura.

---

# 13. Normalização

Os dados provenientes dos diferentes ERPs devem ser convertidos para uma estrutura interna única.

Fluxo:

```text
ERP XPTO
   │
   ▼
Dados XPTO
   │
   ▼
XptoProductMapper
   │
   ▼
ProductData
```

ou:

```text
ERP XYZ
   │
   ▼
Dados XYZ
   │
   ▼
XyzProductMapper
   │
   ▼
ProductData
```

A partir desse ponto, o restante da aplicação deve trabalhar exclusivamente com o modelo interno.

---

# 14. Integração com a Vesti

Criar uma abstração para a plataforma de destino.

Exemplo:

```php
interface SalesPlatformInterface
{
    public function createProduct(
        ProductData $product
    ): array;
}
```

Criar uma implementação:

```text
VestiClient
```

A implementação deve utilizar o HTTP Client do Laravel.

O endpoint indicado pelo desafio é:

```text
POST /products
```

A URL base e demais informações de autenticação devem ser configuradas através de variáveis de ambiente.

---

# 15. Documentação da API Vesti

Antes de implementar o `VestiClient`, analisar a documentação oficial fornecida no desafio:

```text
https://integracao.meuvesti.com/doc/api/index.html#api-Produtos-post_products
```

Determinar exatamente:

* URL;
* Método HTTP;
* Autenticação;
* Headers;
* Campos obrigatórios;
* Campos opcionais;
* Estrutura do produto;
* Estrutura das variações;
* Formatos dos valores;
* Respostas de sucesso;
* Respostas de erro.

Não inventar o payload da Vesti.

O modelo interno deverá ser adaptado ao contrato real da API.

---

# 16. Configuração

Credenciais e configurações externas devem utilizar variáveis de ambiente.

Exemplo:

```env
VESTI_API_URL=
VESTI_API_KEY=
ERP_API_URL=
```

Não armazenar credenciais no código-fonte.

Criar:

```text
.env.example
```

O `.env` real não deve ser versionado.

---

# 17. Serviço de sincronização

Criar um serviço responsável por orquestrar a sincronização.

Exemplo:

```php
final class ProductSyncService
{
    public function sync(): void
    {
        // obter produtos
        // obter variações
        // relacionar variações aos produtos
        // normalizar os dados
        // enviar produtos para a Vesti
    }
}
```

O serviço deve coordenar os componentes.

Ele não deve implementar diretamente:

* chamadas HTTP do ERP;
* regras específicas de um ERP;
* regras específicas da API Vesti;
* leitura direta de arquivos;
* detalhes de autenticação.

Fluxo:

```text
ProductSyncService
        │
        ├── ErpClientInterface
        │
        ├── ProductMapperInterface
        │
        └── SalesPlatformInterface
```

---

# 18. Command

Criar um Artisan Command para executar a sincronização.

Exemplo:

```bash
php artisan products:sync
```

O Command deve apenas iniciar o processo de sincronização.

A lógica de negócio deve permanecer no `ProductSyncService`.

Fluxo:

```text
Artisan Command
      ↓
ProductSyncService
      ↓
ERP
      ↓
Mapper
      ↓
ProductData
      ↓
VestiClient
      ↓
Vesti
```

---

# 19. Jobs e escalabilidade

A implementação inicial deve funcionar de maneira síncrona.

Entretanto, a arquitetura deve permitir a utilização de Jobs do Laravel posteriormente.

Para grandes volumes:

```text
ProductSyncService
        ↓
Dispatch Jobs
        ↓
┌───────────────┐
│ SyncProductJob│
├───────────────┤
│ Product 1     │
│ Product 2     │
│ Product 3     │
│ ...           │
└───────────────┘
        ↓
      Vesti
```

Não adicionar complexidade desnecessária se o volume fornecido pelo desafio não justificar.

---

# 20. Tratamento de erros

Implementar tratamento adequado para erros de integração.

Considerar:

* Falha de conexão;
* Timeout;
* Erro HTTP;
* Erro de autenticação;
* Dados inválidos;
* Produto inválido;
* Falha de transformação;
* Resposta inesperada da API.

Utilizar timeout nas requisições HTTP.

Utilizar retry somente para erros potencialmente temporários.

Erros não recuperáveis não devem ser repetidos desnecessariamente.

---

# 21. Validação

Os dados recebidos dos ERPs devem ser validados antes da transformação ou envio quando necessário.

Campos obrigatórios devem ser tratados explicitamente.

Dados inválidos não devem gerar payloads incorretos para a Vesti.

Erros de validação devem ser identificáveis e não devem interromper desnecessariamente o processamento de produtos válidos.

---

# 22. Preços

O ERP XPTO fornece valores como:

```json
{
    "price": "94,90",
    "price_promotional": 0
}
```

A aplicação deverá tratar corretamente a diferença entre:

```text
"94,90"
```

e um valor numérico.

Não realizar conversões de preço de forma que cause perda de precisão.

A regra definitiva para:

```text
price
price_promotional
```

deve ser definida de acordo com o contrato da Vesti.

Não assumir que `price_promotional = 0` possui significado específico sem verificar a regra do sistema.

---

# 23. Identificação de produtos

O nome do produto não deve ser utilizado como identificador único.

Exemplo:

```text
CALÇA SKINNY
CALÇA SKINNY
CALÇA SKINNY
```

podem representar produtos diferentes.

O identificador do produto deverá ser baseado no campo apropriado fornecido pelo ERP.

No ERP XPTO, o campo observado para identificação do produto pai é:

```text
code
```

---

# 24. Idempotência

A sincronização deve considerar a possibilidade de execução repetida.

A solução deverá analisar o comportamento da API da Vesti para determinar como produtos existentes podem ser identificados.

Não criar persistência local apenas para implementar idempotência se isso não for necessário para o escopo do desafio.

Caso a API não permita determinar adequadamente se um produto já foi cadastrado, documentar essa limitação no README.

---

# 25. Testes

O projeto deve possuir testes automatizados.

Priorizar testes unitários.

## 25.1 XptoProductMapper

Testar:

```text
- transformação correta do produto;
- transformação das variações;
- relacionamento correto entre produto e variações;
- conversão dos preços;
- tratamento de descrição nula;
- tratamento de dados inválidos.
```

## 25.2 XyzProductMapper

Testar a transformação da estrutura específica do ERP XYZ.

O resultado deve ser compatível com o mesmo modelo interno utilizado pelo XPTO.

## 25.3 ProductSyncService

Testar:

```text
- obtenção dos produtos;
- obtenção das variações;
- indexação das variações;
- relacionamento entre produtos e variações;
- utilização do mapper;
- envio para a plataforma;
- tratamento de falhas.
```

## 25.4 VestiClient

Utilizar fake/mock HTTP.

Não realizar chamadas reais para a API da Vesti durante os testes.

Testar:

```text
- método HTTP;
- URL;
- headers;
- autenticação;
- payload;
- resposta de sucesso;
- erro HTTP;
- timeout;
```

---

# 26. TDD

Sempre que possível, desenvolver novas regras utilizando o ciclo:

```text
RED
 ↓
Escrever teste que falha
 ↓
GREEN
 ↓
Implementar o mínimo necessário
 ↓
REFACTOR
 ↓
Melhorar a implementação
```

O objetivo não é criar testes apenas após toda a implementação.

Os testes devem orientar a construção das principais regras de negócio.

---

# 27. Docker

O projeto deve ser executado utilizando Docker.

A configuração deve permitir iniciar a aplicação com:

```bash
docker compose up -d --build
```

A aplicação deverá disponibilizar o Laravel através do ambiente Docker.

Não é necessário criar container de banco de dados nesta versão.

Estrutura conceitual:

```text
Docker Compose
      │
      └── app
          ├── PHP
          ├── Apache
          ├── Composer
          └── Laravel
```

---

# 28. Estrutura de diretórios sugerida

A estrutura pode seguir:

```text
vesti-integration-hub/
│
├── app/
│   ├── Contracts/
│   │   ├── ErpClientInterface.php
│   │   ├── ProductMapperInterface.php
│   │   └── SalesPlatformInterface.php
│   │
│   ├── DTOs/
│   │   └── ProductData.php
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
│   ├── Mappers/
│   │   ├── XptoProductMapper.php
│   │   └── XyzProductMapper.php
│   │
│   └── Console/
│       └── Commands/
│           └── SyncProductsCommand.php
│
├── data/
│   ├── erpxpto/
│   │   ├── produtos-erp.json
│   │   └── variacoes-erp.json
│   │
│   └── erpxyz/
│       ├── produtos-erp.json
│       └── variacoes-erp.json
│
├── tests/
│   ├── Unit/
│   │   ├── Mappers/
│   │   └── Services/
│   │
│   └── Feature/
│
├── Dockerfile
├── docker-compose.yml
├── .env.example
├── .gitignore
├── README.md
└── SPEC.md
```

A estrutura acima é uma sugestão. O desenvolvedor pode ajustá-la se houver uma justificativa arquitetural clara.

---

# 29. README

O README deve conter:

## Objetivo

Explicar o problema resolvido.

## Arquitetura

Explicar:

```text
ERP → Hub → Vesti
```

e a abstração para múltiplos ERPs.

## Requisitos

Listar:

* Docker;
* Docker Compose;
* Git.

## Instalação

Explicar passo a passo:

```bash
git clone ...
cd ...
docker compose up -d --build
```

## Configuração

Explicar o `.env`.

## Execução

Explicar como executar:

```bash
php artisan products:sync
```

ou através do container:

```bash
docker compose exec app php artisan products:sync
```

## Testes

Explicar:

```bash
docker compose exec app php artisan test
```

## Adição de novos ERPs

Explicar como criar um novo Adapter/Mapper sem modificar o núcleo da aplicação.

## Utilização de IA

Documentar de maneira transparente como ferramentas de IA foram utilizadas durante o desenvolvimento.

---

# 30. Utilização de IA

A IA pode ser utilizada durante o desenvolvimento.

O uso deve ser documentado no README.

Exemplos de utilização:

* Análise inicial do problema;
* Sugestões de arquitetura;
* Geração de testes;
* Revisão de código;
* Identificação de possíveis problemas;
* Sugestões de refatoração;
* Auxílio na documentação;
* Geração de código boilerplate.

A implementação final deve ser revisada e compreendida pelo desenvolvedor.

A IA não deve ser utilizada como substituição da análise técnica.

---

# 31. Critérios de qualidade

A implementação será avaliada considerando:

### Responsabilidade

Cada classe e método deve possuir uma responsabilidade clara.

### Escopo

Variáveis devem possuir o menor escopo necessário.

### Legibilidade

O código deve ser simples de compreender.

### Reutilização

Lógicas comuns devem ser reutilizadas em vez de duplicadas.

### Performance

Evitar processamento e chamadas HTTP desnecessárias.

### Escalabilidade

A arquitetura deve permitir aumento do volume de produtos e inclusão de novos ERPs.

### Testabilidade

As principais regras devem poder ser testadas sem depender de serviços externos.

### Abstração

A implementação deve permitir:

```text
ERP A
ERP B
ERP C
ERP N
      ↓
Modelo interno comum
      ↓
Vesti
```

---

# 32. Restrições arquiteturais

Não:

* Colocar toda a lógica no Controller;
* Fazer chamadas HTTP diretamente no Controller;
* Acoplar o Mapper à Vesti;
* Acoplar a regra de negócio ao sistema de arquivos;
* Utilizar o nome do produto como identificador;
* Armazenar credenciais no código;
* Criar banco de dados sem necessidade;
* Fazer chamadas reais à Vesti nos testes;
* Duplicar a lógica de sincronização para cada ERP.

Preferir:

```text
Interfaces
DTOs
Services
Mappers
Adapters/Clients
Dependency Injection
Automated Tests
```

---

# 33. Critério de aceitação principal

Dado um conjunto de dados de qualquer ERP suportado:

```text
Produtos
+
Variações
```

o sistema deverá:

```text
1. Ler os dados;
2. Identificar os produtos;
3. Relacionar suas variações;
4. Normalizar os dados;
5. Criar um modelo interno comum;
6. Converter para o formato exigido pela Vesti;
7. Enviar o produto através da API;
8. Tratar erros adequadamente.
```

O processo deverá funcionar independentemente da estrutura original do ERP, desde que exista um Adapter/Mapper correspondente.

---

# 34. Princípio arquitetural

O principal objetivo da arquitetura é separar:

```text
ORIGEM
ERP
  ↓
ADAPTAÇÃO
Mapper
  ↓
DOMÍNIO
ProductData
  ↓
DESTINO
Vesti
```

Dessa maneira, alterações na estrutura de um ERP não devem exigir alterações na integração com a Vesti.

Da mesma forma, alterações na integração com a Vesti não devem exigir alterações nos Mappers dos ERPs.

A aplicação deve seguir o princípio:

```text
"Novas integrações devem ser adicionadas,
não exigir alterações no núcleo existente."
```
