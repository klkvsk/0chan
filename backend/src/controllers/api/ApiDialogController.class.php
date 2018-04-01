<?php

class ApiDialogController extends ApiBaseController
{
    const MESSAGE_LIMIT_PER_FETCH = 100;

    /**
     * @Auth
     * @return array
     */
    public function listAction()
    {
        $dialogsCriteria = Criteria::create(Dialog::dao())
            ->add(Expression::orBlock(
                Expression::eq('as.user', $this->getUser())
            ))
            ->addOrder(OrderBy::create('updatedAt')->desc());

        /** @var Dialog[] $dialogs */
        $dialogs = $dialogsCriteria->getList();

        /** @var int[] $messageCounts */
        $messageCounts = [];
        /** @var int[] $unreadCounts */
        $unreadCounts = [];
        /** @var DialogMessage[] $lastMessages */
        $lastMessages = [];

        if (count($dialogs) > 0) {
            // TODO: materialized view?
            $messagesInfo = Criteria::create(DialogMessage::dao())
                ->add(Expression::in('dialogs.id', $dialogs))
                ->addProjection(Projection::property('dialogs.id', 'dialog'))
                ->addProjection(Projection::max('id', 'lastMessageId'))
                ->addProjection(Projection::count('id', 'messageCount'))
                ->addProjection(
                    Projection::count(
                        ConditionalSwitch::create()
                            ->addWhen(
                                Expression::andBlock(
                                    Expression::isFalse('read'),
                                    Expression::eq('to.user', $this->getUser())
                                ), true)
                            ->addElse(null),
                        'unreadCount'
                    )
                )
                ->addProjection(Projection::group('dialogs.id'))
                ->getCustomList();

            foreach ($messagesInfo as $messageInfo) {
                $messageCounts[$messageInfo['dialog']] = $messageInfo['messageCount'];
                $unreadCounts [$messageInfo['dialog']] = $messageInfo['unreadCount'];
            }

            /** @var DialogMessage[] $lastMessages */
            $lastMessages = [];
            /** @var array[] $lastMessagesData */
            $lastMessagesData = Criteria::create(DialogMessage::dao())
                ->addProjection(Projection::clazz(DialogMessage::class))
                ->addProjection(Projection::property('dialogs.id', 'dialogId'))
                ->add(Expression::in('dialogs.id', $dialogs))
                ->add(Expression::in('id', ArrayUtils::columnFromSet('lastMessageId', $messagesInfo)))
                ->getCustomList();
            foreach ($lastMessagesData as $row) {
                $dialogId = intval($row['dialogId']);
                $message = DialogMessage::dao()->makeOnlyObject($row);
                $lastMessages[$dialogId] = $message;
            }
        }

        $response = [
            'dialogs' => [],
        ];

        foreach ($dialogs as $dialog) {
            $item = [
                'id'            => $dialog->getId(),
                'as'            => $dialog->getAs()->export(),
                'with'          => $dialog->getWith()->export(),
                'messageCount'  => $messageCounts[$dialog->getId()],
                'unreadCount'   => $unreadCounts[$dialog->getId()],
                'lastMessage'   => $lastMessages[$dialog->getId()]->export($this->getUser()),
            ];

            $response['dialogs'] [] = $item;
        }

        return $response;
    }

    /**
     * @Auth
     *
     * @return array
     */
    public function identitiesAction()
    {
        /** @var UserIdentity[] $identities */
        $identities = Criteria::create(UserIdentity::dao())
            ->add(Expression::eq('user', $this->getUser()))
            ->addOrder(OrderBy::create('id')->asc())
            ->getList();

        $response = [
            'identities' => array_map(
                function (UserIdentity $identity) {
                    return $identity->export();
                },
                $identities
            )
        ];

        return $response;
    }

    /**
     * @Auth
     *
     * @param UserIdentity $as
     * @param UserIdentity $to
     * @param null $after
     * @param null $before
     * @return array
     * @throws ApiForbiddenException
     */
    public function defaultAction(UserIdentity $as, UserIdentity $to, $after = null, $before = null)
    {
        if ($as->getUserId() != $this->getUser()->getId()) {
            throw new ApiForbiddenException();
        }

        $dialog = Dialog::dao()->get($as, $to);

        /** @var DialogMessage[] $messages */
        $messages = [];
        $hasBefore = false;
        if ($dialog) {
            $messagesCriteria = Criteria::create(DialogMessage::dao())
                ->add(Expression::eq('dialogs.id', $dialog))
                ->addOrder(OrderBy::create('id')->desc());

            $before = (int)$before;
            $after = (int)$after;

            if ($before) {
                $messagesCriteria->add(Expression::lt('id', $before));
            } else if ($after) {
                $messagesCriteria->add(Expression::gt('id', $after));
            }

            if (!$after) {
                $messagesCriteria->setLimit(self::MESSAGE_LIMIT_PER_FETCH);
            }

            /** @var DialogMessage[] $messages */
            $messages = $messagesCriteria->getList();

            // asc by date
            $messages = array_reverse($messages);

            if (!empty($messages)) {
                $hasBefore = Criteria::create(DialogMessage::dao())
                    ->add(Expression::eq('dialogs.id', $dialog))
                    ->addOrder(OrderBy::create('id')->desc())
                    ->add(Expression::lt('id', $messages[0]->getId()))
                    ->setLimit(1)
                    ->setProjection(Projection::property('id'))
                    ->getCustom('id') != null;
            }
        }

        $messagesExport = [];
        $unreadIds = [];
        foreach ($messages as $message) {
            $messagesExport []= $message->export($this->getUser());
            if ($message->isUnreadFor($this->getUser())) {
                $unreadIds []= $message->getId();
            }
        }

        if ($unreadIds) {
            $unreadUpdateQuery = OSQL::update(DialogMessage::dao()->getTable())
                ->set('read', true)
                ->where(Expression::in('id', $unreadIds));
            DBPool::getByDao(DialogMessage::dao())->query($unreadUpdateQuery);
        }

        $response = [
            'ok' => true,
            'id' => $dialog ? $dialog->getId() : null,
            'as' => $as->export(),
            'to' => $to->export(),
            'messages' => array_map(
                function (DialogMessage $message) {
                    return $message->export($this->getUser());
                },
                $messages
            ),
        ];

        if (!$after) {
            $response['hasBefore'] = $hasBefore;
        }

        return $response;
    }

    /**
     * @Auth
     * @Post
     *
     * @param UserIdentity $as
     * @param UserIdentity $to
     * @return array
     * @throws ApiBadRequestException
     * @throws ApiFormValidationException
     * @throws ApiNotFoundException
     * @throws Exception
     */
    public function sendAction(UserIdentity $as, UserIdentity $to)
    {
        $result = [];
        if ($as->isDeleted() || $as->getUserId() != $this->getUser()->getId()) {
            $result = [
                'ok' => false,
                'reason' => 'Ошибка в адресе отправителя'
            ];
        }

        if ($to->isDeleted()) {
            $result = [
                'ok' => false,
                'reason' => 'Адресат удалил свою личность'
            ];
        }

        $form = Form::create()
            ->add(FormHelper::stringPrimitive(DialogMessage::proto(), 'text'))
            ->import($this->getRequest()->getPost());

        if ($form->getErrors()) {
            $result = [
                'ok' => false,
                'reason' => $form->getError('text') == Form::WRONG ? 'Слишком длинное сообщение' : 'Пустое сообщение'
            ];
        }

        if ($result && !$result['ok']) {
            $this->limitWithCaptcha('dialogErrors', 3600, 50);
            return $result;
        }

        $this->limitWithCaptcha('dialogSpam', 60, 20);

        $db = DBPool::getByDao(Dialog::dao());
        try {
            $db->begin();

            $now = Timestamp::makeNow();

            $message = DialogMessage::create()
                ->setFrom($as)
                ->setTo($to)
                ->setDate($now)
                ->setText($form->getValue('text'));

            /** @var Dialog[] $dialogs */
            $dialogs = [];
            foreach ([ [$as, $to], [$to, $as] ] as list($me, $with)) {
                /** @var UserIdentity $me */
                /** @var UserIdentity $with */

                $dialog = Dialog::dao()->get($me, $with);
                if (!$dialog) {
                    $dialog = Dialog::create()
                        ->setCreatedAt($now)
                        ->setAs($me)
                        ->setWith($with);
                }
                $dialogs[$me->getId()] = $dialog;
            }

            foreach ($dialogs as $dialog) {
                $dialog->setUpdatedAt($now);
                Dialog::dao()->take($dialog);
            }

            DialogMessage::dao()->add($message);

            $message->getDialogs()->mergeList($dialogs)->save();

            $db->commit();

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollback();
            }
            throw $e;
        }

        return [
            'ok' => true,
            'message' => $message->export($this->getUser())
        ];
    }

    /**
     * @Auth
     *
     * @param UserIdentity|null $to
     * @return array
     */
    public function startAction(UserIdentity $to = null)
    {
        if (!$to || $to->isDeleted()) {
            $this->limitWithCaptcha('dialogErrors', 3600, 50);
            return [ 'ok' => false ];
        }

        /** @var UserIdentity[] $identities */
        $identities = $this->getUser()->getIdentities()->getList();

        /** @var Dialog[] $existingDialogsByIdentity */
        $existingDialogsByIdentity = [];

        /** @var Dialog[] $existingDialogs */
        if ($identities) {
            $existingDialogs = Criteria::create(Dialog::dao())
                ->add(Expression::eq('with', $to))
                ->add(Expression::in('as', $identities))
                ->getList();

            foreach ($existingDialogs as $dialog) {
                $existingDialogsByIdentity[$dialog->getAs()->getId()] = $dialog;
            }
        }

        usort($identities, function (UserIdentity $a, UserIdentity $b) use ($existingDialogsByIdentity) {
            $aDialog = isset($existingDialogsByIdentity[$a->getId()]) ? $existingDialogsByIdentity[$a->getId()] : null;
            $bDialog = isset($existingDialogsByIdentity[$b->getId()]) ? $existingDialogsByIdentity[$b->getId()] : null;
            if ($aDialog && !$bDialog) return -1;
            if (!$aDialog && $bDialog) return  1;
            if ($aDialog && $bDialog) {
                $aUpdateStamp = $aDialog->getUpdatedAt()->toStamp();
                $bUpdateStamp = $bDialog->getUpdatedAt()->toStamp();
                if ($aUpdateStamp > $bUpdateStamp) return -1;
                if ($aUpdateStamp < $bUpdateStamp) return  1;
            }
            return $a->getId() < $b->getId() ? -1 : 1;
        });

        $response = [
            'ok' => true,
            'to' => $to->export(),
            'as' => []
        ];

        foreach ($identities as $identity) {
            $dialog = isset($existingDialogsByIdentity[$identity->getId()]) ? $existingDialogsByIdentity[$identity->getId()] : null;
            $as = array_merge(
                $identity->export(),
                [
                    'last' => $dialog ? $dialog->getUpdatedAt()->toStamp() : null,
                ]
            );
            $response['as'] []= $as;
        }

        return $response;
    }

    /**
     * @Auth
     * @Post
     * @return array
     * @throws ApiFormValidationException
     */
    public function addIdentityAction()
    {
        $this->limitWithCaptcha('newIdentities', 86400, 3);

        $form = Form::create()
            ->add(FormHelper::stringPrimitive(UserIdentity::proto(), 'name'))
            ->import($this->getRequest()->getPost());

        if ($form->getErrors()) {
            throw new ApiFormValidationException($form);
        }

        $userIdentity = UserIdentity::create()
            ->setUser($this->getUser())
            ->setName($form->getValue('name'));

        // вставляем с проверкой на уникальность
        $attempts = 10;
        $inserted = false;
        do {
            try {
                $userIdentity->setAddress(UserIdentity::makeAddress());
                UserIdentity::dao()->add($userIdentity);
                $inserted = true;
            } catch (DuplicateObjectException $e) {
                if (--$attempts == 0) {
                    return [ 'ok' => false ];
                }
            }
        } while (!$inserted);

        return [
            'ok' => true,
            'identity' => $userIdentity->export()
        ];
    }

    /**
     * @Auth
     *
     * @param UserIdentity $address
     * @return array
     * @throws Exception
     */
    public function deleteIdentityAction(UserIdentity $address = null)
    {
        if (!$address || $address->getUserId() != $this->getUser()->getId()) {
            return [ 'ok' => false ];
        }

        $db = DBPool::getByDao(UserIdentity::dao());
        try {
            $db->begin();

            /** @var Dialog[] $dialogs */
            $dialogs = $address->getDialogs()->getList();
            foreach ($dialogs as $dialog) {
                $dialog->getMessages()->dropList();
                Dialog::dao()->drop($dialog);
            }

            $address
                ->dropUser()
                ->setDeleted(true);
            UserIdentity::dao()->save($address);

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

        return [ 'ok' => true ];
    }

    /**
     * @Auth
     *
     * @param Dialog|null $dialog
     * @return array
     * @throws Exception
     */
    public function deleteDialogAction(Dialog $dialog = null)
    {
        if (!$dialog || $dialog->getAs()->getUserId() != $this->getUser()->getId()) {
            return [ 'ok' => false ];
        }

        $db = DBPool::getByDao(Dialog::dao());
        try {
            $db->begin();

            $dialog->getMessages()->dropList();
            Dialog::dao()->drop($dialog);

            $db->commit();
            return [ 'ok' => true ];

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

}