<?php

namespace Database\Factories;

use App\Group;
use App\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'password' => '$2y$10$RQ0t2iYvTTbxN.Jn9ePgL.aMcmhh8/Vj/TyQnhbU3sgUAlaYMZRdK', // testing123
            'pubkey' => '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAmvhn58U9Vt4UFdxBpBeF
Za064ZuIXnZjRTVgiOwYvaPIqYoqWJwzEuI4KdQXO/fO1oPuAq/E68RCN4Cy3klc
95+rfac9qSKjbjZ4E2Cnsd11CEDhbyxA+lFK0HinMUmU2sjtoplYCEIEHzLrt5p/
vnY2TIdtxFxPdwQ0muLz+v7iq6UGgdiFF4l95a8SYEiVz1d9zUOArudITpjngbP5
3LMGsrG5ozmU9cGHwMhAisz+nOVVqL4oJjr66cgvcJyVvdgzQt/6A8Bw4mQlMCeB
SATeMkNddyS5TUjcO8PpQZfrymttZqoiwsBtoX7OwtrcKI0W4slhaxOaqh1HG0t4
7WBTWYwZkda4O6jppe8DHaEx+RObXBpq+mcPVBcmqEfr/leZhCIjYD2C8/IEdOD1
brYDMa0teSJApZK42kufgyM0r/v/h00euTOBtU/ax/+PDK75SnlhQ85OXHhn56Bh
jT1Nl+j/Ub7SJaG20Ww8fLA7Edi/BM0Ele70fxI2Ec3u858CwcEe7JSVw+38x0qu
2o0n5Z+cBu57ViJ3QtDnmqxsimP3nV1gp58iF4QV0u+jDiXkX6zUOhsPj9/UoGmB
wERt0+IZquq5clCzUhB6CIIT4gXTTXRxwkMeHnWLFuWL9570oONewo0qO2qm3OtE
0vTCQTMhRCeSt/RSq/b+rCsCAwEAAQ==
-----END PUBLIC KEY-----
',
            'privkey' => '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:f23b591ecd9a828290f7c6aece62bbf7',
            'primarygroup' => Group::factory(),
        ];
    }
}
