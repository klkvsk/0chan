<?php

class Script_BuildApiDoc extends ConsoleScript {

    public function run() {

        // TODO: выдирать инфу из phpdoc-комментов
        // TODO: генерить в какой-нибудь markdown-файл

        // инклюдим все классы контроллеров
        $controllerFileNames =
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(PATH_CONTROLLERS, RecursiveDirectoryIterator::SKIP_DOTS)
            );
        foreach ($controllerFileNames as $controllerFileName) {
            include_once $controllerFileName;
        }

        foreach (get_declared_classes() as $className) {
            if (is_subclass_of($className, ApiBaseController::class)) {
                $this->log($className);
                preg_match('/^Api(.+)Controller$/', $className, $matches);
                $apiMethodName = lcfirst($matches[1]);
                $rc = new ReflectionClass($className);
                foreach ($rc->getMethods() as $rm) {
                    if (preg_match('/^(.+)Action$/', $rm->getName(), $matches)) {
                        $apiActionName = $matches[1];


                        $apiParametersInfo = [];
                        foreach ($rm->getParameters() as $rp) {
                            $parameter = '    ' . $rp->getName();
                            if ($rp->getClass()) {
                                $parameter .= ':' . $rp->getClass()->getName();
                            }
                            if ($rp->isDefaultValueAvailable()) {
                                if ($rp->isDefaultValueConstant()) {
                                    $parameter .= ' (default: ' . $rp->getDefaultValueConstantName() . ')';
                                } else {
                                    $parameter .= ' (default: ' . var_export($rp->getDefaultValue(), true). ')';
                                }
                            }
                            $apiParametersInfo []= $parameter;
                        }


                        $apiEndPoint = ' /' . $apiMethodName . '/';
                        if ($apiActionName != 'default') {
                            $apiEndPoint .= $apiActionName . '/';
                        }
                        if ($apiParametersInfo) {
                            $apiEndPoint .= ' (';
                        } else {
                            $apiEndPoint .= ' ()';
                        }

                        $this->log($apiEndPoint);
                        foreach ($apiParametersInfo as $apiParameterInfo) {
                            $this->log($apiParameterInfo);
                        }

                        if ($apiParametersInfo) {
                            $this->log(' )');
                        }
                    }
                }
                $this->log('');
            }
        }
    }

}