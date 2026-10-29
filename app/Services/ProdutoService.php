<?php

namespace App\Services;

use App\Models\Produto;
use App\Repositories\ProdutoRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ProdutoService
{
    public function __construct(private ProdutoRepositoryInterface $produtos) {}

    public function create(array $dados): Produto
    {
        $this->verificarMargem($dados['preco'], $dados['preco_custo']);

        return $this->produtos->create($dados);
    }

    public function listar(array $filtros): LengthAwarePaginator
    {
        return $this->produtos->search($filtros);
    }

    public function update(Produto $produto, array $dados): Produto
    {
        $preco = $dados['preco'] ?? $produto->preco;
        $custo = $dados['preco_custo'] ?? $produto->preco_custo;

        $this->verificarMargem($preco, $custo);

        return $this->produtos->update($produto, $dados);
    }

    public function delete(Produto $produto): void
    {
        $this->produtos->delete($produto);
    }

    private function verificarMargem(float $preco, float $custo): void
    {
        $valor = $custo + (0.1 * $custo);
        $preco_minimo = round($valor, 2);

        if ($preco < $preco_minimo) {
            throw ValidationException::withMessages([
                'preco' => sprintf('O preço deve ser mais do que 10 por cento do valor de custo: %.2f', $preco_minimo),
            ]);
        }
    }
}
