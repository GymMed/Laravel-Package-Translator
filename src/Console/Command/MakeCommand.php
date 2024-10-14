<?php

namespace GymMed\LaravelPackageTranslator\Console\Command;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeCommand extends Command
{
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        protected Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    /**
     * Get name in studly case.
     *
     * @return string
     */
    public function getStudlyName()
    {
        return class_basename($this->argument('package'));
    }

    /**
     * Get Package Name in Lower case.
     * 
     * @return string
     */
    protected function getLowerName()
    {
        return strtolower(class_basename($this->argument('package')));
    }

    /**
     * Get Class Name.
     * 
     * @return string
     */
    protected function getClassName()
    {
        return class_basename($this->argument('name'));
    }

    /**
     * Get NameSpace for Controller.
     * 
     * @return string
     */
    protected function getClassNamespace(string $name)
    {
        return str_replace('/', '\\', $name);
    }

    /**
     * Get Controller Name.
     * 
     * @return string
     */
    protected function getClassControllerName()
    {
        return $this->getStudlyName() . 'Controller';
    }
}
