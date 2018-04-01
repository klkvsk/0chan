<?php

class ApiModerationController extends ApiBaseController
{
    const PAGINATION_LIMIT = 30;

    /**
     * @param Board|null $board
     * @throws ApiForbiddenException
     */
    public function assertAccess(Board $board = null)
    {
        if ($board && !$this->getUser()->canModerateBoard($board)) {
            throw new ApiForbiddenException;
        }
    }

    /**
     * @Auth
     *
     * @param Board|null $board
     * @return array
     */
    public function defaultAction(Board $board = null)
    {
        $this->assertAccess($board);

        $response = [];

        $response['moderatedBoards'] = [];
        foreach ($this->getUser()->getModeratedBoards() as $board) {
            $response['moderatedBoards'] [] = $board->export();
        }

        //$newReportsCriteria = Criteria::create(PostReport::dao())
        //    ->add(Expression::eq('status', PostReportStatus::AWAITING))
        //    ->addProjection(Projection::count('id', 'count'));
        //$this->addBoardExpression($newReportsCriteria, $board, 'post.thread.');
        $response['newReports'] = 0;
        //(int)$newReportsCriteria->getCustom('count');

        return $response;
    }

    /**
     * @Auth
     * @Post
     *
     * @param Board $board
     * @return array
     */
    public function messageAction(Board $board)
    {
        $this->assertAccess($board);

        $form = Form::create()
            ->add(
                FormHelper::stringPrimitive(ModeratorLog_Message::proto(), 'message')
                    ->required()
            );

        $form->import($this->getRequest()->getPost());
        if ($form->getErrors()) {
            return ['ok' => false];
        }

        $message = $form->getValue('message');

        ModeratorLog_Message::make($board, ModeratorLogEventType::MOD_MESSAGE)
            ->setMessage($message)
            ->add();

        return ['ok' => true];
    }

    /**
     * @Auth
     *
     * @param Board|null $board
     * @param int $page
     * @return array
     */
    public function logsAction(Board $board = null, $page = 1)
    {
        $this->assertAccess($board);

        $logsCriteria = Criteria::create(ModeratorLog::dao())
            ->addOrder(OrderBy::create('eventDate')->desc());

        $this->addBoardExpression($logsCriteria, $board);
        $this->applyPagination($logsCriteria, $page, self::PAGINATION_LIMIT);

        $logsResult = $logsCriteria->getResult();
        /** @var ModeratorLog[] $logs */
        $logs = $logsResult->getList();

        $response = ['logs' => []];

        foreach ($logs as $log) {
            $response['logs'] [] = $log->export($this->getUser());
        }

        $response['pagination'] = [
            'page' => $page,
            'total' => ceil($logsResult->getCount() / self::PAGINATION_LIMIT)
        ];

        return $response;
    }

    /**
     * @Auth
     *
     * @param Board|null $board
     * @param int $page
     * @return array
     */
    public function feedAction(Board $board = null, $page = 1)
    {
        $this->assertAccess($board);

        $feedCriteria = Criteria::create(Attachment::dao())
            ->add(Expression::isNull('embed'))
            ->add(Expression::isTrue('published'))
            ->addOrder(OrderBy::create('post')->desc())
            ->addOrder(OrderBy::create('id')->desc());

        $this->addBoardExpression($feedCriteria, $board, 'post.thread.');
        $this->applyPagination($feedCriteria, $page, self::PAGINATION_LIMIT);

        $feedResult = $feedCriteria->getResult();
        /** @var Attachment[] $attachments */
        $attachments = $feedResult->getList();

        $response = ['feed' => []];

        foreach ($attachments as $attachment) {
            $response['feed'] [] = [
                'postId' => $attachment->getPostId(),
                'publishedAt' => $attachment->getPost()->getCreateDate()->toStamp(),
                'attachment' => $attachment->export(),
            ];
        }

        $response['pagination'] = [
            'page' => $page,
            'total' => ceil($feedResult->getCount() / self::PAGINATION_LIMIT)
        ];

        return $response;
    }

    /**
     * @Auth
     *
     * @param Board $board
     * @return array
     */
    public function statsAction(Board $board)
    {
        $this->assertAccess($board);

        $dateRange = DateRange::create(
            Date::makeToday()->modify('60 days ago'),
            Date::makeToday()
        );

        $statsCriteria = Criteria::create(BoardStatsDaily::dao())
            ->add(Expression::between('date', $dateRange->getStart(), $dateRange->getEnd()))
            ->add(Expression::eq('board', $board))
            ->addOrder(OrderBy::create('date')->asc());
        /** @var BoardStatsDaily[] $stats */
        $stats = $statsCriteria->getList();

        $statsByDate = [];
        foreach ($stats as $stat) {
            $statsByDate[$stat->getDate()->toStamp()] = $stat;
        }

        $response = ['stats' => []];

        foreach ($dateRange->split() as $date) {
            /** @var $date Date */
            if (isset($statsByDate[$date->toStamp()])) {
                $stat = $statsByDate[$date->toStamp()];
            } else {
                $stat = BoardStatsDaily::create()
                    ->setDate($date)
                    ->setBoard($board);
            }
            $response['stats'] []= $stat->export();
        }


        return $response;
    }

    /**
     * @Auth
     *
     * @param Board|null $board
     * @param int $page
     * @return array
     */
    public function reportsAction(Board $board = null, $page = 1)
    {
        $this->assertAccess($board);

        $reportsCriteria = Criteria::create(Post::dao())
            ->add(Expression::eq(
                'reports.date',
                Criteria::create(PostReport::dao())
                    ->addProjection(Projection::max('date'))
                    ->add(Expression::eq('post', DBField::create('id', 'post')))
            ))
            ->addOrder(OrderBy::create('reports.date')->desc());

        $this->addBoardExpression($reportsCriteria, $board, 'thread.');
        $this->applyPagination($reportsCriteria, $page, self::PAGINATION_LIMIT);

        $reportsResult = $reportsCriteria->getResult();
        /** @var Post[] $reportedPosts */
        $reportedPosts = $reportsCriteria->getList();

        /** @var PostReport[] $reports */
        if (count($reportedPosts)) {
            $reports = Criteria::create(PostReport::dao())
                ->add(Expression::in('post', $reportedPosts))
                ->addOrder(OrderBy::create('date')->desc())
                ->getList();
        } else {
            $reports = [];
        }

        $reasons = [];
        foreach ($reports as $report) {
            if (!isset($reasons[$report->getPostId()])) {
                $reasons[$report->getPostId()] = [];
            }
            $reasons[$report->getPostId()] [] = $report->export();
        }

        $response = ['reports' => []];
        foreach ($reportedPosts as $post) {
            $response['reports'] [] = [
                'post' => $post->export(),
                'reasons' => $reasons[$post->getId()],
                'isApproved' => $post->isApproved()
            ];
        }

        $response['pagination'] = [
            'page' => $page,
            'total' => ceil($reportsResult->getCount() / self::PAGINATION_LIMIT)
        ];

        return $response;
    }

    /**
     * @Auth
     *
     * @param Board|null $board
     * @param int $page
     * @return array
     */
    public function bansAction(Board $board = null, $page = 1)
    {
        $this->assertAccess($board);

        $bansCriteria = Criteria::create(Ban::dao())
            ->addOrder(OrderBy::create('id')->desc());

        $this->addBoardExpression($bansCriteria, $board);
        $this->applyPagination($bansCriteria, $page, self::PAGINATION_LIMIT);

        $bansResult = $bansCriteria->getResult();
        /** @var Ban[] $bans */
        $bans = $bansResult->getList();

        $response = ['bans' => []];

        foreach ($bans as $ban) {
            $response['bans'] [] = $ban->export(true);
        }

        $response['pagination'] = [
            'page' => $page,
            'total' => ceil($bansResult->getCount() / self::PAGINATION_LIMIT)
        ];

        return $response;
    }

    /**
     * @Auth
     * @Post
     *
     * @param Post $post
     * @return array
     * @throws Exception
     */
    public function addBanAction(Post $post)
    {
        $this->assertAccess($post->getThread()->getBoard());

        $form = Form::create()
            ->add(FormHelper::stringPrimitive(Ban::proto(), 'reason')->required())
            ->add(Primitive::integer('time')->setMin(10)->setMax(60 * 24 * 365 * 10)->required())
            ->import($this->getRequest()->getPost());

        /** @var string $reason */
        $reason = $form->getValue('reason');
        /** @var string $time */
        $time = $form->getValue('time');
        /** @var Timestamp $timeStart */
        $timeStart = Timestamp::makeNow();
        /** @var Timestamp $timeEnd */
        $timeEnd = $timeStart->spawn('+' . $time . ' min');

        $db = DBPool::getByDao(Ban::dao());
        try {
            $db->begin();

            $ban = Ban::create()
                ->setBannedBy($this->getUser())
                ->setBannedAt($timeStart)
                ->setBannedTill($timeEnd)
                ->setReason($reason)
                ->setUserId($post->getUserId())
                ->setIpHash($post->getIpHash())
                ->setBoard($post->getThread()->getBoard())
                ->setPost($post);

            $post->setBanned(true);

            Ban::dao()->take($ban);
            Post::dao()->take($post);

            ModeratorLog_Ban::make($ban->getBoard(), ModeratorLogEventType::USER_BANNED)
                ->setBan($ban)
                ->add();

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

        return [
            'ok' => true,
            'ban' => $ban->export(true)
        ];
    }

    /**
     * @Auth
     *
     * @param Ban $ban
     * @return array
     * @throws Exception
     */
    public function removeBanAction(Ban $ban)
    {
        $this->assertAccess($ban->getBoard());

        $db = DBPool::getByDao(Ban::dao());
        try {
            $db->begin();

            $ban
                ->setUnbannedBy($this->getUser())
                ->setUnbannedAt(Timestamp::makeNow());

            Ban::dao()->take($ban);

            ModeratorLog_Ban::make($ban->getBoard(), ModeratorLogEventType::USER_UNBANNED)
                ->setBan($ban)
                ->add();

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

        return [
            'ok' => true,
            'ban' => $ban->export(true)
        ];
    }

    /**
     * @param Ban $ban
     * @return array
     * @throws ApiForbiddenException
     */
    public function banInfoAction(Ban $ban)
    {
        $isModerator = false;
        if ($this->getUser() && $this->getUser()->canModerateBoard($ban->getBoard())) {
            $isModerator = true;
        }

        $isBannedUser = $ban->isAppliedTo($this->getSession());

        if (!$isBannedUser && !$isModerator) {
            throw new ApiForbiddenException();
        }

        return [
            'ban' => $ban->export($isModerator)
        ];
    }

    /**
     * @param Ban $ban
     * @return array
     */
    public function appealBanAction(Ban $ban)
    {
        if (!$ban->isAppliedTo($this->getSession())) {
            return ['ok' => false];
        }

        $form = Form::create()
            ->add(FormHelper::stringPrimitive(Ban::proto(), 'appeal'))
            ->import($this->getRequest()->getPost());
        $appeal = $form->getValue('appeal');
        if (!$appeal || $appeal == $ban->getAppeal()) {
            return ['ok' => false];
        }

        $ban->setAppeal($appeal);
        Ban::dao()->take($ban);

        return ['ok' => true];
    }

    /**
     * @param Post $post
     * @return array
     */
    public function reportPostAction(Post $post)
    {
        if ($post->isApproved()) {
            return ['ok' => false];
        }

        if (!$this->getUser() && !$this->getSession()->getIpHash()) {
            return ['ok' => false];
        }

        $this->limitWithCaptcha('reportPost', 3600, 5);

        $existing = Criteria::create(PostReport::dao())
            ->add(
                Expression::orBlock(
                    Expression::eq('reportedBy', $this->getUser() ?: -1),
                    Expression::eq('reportedByIpHash', $this->getSession()->getIpHash())
                )
            )
            ->add(Expression::eq('post', $post))
            ->addProjection(Projection::count('id', 'count'))
            ->getCustom('count');

        if (!$existing) {
            /** @var PrimitiveString $reasonPrm */
            $reasonPrm = Primitive::prototyped(PostReport::class, 'reason');
            $reasonPrm->addImportFilter(FormHelper::makeStringFilter());
            $form = Form::create()
                ->add($reasonPrm->required())
                ->import($this->getRequest()->getPost());

            if ($form->getErrors()) {
                return ['ok' => false, 'errors' => $form->getErrors()];
            }

            $report = PostReport::create()
                ->setDate(Timestamp::makeNow())
                ->setPost($post)
                ->setReason($form->getValue('reason'));

            if ($this->getUser()) {
                $report->setReportedBy($this->getUser());
            }

            if ($this->getSession()->getIpHash()) {
                $report->setReportedByIpHash($this->getSession()->getIpHash());
            }

            PostReport::dao()->add($report);
        }

        return ['ok' => true];
    }

    /**
     * @Auth
     *
     * @param Post $post
     * @param $isApproved
     * @return array
     * @throws ApiForbiddenException
     * @throws Exception
     */
    public function markApprovedAction(Post $post, $isApproved)
    {
        if (!$post->canBeModeratedBy($this->getUser())) {
            throw new ApiForbiddenException();
        }

        $isApproved = $this->getBooleanParam($isApproved);

        $db = DBPool::getByDao(PostReport::dao());
        try {
            $db->begin();

            $post->setApproved($isApproved);
            Post::dao()->save($post);

            //$post->getReports()->dropList();

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

        return [
            'ok' => true,
            'isApproved' => $isApproved
        ];
    }

    /**
     * @param Post $post
     */
    protected function deletePost(Post $post)
    {
        $post->setDeleted(true);
        Post::dao()->save($post);

        $thread = $post->getThread();

        if ($post->isOpPost()) {
            $thread->setDeleted(true);
            $thread->setDeletedAt(Timestamp::makeNow());
            Thread::dao()->save($thread);
            $modlog = ModeratorLog_Post::make($thread->getBoard(), ModeratorLogEventType::THREAD_DELETED);
        } else {
            $modlog = ModeratorLog_Post::make($thread->getBoard(), ModeratorLogEventType::POST_DELETED);
        }

        $modlog->setPost($post)->add();
    }

    /**
     * @Auth
     *
     * @param Post $post
     * @return array
     * @throws ApiForbiddenException
     * @throws Exception
     */
    public function deletePostAction(Post $post)
    {
        if (!$post->canBeModeratedBy($this->getUser())) {
            throw new ApiForbiddenException();
        }

        $db = DBPool::getByDao(Post::dao());
        try {
            $db->begin();
            $this->deletePost($post);
            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

        $post->getThread()->getPostCount(true);

        return [
            'ok' => true,
            'post' => $post->export()
        ];
    }

    /**
     * @Auth
     *
     * @param Post $post
     * @return array
     * @throws ApiForbiddenException
     * @throws Exception
     */
    public function restorePostAction(Post $post)
    {
        if (!$post->canBeModeratedBy($this->getUser())) {
            throw new ApiForbiddenException();
        }

        $db = DBPool::getByDao(Post::dao());
        try {
            $db->begin();

            $post->setDeleted(false);
            Post::dao()->save($post);

            $thread = $post->getThread();
            if ($post->isOpPost()) {
                $thread
                    ->setDeleted(false)
                    ->dropDeletedAt();
                Thread::dao()->save($thread);
                $modlog = ModeratorLog_Post::make($thread->getBoard(), ModeratorLogEventType::THREAD_RESTORED);
            } else {
                $modlog = ModeratorLog_Post::make($thread->getBoard(), ModeratorLogEventType::POST_RESTORED);
            }

            $modlog->setPost($post)->add();

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

        $post->getThread()->getPostCount(true);

        return [
            'ok' => true,
            'post' => $post->export()
        ];
    }

    /**
     * @Auth
     *
     * @param Attachment $attachment
     * @return array
     * @throws Exception
     */
    public function deleteAttachmentAction(Attachment $attachment)
    {
        if (!$attachment->getPost() || !$attachment->getPost()->canBeModeratedBy($this->getUser())) {
            return [ 'ok' => false ];
        }

        $db = DBPool::getByDao(Attachment::dao());
        try {
            $db->begin();

            $attachment
                ->setDeleted(true)
                ->setDeletedAt(Timestamp::makeNow());
            Attachment::dao()->save($attachment);

            ModeratorLog_Attachment::make($attachment->getPost()->getThread()->getBoard(), ModeratorLogEventType::IMAGE_DELETED)
                ->setAttachment($attachment)
                ->add();

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

        return [
            'ok' => true,
            'isDeleted' => $attachment->isDeleted(),
        ];
    }

    /**
     * @Auth
     *
     * @param Attachment $attachment
     * @return array
     * @throws Exception
     */
    public function restoreAttachmentAction(Attachment $attachment)
    {
        if (!$attachment->getPost() || !$attachment->getPost()->canBeModeratedBy($this->getUser())) {
            return [ 'ok' => false ];
        }

        $db = DBPool::getByDao(Attachment::dao());
        try {
            $db->begin();

            $attachment
                ->setDeleted(false)
                ->dropDeletedAt()
            ;
            Attachment::dao()->save($attachment);

            ModeratorLog_Attachment::make($attachment->getPost()->getThread()->getBoard(), ModeratorLogEventType::IMAGE_RESTORED)
                ->setAttachment($attachment)
                ->add();

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

        return [
            'ok' => true,
            'isDeleted' => $attachment->isDeleted(),
        ];
    }

    /**
     * @param Attachment $attachment
     * @param $isNsfw
     * @return array
     */
    public function markNsfwAttachmentAction(Attachment $attachment, $isNsfw)
    {
        $isNsfw = $this->getBooleanParam($isNsfw);

        if (!$attachment->getPost() || !$attachment->getPost()->canBeModeratedBy($this->getUser())) {
            return [ 'ok' => false ];
        }

        $attachment->setNsfw($isNsfw);
        Attachment::dao()->take($attachment);

        return ['ok' => true, 'isNsfw' => $attachment->isNsfw() ];
    }

    /**
     * @Auth
     *
     * @param Thread $thread
     * @param $isPin
     * @return array
     * @throws ApiForbiddenException
     */
    public function pinThreadAction(Thread $thread, $isPin)
    {
        if (!$this->getUser()->canModerateBoard($thread->getBoard())) {
            throw new ApiForbiddenException();
        }

        $isPin = $this->getBooleanParam($isPin);
        $thread->setSticky($isPin);
        Thread::dao()->save($thread);

        return ['ok' => true, 'isPinned' => $isPin];
    }

    /**
     * @param Thread $thread
     * @param $isLock
     * @return array
     * @throws ApiForbiddenException
     */
    public function lockThreadAction(Thread $thread, $isLock)
    {
        if (!$this->getUser()->canModerateBoard($thread->getBoard())) {
            throw new ApiForbiddenException();
        }

        $isLock = $this->getBooleanParam($isLock);
        $thread->setLocked($isLock);
        Thread::dao()->save($thread);

        return ['ok' => true, 'isLocked' => $isLock];
    }

    /**
     * @param Thread $thread
     * @return array
     * @throws ApiForbiddenException
     */
    public function unpurgeThreadAction(Thread $thread)
    {
        if (!$this->getUser()->canModerateBoard($thread->getBoard())) {
            throw new ApiForbiddenException();
        }

        $thread
            ->dropPurgedAt()
            ->setUpdateDate(Timestamp::makeNow());
        Thread::dao()->take($thread);

        return ['ok' => true ];
    }

    /**
     * @param Criteria $criteria
     * @param Board|null $board
     * @param null $prefix
     * @throws WrongArgumentException
     */
    protected function addBoardExpression(Criteria $criteria, Board $board = null, $prefix = null)
    {
        $criteria->add(Expression::isFalse($prefix . 'board.deleted'));
        if ($board) {
            $criteria->add(Expression::eq($prefix . 'board', $board));
        } else if (!$this->getUser()->canModerateAllBoards()) {
            $criteria->add(Expression::in($prefix . 'board', $this->getUser()->getModeratedBoards() ?: [-1]));
        } else if ($this->getUser()->canModerateAllBoards()) {
            // nothing, all boards are selected
        } else {
            throw new WrongArgumentException;
        }
    }
}