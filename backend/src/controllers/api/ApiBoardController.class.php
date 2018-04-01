<?php

class ApiBoardController extends ApiBaseController
{
    const THREADS_PER_PAGE = 10;
    const THREADS_IN_CURSOR = 200;
    const NUM_LAST_POSTS = 3;

    /**
     * @return array
     * @throws ApiBadRequestException
     * @throws ApiNotFoundException
     */
    public function defaultAction()
    {
        $form = Form::create()
            ->add(Primitive::string('dir'))
            ->add(Primitive::boolean('favourite'))
            ->add(Primitive::boolean('watched'))
            ->add(Primitive::string('cursor'))
            ->add(Primitive::integer('page')->setMin(1)->setDefault(1))
            ->add(
                Primitive::choice('sort')
                    ->setList([
                        'created' => 'createDate',
                        'updated' => 'updateDate',
                        'rating' => 'id'
                    ])
                    ->setDefault('updated')
                    ->optional()
            )
            ->import($this->getRequest()->getGet());

        if ($form->getErrors()) {
            throw new ApiBadRequestException;
        }

        $dir = $form->getValue('dir');
        $board = null;
        if (strlen($dir)) {
            try {
                $board = Board::dao()->getByRequestedValue($dir);
            } catch (ObjectNotFoundException $e) {
                throw new ApiNotFoundException;
            }
        }

        $isFavourites = $form->getValue('favourite') && $this->getUser();
        $isOnlyWatched = $form->getValue('watched') && $this->getUser();

        $criteria = Criteria::create(Thread::dao());

        $cursorId = $form->getValue('cursor');
        $page = $form->getValueOrDefault('page');
        $fromCursor = false;
        $cursorChunk = [];
        $cursor = [];
        if ($cursorId && $page) {
            $cursor = Cache::me()->get('cursor:' . $cursorId) ?: [];
            if ($cursor) {
                $cursorChunk = array_slice($cursor, ($page - 1) * self::THREADS_PER_PAGE, self::THREADS_PER_PAGE);
                $criteria->add(Expression::in('id', $cursorChunk ?: [-1]));
                $fromCursor = true;
            }
        }

        if ($board instanceof Board) {
            $criteria->addOrder(OrderBy::create('sticky')->desc()->nullsLast());
        }

        $deletedExpression = Expression::andBlock(
            Expression::isFalse('deleted'),
            Expression::isFalse('board.deleted')
        );
        if ($this->getUser() && $this->getUser()->isViewDeleted()) {
            if ($this->getUser()->getRole()->isGlobalMod()) {
                $deletedExpression = null; // sees all
            } else {
                $moderatedBoards = $this->getUser()->getModeratedBoards();
                if ($moderatedBoards) {
                    $deletedExpression = Expression::orBlock(
                        $deletedExpression,
                        Expression::in('board', $moderatedBoards)
                    );
                }
            }
        }
        if ($deletedExpression) {
            $criteria->add($deletedExpression);
        }


        if ($board instanceof Board) {
            $canModerateBoard = $this->getUser() && $this->getUser()->canModerateBoard($board);
            if ($board->isDeleted() && !$canModerateBoard) {
                throw new ApiNotFoundException();
            }
            if ($this->getSession()->isIpCountryRu() && $board->isBlockRu() && !$canModerateBoard) {
                throw new ApiBlockRuException();
            }
            $criteria->add(Expression::eq('board', $board));

        } else if ($isFavourites) {
            $criteria->add(Expression::eq('board.favouritedBy.user', $this->getUser()));

        } else if ($isOnlyWatched) {
            $criteria->add(Expression::in('id', $this->getUser()->getWatchedThreads(true)->makeQuery()));

        } else {
            $criteria->add(Expression::isFalse('board.hidden'));
            if (!$this->getUser() || !$this->getUser()->isShowNsfw()) {
                $criteria->add(Expression::isFalse('board.nsfw'));
            }
        }

        $canModerateAll = $this->getUser() && $this->getUser()->getRole()->isGlobalMod();
        if ($this->getSession()->isIpCountryRu() && !$canModerateAll) {
            $criteria->add(Expression::isFalse('board.blockRu'));
        }

        $sort = $form->getActualChoiceValue('sort');
        if ($sort && Thread::proto()->isPropertyExists($sort)) {
            $criteria->addOrder(OrderBy::create($sort)->desc()->nullsLast());
        }

        /** @var Thread[] $threads */
        if ($fromCursor) {
            $criteria->dropOrder();
            $threadUnordered = ArrayUtils::convertObjectList($criteria->getList());
            $threads = [];
            foreach ($cursorChunk as $threadId) {
                if (!isset($threadUnordered[$threadId])) {
                    // the thread is gone by now
                    continue;
                }
                $threads [] = $threadUnordered[$threadId];
            }
            $cursorHasMore = count($cursor) > ($page * self::THREADS_PER_PAGE);
        } else {
            $criteria->setLimit(self::THREADS_PER_PAGE);
            $threads = $criteria->getList();

            $criteriaThreadIds = clone $criteria;
            $cursor = $criteriaThreadIds
                ->addProjection(Projection::property('id'))
                ->setLimit($board ? null : self::THREADS_IN_CURSOR)
                ->getCustomList();
            $cursor = ArrayUtils::columnFromSet('id', $cursor);
            $cursorId = RandomUtils::makeString(16);
            Cache::me()->set('cursor:' . $cursorId, $cursor, Cache::EXPIRES_MEDIUM);
            $cursorHasMore = count($cursor) > ($page * self::THREADS_PER_PAGE);
        }

        if ($this->getUser()) {
            $this->getUser()->getWatchedThreads(true)->getList();
        }

        $response = [];
        if ($board instanceof Board) {
            $response['board'] = $board->exportExtended($this->getSession());
        } else if ($isFavourites) {
            $response['board'] = [
                'name' => _('Моя подборка')
            ];
        } else if ($isOnlyWatched) {
            $response['board'] = [
                'name' => _('Отмеченные треды')
            ];
        } else {
            $response['board'] = [
                'name' => _('Øchan')
            ];
        }
        $response['board']['sort'] = $form->getValueOrDefault('sort');

        $response['pagination'] = [
            'cursor' => $cursorId,
            'page' => $page,
            'hasMore' => $cursorHasMore
        ];

        $response['threads'] = [];
        foreach ($threads as $thread) {
            $lastPosts = [];
            foreach ($thread->getLastPosts(self::NUM_LAST_POSTS) as $lastPost) {
                $lastPosts [] = $lastPost->export();
            }

            $response['threads'][] = [
                'thread' => $thread->export(),
                'opPost' => $thread->getOpPost()->export(),
                'skippedPosts' => $thread->getPostCount() - 1 - count($lastPosts),
                'lastPosts' => $lastPosts,
            ];
        }

        return $response;
    }

    /**
     * @param null|string $search
     * @return array
     */
    public function listAction($search = null, $sort = 'popularity')
    {
        $criteria = Criteria::create(Board::dao());
        $listExpression = Expression::andBlock();
        // прячем удаленные
        $criteria->add(Expression::isFalse('deleted'));
        // прячем скрытые
        $listExpression->expAnd(Expression::isFalse('hidden'));
        // прячем срамные, если не включены
        if (!$this->getUser() || !$this->getUser()->isShowNsfw()) {
            $listExpression->expAnd(Expression::isFalse('nsfw'));
        }

        $canModerateAll = $this->getUser() && $this->getUser()->getRole()->isGlobalMod();

        if ($this->getSession()->isIpCountryRu() && !$canModerateAll) {
            $listExpression->expAnd(Expression::isFalse('blockRu'));
        }

        if ($this->getUser()) {
            $favouriteBoards = array_map(
                function (FavouriteBoard $fb) {
                    return $fb->getBoardId();
                },
                $this->getUser()->getFavouriteBoards()->getList()
            );
            $moderatedBoards = $this->getUser()->getModeratedBoards();
            $isFavouritedExpression = Expression::in('id', $favouriteBoards ?: [-1]);
            $isModeratedExpression = Expression::in('id', $moderatedBoards ?: [-1]);
            $listExpression = Expression::orBlock(
                $listExpression,
                $isFavouritedExpression,
                $isModeratedExpression
            );

            $criteria->addOrder(
                OrderBy::create(
                    ConditionalSwitch::create()
                        ->addWhen(Expression::andBlock($isFavouritedExpression, $isModeratedExpression), 3)
                        ->addWhen($isFavouritedExpression, 2)
                        ->addWhen($isModeratedExpression, 1)
                        ->addElse(0)
                )
                    ->desc()
            );
        }

        $criteria->add($listExpression);

        $search = (string)$search;
        if (strlen($search) > 0) {
            $pattern = '%' . $search . '%';
            $criteria->add(
                Expression::orBlock(
                    Expression::ilike('name', $pattern),
                    Expression::ilike('dir', $pattern)
                )
            );
        }

        if ($sort == 'popularity') {
            $criteria
                ->addOrder(OrderBy::create('popularity')->desc());
        } else if ($sort == 'name') {
            $criteria
                ->addOrder(
                    OrderBy::create(
                        ConditionalSwitch::create()
                            ->addWhen(Expression::lt(SQLFunction::create('ascii', 'name'), 64), 0)
                            ->addWhen(Expression::lt(SQLFunction::create('ascii', 'name'), 128), 1)
                            ->addElse(3)
                    )->desc()
                )
                ->addOrder(
                    OrderBy::create('name')->asc()
                );
        }

        /** @var Board[] $boards */
        $boards = $criteria->getList();
        $response = ['boards' => []];
        foreach ($boards as $board) {
            $boardInfo = $board->export();
            if ($this->getUser()) {
                $boardInfo = array_merge($boardInfo, [
                    'isFavourite' => $this->getUser()->isFavouriteBoard($board),
                    'isModerated' => $this->getUser()->canModerateBoard($board)
                ]);
            }
            $response['boards'] [] = $boardInfo;
        }
        return $response;
    }

    /**
     * @Auth
     * @param Board $board
     * @param bool $isFavourite
     * @return array
     * @throws ApiBadRequestException
     */
    public function favouriteAction(Board $board, $isFavourite = true)
    {
        $isFavourite = $this->getBooleanParam($isFavourite);

        $existing = Criteria::create(FavouriteBoard::dao())
            ->add(Expression::eq('user', $this->getUser()))
            ->add(Expression::eq('board', $board))
            ->get();

        if ($isFavourite && !$existing) {
            FavouriteBoard::dao()->add(
                FavouriteBoard::create()
                    ->setBoard($board)
                    ->setUser($this->getUser())
                    ->setCreateDate(Timestamp::makeNow())
            );

        } else if (!$isFavourite && $existing) {
            FavouriteBoard::dao()->drop($existing);
        }

        return [
            'ok' => true,
            'board' => $board->getDir(),
            'isFavourite' => $isFavourite
        ];
    }
}