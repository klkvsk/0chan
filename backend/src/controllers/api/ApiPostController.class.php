<?php

class ApiPostController extends ApiBaseController
{
    /**
     * @param Post $post
     * @return array
     * @throws ApiBlockRuException
     */
    public function defaultAction(Post $post)
    {
        if ($this->getSession()->isIpCountryRu() && $post->getThread()->getBoard()->isBlockRu() && !$post->canBeModeratedBy($this->getUser())) {
            throw new ApiBlockRuException();
        }

        return ['post' => $post->export()];
    }

    /**
     * @Auth
     *
     * @param Post $post
     * @param $isLike
     * @return array
     */
    public function rateAction(Post $post, $isLike)
    {
        return ['ok' => true];
    }
}