<?php
namespace DevpeakIT\PWDSafe\Callbacks;

use \DevpeakIT\PWDSafe\Exceptions\AuthorizationFailedException;
use \DevpeakIT\PWDSafe\Credentials;
use \DevpeakIT\PWDSafe\Encryption;
use \DevpeakIT\PWDSafe\DB;
use DevpeakIT\PWDSafe\RequireAuthorization;

class PasswordForcallback extends RequireAuthorization
{
        /**
         * @brief Used for getting credentials based on id
         * @param $id int credential id
         */
        public function get($id)
        {
                $pwd = $this->getCredForID($id);

                if ($pwd) {
                        $encryption = new Encryption();

                        $pwdunbase = base64_decode($pwd['pass']);

                        $pwddecoded = $encryption->decWithPriv(
                            $pwdunbase,
                            $encryption->dec($_SESSION['privkey'], $_SESSION['pass'])
                        );
                        echo json_encode([
                                'status' => 'OK',
                                'pwd' => $pwddecoded,
                                'user' => $pwd['user'],
                                'site' => $pwd['site'],
                                'notes' => $pwd['notes'],
                                'groupid' => $pwd['groupid']
                        ]);
                }
        }

        /**
         * @param $id int credential id
         * @return bool|array containing site, username and password
         */
        private function getCredForID($id)
        {
                $credentials = new Credentials(DB::getInstance());

                try {
                        return $credentials->getPwdFor($_SESSION['id'], $id);
                } catch (AuthorizationFailedException $ex) {
                        echo json_encode([
                            'status' => 'Fail',
                            'reason' => 'Authorisation failed, you do not have access to the requested credentials'
                        ]);
                        return false;
                }
        }
}
