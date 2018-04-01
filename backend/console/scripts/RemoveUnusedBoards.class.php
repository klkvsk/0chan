<?php
/**
 * @package Scripts
 */
class Script_RemoveUnusedBoards extends ConsoleScript {

    const MAX_POSTS = 100;
    const LAST_POST = '6 month ago';

    public function run() {
        $now = Timestamp::makeNow();
        $lastPostDate = SQLFunction::create('max', 'threads.posts.createDate');
        $postCount = SQLFunction::create('count', 'threads.posts.id');

        $statsCriteria = Criteria::create(Board::dao())
            ->addProjection(Projection::property('id', 'boardId'))
            ->addProjection(Projection::property($postCount, 'postCount'))
            ->addProjection(Projection::property($lastPostDate, 'lastPostDate'))
            // "not true" used for "false OR null" (null when nothing was left-joined)
            ->add(Expression::not(Expression::isTrue('deleted')))
            ->add(Expression::not(Expression::isTrue('threads.deleted')))
            ->add(Expression::not(Expression::isTrue('threads.posts.deleted')))
            ->addProjection(Projection::having(
                Expression::orBlock(
                    // no posts at all
                    Expression::eq($postCount, 0),
                    // or very old and very few posts
                    Expression::andBlock(
                        Expression::lt(
                            $lastPostDate,
                            $now->spawn(self::LAST_POST)
                        ),
                        Expression::lt(
                            $postCount,
                            self::MAX_POSTS
                        )
                    )
                )
            ))
            ->addProjection(Projection::group('id'))
            ->addOrder(OrderBy::create($postCount)->desc());

        $stats = $statsCriteria->getCustomList();

        foreach ($stats as $row) {
            /** @var Board $board */
            $board = Board::dao()->getById($row['boardId']);
            $this->log(sprintf(' id: %6d | dir: %16s | count: %3d | last: %s',
                $row['boardId'], $board->getDir(), $row['postCount'], $row['lastPostDate']
            ));

            $board
                ->setDeleted(true)
                ->setDeletedAt($now);

            Board::dao()->save($board);
        }

        $this->log('all done! deleted ' . count($stats) . ' boards');
    }

}