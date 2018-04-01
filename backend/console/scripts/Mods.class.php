<?php

/*
 * @package Scripts
 */
class Script_Mods extends ConsoleScript {

    public function run() {
        $args = $this->getArgs();
        if (!isset($args[0])) {
            $this->log('Usage: php run.php mods [ list | add <login> | rm <login> ]');
            return;
        }

        switch ($args[0]) {
            case 'list':
                /** @var User[] $mods */
                $mods = Criteria::create(User::dao())
                    ->add(Expression::eq('role', UserRole::MODERATOR))
                    ->addOrder(OrderBy::create('login')->asc())
                    ->getList();
                foreach ($mods as $mod) {
                    $this->log('-> ' . $mod->getLogin());
                }
                break;

            case 'add':
                if (!isset($args[1])) {
                    $this->log('Usage: php run.php mods add <login>');
                }
                $mod = User::dao()->getByLogin($args[1]);
                if ($mod->getRole()->is(UserRole::USER)) {
                    $mod->setRoleId(UserRole::MODERATOR);
                    User::dao()->take($mod);
                    $this->log('Given mod rights to user "' . $mod->getLogin() . '"');
                } else {
                    $this->log('User "' . $mod->getLogin() . '" is ' . $mod->getRole()->getName());
                }
                break;

            case 'rm':
                if (!isset($args[1])) {
                    $this->log('Usage: php run.php mods add <login>');
                }
                $mod = User::dao()->getByLogin($args[1]);
                if ($mod->getRole()->is(UserRole::MODERATOR)) {
                    $mod->setRoleId(UserRole::USER);
                    User::dao()->take($mod);
                    $this->log('Revoked mod rights from user "' . $mod->getLogin() . '"');
                } else {
                    $this->log('User "' . $mod->getLogin() . '" is not a moderator');
                }
                break;
        }
    }

}