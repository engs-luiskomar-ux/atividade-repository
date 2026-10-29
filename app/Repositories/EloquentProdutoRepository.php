<?php

namespace App\Repositories;

use App\Models\Produto;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentProdutoRepository implements ProdutoRepositoryInterface
{
    public function create(array $dados): Produto
    {
        return Produto::create($dados);
    }

    public function update(Produto $produto, array $dados): Produto
    {
        $produto->update($dados);

        return $produto->refresh();
    }

    public function delete(Produto $produto): void
    {
        $produto->delete();
    }

    public function search(array $filtros = [], int $porPagina = 10): LengthAwarePaginator
    {
        $query = Produto::query();

        if (isset($filtros['search']) && $filtros['search'] !== '') {
            $query->where('nome', 'LIKE', '%'.$filtros['search'].'%');
        }

        if (isset($filtros['preco_min'])) {
            $query->where('preco', '>=', $filtros['preco_min']);
        }

        if (isset($filtros['preco_max'])) {
            $query->where('preco', '<=', $filtros['preco_max']);
        }

        return $query->paginate($porPagina)->withQueryString();
    }
}
