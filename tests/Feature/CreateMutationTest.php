<?php

namespace Bakery\Tests\Feature;

use Bakery\Tests\Models;
use Bakery\Tests\FeatureTestCase;

class CreateMutationTest extends FeatureTestCase
{
    /** @test */
    public function it_does_not_allow_creating_entity_as_guest()
    {
        $this->withExceptionHandling();

        $query = '
            mutation {
                createArticle(input: {
                    title: "Hello world!"
                    slug: "hello-world"
                    content: "Lorem ispum"
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonMissing(['data']);
        $this->assertDatabaseMissing('articles', ['title' => 'Hello world!']);
    }

    /** @test */
    public function it_does_not_allow_creating_entity_as_user_when_there_is_no_policy()
    {
        $this->withExceptionHandling();
        $this->actingAs(factory(Models\User::class)->create());

        $query = '
            mutation {
                createCategory(input: {
                    name: "some-category"
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonMissing(['data']);
        $this->assertDatabaseMissing('categories', ['name' => 'some-category']);
    }

    /** @test */
    public function it_does_allow_creating_entity_as_user_when_it_is_allowed_by_policy()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                createArticle(input: {
                    title: "Hello world!"
                    slug: "hello-world"
                    content: "Lorem ipsum"
                    userId: 1,
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('articles', ['title' => 'Hello world!']);
    }

    /** @test */
    public function it_lets_you_create_a_has_one_relationship()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                createUser(input: {
                    email: "jane.doe@example.com",
                    name: "Jane Doe",
                    password: "secret",
                    phone: { number: "+31612345678" },
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('users', ['email' => 'jane.doe@example.com']);
        $this->assertDatabaseHas('phones', ['number' => '+31612345678', 'user_id' => '2']);
    }

    /** @test */
    public function it_lets_you_save_a_has_one_relationship()
    {
        $phone = factory(Models\Phone::class)->create();

        $this->actingAs($phone->user);

        $query = '
            mutation {
                createUser(input: {
                    email: "jane.doe@example.com",
                    name: "Jane Doe",
                    password: "secret",
                    phoneId: "'.$phone->id.'",
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('users', ['email' => 'jane.doe@example.com']);
        $this->assertDatabaseHas('phones', ['user_id' => '2']);
    }

    /** @test */
    public function it_lets_you_set_a_has_one_relationship_to_null()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                createUser(input: {
                    email: "jane.doe@example.com",
                    name: "Jane Doe",
                    password: "secret",
                    phoneId: null, 
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('users', ['email' => 'jane.doe@example.com']);
    }

    /** @test */
    public function it_lets_you_create_a_belongs_to_relationship()
    {
        $user = factory(Models\User::class)->create();

        $this->actingAs($user);

        $query = '
            mutation {
                createPhone(input: {
                    number: "+31612345678",
                    user: {
                        name: "Jane Doe",
                        email: "jane.doe@example.com",
                        password: "secret",
                    }
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('phones', ['number' => '+31612345678', 'user_id' => '2']);
        $this->assertDatabaseHas('users', ['name' => 'Jane Doe']);
    }

    /** @test */
    public function it_lets_you_to_assign_a_belongs_to_relationship()
    {
        $user = factory(Models\User::class)->create();

        $this->actingAs($user);

        $article = factory(Models\Article::class)->create(['user_id' => 1]);

        $query = '
            mutation {
                createComment(input: {
                    body: "Cool story bro",
                    userId: '.$user->id.'
                    articleId: '.$article->id.'
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('comments', ['id' => '1', 'article_id' => $article->id, 'user_id' => $user->id]);
    }

    /** @test */
    public function it_lets_you_assign_a_many_to_many_relationship()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $tags = factory(Models\Tag::class, 2)->create();

        $query = '
            mutation {
                createArticle(input: {
                    title: "Hello world"
                    slug: "hello-world"
                    content: "Hello world"
                    userId: "'.$user->id.'"
                    tagIds: ["'.$tags[0]->id.'", "'.$tags[1]->id.'"]
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('article_tag', ['article_id' => '1', 'tag_id' => '1']);
        $this->assertDatabaseHas('article_tag', ['article_id' => '1', 'tag_id' => '2']);
    }

    /** @test */
    public function it_lets_you_insert_a_has_many_relationship()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                createArticle(input: {
                    title: "Hello World"
                    slug: "hello-world"
                    content: "Lorem ipsum"
                    userId: 1
                    comments: [
                        { body: "First!", userId: 1 }
                        { body: "Great post!", userId: 1 }
                    ]
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('articles', ['title' => 'Hello World', 'user_id' => '1']);
        $this->assertDatabaseHas('comments', ['body' => 'First!', 'article_id' => '1']);
        $this->assertDatabaseHas('comments', ['body' => 'Great post!', 'article_id' => '1']);
    }

    /** @test */
    public function it_lets_you_do_deep_nested_create_mutations()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                createUser(input: {
                    email: "jane.doe@example.com"
                    name: "Jane Doe"
                    password: "secret"
                    articles: [{
                        title: "Hello World!"
                        slug: "hello-world" 
                        content: "Lorem ipsum"
                        comments: [
                            { body: "First!", userId: 1 }
                            { body: "Great post!", userId: 1 }
                        ]
                    }]
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('users', ['email' => 'jane.doe@example.com', 'name' => 'Jane Doe']);
        $this->assertDatabaseHas('articles', ['title' => 'Hello World!', 'user_id' => '2']);
        $this->assertDatabaseHas('comments', ['body' => 'First!', 'article_id' => '1']);
        $this->assertDatabaseHas('comments', ['body' => 'Great post!', 'article_id' => '1']);
    }

    /** @test */
    public function it_lets_you_reset_a_belongs_to_relationship()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                createArticle(input: {
                    userId: "'.$user->id.'",
                    categoryId: null,
                    title: "Hello World!"
                    slug: "hello-world" 
                    content: "Lorem ipsum"
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('articles', ['title' => 'Hello World!', 'category_id' => null]);
    }

    /** @test */
    public function it_lets_you_attach_pivot_data()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                createRole(input: {
                    name: "administrator"
                    userIds: [
                        { id: "'.$user->id.'", pivot: { comment: "foobar" } }
                    ],
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('role_user', [
            'user_id' => '1',
            'role_id' => '1',
            'comment' => 'foobar',
        ]);
    }

    /** @test */
    public function it_lets_you_attach_pivot_data_with_custom_pivot()
    {
        $user = factory(Models\User::class)->create();
        $role = factory(Models\Role::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                createUser(input: {
                    email: "jane.doe@example.com",
                    name: "Jane Doe",
                    password: "secret",
                    roleIds: [
                        { id: "'.$role->id.'", customPivot: { comment: "foobar" } }
                    ],
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('role_user', [
            'user_id' => '2',
            'role_id' => $role->id,
            'comment' => 'foobar',
        ]);
    }

    /** @test */
    public function it_lets_you_set_pivot_data_while_creating_relation()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                createRole(input: {
                    name: "administrator",
                    users: [
                        {
                            email: "jane.doe@example.com"
                            name: "Jane Doe"
                            password: "secret"
                            pivot: {
                                comment: "foobar"
                            }
                        }
                    ],
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('roles', ['id' => '1', 'name' => 'administrator']);
        $this->assertDatabaseHas('users', ['id' => '2', 'email' => 'jane.doe@example.com']);
        $this->assertDatabaseHas('role_user', [
            'user_id' => '2',
            'role_id' => '1',
            'comment' => 'foobar',
        ]);
    }

    /** @test */
    public function it_lets_you_set_pivot_data_while_creating_relation_with_custom_pivot_accessor()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $query = '
            mutation {
                createUser(input: {
                    email: "jane.doe@example.com",
                    name: "Jane Doe",
                    password: "secret",
                    roles: [
                        { name: "administrator", customPivot: { comment: "foobar" } }
                    ],
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('roles', ['id' => '1', 'name' => 'administrator']);
        $this->assertDatabaseHas('role_user', [
            'user_id' => '2',
            'role_id' => '1',
            'comment' => 'foobar',
        ]);
    }

    /** @test */
    public function it_lets_you_attach_polymorphic_relation()
    {
        $user = factory(Models\User::class)->create();
        $this->actingAs($user);

        $article = factory(Models\Article::class)->create();

        $query = '
            mutation {
                createUpvote(input: {
                    upvoteableId: { article: "'.$article->id.'" }
                }) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonKey('id');
        $this->assertDatabaseHas('upvotes', ['upvoteable_id' => $article->id]);
    }
}
