# API de Produtos

Atividade de repository feita a partir do projeto do professor.

Para rodar, instale as dependências com `composer install`, copie `.env.example` para `.env` e preencha a senha do seu PostgreSQL. Crie o banco `atividade_repository` e execute:

```bash
php artisan key:generate
php artisan migrate
php artisan serve
```

A rota `/api/produtos` permite cadastrar, listar, consultar, atualizar e excluir produtos.

Na listagem, use `search` para buscar pelo nome, `preco_min` e `preco_max` para filtrar pelo preço. Exemplo: `/api/produtos?search=Caderno&preco_min=10&preco_max=30`.

Para atualizar, use `PUT` ou `PATCH /api/produtos/{id}`. Para excluir, use `DELETE /api/produtos/{id}`. O preço deve ficar pelo menos 10% acima do custo.

Para testar: `php artisan test`.
