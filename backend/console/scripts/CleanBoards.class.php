<?php
/**
 * @package Scripts
 */
class Script_CleanBoards extends ConsoleScript {

    public function run() {
        $maxPurgeAtDate                 = Timestamp::makeNow()->modify(Thread::CLEANUP_DELAY_AFTER_PURGED  . ' ago');
        $maxDeletedAtDate               = Timestamp::makeNow()->modify(Thread::CLEANUP_DELAY_AFTER_DELETED . ' ago');
        $maxDeletedAtDateForBoards      = Timestamp::makeNow()->modify(Board::CLEANUP_DELAY_AFTER_DELETED  . ' ago');
        $maxDeletedAtDateForAttachments = Timestamp::makeNow()->modify(Attachment::CLEANUP_DELAY_AFTER_DELETED  . ' ago');
        $db = DBPool::getByDao(Thread::dao());

        /** @var Board[] $boards */
        $boards = Criteria::create(Board::dao())
            ->addOrder(OrderBy::create('dir')->asc())
            ->getList();

        foreach ($boards as $board) {
            $this->log('cleaning board: ' . $board->getDir());
            try {
                $db->begin();
                /** @var Thread[] $cleanedThreads */
                $cleanedThreads = Criteria::create(Thread::dao())
                    ->add(Expression::eq('board', $board))
                    ->add(Expression::orBlock(
                        Expression::andBlock(
                            Expression::notNull('purgedAt'),
                            Expression::lt('purgedAt', $maxPurgeAtDate)
                        ),
                        Expression::andBlock(
                            Expression::isTrue('board.deleted'),
                            Expression::lt('board.deletedAt', $maxDeletedAtDateForBoards)
                        ),
                        Expression::lt('deletedAt', $maxDeletedAtDate)
                    ))
                    ->addOrder(OrderBy::create('id')->asc())
                    ->getList();

                foreach ($cleanedThreads as $thread) {
                    $this->log('- cleaning up for thread #' . $thread->getId() . ': ' . $thread->getTitle());

                    // delete reports
                    $reportIds = array_column(
                        Criteria::create(PostReport::dao())
                            ->add(Expression::eq('post.thread', $thread))
                            ->addProjection(Projection::property('id', 'id'))
                            ->getCustomList(),
                        'id'
                    );

                    if ($reportIds) {
                        $this->log('-- removing reports: ' . implode(', ', $reportIds));
                        $db->query(
                            OSQL::delete()->from(PostReport::dao()->getTable())
                                ->where(Expression::in('id', $reportIds))
                        );
                        PostReport::dao()->uncacheByIds($reportIds);
                    }

                    // delete attachments
                    $attachments = Criteria::create(Attachment::dao())
                        ->add(Expression::eq('post.thread', $thread))
                        ->getList();

                    if ($attachments) {
                        $this->log('-- removing attachments: ' . implode(', ', ArrayUtils::getIdsArray($attachments)));
                        foreach ($attachments as $attachment) {
                            Attachment::dao()->drop($attachment);
                        }
                    }

                    // get posts
                    $postIds = array_column(
                        Criteria::create(Post::dao())
                            ->add(Expression::eq('thread', $thread))
                            ->addProjection(Projection::property('id', 'id'))
                            ->getCustomList(),
                        'id'
                    );

                    // update bans
                    $banIds = $db->queryColumn(
                        OSQL::update(Ban::dao()->getTable())
                            ->set('post_id', null)
                            ->where(Expression::in('post_id', $postIds))
                            ->returning('id')
                    );
                    if ($banIds) {
                        $this->log('-- updated bans: ' . implode(', ', $banIds));
                        Ban::dao()->uncacheByIds($banIds);
                    }

                    if ($postIds) {
                        // delete references
                        $this->log('-- removing posts references');
                        $db->queryColumn(
                            OSQL::delete()
                                ->from(PostReference::dao()->getTable())
                                ->  where(Expression::in('referenced_by_id', $postIds))
                                ->orWhere(Expression::in('references_to_id', $postIds))
                                ->returning('id')
                        );

                        // delete posts
                        $this->log('-- removing posts: ' . implode(', ', $postIds));
                        $db->query(
                            OSQL::delete()->from(Post::dao()->getTable())
                                ->where(Expression::in('id', $postIds))
                        );
                        Post::dao()->uncacheByIds($postIds);
                    }

                    // delete thread
                    $this->log('-- removing thread');
                    Thread::dao()->drop($thread);

                    // delete watchers
                    $watchersDao = User::create()->getWatchedThreads();
                    $this->log('-- removing thread watchers');
                    $db->query(
                        OSQL::delete()
                            ->from($watchersDao->getHelperTable())
                            ->where(Expression::eq($watchersDao->getChildIdField(), $thread->getId()))
                    );
                }

                if ($board->isDeleted() && $board->getDeletedAt()->toStamp() < $maxDeletedAtDateForBoards->toStamp()) {
                    /** @var GenericDAO[] $relatedToBoard */
                    $relatedToBoard = [
                        'modlogs' => ModeratorLog::dao(),
                        'favourites' => FavouriteBoard::dao(),
                        'bans' => Ban::dao(),
                        'moderators' => BoardModerator::dao(),
                    ];

                    foreach ($relatedToBoard as $relationName => $relationDao) {
                        $relationIds = $db->queryColumn(
                            OSQL::delete()
                                ->from($relationDao->getTable())
                                ->where(Expression::eq('board_id', $board->getId()))
                                ->returning('id')
                        );
                        if ($relationIds) {
                            $this->log('-- removed ' . $relationName . ' : ' . implode(', ', $relationIds));
                            ModeratorLog::dao()->uncacheByIds($relationIds);
                        }
                    }

                    $this->log('-- removed board ' . $board->getDir());
                    Board::dao()->drop($board);

                } else {
                    /** @var Thread[] $overflowThreads */
                    $overflowThreads = Criteria::create(Thread::dao())
                        ->add(Expression::eq('board', $board))
                        ->add(Expression::isNull('purgedAt'))
                        ->addOrder(OrderBy::create('sticky')->desc()->nullsLast())
                        ->addOrder(OrderBy::create('updateDate')->desc())
                        ->setOffset($board->getThreadLimit())
                        ->getList();

                    foreach ($overflowThreads as $thread) {
                        $this->log(' - ' . $thread->getId() . ': ' . $thread->getTitle());
                        $thread->setPurgedAt(Timestamp::makeNow());
                        Thread::dao()->take($thread);
                    }
                    $this->log('-- overflow threads: ' . count($overflowThreads));

                    /** @var Attachment[] $deletedAttachments */
                    $deletedAttachments = Criteria::create(Attachment::dao())
                        ->add(Expression::eq('post.thread.board', $board))
                        ->add(Expression::isTrue('deleted'))
                        ->add(Expression::ltEq('deletedAt', $maxDeletedAtDateForAttachments))
                        ->getList();

                    foreach ($deletedAttachments as $attachment) {
                        Attachment::dao()->drop($attachment);
                    }
                    $this->log('-- deleted attachments: ' . count($deletedAttachments));
                }

                $this->log('committing (suicide)... ');
                $db->commit();

            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
        }
        $this->log('all done!');
    }

}