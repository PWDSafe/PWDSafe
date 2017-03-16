<?php
namespace DevpeakIT\PWDSafe\Callbacks;

use DevpeakIT\PWDSafe\DB;

class GroupsUnshareCallback
{
        public function post($groupid = null, $userid = null)
        {
                if (is_null($groupid) || is_null($userid) || !is_numeric($groupid) || !is_numeric($userid)) {
                        echo json_encode([
                            'status' => 'Fail',
                            'reason' => 'Missing groupid or userid'
                        ]);
                        die();
                }

                // Check access
                $access_sql = "SELECT groups.id FROM groups
                               INNER JOIN usergroups ON groups.id = usergroups.groupid
                               INNER JOIN users ON usergroups.userid = users.id
                               WHERE usergroups.userid = :userid AND usergroups.groupid = :groupid";
                $access_stmt = DB::getInstance()->prepare($access_sql);
                $access_stmt->execute([
                    'userid' => $_SESSION['id'],
                    'groupid' => $groupid
                ]);

                if ($access_stmt->rowCount() === 0) {
                        echo json_encode([
                            'status' => 'Fail',
                            'reason' => 'Unauthorized'
                        ]);
                        die();
                }

                // Access OK, remove credentials for this user in this group
                $sql = "DELETE FROM encryptedcredentials
                        WHERE userid = :userid AND credentialid IN (
                          SELECT id FROM credentials WHERE groupid = :groupid
                        )";
                $stmt = DB::getInstance()->prepare($sql);
                $stmt->execute([
                    'userid' => $userid,
                    'groupid' => $groupid
                ]);

                // Remove user from group
                $sql = "DELETE FROM usergroups WHERE groupid = :groupid AND userid = :userid LIMIT 1";
                $stmt = DB::getInstance()->prepare($sql);
                $stmt->execute([
                    'groupid' => $groupid,
                    'userid' => $userid
                ]);

                echo json_encode([
                    'status' => 'OK'
                ]);
        }
}