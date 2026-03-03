# API de Cadastro de Clientes - Laravel

Uma API RESTful desenvolvida em Laravel para gerenciar o cadastro de clientes. O sistema realiza a busca automática e preenchimento de endereços consumindo uma API externa de CEP.

## Tecnologias e Requisitos

- **PHP 8.2+** & **Laravel 10+**
- **Arquitetura:** Uso de Controllers, FormRequests para validação, Models (com $fillable, Local Scopes e Accessors) e Migrations.
- **Service Layer & Interfaces:** A integração com a API de CEP foi isolada através da AddressProviderInterface. Isso permite a fácil substituição do provedor de dados (ex: trocar ViaCEP por BrasilAPI) sem alterar a regra de negócio do Controller.
- **Facades:** Utilização de Facades nativas do Laravel:
  - Http: Para requisições HTTP externas.
  - Cache: Para otimização de performance.
  - Log: Para rastreamento de erros e debug.
  - DB: Para garantir a integridade dos dados via transações.
- **Testes:** Cobertura de testes automatizados utilizando Pest, com Mock da interface de integração externa.
- **Banco de Dados:** MySQL (via Docker) para a aplicação e SQLite (em memória) para os testes automatizados. Seeders e Factories configurados para popular o banco.

##  Pré-requisitos

Para rodar este projeto localmente, você precisará ter instalado em sua máquina:
- PHP 8.2 ou superior (com a extensão sqlite3 habilitada para rodar os testes).
- Composer.
- Docker e Docker Compose (para subir o banco MySQL de desenvolvimento).

##  Como rodar o projeto localmente

1. Clone o repositório:
   git clone https://github.com/mateussemj/cliente-api.git
   cd cliente-api

2. Instale as dependências do PHP:
   composer install

3. Configure as variáveis de ambiente:
   cp .env.example .env
   php artisan key:generate

   (Nota: O arquivo .env.example já está configurado com as credenciais padrão para conectar no contêiner Docker do projeto).

4. Suba o banco de dados MySQL via Docker:
   docker compose up -d

5. Rode as migrations e popule o banco (Factory com 20 clientes falsos):
   php artisan migrate --seed

6. Gere a documentação inicial do Swagger (opcional):
   php artisan l5-swagger:generate

7. Inicie o servidor local do Laravel:
   php artisan serve

   A API estará disponível em http://localhost:8000/api/customers.

##  Como rodar os testes

A aplicação utiliza o Pest para testes automatizados. 

Aviso importante: Os testes foram configurados para rodar em um banco de dados SQLite em memória para maior velocidade e para não interferir no banco de dados de desenvolvimento. Certifique-se de que a extensão SQLite está instalada no seu PHP (ex: sudo apt-get install php-sqlite3).

Para executar a suíte de testes (que inclui o mock da API externa de CEP), rode:
   php artisan test

## Documentação da API (Swagger)

A aplicação conta com uma documentação interativa onde é possível visualizar e testar todos os endpoints do CRUD de clientes diretamente pelo navegador.

1. Com o servidor local rodando (`php artisan serve`), acesse no navegador:
   http://localhost:8000/api/documentation

2. Caso faça alguma alteração nos atributos de documentação do Controller, será necessário regerar com o comando:
   php artisan l5-swagger:generate

##  Decisões Técnicas

- **Desacoplamento de API Externa:** A busca de CEP não ocorre diretamente no Controller. Foi criada uma AddressProviderInterface. Se a API externa principal cair, basta injetar outro Service no AppServiceProvider e o sistema continua funcionando perfeitamente.
- **Cache de Requisições:** Respostas de sucesso da API externa são cacheadas por 30 dias usando Cache::remember(). Isso evita chamadas repetidas para o mesmo CEP, melhorando o tempo de resposta e evitando bloqueios por excesso de requisições.
- **Database Transactions:** O salvamento dos dados mesclados (Input do usuário + Dados da API de CEP) ocorre dentro de um bloco DB::transaction(). Se houver qualquer falha durante a persistência, o banco sofre um rollback, garantindo que nenhum registro fique pela metade.