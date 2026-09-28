# Instalação — Supplier Management

## Pré-requisitos

- Git.
- Docker Engine ou Docker Desktop com Docker Compose v2 e acesso ao daemon pelo seu usuário.
- Portas `8000` e `5173` disponíveis.
- Conexão com a internet para baixar imagens e dependências.

No Windows, execute os comandos pelo WSL2. PHP, Composer, Node.js e MySQL são fornecidos pelos containers.

## 1. Obter o projeto

```sh
git clone https://github.com/douglasgondimp/supplier-management.git
cd supplier-management
```

Se já tiver o repositório, acesse sua pasta e siga para a próxima etapa.

## 2. Preparar o ambiente

Na raiz do projeto, execute com seu usuário normal, sem `sudo`:

```sh
sh docker/setup.sh
```

O script cria o `.env` a partir de `.env.example`, caso ele não exista, e prepara o ambiente local com PHP 8.4, Composer 2, Node.js 24 e MySQL 8.4. Também instala as dependências, gera a chave da aplicação, executa as migrations, cria o link de armazenamento e inicia a aplicação, o Vite e o worker de filas.

O `.env.example` já contém a configuração de conexão com o MySQL do Docker:

```dotenv
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=supplier_management
DB_USERNAME=supplier
DB_PASSWORD=local_password
MYSQL_ROOT_PASSWORD=local_root_password
```

Se já houver um `.env`, confira esses valores antes de executar o script. Para personalizar as credenciais, copie `.env.example` para `.env` e edite os valores antes da primeira instalação. Alterar as credenciais no arquivo após a criação do banco não atualiza os usuários existentes no MySQL.

## 3. Popular o banco

Após concluir o script, execute uma vez no banco recém-instalado:

```sh
docker compose exec app php artisan db:seed
```

Essa etapa cadastra os estados, as cidades e o usuário inicial. Não repita o comando em um banco que já contenha `test@example.com`, pois o seeder tenta criar esse usuário novamente.

## 4. Acessar a aplicação

Abra **http://localhost:8000** e entre com as credenciais de desenvolvimento:

- **E-mail:** `test@example.com`
- **Senha:** `password`

A porta `5173` é usada pelo Vite e precisa permanecer disponível. A configuração fornecida destina-se ao ambiente local de desenvolvimento.

## Problemas durante a instalação

Se Composer ou npm apresentarem `Could not resolve host` dentro do container, mas a conexão funcionar no host Linux, execute:

```sh
DEPENDENCY_NETWORK=host sh docker/setup.sh
```

Essa opção usa a rede do host apenas para instalar as dependências.

Se houver conflito nas portas `8000` ou `5173`, libere-as antes de executar novamente o script.
