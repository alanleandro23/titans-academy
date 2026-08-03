# Titans Academy Futebol — TAF V3

Portal responsivo em PHP 8.2+ e MySQL/MariaDB para gestão de uma escolinha de futebol.

## Recursos principais

- Página inicial moderna com identidade visual inspirada nas cores da Titans.
- Carrossel automático e responsivo de últimas notícias.
- Cadastro administrativo de notícias, imagem, resumo, conteúdo, data, destaque e visibilidade.
- Página individual para leitura de cada notícia.
- Personalização de nome, logo, cores, textos e contatos.
- Cadastro de professores, atletas, times, competições, partidas e conquistas.
- Cadastro próprio de categorias Sub.
- Abas e filtro de busca de atletas no site e no painel.
- Cadastro de múltiplos endereços e unidades.
- Mural de comentários com aprovação administrativa.
- Logo Titans V3 incluída em `assets/images/logo-titans-v3.png`.

## Instalação nova

1. Copie a pasta para `C:\xampp\htdocs\escolinha-futebol`.
2. Inicie Apache e MySQL no XAMPP.
3. Crie um banco no phpMyAdmin.
4. Acesse `http://localhost/escolinha-futebol/install.php`.
5. Preencha os dados do banco e crie o administrador.

A instalação nova já cria o módulo de notícias e três publicações iniciais que podem ser editadas ou excluídas.

## Atualização da V2 para V3

1. Aplique o patch V3.
2. Inicie Apache e MySQL.
3. Entre no painel administrativo.
4. Acesse `http://localhost/escolinha-futebol/upgrade_v3.php`.
5. Execute a atualização.
6. Atualize o navegador com `Ctrl + F5`.

A migration esperada é:

```text
20260802235500_home_news_carousel_v3
```

Consulte `PASSO_A_PASSO_V3.md` para as instruções completas.
