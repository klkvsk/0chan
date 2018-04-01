<?php
/**
 * @package Scripts
 */
class Script_CleanStorageTrash extends ConsoleScript {

    /** @var StorageServer[] */
    protected $storages = [];

    /**
     * @param $server
     * @return StorageServer|null
     */
    public function getStorage($server)
    {
        if (!array_key_exists($server, $this->storages)) {
            $this->storages[$server] = StorageServer::getByExtName($server);
        }
        return $this->storages[$server];
    }

    public function run()
    {
        $i = 0;
        do {
            /** @var StorageTrash[] $trash */
            $trash = Criteria::create(StorageTrash::dao())
                ->addOrder(OrderBy::create('id'))
                ->setLimit(100)
                ->setOffset(100 * $i++)
                ->getList();

            foreach ($trash as $file) {
                $storage = $this->getStorage($file->getServer());
                if (!$storage) {
                    $this->log('server ' . $file->getServer() . ' is offline, skipping ' . $file->getFilename());
                } else {
                    $this->log('removing from ' . $file->getServer() . ' file ' . $file->getFilename());
                    try {
                        $storage->deleteFile($file->getFilename());
                        StorageTrash::dao()->drop($file);
                    } catch (Exception $e) {
                        $this->log($e);
                    }
                }
            }

        } while (!empty($trash));
    }

}