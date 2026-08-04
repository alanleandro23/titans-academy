# ⚽ Titans Academy Futebol

<p align="center">
  <img src="assets/img/logo.png" alt="Titans Academy Futebol" width="180">
</p>

<p align="center">
Sistema completo para gestão administrativa e esportiva de escolas de futebol.
</p>

---

## Sobre o projeto

O **Titans Academy Futebol** é um sistema web desenvolvido para auxiliar na gestão completa de uma escola de futebol, centralizando informações administrativas, esportivas e institucionais em uma única plataforma.

O sistema permite gerenciar atletas, professores, categorias, competições, partidas, estatísticas, notícias, conteúdos multimídia e informações institucionais, oferecendo também um portal público para divulgação da academia.

---

# Principais funcionalidades

## Área Pública

- Página inicial institucional
- Sobre a academia
- Equipe técnica
- Atletas
- Categorias
- Competições
- Partidas
- Últimos resultados
- Próximos jogos
- Conquistas
- Notícias
- Galeria de fotos
- Wallpapers
- Vídeos
- Mural
- Contato
- Redes sociais

---

## Área Administrativa

Painel administrativo completo para gerenciamento da academia.

### Atletas

- Cadastro completo
- Foto
- Categoria
- Posição
- Dados pessoais
- Responsáveis
- Informações médicas
- Histórico esportivo

---

### Professores

- Cadastro
- Cargo
- Foto
- Mini currículo
- Redes sociais

---

### Categorias

Gerenciamento das categorias da academia.

Exemplo:

- Sub-07
- Sub-09
- Sub-11
- Sub-13
- Sub-15
- Sub-17
- Adulto

---

### Competições

Cadastro de:

- Campeonatos
- Copas
- Festivais
- Amistosos
- Jogos-treino

Informações:

- Categoria
- Formato
- Local
- Data
- Regulamento
- Descrição

---

### Times

Cadastro dos clubes participantes.

Informações:

- Nome
- Escudo
- Técnico
- Cidade
- Categoria

---

### Partidas

Cadastro completo de partidas.

- Data
- Horário
- Local
- Competição
- Mandante
- Visitante
- Escudos
- Placar

---

### Elencos

Cada partida possui:

- Titulares
- Reservas
- Comissão técnica
- Técnico responsável

---

### Súmula

Registro completo dos eventos da partida.

Eventos suportados:

- Gol
- Assistência
- Cartão amarelo
- Cartão vermelho
- Substituição

Cada evento registra:

- Equipe
- Atleta
- Tempo da partida
- Minuto
- Observações

---

### Estatísticas

O sistema gera automaticamente:

- Jogos disputados
- Vitórias
- Empates
- Derrotas
- Gols marcados
- Gols sofridos
- Saldo de gols
- Artilharia
- Assistências
- Cartões

---

### Conquistas

Gerenciamento dos títulos conquistados pela academia.

- Cadastro manual
- Associação às competições
- Galeria pública

---

### Notícias

Publicação de notícias e comunicados.

---

### Galeria Multimídia

- Fotos
- Vídeos
- Wallpapers

---

### Mural

Espaço para mensagens enviadas pelos visitantes.

---

### Personalização

Permite configurar:

- Logo
- Banner principal
- Informações institucionais
- Redes sociais
- Rodapé
- Dados de contato

---

# Tecnologias utilizadas

- PHP 8+
- MySQL
- HTML5
- CSS3
- JavaScript
- Bootstrap
- Font Awesome
- PDO

---

# Requisitos

- PHP 8.0 ou superior
- MySQL 5.7 ou superior
- Apache ou Nginx
- Extensão PDO habilitada

---

# Instalação

## 1. Clone o repositório

```bash
git clone https://github.com/SEU-USUARIO/titans-academy.git
```

## 2. Acesse a pasta

```bash
cd titans-academy
```

## 3. Crie um banco de dados

```
escolinha_futebol
```

## 4. Importe o arquivo SQL

```
database.sql
```

## 5. Configure a conexão

Edite:

```
config/config.php
```

Informando:

- Host
- Banco
- Usuário
- Senha

## 6. Execute

```
http://localhost/titans-academy
```

---

# Estrutura do projeto

```
admin/
assets/
config/
database/
includes/
uploads/

index.php
login.php
logout.php
```

---

# Perfis de utilização

O sistema foi desenvolvido para atender:

- Escolas de futebol
- Academias esportivas
- Projetos sociais
- Clubes de formação
- Centros de treinamento
- Equipes amadoras

---

# Objetivos do sistema

- Centralizar informações administrativas.
- Organizar competições e partidas.
- Registrar estatísticas esportivas.
- Divulgar a academia na internet.
- Facilitar a comunicação com atletas e responsáveis.
- Preservar o histórico esportivo da instituição.

---

# Segurança

O sistema utiliza:

- Sessões autenticadas
- Controle de acesso ao painel administrativo
- Prepared Statements (PDO)
- Upload controlado de arquivos
- Organização separada entre área pública e administrativa

---

# Licença

Este projeto é de uso exclusivo da **Titans Academy Futebol**.

Todos os direitos reservados.
