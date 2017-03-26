<?php
namespace DevpeakIT\PWDSafe\Callbacks;

use DevpeakIT\PWDSafe\DB;
use DevpeakIT\PWDSafe\Encryption;
use DevpeakIT\PWDSafe\FormChecker;
use DevpeakIT\PWDSafe\Group;
use DevpeakIT\PWDSafe\GUI\Graphics;
use DevpeakIT\PWDSafe\RequireAuthorization;

class GroupsShareCallback extends RequireAuthorization
{
        public function get($groupid = null)
        {
                $groupid = is_null($groupid)?$_SESSION['primarygroup']:$groupid;

                // Check access
                $grp = new Group();
                $grp->id = $groupid;

                if (!$grp->checkAccess($_SESSION['id'])) {
                        $graphics = new Graphics();
                        $graphics->showUnathorized();
                        return;
                }

                $groupname = $grp->getName();
                $data = $grp->getMembersExcept($_SESSION['id']);

                $graphics = new Graphics();
                $graphics->showShareGroup($data, $groupid, $groupname);
        }

        public function post($groupid = null)
        {
                $fc = new FormChecker();
                if (!$fc->checkRequiredFields(['email'])) {
                        return;
                }

                $group = new Group();
                $group->id = $groupid;

                if (!$group->checkAccess($_SESSION['id'])) {
                        echo json_encode([
                            'status' => 'Fail',
                            'reason' => 'Unauthorized'
                        ]);
                        return;
                }

                // Make sure new user exists, grab pubkey
                $sql = "SELECT id, pubkey FROM users WHERE email = :email";
                $stmt = DB::getInstance()->prepare($sql);
                $stmt->execute(['email' => $_POST['email']]);
                if ($stmt->rowCount() === 0) {
                        echo json_encode(['status' => 'Fail', 'reason' => 'User does not exist']);
                        return;
                }
                $newuser = $stmt->fetch();

                // Make sure user is not already in group
                $sql = "SELECT users.id FROM users INNER JOIN usergroups ON usergroups.userid = users.id
                        WHERE usergroups.groupid = :groupid AND users.email = :email";
                $stmt = DB::getInstance()->prepare($sql);
                $stmt->execute(['email' => $_POST['email'], 'groupid' => $groupid]);
                if ($stmt->rowCount() > 0) {
                        echo json_encode(['status' => 'Fail', 'reason' => 'User already in group']);
                        return;
                }

                // Grab all credentials for group, decode and reinsert with the new users pubkey
                $sql = "SELECT encryptedcredentials.data, encryptedcredentials.credentialid FROM encryptedcredentials
                        INNER JOIN credentials ON credentials.id = encryptedcredentials.credentialid
                        INNER JOIN groups ON credentials.groupid = groups.id
                        INNER JOIN usergroups ON usergroups.groupid = groups.id
                        WHERE usergroups.groupid = :groupid AND usergroups.userid = :userid
                        AND encryptedcredentials.userid = :userid";
                $stmt = DB::getInstance()->prepare($sql);
                $stmt->execute([
                    'groupid' => $groupid,
                    'userid' => $_SESSION['id']
                ]);

                $insert_sql = "INSERT INTO encryptedcredentials (credentialid, userid, data)
                               VALUES (:credid, :userid, :data)";
                $insert_stmt = DB::getInstance()->prepare($insert_sql);

                $enc = new Encryption();
                while ($row = $stmt->fetch()) {
                        $data = $enc->decWithPriv(
                            base64_decode($row['data']),
                            $enc->dec($_SESSION['privkey'], $_SESSION['pass'])
                        );
                        
                        $insert_stmt->execute([
                            'credid' => $row['credentialid'],
                            'userid' => $newuser['id'],
                            'data' => base64_encode($enc->encWithPub($data, $newuser['pubkey']))
                        ]);
                }

                // Add new user to usergroups for particular group
                $sql = "INSERT INTO usergroups (userid, groupid) VALUES (:userid, :groupid)";
                $stmt = DB::getInstance()->prepare($sql);
                $stmt->execute(['userid' => $newuser['id'], 'groupid' => $groupid]);

                echo json_encode(['status' => 'OK']);
        }
}
