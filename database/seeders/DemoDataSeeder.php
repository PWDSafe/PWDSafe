<?php

namespace Database\Seeders;

use App\Credential;
use App\Encryptedcredential;
use App\Group;
use App\Helpers\Encryption;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds a demo user with a working zero-knowledge vault, a private subgroup,
 * shared groups and a handful of credentials. Useful for local development
 * and for taking screenshots of a populated vault.
 *
 * Demo login: demo@pwdsafe.test / DemoPassword123!
 * Colleague login: colleague@pwdsafe.test / DemoPassword123!
 */
class DemoDataSeeder extends Seeder
{
    private const EMAIL = 'demo@pwdsafe.test';

    private const COLLEAGUE_EMAIL = 'colleague@pwdsafe.test';

    private const PASSWORD = 'DemoPassword123!';

    public function run(): void
    {
        if (User::where('email', self::EMAIL)->exists()) {
            return;
        }

        User::registerUser(self::EMAIL, self::PASSWORD);
        User::registerUser(self::COLLEAGUE_EMAIL, self::PASSWORD);

        /** @var User $user */
        $user = User::where('email', self::EMAIL)->firstOrFail();
        /** @var User $colleague */
        $colleague = User::where('email', self::COLLEAGUE_EMAIL)->firstOrFail();
        $enc = app(Encryption::class);

        $this->addCredential($enc, [$user], $user->primarygroup, 'GitHub', 'demo@pwdsafe.test', 'Tr0ub4dor&3', 'Personal account');
        $this->addCredential($enc, [$user], $user->primarygroup, 'Gmail', 'demo@gmail.com', 'C0rrect-Horse-Battery', '');

        $workGroup = Group::create([
            'name' => 'Work',
            'parent_id' => $user->primarygroup,
        ]);
        $user->groups()->attach($workGroup, ['permission' => 'admin']);

        $this->addCredential($enc, [$user], $workGroup->id, 'AWS Console', 'demo-iam-user', 'aws-Secret-Pwd-99', 'Production account, MFA required');
        $this->addCredential($enc, [$user], $workGroup->id, 'Office Wifi', 'guest', 'office-guest-wifi', '');

        $marketingGroup = Group::create(['name' => 'Marketing Team', 'parent_id' => null]);
        $user->groups()->attach($marketingGroup, ['permission' => 'admin']);
        $colleague->groups()->attach($marketingGroup, ['permission' => 'viewer']);

        $this->addCredential($enc, [$user, $colleague], $marketingGroup->id, 'Hootsuite', 'social@example.com', 'Soc1al-Sched-2024', 'Shared social media scheduler');
        $this->addCredential($enc, [$user, $colleague], $marketingGroup->id, 'Canva', 'design@example.com', 'Canva-Team-Pwd!', '');

        $itGroup = Group::create(['name' => 'IT Department', 'parent_id' => null]);
        $user->groups()->attach($itGroup, ['permission' => 'admin']);
        $colleague->groups()->attach($itGroup, ['permission' => 'admin']);

        $this->addCredential($enc, [$user, $colleague], $itGroup->id, 'Firewall Admin', 'admin', 'F1rew@ll-Admin-2024', 'Office firewall management console');
        $this->addCredential($enc, [$user, $colleague], $itGroup->id, 'Backup Server', 'backupadmin', 'B@ckup-Server-Pwd', 'NAS web interface');
    }

    /**
     * @param array<int, User> $users  Users who should be able to decrypt this credential
     */
    private function addCredential(Encryption $enc, array $users, int $groupId, string $site, string $username, string $password, string $notes): void
    {
        DB::transaction(function () use ($enc, $users, $groupId, $site, $username, $password, $notes) {
            $credential = new Credential();
            $credential->groupid = $groupId;
            $credential->site = $site;
            $credential->username = $username;
            $credential->notes = $notes;
            $credential->save();

            foreach ($users as $user) {
                $encryptedcredential = new Encryptedcredential();
                $encryptedcredential->credentialid = $credential->id;
                $encryptedcredential->userid = $user->id;
                $encryptedcredential->data = $enc->encWithPub($password, $user->pubkey);
                $encryptedcredential->save();
            }
        });
    }
}
