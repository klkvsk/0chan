<?php

class ApiDebugController extends ApiBaseController
{
    /**
     * @return array
     */
    public function defaultAction($id)
    {
        return Cache::me()->get('debug_' . $id);
    }

}