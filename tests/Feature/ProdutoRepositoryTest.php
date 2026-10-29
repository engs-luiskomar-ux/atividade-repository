<?php

namespace Tests\Feature;

use App\Models\Produto;
use App\Repositories\ProdutoRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdutoRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private function produto(string $nome, float $preco): Produto
    {
        return Produto::create([
            'nome' => $nome,
            'preco_custo' => 0,
            'preco' => $preco,
            'quantidade' => 5,
        ]);
    }

    public function test_filtra_por_preco_minimo_incluindo_o_limite(): void
    {
        $this->produto('Caderno', 10);
        $this->produto('Livro', 20);

        $resultado = app(ProdutoRepositoryInterface::class)->search(['preco_min' => 20]);

        $this->assertSame(1, $resultado->total());
        $this->assertSame('Livro', $resultado->first()->nome);
    }

    public function test_filtra_por_preco_maximo_zero(): void
    {
        $this->produto('Amostra', 0);
        $this->produto('Caderno', 10);

        $resultado = app(ProdutoRepositoryInterface::class)->search(['preco_max' => 0]);

        $this->assertSame(1, $resultado->total());
        $this->assertSame('Amostra', $resultado->first()->nome);
    }

    public function test_combina_nome_e_faixa_de_preco(): void
    {
        $this->produto('Caderno pequeno', 10);
        $this->produto('Caderno grande', 20);
        $this->produto('Livro', 20);
        $this->produto('Caderno especial', 30);

        $resultado = app(ProdutoRepositoryInterface::class)->search([
            'search' => 'Caderno',
            'preco_min' => 20,
            'preco_max' => 20,
        ]);

        $this->assertSame(1, $resultado->total());
        $this->assertSame('Caderno grande', $resultado->first()->nome);
    }

    public function test_busca_nome_zero(): void
    {
        $this->produto('Produto 0', 10);
        $this->produto('Caderno', 20);

        $resultado = app(ProdutoRepositoryInterface::class)->search(['search' => '0']);

        $this->assertSame(1, $resultado->total());
        $this->assertSame('Produto 0', $resultado->first()->nome);
    }

    public function test_mantem_paginacao_sem_filtros(): void
    {
        foreach (range(1, 12) as $numero) {
            $this->produto('Produto '.$numero, 10);
        }

        $resultado = app(ProdutoRepositoryInterface::class)->search();

        $this->assertSame(12, $resultado->total());
        $this->assertCount(10, $resultado->items());
    }

    public function test_update_persiste_e_retorna_produto_atualizado(): void
    {
        $produto = $this->produto('Caderno', 10);

        $atualizado = app(ProdutoRepositoryInterface::class)->update($produto, [
            'nome' => 'Caderno grande',
            'quantidade' => 0,
        ]);

        $this->assertSame($produto->id, $atualizado->id);
        $this->assertSame('Caderno grande', $atualizado->nome);
        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'nome' => 'Caderno grande',
            'quantidade' => 0,
            'preco' => 10,
        ]);
    }

    public function test_delete_remove_produto(): void
    {
        $produto = $this->produto('Caderno', 10);

        app(ProdutoRepositoryInterface::class)->delete($produto);

        $this->assertDatabaseMissing('produtos', ['id' => $produto->id]);
    }
}
