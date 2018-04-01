<?php

class ApiAttachmentController extends ApiBaseController
{
    /**
     * @Post
     *
     * @return array
     * @throws ApiBadRequestException
     * @throws Exception
     */
    public function uploadAction()
    {
        $form = Form::create()
            ->add(Primitive::binary('data')
                ->setMax(Attachment::MAX_FILE_SIZE)
            )
            ->add(Primitive::file('file')
                ->setMax(Attachment::MAX_FILE_SIZE)
            )
            ->add(Primitive::string('url'))
            ->add(Primitive::boolean('b64'))
            ->import($this->getRequest()->getGet())
            ->importMore($this->getRequest()->getPost())
            ->importMore($this->getRequest()->getFiles());

        $originalImageBlob = $form->getValue('data');
        if ($originalImageBlob && $form->getValue('b64')) {
            $originalImageBlob = base64_decode($originalImageBlob);
        } else if ($form->getValue('url')) {
            $originalImageBlob = file_get_contents($form->getValue('url'));
        } else if ($form->getValue('file')) {
            $originalImageBlob = file_get_contents($form->getValue('file'));
        }

        if (!$originalImageBlob) {
            return [
                'ok' => false,
                'reason' => 'Не удалось загрузить файл'
            ];
        }

        try {
            $images = AttachmentImage::dao()->createImages($originalImageBlob);
        } catch (Exception $e) {
            return [
                'ok' => false,
                'reason' => $e->getMessage()
            ];
        }

        return $this->addAttachment($images);
    }

    /**
     * @Post
     *
     * @return array
     * @throws ApiBadRequestException
     * @throws ApiNotFoundException
     */
    public function embedAction($url)
    {
        try {
            $embed = AttachmentEmbed::createFromUrl($url);
        } catch (ObjectNotFoundException $e) {
            $embed = null;
        }

        if (!$embed) {
            return [
                'ok' => false,
            ];
        }

        $images = [];
        $previewUrl = $embed->getService()->getPreviewUrl($embed->getEmbedId());
        if ($previewUrl) {
            try {
                $previewImageBlob = file_get_contents($previewUrl);
                Assert::isNotEmpty($previewImageBlob);
            } catch (Exception $e) {
                return [
                    'ok' => false,
                    'reason' => 'Не удалось загрузить превью'
                ];
            }
            try {
                $images = AttachmentImage::dao()->createImages($previewImageBlob);
            } catch (Exception $e) {
                return [
                    'ok' => false,
                    'reason' => $e->getMessage()
                ];
            }
        }

        return $this->addAttachment($images, $embed);
    }


    /**
     * @param Attachment $attachment
     * @param $token
     * @return array
     * @throws Exception
     */
    public function cancelAction(Attachment $attachment, $token)
    {
        if ($attachment->getPost() || $attachment->isPublished() || $attachment->getPublishToken() !== $token) {
            return [ 'ok' => false ];
        }

        $db = DBPool::getByDao(Attachment::dao());
        try {
            $db->begin();
            Attachment::dao()->drop($attachment);
            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
        return ['ok' => true, 'isDeleted' => true];
    }

    /**
     * @param Attachment $attachment
     * @param $token
     * @param $isNsfw
     * @return array
     * @throws Exception
     */
    public function markNsfwAction(Attachment $attachment, $token, $isNsfw)
    {
        $isNsfw = $this->getBooleanParam($isNsfw);

        if ($attachment->getPost() || $attachment->isPublished() || $attachment->getPublishToken() !== $token) {
            return [ 'ok' => false ];
        }

        $attachment->setNsfw($isNsfw);
        Attachment::dao()->take($attachment);

        return ['ok' => true, 'isNsfw' => $attachment->isNsfw() ];
    }

    /**
     * @param AttachmentImage[] $images
     * @param AttachmentEmbed|null $embed
     * @return array
     * @throws Exception
     */
    protected function addAttachment(array $images = [], AttachmentEmbed $embed = null)
    {
        $attachment = Attachment::create()
            ->setCreateDate(Timestamp::makeNow())
            ->setPublishToken(AttachmentDAO::makePublishToken());

        $db = DBPool::getByDao(Attachment::dao());
        try {
            $db->begin();

            if ($embed) {
                AttachmentEmbed::dao()->add($embed);
                $attachment->setEmbed($embed);
            }

            Attachment::dao()->add($attachment);

            foreach ($images as $image) {
                $image->setAttachment($attachment);
                AttachmentImage::dao()->add($image);
            }

            $db->commit();

        } catch (Exception $e) {

            if ($db->inTransaction()) {
                $db->rollback();
            }

            foreach ($images as $image) {
                try {
                    $image->removeFile();
                } catch (Exception $e) {}
            }

            throw $e;
        }

        return [
            'ok' => true,
            'attachment' => array_merge(
                [ 'token' => $attachment->getPublishToken()],
                $attachment->export()
            )
        ];
    }
}