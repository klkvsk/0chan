<?php
/**
 * @package Scripts
 */
class Script_CountRatings extends ConsoleScript {

    public function run() {

        $boardsPopularity = Criteria::create(Post::dao())
            ->addProjection(Projection::property('thread.board', 'boardId'))
            ->addProjection(Projection::group('thread.board'))
            ->addProjection(Projection::distinctCount('ipHash', 'count'))
            ->add(Expression::gt('createDate', Timestamp::makeNow()->modify('24 hours ago')))
            ->getCustomList();

        $db = DBPool::getByDao(Board::dao());
        foreach ($boardsPopularity as $row) {
            $boardId = $row['boardId'];
            $count   = $row['count'];
            //$this->log('board ' . $boardId . ' popularity = ' . $count);

            $db->query(
                OSQL::update(Board::dao()->getTable())
                    ->set('popularity', $count)
                    ->where(Expression::eq('id', $boardId))
            );



            Board::dao()->uncacheById($boardId);
        }

    }

}