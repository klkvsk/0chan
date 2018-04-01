<?php

class DialogsTest extends ConsoleTestScript {

    protected $users = [];

    public function setUp() {
        for ($i = 1; $i <= 2; $i++) {
            $username = 'testuser_dialogs_' . $i;
            $password = 'testtest';
            $login = $this->api(HttpMethod::post(), 'api/user/login', [],
                [ 'login' => $username, 'password' => $password ]);

            if ($login['ok']) {
                $session = $login['session'];

            } else {
                $register = $this->api(HttpMethod::post(), 'api/user/register', [],
                    [ 'login' => $username, 'password' => $password ]);

                if ($register['ok']) {
                    $session = $register['session'];
                } else {
                    throw new Exception('failed to setup test user');
                }
            }
            $this->users[$i] = [ 'id' => $username, 'session' => $session ];
        }
    }

    public function testCreateIdentities() {
        foreach ($this->users as &$user) {
            $identity = $this->api(HttpMethod::get(), 'api/user/createIdentity', ['session' => $user['session']]);
            $user['identity'] = $identity['identity'];
            $this->log('User ' . $user['id'] . ' got identity ' . $user['identity']);
        }
    }

    public function testCreateNewDialog() {
        // user 1 to user 2
        $send = $this->api(HttpMethod::post(), 'api/dialog/send',
            [
                'from' => $this->users[1]['identity'],
                'to'   => $this->users[2]['identity'],
                'session' => $this->users[1]['session']
            ],
            [
                'text' => 'Hello pidor!'
            ]
        );
        // user 2 to user 1
        $send = $this->api(HttpMethod::post(), 'api/dialog/send',
            [
                'from' => $this->users[2]['identity'],
                'to'   => $this->users[1]['identity'],
                'session' => $this->users[2]['session']
            ],
            [
                'text' => 'Sam pidor!'
            ]
        );
    }

    public function testDialogListOnBothSides() {
        foreach ($this->users as &$user) {
            $dialogs = $this->api(HttpMethod::get(), 'api/dialog', ['session' => $user['session']]);
            $user['dialog'] = array_shift($dialogs['dialogs']);
            print_r($user['dialog']);
        }
    }

    public function testDialogViewOnOtherSide() {
        $dialogView = $this->api(HttpMethod::get(), 'api/dialog/read',
            [
                'me'      => $this->users[2]['dialog']['me'],
                'with'    => $this->users[2]['dialog']['with'],
                'session' => $this->users[2]['session']
            ]
        );
        print_r($dialogView);

        Assert::isEqual($dialogView['messages'][0]['text'], 'Hello pidor!');
        Assert::isEqual($dialogView['messages'][1]['text'], 'Sam pidor!');
    }

    public function tearDown() {

    }

}