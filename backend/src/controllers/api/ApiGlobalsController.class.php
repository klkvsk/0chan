<?php

class ApiGlobalsController extends ApiBaseController
{
    /**
     * @throws ApiForbiddenException
     */
    protected function assertAccess()
    {
        if (!$this->getUser()->getRole()->isGlobalAdmin()) {
            throw new ApiForbiddenException;
        }
    }

    /**
     * @Auth
     * @return array
     * @throws ApiForbiddenException
     */
    public function listAction()
    {
        $this->assertAccess();

        /** @var User[] $globals */
        $globals = Criteria::create(User::dao())
            ->add(Expression::gt('role', UserRole::USER))
            ->addOrder(OrderBy::create('role')->desc())
            ->addOrder(OrderBy::create('login')->asc())
            ->getList();

        $response = [
            'globals' => []
        ];
        foreach ($globals as $global) {
            $response['globals'] []= [
                'login' => $global->getLogin(),
                'role' => $global->getRole()->getName()
            ];
        }

        return $response;
    }

    /**
     * @Auth
     * @param User $user
     * @param bool $isAdmin
     * @return array
     * @throws ApiForbiddenException
     */
    public function addAction(User $user, bool $isAdmin = false)
    {
        $this->assertAccess();

        $user->setRoleId($isAdmin ? UserRole::ADMIN : UserRole::MODERATOR);
        User::dao()->save($user);

        return [ 'ok' => true ];
    }

    /**
     * @param User $user
     * @return array
     * @throws ApiForbiddenException
     */
    public function removeAction(User $user)
    {
        $this->assertAccess();

        $user->setRoleId(UserRole::USER);
        User::dao()->save($user);

        return [ 'ok' => true ];
    }

}