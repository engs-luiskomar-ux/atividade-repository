<?php

namespace Tests\Feature;

use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdutoApiRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private function produto(): Produto
    {
        return Produto::create([
            'nome' => 'Caderno',
            'descricao' => 'Capa dura',
            'preco_custo' => 10,
            'preco' => 11,
            'quantidade' => 5,
        ]);
    }

    public function test_api_recebe_os_tres_filtros(): void
    {
        $produto = $this->produto();
        Produto::create(['nome' => 'Livro', 'preco_custo' => 10, 'preco' => 25]);
        Produto::create(['nome' => 'Caderno grande', 'preco_custo' => 10, 'preco' => 30]);
        Produto::create(['nome' => 'Caderno pequeno', 'preco_custo' => 0, 'preco' => 5]);

        $this->getJson('/api/produtos?search=Caderno&preco_min=10&preco_max=20')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $produto->id);
    }

    public function test_rejeita_filtros_invalidos(): void
    {
        foreach (['preco_min=-1', 'preco_min=abc', 'preco_max=-1', 'preco_max=abc', 'search[]=nome'] as $query) {
            $this->getJson('/api/produtos?'.$query)->assertUnprocessable();
        }

        $this->getJson('/api/produtos?preco_min=20&preco_max=10')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('preco_max');
    }

    public function test_filtros_vazios_nao_restringem_busca(): void
    {
        $this->produto();

        $this->getJson('/api/produtos?search=&preco_min=&preco_max=')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_paginacao_mantem_os_filtros(): void
    {
        foreach (range(1, 11) as $numero) {
            $this->produto();
        }
        Produto::create(['nome' => 'Livro', 'preco_custo' => 10, 'preco' => 25]);

        $resposta = $this->getJson('/api/produtos?search=Caderno&preco_min=10&preco_max=20')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 11);

        $proximaPagina = $resposta->json('links.next');
        $this->assertStringContainsString('search=Caderno', $proximaPagina);
        $this->assertStringContainsString('preco_min=10', $proximaPagina);
        $this->assertStringContainsString('preco_max=20', $proximaPagina);

        $this->getJson($proximaPagina)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 11);
    }

    public function test_put_atualiza_produto(): void
    {
        $produto = $this->produto();

        $this->putJson('/api/produtos/'.$produto->id, [
            'nome' => 'Caderno novo',
            'descricao' => null,
            'preco_custo' => 20,
            'preco' => 22,
            'quantidade' => 0,
        ])->assertOk()
            ->assertJsonPath('data.nome', 'Caderno novo')
            ->assertJsonPath('data.descricao', null)
            ->assertJsonPath('data.preco', 22)
            ->assertJsonPath('data.quantidade', 0);

        $this->assertDatabaseHas('produtos', ['id' => $produto->id, 'preco_custo' => 20, 'preco' => 22]);
    }

    public function test_patch_preserva_campos_omitidos(): void
    {
        $produto = $this->produto();

        $this->patchJson('/api/produtos/'.$produto->id, ['quantidade' => 0])
            ->assertOk()
            ->assertJsonPath('data.nome', 'Caderno')
            ->assertJsonPath('data.preco', 11)
            ->assertJsonPath('data.quantidade', 0);

        $this->assertDatabaseHas('produtos', ['id' => $produto->id, 'preco_custo' => 10, 'preco' => 11]);
    }

    public function test_atualizacao_respeita_margem_com_preco_ou_custo_parcial(): void
    {
        $produto = $this->produto();

        foreach ([['preco' => 10.99], ['preco_custo' => 11]] as $dados) {
            $this->patchJson('/api/produtos/'.$produto->id, $dados)
                ->assertUnprocessable()
                ->assertJsonValidationErrors('preco');
        }

        $this->assertDatabaseHas('produtos', ['id' => $produto->id, 'preco_custo' => 10, 'preco' => 11]);
    }

    public function test_rejeita_quantidade_negativa_sem_alterar_produto(): void
    {
        $produto = $this->produto();

        $this->patchJson('/api/produtos/'.$produto->id, ['quantidade' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantidade');

        $this->assertDatabaseHas('produtos', ['id' => $produto->id, 'quantidade' => 5]);
    }

    public function test_delete_retorna_204_e_remove_produto(): void
    {
        $produto = $this->produto();

        $this->deleteJson('/api/produtos/'.$produto->id)->assertNoContent();

        $this->assertDatabaseMissing('produtos', ['id' => $produto->id]);
        $this->getJson('/api/produtos/'.$produto->id)->assertNotFound();
        $this->deleteJson('/api/produtos/'.$produto->id)->assertNotFound();
    }

    public function test_atualizar_produto_inexistente_retorna_404(): void
    {
        $this->putJson('/api/produtos/999999', ['nome' => 'Novo'])->assertNotFound();
        $this->patchJson('/api/produtos/999999', ['quantidade' => 0])->assertNotFound();
    }
}
