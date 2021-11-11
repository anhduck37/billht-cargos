<?php namespace Tests\APIs;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use App\Models\user;

class usersApiTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;

    /**
     * @test
     */
    public function test_create_users()
    {
        $users = factory(user::class)->make()->toArray();

        $this->response = $this->json(
            'POST',
            '/api/user', $users
        );

        $this->assertApiResponse($users);
    }

    /**
     * @test
     */
    public function test_read_users()
    {
        $users = factory(user::class)->create();

        $this->response = $this->json(
            'GET',
            '/api/user/'.$users->id
        );

        $this->assertApiResponse($users->toArray());
    }

    /**
     * @test
     */
    public function test_update_users()
    {
        $users = factory(user::class)->create();
        $editedusers = factory(user::class)->make()->toArray();

        $this->response = $this->json(
            'PUT',
            '/api/user/'.$users->id,
            $editedusers
        );

        $this->assertApiResponse($editedusers);
    }

    /**
     * @test
     */
    public function test_delete_users()
    {
        $users = factory(user::class)->create();

        $this->response = $this->json(
            'DELETE',
             '/api/user/'.$users->id
         );

        $this->assertApiSuccess();
        $this->response = $this->json(
            'GET',
            '/api/user/'.$users->id
        );

        $this->response->assertStatus(404);
    }
}
