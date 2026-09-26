# Supplier Management

Projeto Laravel com Vue, TypeScript e Inertia, iniciado a partir do [starter kit oficial](https://github.com/laravel/vue-starter-kit). Inclui as telas de autenticação do starter kit.

## Desenvolvimento com Docker

Pré-requisitos: Docker Engine ou Docker Desktop com Docker Compose e acesso ao daemon Docker pelo seu usuário. Não é necessário instalar PHP, Composer ou Node no computador. No Windows, execute os comandos pelo WSL2.

Na primeira execução:

```sh
sh docker/setup.sh
```

O script copia `.env.example` se necessário, constrói a imagem com PHP 8.4, Composer e Node 24, instala as dependências, gera a chave da aplicação, inicia o MySQL 8.4, executa as migrations e sobe os serviços. As dependências ficam em `vendor/` e `node_modules/` na pasta do projeto. Os arquivos são criados com o usuário que executou o script; não execute com `sudo`.

Acesse **http://localhost:8000**. O Vite usa a porta **5173** para atualizar o frontend automaticamente enquanto você edita os arquivos. Abra a aplicação pela porta 8000.

Serviços:

| Serviço | Função                                          |
| ------- | ----------------------------------------------- |
| `app`   | Servidor de desenvolvimento Laravel             |
| `vite`  | Vue/Inertia com atualização automática          |
| `queue` | Processamento das filas do Laravel              |
| `mysql` | Banco MySQL com volume persistente `mysql-data` |

O Laravel conecta ao banco pelo endereço `mysql:3306`, usando as variáveis `DB_*` do `.env`. O banco fica acessível pela rede interna do Docker. As senhas de exemplo são apenas para desenvolvimento local. Alterar as credenciais no `.env` depois da criação do volume não altera os usuários já existentes no MySQL.

## Comandos úteis

```sh
# Iniciar novamente após a instalação
docker compose up -d

# Acompanhar os logs
docker compose logs -f app vite queue

# Executar migrations ou comandos Artisan
docker compose exec app php artisan migrate
docker compose exec app php artisan tinker

# Instalar dependências
docker compose exec app composer require nome/pacote
docker compose exec vite npm install nome-do-pacote

# Compilar o frontend e executar os testes do starter kit
docker compose exec vite npm run build
docker compose exec app php artisan test

# Reiniciar o worker após alterar código de jobs
docker compose restart queue

# Encerrar preservando o banco
docker compose down
```

Os testes usam SQLite em memória, separado do MySQL de desenvolvimento. A imagem inclui os dois drivers.

`docker compose down -v` também **apaga os dados do banco**. Use apenas se quiser recriar o ambiente sem os dados anteriores.

Se mudar o Dockerfile, execute novamente `sh docker/setup.sh`. Se as portas 8000 ou 5173 estiverem ocupadas, libere-as antes de iniciar. Ao personalizar as portas, mantenha `compose.yaml`, `APP_URL` e as opções `server` de `vite.config.ts` alinhadas. Em ambientes WSL2 com problemas de atualização automática, mantenha o projeto no sistema de arquivos Linux.

Esta configuração utiliza o servidor de desenvolvimento do Laravel e o Vite. Para produção, é necessário preparar uma imagem própria, compilar os assets e configurar um servidor de aplicação adequado.

## Falha de DNS durante a instalação

Se Composer ou npm retornarem `Could not resolve host` dentro do container, mas a internet funcionar no host Linux, você pode usar temporariamente a rede do host apenas para instalar as dependências:

```sh
DEPENDENCY_NETWORK=host sh docker/setup.sh
```

Os serviços da aplicação continuam na rede interna do Compose. Esse contorno foi necessário na instalação inicial deste computador.
