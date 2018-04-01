<?php

class Script_CreateAdminUser extends ConsoleScript {

    const ADMIN_LOGIN    = 'epicfailguy';
    const ADMIN_PASSWORD = '123123123';

    public function run() {
        if (User::dao()->getByLogin(self::ADMIN_LOGIN)) {
            $this->log('user "' . self::ADMIN_LOGIN . '" already exists"');
            return;
        }

        $user = User::create()
            ->setCreateDate(Timestamp::makeNow())
            ->setLogin(self::ADMIN_LOGIN)
            ->setPasswordHashed(self::ADMIN_PASSWORD)
            ->setRoleId(UserRole::ADMIN)
            ->setShowNsfw(true);

        User::dao()->add($user);

        $this->log('created user "' . self::ADMIN_LOGIN . '" with password "' . self::ADMIN_PASSWORD . '"');
    }

}