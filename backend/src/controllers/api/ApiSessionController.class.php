<?php

class ApiSessionController extends ApiBaseController
{
    /**
     * @return array
     */
    public function defaultAction()
    {
        $user = $this->getUser();
        $this->getSession()->send();
        if ($user instanceof User) {
            $unreadCount = Criteria::create(DialogMessage::dao())
                ->addProjection(Projection::count('id', 'count'))
                ->add(Expression::isFalse('read'))
                ->add(Expression::eq('to.user', $this->getUser()))
                ->getCustom('count');

            $response = [
                'auth' => true,
                'isGlobalAdmin' => $user->getRole()->isGlobalAdmin(),
                'isGlobalMod'   => $user->getRole()->isGlobalMod(),
                'messages' => (int)$unreadCount,
                'settings' => [
                    'showNsfw' => $user->isShowNsfw(),
                    'treeView' => $user->isTreeView(),
                    'viewDeleted' => $user->isViewDeleted()
                ],
            ];
        } else {
            $response = [
                'auth' => false,
            ];
        }

        $response['version'] = getenv('VERSION') ?: null;

        return $response;
    }

    public function testAction()
    {
        return 'foo';
    }
}