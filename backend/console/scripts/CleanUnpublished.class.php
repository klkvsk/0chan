<?php
/**
 * @package Scripts
 */
class Script_CleanUnpublished extends ConsoleScript {

    public function run() {
        /** @var Attachment[] $unpublisheds */
        $unpublisheds = Criteria::create(Attachment::dao())
            ->add(Expression::isFalse('published'))
            ->add(Expression::lt('createDate', Timestamp::makeNow()->modify('1 hour ago')))
            ->addOrder(OrderBy::create('id')->asc())
            ->getList();

        $db = DBPool::getByDao(Attachment::dao());

        foreach ($unpublisheds as $unpublished) {
            $this->log('ID=' . $unpublished->getId() . ' token=' . $unpublished->getPublishToken() . ' ' . $unpublished->getCreateDate()->toString());

            try {
                $db->begin();
                Attachment::dao()->drop($unpublished);
                $db->commit();
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
        }
    }

}