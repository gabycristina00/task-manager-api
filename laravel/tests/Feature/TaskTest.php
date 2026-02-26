<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private function autenticar(): array
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        return ['user' => $user, 'token' => $token];
    }

    public function test_usuario_pode_criar_tarefa(): void
    {
        $auth = $this->autenticar();

        $response = $this->withToken($auth['token'])
            ->postJson('/api/tasks', [
                'title'    => 'Tarefa de teste',
                'priority' => 'high',
            ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['title' => 'Tarefa de teste']);
    }

    public function test_usuario_pode_listar_suas_tarefas(): void
    {
        $auth = $this->autenticar();

        Task::factory()->count(3)->create(['user_id' => $auth['user']->id]);

        $response = $this->withToken($auth['token'])
            ->getJson('/api/tasks');

        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    public function test_usuario_nao_pode_ver_tarefa_de_outro_usuario(): void
    {
        $auth = $this->autenticar();
        $outro = User::factory()->create();
        $tarefa = Task::factory()->create(['user_id' => $outro->id]);

        $response = $this->withToken($auth['token'])
            ->getJson("/api/tasks/{$tarefa->id}");

        $response->assertStatus(403);
    }

    public function test_usuario_pode_atualizar_sua_tarefa(): void
    {
        $auth = $this->autenticar();
        $tarefa = Task::factory()->create(['user_id' => $auth['user']->id]);

        $response = $this->withToken($auth['token'])
            ->putJson("/api/tasks/{$tarefa->id}", [
                'status' => 'completed',
            ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['status' => 'completed']);
    }

    public function test_usuario_pode_deletar_sua_tarefa(): void
    {
        $auth = $this->autenticar();
        $tarefa = Task::factory()->create(['user_id' => $auth['user']->id]);

        $response = $this->withToken($auth['token'])
            ->deleteJson("/api/tasks/{$tarefa->id}");

        $response->assertStatus(204);
    }
}