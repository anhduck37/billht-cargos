<?php namespace Tests\Repositories;

use App\Models\user;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

class usersRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var UserRepository
     */
    protected $usersRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->usersRepo = \App::make(UserRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_users()
    {
        $users = factory(user::class)->make()->toArray();

        $createdusers = $this->usersRepo->create($users);

        $createdusers = $createdusers->toArray();
        $this->assertArrayHasKey('id', $createdusers);
        $this->assertNotNull($createdusers['id'], 'Created user must have id specified');
        $this->assertNotNull(user::find($createdusers['id']), 'user with given id must be in DB');
        $this->assertModelData($users, $createdusers);
    }

    /**
     * @test read
     */
    public function test_read_users()
    {
        $users = factory(user::class)->create();

        $dbusers = $this->usersRepo->find($users->id);

        $dbusers = $dbusers->toArray();
        $this->assertModelData($users->toArray(), $dbusers);
    }

    /**
     * @test update
     */
    public function test_update_users()
    {
        $users = factory(user::class)->create();
        $fakeusers = factory(user::class)->make()->toArray();

        $updatedusers = $this->usersRepo->update($fakeusers, $users->id);

        $this->assertModelData($fakeusers, $updatedusers->toArray());
        $dbusers = $this->usersRepo->find($users->id);
        $this->assertModelData($fakeusers, $dbusers->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_users()
    {
        $users = factory(user::class)->create();

        $resp = $this->usersRepo->delete($users->id);

        $this->assertTrue($resp);
        $this->assertNull(user::find($users->id), 'user should not exist in DB');
    }
}
