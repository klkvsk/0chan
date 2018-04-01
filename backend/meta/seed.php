<?php
require '../config.inc.php';

$admin = User::dao()->getByLogin('admin');
if (!$admin) {
    $admin = User::create()
        ->setCreateDate(Timestamp::makeNow())
        ->setLogin('admin')
        ->setPasswordHashed('123123123')
        ->setRoleId(UserRole::ADMIN);

    User::dao()->add($admin);

    echo "created admin!\n";
}
